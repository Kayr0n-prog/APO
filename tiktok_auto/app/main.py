"""Rotina diária: busca material no Pixabay, monta 3 vídeos 9:16 sem música
e gera legendas, hashtags e créditos. Pode ser executada várias vezes no
mesmo dia: o que já foi concluído é pulado (recuperação de interrupções)."""
import argparse
import datetime as dt
import json
import logging
import os
import random
import shutil
import sys
import time
import traceback

from . import pixabay as pb
from . import render

RAIZ = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))


# ---------------------------------------------------------------- util
def ler_json(caminho, padrao):
    try:
        with open(caminho, encoding="utf-8") as f:
            return json.load(f)
    except FileNotFoundError:
        return padrao


def gravar_json(caminho, dados):
    tmp = caminho + ".tmp"
    with open(tmp, "w", encoding="utf-8") as f:
        json.dump(dados, f, ensure_ascii=False, indent=1)
    os.replace(tmp, caminho)


def pasta_saida(cfg, arg):
    if arg:
        return os.path.abspath(os.path.expanduser(arg))
    if os.name == "nt":
        return os.path.expandvars(cfg["pasta_saida_windows"])
    return os.path.expanduser(cfg["pasta_saida_padrao"])


def configurar_log(pasta_logs, dia):
    os.makedirs(pasta_logs, exist_ok=True)
    log = logging.getLogger("tiktok_auto")
    log.setLevel(logging.INFO)
    log.handlers.clear()
    fmt = logging.Formatter("%(asctime)s %(levelname)s %(message)s")
    fh = logging.FileHandler(os.path.join(pasta_logs, dia + ".log"), encoding="utf-8")
    fh.setFormatter(fmt)
    sh = logging.StreamHandler(sys.stdout)
    sh.setFormatter(fmt)
    log.addHandler(fh)
    log.addHandler(sh)
    return log


def processo_vivo(pid):
    if pid <= 0:
        return False
    if os.name == "nt":
        import ctypes
        h = ctypes.windll.kernel32.OpenProcess(0x1000, False, pid)  # QUERY_LIMITED_INFORMATION
        if not h:
            return False
        codigo = ctypes.c_ulong()
        ctypes.windll.kernel32.GetExitCodeProcess(h, ctypes.byref(codigo))
        ctypes.windll.kernel32.CloseHandle(h)
        return codigo.value == 259  # STILL_ACTIVE
    try:
        os.kill(pid, 0)
        return True
    except OSError:
        return False


class Trava:
    def __init__(self, caminho, log):
        self.caminho, self.log = caminho, log

    def __enter__(self):
        if os.path.exists(self.caminho):
            try:
                pid = int(open(self.caminho).read().strip() or 0)
            except ValueError:
                pid = 0
            recente = time.time() - os.path.getmtime(self.caminho) < 6 * 3600
            if recente and processo_vivo(pid):
                raise RuntimeError("já existe uma execução em andamento (pid %d)" % pid)
            self.log.warning("trava antiga encontrada (execução interrompida); retomando")
        with open(self.caminho, "w") as f:
            f.write(str(os.getpid()))
        return self

    def __exit__(self, *a):
        try:
            os.remove(self.caminho)
        except OSError:
            pass


# ---------------------------------------------------------------- seleção
def melhor_arquivo_video(hit, q):
    for tam in ("large", "medium"):
        v = hit.get("videos", {}).get(tam) or {}
        w, h, url = v.get("width", 0), v.get("height", 0), v.get("url", "")
        if not url:
            continue
        if h > w and w >= q["video_retrato_largura"] and h >= q["video_retrato_altura"]:
            return url, w, h
        if w >= h and h >= q["video_paisagem_altura_para_recorte"]:
            return url, w, h
    return None


def melhor_foto(hit, q):
    for chave in ("imageURL", "fullHDURL", "largeImageURL"):
        url = hit.get(chave)
        if url:
            break
    else:
        return None
    w, h = hit.get("imageWidth", 0), hit.get("imageHeight", 0)
    if h <= w or w < 1080 or h < 1920:
        return None
    lado = max(w, h) if chave != "largeImageURL" else 1280
    if lado < q["foto_lado_maior"]:
        return None
    return url, chave


