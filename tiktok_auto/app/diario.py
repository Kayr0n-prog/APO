"""Rotina diária no formato das inspirações (montagem rápida, 4:3, frase fixa).

Para cada vídeo: escolhe um estilo (sem repetir o de ontem), uma frase inédita
do banco (data/frases.json), imagens do banco local da conta e clipes do
Pixabay, e monta com render.montagem. Retoma execuções interrompidas.
"""
import argparse
import datetime as dt
import os
import random
import shutil
import subprocess
import sys
import traceback

from . import pixabay as pb
from . import render
from .main import RAIZ, ler_json, gravar_json, pasta_saida, configurar_log, Trava

ESTILOS_CFG = {
    # estilo: (categorias do banco local, temas de frase, corte_s, encaixe, inspiração)
    "clean": (["thatgirl_autocuidado", "estudo_trabalho"], ["amor_proprio", "disciplina"], 0.25, "cover", "v16"),
    "noite": (["luxo_noite"], ["disciplina", "amor_proprio"], 0.25, "cover", "v23"),
    "zen": (["zen_lotus", "natureza"], ["paz"], 0.3, "cover", "v07/v08"),
    "luxo": (["luxo_noite"], ["manifestacao"], 0.3, "cover", "v17"),
    "cosmos": (["cosmos"], ["manifestacao"], 0.6, "cover", "v22/v29"),
    "flores": (["flores_fundo_preto_gif"], ["paz", "amor_proprio"], 0.6, "contain", "v12"),
}
CTAS = ["salva pra lembrar disso amanhã", "manda pra alguém que precisa ler isso",
        "comenta \"eu recebo\" se isso é pra você", "salva e volta aqui quando esquecer"]
GANCHOS = ["se isso apareceu pra você, é um sinal", "lembrete de hoje", "precisava ouvir isso hoje",
           "a mulher que eu estou me tornando", "leia com calma"]


def duracao(arq):
    out = subprocess.run(["ffprobe", "-v", "error", "-show_entries", "format=duration", "-of", "csv=p=0", arq],
                         capture_output=True, text=True).stdout.strip()
    try:
        return float(out)
    except ValueError:
        return 5.0


def clipes_pixabay(api, est, n, usados, cfg, log):
    proib = cfg.get("filtro_tags_proibidas", [])
    buscas = est["buscas"][:]
    random.shuffle(buscas)
    res = []
    for termo in buscas:
        try:
            hits = api.buscar_videos(termo)
        except pb.ErroChave:
            raise
        except Exception as e:
            log.warning("busca '%s' falhou: %s", termo, e)
            continue
        ok = 0
        for h in hits:
            pid = "v%d" % h["id"]
            if pid in usados or pid in cfg.get("pixabay_bloqueados", []) or any(p in (h.get("tags") or "").lower() for p in proib) or h.get("duration", 0) < 4:
                continue
            v = h.get("videos", {}).get("large") or h.get("videos", {}).get("medium") or {}
            if not v.get("url") or v.get("width", 0) < v.get("height", 1) or v.get("height", 0) < 720:
                continue
            res.append({"id": pid, "tipo": "pixabay", "url": v["url"], "duracao": h["duration"],
                        "pagina": h.get("pageURL"), "autor": h.get("user")})
            usados.add(pid)
            ok += 1
            if ok >= 2:
                break
        if len(res) >= n:
            break
    return res[:n]


