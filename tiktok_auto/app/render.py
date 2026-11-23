"""Montagem dos vídeos com ffmpeg: cenas com movimento suave, transições
cruzadas presas a uma grade de batidas, grade de cor única e texto ASS."""
import os
import shutil
import subprocess


def ffmpeg_bin():
    exe = shutil.which("ffmpeg")
    if not exe:
        raise RuntimeError("ffmpeg não encontrado no PATH")
    return exe


def run(cmd, log, cwd=None):
    p = subprocess.run(cmd, cwd=cwd, capture_output=True, text=True, encoding="utf-8", errors="replace")
    if p.returncode != 0:
        log.error("ffmpeg falhou: %s", p.stderr[-1500:])
        raise RuntimeError("ffmpeg falhou (código %d)" % p.returncode)


def ass_tempo(s):
    h = int(s // 3600); m = int(s % 3600 // 60); sec = s % 60
    return "%d:%02d:%05.2f" % (h, m, sec)


def escrever_ass(caminho, ident, W, H, eventos):
    cab = """[Script Info]
ScriptType: v4.00+
PlayResX: %d
PlayResY: %d
WrapStyle: 0

[V4+ Styles]
Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding
Style: Gancho,{f},{g},{c},{c},{o},&H96000000,0,1,0,0,100,100,1,0,1,3,2,5,110,110,0,1
Style: Frase,{f},{p},{c},{c},{o},&H96000000,0,1,0,0,100,100,1,0,1,3,2,5,120,120,0,1

[Events]
Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text
""" % (W, H)
    cab = cab.format(f=ident["fonte"], g=ident["tamanho_gancho"], p=ident["tamanho_frase"],
                     c=ident["cor_texto"], o=ident["cor_contorno"])
    y = int(H * 0.44)
    linhas = []
    for ini, fim, estilo, texto in eventos:
        if ident.get("minusculas"):
            texto = texto.lower()
        texto = texto.replace("\n", "\\N")
        linhas.append("Dialogue: 0,%s,%s,%s,,0,0,0,,{\\pos(%d,%d)\\fad(350,300)\\blur3}%s" % (
            ass_tempo(ini), ass_tempo(fim), estilo, W // 2, y, texto))
    with open(caminho, "w", encoding="utf-8") as f:
        f.write(cab + "\n".join(linhas) + "\n")


def cena(mat, idx, dur, cfg, pasta, log):
    """Renderiza uma cena (foto ou vídeo) em 1080x1920 com movimento suave."""
    W, H, fps = cfg["largura"], cfg["altura"], cfg["fps"]
    saida = os.path.join(pasta, "cena_%02d.mp4" % idx)
    if os.path.isfile(saida) and os.path.getsize(saida) > 0:
        return saida
    n = int(round(dur * fps))
    if mat["tipo"] == "foto":
        modo = idx % 3
        if modo == 0:
            z, x, y = "1+0.10*on/%d" % n, "(iw-iw/zoom)/2", "(ih-ih/zoom)/2"
        elif modo == 1:
            z, x, y = "1.10-0.10*on/%d" % n, "(iw-iw/zoom)/2", "(ih-ih/zoom)/2"
        else:
            z, x, y = "1.08", "(iw-iw/zoom)*on/%d" % n, "(ih-ih/zoom)/2"
        vf = ("scale=%d:%d:force_original_aspect_ratio=increase:flags=lanczos,crop=%d:%d,"
              "zoompan=z='%s':x='%s':y='%s':d=%d:s=%dx%d:fps=%d,format=yuv420p") % (
            W * 2, H * 2, W * 2, H * 2, z, x, y, n, W, H, fps)
        cmd = [ffmpeg_bin(), "-v", "error", "-y",
               "-i", mat["arquivo"], "-vf", vf, "-frames:v", str(n), "-an",
               "-c:v", "libx264", "-preset", "medium", "-crf", "16", saida]
    else:
        ini = max(0.0, (mat.get("duracao", dur) - dur) / 2)
        vf = ("scale=%d:%d:force_original_aspect_ratio=increase:flags=lanczos,crop=%d:%d,"
              "fps=%d,setpts=PTS-STARTPTS,tpad=stop_mode=clone:stop_duration=%.2f,format=yuv420p") % (W, H, W, H, fps, dur)
        cmd = [ffmpeg_bin(), "-v", "error", "-y", "-ss", "%.2f" % ini, "-i", mat["arquivo"],
               "-vf", vf, "-frames:v", str(n), "-an", "-c:v", "libx264", "-preset", "medium", "-crf", "16", saida]
    run(cmd, log)
    return saida


def montar(materiais, textos, cfg, ident, fontes_dir, pasta_trab, saida, log):
    """materiais: lista na ordem das cenas. textos: lista (estilo, texto, cena_ini, cena_fim)."""
    W, H, fps = cfg["largura"], cfg["altura"], cfg["fps"]
    passo = 60.0 / cfg["bpm_grade"] * cfg["batidas_por_cena"]
    T = cfg["transicao_s"]
    n = len(materiais)
    arquivos = [cena(m, i, passo + T, cfg, pasta_trab, log) for i, m in enumerate(materiais)]
    total = n * passo + T

    eventos = []
    for estilo, texto, ci, cf in textos:
        ini = ci * passo + (0.25 if ci == 0 else T)
        fim = min(total, (cf + 1) * passo + T * 0.5)
        eventos.append((ini, fim, estilo, texto))
    escrever_ass(os.path.join(pasta_trab, "texto.ass"), ident, W, H, eventos)
    fdir = os.path.join(pasta_trab, "fonts")
    os.makedirs(fdir, exist_ok=True)
    for f in os.listdir(fontes_dir):
        if f.lower().endswith((".ttf", ".otf")):
            shutil.copy2(os.path.join(fontes_dir, f), fdir)

    entradas = []
    for a in arquivos:
        entradas += ["-i", os.path.basename(a)]
    trans = ["fade", "dissolve", "fade", "smoothup", "fade", "dissolve"]
    partes = []
    atual = "[0:v]"
    for k in range(1, n):
        rot = "[x%d]" % k
        partes.append("%s[%d:v]xfade=transition=%s:duration=%.3f:offset=%.3f%s" % (
            atual, k, trans[k % len(trans)], T, k * passo, rot))
        atual = rot
    partes.append("%s%s,ass=texto.ass:fontsdir=fonts,fade=t=in:st=0:d=0.3,format=yuv420p[v]" % (
        atual, ident["grade_cor"]))
    tmp = os.path.join(pasta_trab, "final.mp4")
    cmd = [ffmpeg_bin(), "-v", "error", "-y"] + entradas + [
        "-filter_complex", ";".join(partes), "-map", "[v]", "-r", str(fps),
        "-c:v", "libx264", "-preset", "slow", "-crf", str(cfg["crf"]), "-profile:v", "high",
        "-pix_fmt", "yuv420p", "-movflags", "+faststart", "-an", "final.mp4"]
    run(cmd, log, cwd=pasta_trab)
    os.replace(tmp, saida)
    return total
