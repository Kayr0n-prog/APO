"""Gera uma amostra do formato 'montagem rápida' para um estilo de data/estilos.json."""
import json, logging, os, random, sys
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from app import pixabay as pb, render
RAIZ = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
estilo, saida = sys.argv[1], sys.argv[2]
logging.basicConfig(level=logging.INFO, format="%(message)s")
log = logging.getLogger("amostra")
cfg = json.load(open(os.path.join(RAIZ, "config.json"), encoding="utf-8"))
est = json.load(open(os.path.join(RAIZ, "data", "estilos.json"), encoding="utf-8"))[estilo]
proib = cfg["filtro_tags_proibidas"]
api = pb.Pixabay(pb.ler_chave(), log, cache_dir=os.path.join(os.path.dirname(saida), ".cache"))
random.seed(estilo)
clipes = []
for termo in est["buscas"]:
    hits = api.buscar_videos(termo)
    ok = 0
    for h in hits:
        if any(p in (h.get("tags") or "").lower() for p in proib):
            continue
        v = h["videos"].get("large") or {}
        if not v.get("url") or v["width"] < 1920 or v["height"] < 1080 or v["height"] > v["width"] or h["duration"] < 4:
            continue
        clipes.append({"id": h["id"], "url": v["url"], "duracao": h["duration"], "tags": h["tags"]})
        ok += 1
        if ok >= 2:
            break
random.shuffle(clipes)
clipes = clipes[:16]
trab = os.path.join(os.path.dirname(saida), ".trab_" + estilo)
os.makedirs(trab, exist_ok=True)
for c in clipes:
    c["arquivo"] = os.path.join(trab, "px%d.mp4" % c["id"])
    pb.baixar(c["url"], c["arquivo"], log)
frase = random.choice(est["frases"])
vcfg = {"largura": 1440, "altura": 1080, "fps": 30, "duracao_s": 12, "corte_s": 0.25,
        "texto": {"fonte": "Inter Medium", "tamanho": 44}}
print(len(clipes), "clipes |", frase)
render.montagem(clipes, frase, est["grade"], vcfg, os.path.join(RAIZ, "fonts"), trab, saida, log)