def planejar(i, hoje, hist, estado, cfg, estilos, frases, banco, pasta_banco, api, usados, log):
    ontem = (hoje - dt.timedelta(days=1)).isoformat()
    proibidos = set(hist["estilos"].get(ontem, [])) | {p.get("estilo") for p in estado["videos"]}
    ultimo = {k: max([d for d, ts in hist["estilos"].items() if k in ts] or ["0"]) for k in ESTILOS_CFG}
    cands = sorted([k for k in ESTILOS_CFG if k not in proibidos] or list(ESTILOS_CFG), key=lambda k: ultimo[k])
    lim_f = (hoje - dt.timedelta(days=cfg["dias_sem_repetir_frase"])).isoformat()
    usadas_f = {f for f, d in hist["frases"].items() if d >= lim_f}
    erros = []
    for estilo in cands:
        cats, temas_f, corte, encaixe, insp = ESTILOS_CFG[estilo]
        livres = [f for t in temas_f for f in frases[t] if f not in usadas_f]
        if not livres:
            erros.append("frases inéditas esgotadas para '%s'" % estilo)
            continue
        frase = random.choice(livres)
        locais = []
        if pasta_banco:
            ontem_locais = set(hist.get("locais", {}).get(ontem, []))
            for c in cats:
                for a in banco.get(c, []):
                    f = os.path.join(pasta_banco, a)
                    if os.path.isfile(f) and a not in ontem_locais:
                        locais.append({"id": a, "tipo": "local", "arquivo": f})
            random.shuffle(locais)
        alvo = 14 if corte <= 0.3 else 10
        locais = locais[:max(0, alvo - 4)] if estilo != "flores" else locais[:alvo]
        px = [] if estilo == "flores" or api is None else clipes_pixabay(api, estilos[estilo], alvo - len(locais), usados, cfg, log)
        mats = locais + px
        if len(mats) < cfg["video"]["clipes_min"]:
            erros.append("material insuficiente para '%s' (%d locais, %d Pixabay)" % (estilo, len(locais), len(px)))
            continue
        random.shuffle(mats)
        tags = estilos[estilo]["hashtags"][:]
        random.shuffle(tags)
        tags = tags[:4] + ([cfg["hashtag_marca"]] if cfg.get("hashtag_marca") else [])
        gancho = random.choice(GANCHOS)
        legenda = "%s ✨\n\n%s\n\n%s 🤍" % (gancho, frase, random.choice(CTAS))
        return {"numero": i, "estilo": estilo, "inspiracao": insp, "frase": frase, "corte_s": corte,
                "encaixe": encaixe, "materiais": mats, "legenda": legenda, "hashtags": tags,
                "status": "planejado"}, None
    return None, "; ".join(erros)


def produzir(dia_dir, p, cfg, log):
    num = p["numero"]
    orig = os.path.join(dia_dir, "originais", "video_%d" % num)
    trab = os.path.join(dia_dir, ".trabalho", "video_%d" % num)
    os.makedirs(orig, exist_ok=True)
    os.makedirs(trab, exist_ok=True)
    for m in p["materiais"]:
        if m["tipo"] == "pixabay":
            m["arquivo"] = os.path.join(orig, "pixabay_%s.mp4" % m["id"])
            pb.baixar(m["url"], m["arquivo"], log)
        else:
            dest = os.path.join(orig, m["id"])
            if not os.path.isfile(dest):
                shutil.copy2(m["arquivo"], dest)
            m["arquivo"] = dest
        m["duracao"] = duracao(m["arquivo"])
    v = cfg["video"]
    rcfg = {"largura": v["largura"], "altura": v["altura"], "fps": v["fps"], "duracao_s": v["duracao_s"],
            "corte_s": p["corte_s"], "encaixe": p["encaixe"],
            "texto": {"fonte": v["fonte"], "tamanho": int(v["altura"] * v["texto_rel"])}}
    saida = os.path.join(dia_dir, "video_%d.mp4" % num)
    render.montagem(p["materiais"], p["frase"], ESTILOS_GRADE[p["estilo"]], rcfg,
                    os.path.join(RAIZ, "fonts"), trab, saida, log)


ESTILOS_GRADE = {}


def publicacoes(dia_dir, videos, dia):
    L = ["# Publicações de %s" % dia, "",
         "Vídeos sem música (4:3). Escolha o som no TikTok; os cortes seguem 120 BPM "
         "(0,25 s) ou 100 BPM (0,3/0,6 s).", ""]
    for p in videos:
        L += ["## Vídeo %d — estilo %s (base: inspiração %s)" % (p["numero"], p.get("estilo", "-"), p.get("inspiracao", "-")), ""]
        if p["status"] != "concluido":
            L += ["**Não gerado:** %s" % p.get("erro"), ""]
            continue
        L += ["- Arquivo: `video_%d.mp4`" % p["numero"], "- Frase no vídeo: \"%s\"" % p["frase"], "",
              "**Legenda (copiar):**", "", "```", p["legenda"], "", " ".join(p["hashtags"]), "```", "",
              "**Fontes:**", ""]
        for m in p["materiais"]:
            if m["tipo"] == "pixabay":
                L.append("- Pixabay %s — por %s — %s (Pixabay Content License)" % (m["id"], m.get("autor"), m.get("pagina")))
            else:
                L.append("- banco da conta: %s" % m["id"])
        L.append("")
    with open(os.path.join(dia_dir, "PUBLICACOES.md"), "w", encoding="utf-8") as f:
        f.write("\n".join(L))