def proibido(hit, cfg):
    tags = (hit.get("tags") or "").lower()
    return any(t in tags for t in cfg.get("filtro_tags_proibidas", []))


def coletar(api, tema, n_cenas, max_fotos, usados, cfg, log):
    q = cfg["qualidade_minima"]
    buscas = tema["buscas"][:]
    random.shuffle(buscas)
    videos, fotos = [], []
    for termo in buscas:
        try:
            hits = api.buscar_videos(termo)
        except pb.ErroChave:
            raise
        except Exception as e:
            log.warning("busca de vídeos '%s' falhou: %s", termo, e)
            hits = []
        cont = 0
        for h in hits:
            pid = "v%d" % h["id"]
            if pid in usados or proibido(h, cfg) or h.get("duration", 0) < q["duracao_minima_clipe_s"]:
                continue
            arq = melhor_arquivo_video(h, q)
            if not arq:
                continue
            videos.append({"id": pid, "tipo": "video", "url": arq[0], "largura": arq[1], "altura": arq[2],
                           "duracao": h.get("duration", 0), "pagina": h.get("pageURL"), "autor": h.get("user"),
                           "tags": h.get("tags"), "busca": termo})
            usados.add(pid)
            cont += 1
            if cont >= 2:
                break
        if len(fotos) < max_fotos * 2:
            try:
                fh = api.buscar_fotos(termo)
            except pb.ErroChave:
                raise
            except Exception as e:
                log.warning("busca de fotos '%s' falhou: %s", termo, e)
                fh = []
            for h in fh[:20]:
                pid = "f%d" % h["id"]
                if pid in usados or proibido(h, cfg) or h.get("type") not in (None, "photo"):
                    continue
                f = melhor_foto(h, q)
                if f:
                    fotos.append({"id": pid, "tipo": "foto", "url": f[0], "campo": f[1],
                                  "largura": h.get("imageWidth"), "altura": h.get("imageHeight"),
                                  "pagina": h.get("pageURL"), "autor": h.get("user"),
                                  "tags": h.get("tags"), "busca": termo})
                    usados.add(pid)
                    break
        if len(videos) >= n_cenas:
            break
    escolha = videos[:n_cenas]
    faltam = n_cenas - len(escolha)
    escolha += fotos[:min(faltam, max_fotos)]
    if len(escolha) < cfg["video"]["cenas_min"]:
        return None, "material de qualidade insuficiente para '%s': %d vídeos e %d fotos aprovados" % (
            tema["nome"], len(videos), len(fotos))
    random.shuffle(escolha)
    return escolha, None


def escolher_frases(tema, n, hist, dias, hoje):
    lim = (hoje - dt.timedelta(days=dias)).isoformat()
    usadas = {t for t, d in hist["frases"].items() if d >= lim}
    livres = [f for f in tema["frases"] if f not in usadas]
    if len(livres) < n:
        return None
    return random.sample(livres, n)


def montar_hashtags(tema, cfg):
    tags = tema["hashtags"]
    esc = [tags[0]] + random.sample(tags[1:], 2) + [random.choice(["#aesthetic", "#fyp", "#paravocê"])]
    if cfg.get("hashtag_marca"):
        esc.append(cfg["hashtag_marca"])
    return esc


CTAS = ["salva para ler de novo amanhã", "manda para alguém que precisa ouvir isso",
        "comenta \"eu recebo\" se isso é para você", "salva e volta aqui quando esquecer"]


def planejar(i, temas, hist, hoje, temas_hoje, cfg, api, usados, log):
    ontem = (hoje - dt.timedelta(days=1)).isoformat()
    proibidos = set(hist["temas"].get(ontem, [])) | set(temas_hoje)
    ultimo_uso = {k: max([d for d, ts in hist["temas"].items() if k in ts] or ["0"]) for k in temas}
    candidatos = sorted([k for k in temas if k not in proibidos] or list(temas), key=lambda k: ultimo_uso[k])
    erros = []
    for chave in candidatos:
        tema = temas[chave]
        n = random.randint(cfg["video"]["cenas_min"], cfg["video"]["cenas_max"])
        n -= n % 2
        n_frases = (n - 2) // 2
        frases = escolher_frases(tema, n_frases, hist, cfg["dias_sem_repetir_frase"], hoje)
        if not frases:
            erros.append("frases inéditas esgotadas em '%s'" % tema["nome"])
            continue
        mats, erro = coletar(api, tema, n, cfg["video"]["max_fotos_por_video"], usados, cfg, log)
        if not mats:
            erros.append(erro)
            continue
        n = len(mats) - len(mats) % 2
        mats = mats[:n]
        n_frases = (n - 2) // 2
        frases = frases[:n_frases]
        gancho = random.choice(tema["ganchos"])
        textos = [("Gancho", gancho, 0, 1)] + [("Frase", f, 2 + 2 * k, 3 + 2 * k) for k, f in enumerate(frases)]
        tags = montar_hashtags(tema, cfg)
        cta = random.choice(CTAS)
        legenda = "%s ✨\n\n%s.\n\n%s 🤍" % (gancho[0].upper() + gancho[1:], frases[0][0].upper() + frases[0][1:],
                                             cta[0].upper() + cta[1:])
        return {"numero": i, "tema": chave, "tema_nome": tema["nome"], "gancho": gancho, "frases": frases,
                "textos": textos, "materiais": mats, "titulo": gancho[0].upper() + gancho[1:],
                "legenda": legenda, "hashtags": tags, "status": "planejado"}, None
    return None, "; ".join(erros)


# ---------------------------------------------------------------- execução
def processar(dia_dir, plano, cfg, log):
    num = plano["numero"]
    fontes = os.path.join(dia_dir, "originais", "video_%d" % num)
    trab = os.path.join(dia_dir, ".trabalho", "video_%d" % num)
    os.makedirs(fontes, exist_ok=True)
    os.makedirs(trab, exist_ok=True)
    for m in plano["materiais"]:
        ext = os.path.splitext(m["url"].split("?")[0])[1] or (".mp4" if m["tipo"] == "video" else ".jpg")
        m["arquivo"] = os.path.join(fontes, "pixabay_%s%s" % (m["id"], ext))
        pb.baixar(m["url"], m["arquivo"], log)
    saida = os.path.join(dia_dir, "video_%d.mp4" % num)
    dur = render.montar(plano["materiais"], plano["textos"], cfg["video"], cfg["identidade"],
                        os.path.join(RAIZ, "fonts"), trab, saida, log)
    plano["duracao_s"] = round(dur, 1)
    return saida


def escrever_publicacao(dia_dir, planos, dia):
    linhas = ["# Publicações de %s" % dia, "",
              "Vídeos sem música: escolha o som dentro do TikTok. Os cortes seguem uma grade de 100 BPM "
              "(troca de cena a cada 2,4 s), que combina com a maioria dos sons calmos.", ""]
    for p in planos:
        linhas += ["## Vídeo %d — %s" % (p["numero"], p["tema_nome"]), ""]
        if p["status"] != "concluido":
            linhas += ["**Não gerado:** %s" % p.get("erro", p["status"]), ""]
            continue
        linhas += ["- Arquivo: `video_%d.mp4` (%s s)" % (p["numero"], p.get("duracao_s")),
                   "- Título: %s" % p["titulo"], "", "**Legenda:**", "", p["legenda"], "",
                   "**Hashtags:** " + " ".join(p["hashtags"]), "", "**Fontes (Pixabay Content License — uso gratuito, "
                   "atribuição não obrigatória):**", ""]
        for m in p["materiais"]:
            linhas.append("- %s %s — por %s — %s" % (m["tipo"], m["id"], m.get("autor"), m.get("pagina")))
        linhas.append("")
    with open(os.path.join(dia_dir, "PUBLICACOES.md"), "w", encoding="utf-8") as f:
        f.write("\n".join(linhas))