def main(argv=None):
    ap = argparse.ArgumentParser()
    ap.add_argument("--saida")
    ap.add_argument("--data")
    ap.add_argument("--quantidade", type=int)
    ap.add_argument("--banco", help="pasta com as imagens da conta (padrão: config/env TIKTOK_BANCO)")
    a = ap.parse_args(argv)
    cfg = ler_json(os.path.join(RAIZ, "config.json"), None)
    cfg["video"].update(cfg.get("montagem", {}))
    estilos = ler_json(os.path.join(RAIZ, "data", "estilos.json"), None)
    frases = ler_json(os.path.join(RAIZ, "data", "frases.json"), None)
    banco = ler_json(os.path.join(RAIZ, "data", "banco_local.json"), {})
    ESTILOS_GRADE.update({k: v["grade"] for k, v in estilos.items()})
    raiz = pasta_saida(cfg, a.saida)
    os.makedirs(raiz, exist_ok=True)
    hoje = dt.date.fromisoformat(a.data) if a.data else dt.date.today()
    dia = hoje.isoformat()
    log = configurar_log(os.path.join(raiz, "logs"), dia)
    random.seed(dia)
    pasta_banco = a.banco or os.environ.get("TIKTOK_BANCO") or os.path.expandvars(cfg.get("pasta_banco_local", ""))
    if pasta_banco and not os.path.isdir(pasta_banco):
        log.warning("pasta do banco local não encontrada: %s (usando só o Pixabay)", pasta_banco)
        pasta_banco = ""
    qtd = a.quantidade or cfg["videos_por_dia"]
    dia_dir = os.path.join(raiz, dia)
    os.makedirs(dia_dir, exist_ok=True)
    try:
        with Trava(os.path.join(raiz, ".em_execucao"), log):
            try:
                api = pb.Pixabay(pb.ler_chave(), log, cache_dir=os.path.join(raiz, ".cache_api"))
            except pb.ErroChave as e:
                if not pasta_banco:
                    raise
                log.warning("%s — seguindo só com o banco local", e)
                api = None
            hp = os.path.join(raiz, "historico.json")
            hist = ler_json(hp, {})
            for k in ("materiais", "frases", "estilos", "locais"):
                hist.setdefault(k, {})
            ep = os.path.join(dia_dir, "estado.json")
            estado = ler_json(ep, {"data": dia, "formato": "montagem", "videos": []})
            lim = (hoje - dt.timedelta(days=cfg["dias_sem_repetir_material"])).isoformat()
            usados = {k for k, d in hist["materiais"].items() if d >= lim}
            for i in range(1, qtd + 1):
                if any(p["numero"] == i for p in estado["videos"]):
                    continue
                p, erro = planejar(i, hoje, hist, estado, cfg, estilos, frases, banco, pasta_banco, api, usados, log)
                if not p:
                    log.error("vídeo %d sem material: %s", i, erro)
                    estado["videos"].append({"numero": i, "status": "sem_material", "erro": erro})
                else:
                    estado["videos"].append(p)
                    hist["estilos"].setdefault(dia, []).append(p["estilo"])
                    hist["frases"][p["frase"]] = dia
                    for m in p["materiais"]:
                        if m["tipo"] == "pixabay":
                            hist["materiais"][m["id"]] = dia
                        else:
                            hist["locais"].setdefault(dia, []).append(m["id"])
                gravar_json(ep, estado)
                gravar_json(hp, hist)
            for p in estado["videos"]:
                if p["status"] in ("concluido", "sem_material"):
                    continue
                try:
                    log.info("montando vídeo %d (estilo %s, base %s)", p["numero"], p["estilo"], p["inspiracao"])
                    produzir(dia_dir, p, cfg, log)
                    p["status"] = "concluido"
                    p.pop("erro", None)
                except pb.ErroChave:
                    raise
                except Exception as e:
                    p["status"] = "falhou"
                    p["erro"] = str(e)
                    log.error("vídeo %d falhou: %s\n%s", p["numero"], e, traceback.format_exc())
                gravar_json(ep, estado)
            publicacoes(dia_dir, estado["videos"], dia)
            prob = [p for p in estado["videos"] if p["status"] != "concluido"]
            if not prob:
                shutil.rmtree(os.path.join(dia_dir, ".trabalho"), ignore_errors=True)
            else:
                with open(os.path.join(dia_dir, "PROBLEMAS.txt"), "w", encoding="utf-8") as f:
                    for p in prob:
                        f.write("vídeo %d: %s — %s\n" % (p["numero"], p["status"], p.get("erro")))
            ok = [p for p in estado["videos"] if p["status"] == "concluido"]
            log.info("RESUMO %s: %d/%d vídeos prontos em %s", dia, len(ok), len(estado["videos"]), dia_dir)
            for p in ok:
                log.info("  video_%d.mp4 | %s (%s) | %s", p["numero"], p["estilo"], p["inspiracao"], p["frase"])
            return 0 if not prob else 2
    except pb.ErroChave as e:
        log.error("%s", e)
        return 3
    except Exception as e:
        log.error("falha geral: %s\n%s", e, traceback.format_exc())
        return 1


if __name__ == "__main__":
    sys.exit(main())