def main(argv=None):
    ap = argparse.ArgumentParser()
    ap.add_argument("--saida", help="pasta raiz de saída")
    ap.add_argument("--data", help="AAAA-MM-DD (padrão: hoje)")
    ap.add_argument("--quantidade", type=int)
    a = ap.parse_args(argv)

    cfg = ler_json(os.path.join(RAIZ, "config.json"), None)
    temas = ler_json(os.path.join(RAIZ, "data", "temas.json"), None)
    raiz = pasta_saida(cfg, a.saida)
    os.makedirs(raiz, exist_ok=True)
    hoje = dt.date.fromisoformat(a.data) if a.data else dt.date.today()
    dia = hoje.isoformat()
    log = configurar_log(os.path.join(raiz, "logs"), dia)
    random.seed(dia)
    qtd = a.quantidade or cfg["videos_por_dia"]
    dia_dir = os.path.join(raiz, dia)
    os.makedirs(dia_dir, exist_ok=True)

    try:
        with Trava(os.path.join(raiz, ".em_execucao"), log):
            chave = pb.ler_chave()
            api = pb.Pixabay(chave, log, cache_dir=os.path.join(raiz, ".cache_api"))
            hist_path = os.path.join(raiz, "historico.json")
            hist = ler_json(hist_path, {"materiais": {}, "frases": {}, "temas": {}})
            estado_path = os.path.join(dia_dir, "estado.json")
            estado = ler_json(estado_path, {"data": dia, "videos": []})
            lim = (hoje - dt.timedelta(days=cfg["dias_sem_repetir_material"])).isoformat()
            usados = {k for k, d in hist["materiais"].items() if d >= lim}
            for p in estado["videos"]:
                usados |= {m["id"] for m in p.get("materiais", [])}

            for i in range(1, qtd + 1):
                if any(p["numero"] == i for p in estado["videos"]):
                    continue
                temas_hoje = [p["tema"] for p in estado["videos"] if p.get("tema")]
                plano, erro = planejar(i, temas, hist, hoje, temas_hoje, cfg, api, usados, log)
                if not plano:
                    log.error("vídeo %d não planejado: %s", i, erro)
                    estado["videos"].append({"numero": i, "status": "sem_material", "erro": erro,
                                             "tema_nome": "-"})
                else:
                    estado["videos"].append(plano)
                    hist["temas"].setdefault(dia, []).append(plano["tema"])
                    for f in plano["frases"]:
                        hist["frases"][f] = dia
                    for m in plano["materiais"]:
                        hist["materiais"][m["id"]] = dia
                gravar_json(estado_path, estado)
                gravar_json(hist_path, hist)

            for p in estado["videos"]:
                if p["status"] in ("concluido", "sem_material"):
                    continue
                try:
                    log.info("montando vídeo %d (%s)", p["numero"], p["tema_nome"])
                    processar(dia_dir, p, cfg, log)
                    p["status"] = "concluido"
                    p.pop("erro", None)
                except pb.ErroChave:
                    raise
                except Exception as e:
                    p["status"] = "falhou"
                    p["erro"] = str(e)
                    p["tentativas"] = p.get("tentativas", 0) + 1
                    log.error("vídeo %d falhou: %s\n%s", p["numero"], e, traceback.format_exc())
                gravar_json(estado_path, estado)

            escrever_publicacao(dia_dir, estado["videos"], dia)
            ok = [p for p in estado["videos"] if p["status"] == "concluido"]
            if all(p["status"] == "concluido" for p in estado["videos"]):
                shutil.rmtree(os.path.join(dia_dir, ".trabalho"), ignore_errors=True)
            problemas = [p for p in estado["videos"] if p["status"] != "concluido"]
            if problemas:
                with open(os.path.join(dia_dir, "PROBLEMAS.txt"), "w", encoding="utf-8") as f:
                    for p in problemas:
                        f.write("vídeo %d: %s — %s\n" % (p["numero"], p["status"], p.get("erro")))
            log.info("RESUMO %s: %d/%d vídeos prontos em %s", dia, len(ok), len(estado["videos"]), dia_dir)
            for p in ok:
                log.info("  video_%d.mp4 | %s | %s", p["numero"], p["titulo"], " ".join(p["hashtags"]))
            return 0 if not problemas else 2
    except pb.ErroChave as e:
        log.error("%s", e)
        return 3
    except Exception as e:
        log.error("falha geral: %s\n%s", e, traceback.format_exc())
        return 1


if __name__ == "__main__":
    sys.exit(main())
