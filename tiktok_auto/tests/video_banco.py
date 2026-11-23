"""Monta um vídeo no formato montagem a partir de uma categoria do banco local."""
import json, logging, os, random, subprocess, sys
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from app import render
RAIZ = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
pasta, cat, saida, frase = sys.argv[1], sys.argv[2], sys.argv[3], sys.argv[4]
W, H, corte, dur, encaixe = int(sys.argv[5]), int(sys.argv[6]), float(sys.argv[7]), float(sys.argv[8]), sys.argv[9]
grade = sys.argv[10]
logging.basicConfig(level=logging.INFO, format="%(message)s")
banco = json.load(open(os.path.join(RAIZ, "data", "banco_local.json"), encoding="utf-8"))
arqs = banco[cat][:]
random.seed(cat); random.shuffle(arqs)
clipes = []
for a in arqs:
    f = os.path.join(pasta, a)
    d = subprocess.run(["ffprobe", "-v", "error", "-show_entries", "format=duration", "-of", "csv=p=0", f], capture_output=True, text=True).stdout.strip()
    clipes.append({"arquivo": f, "duracao": float(d) if d not in ("", "N/A") else 5})
trab = os.path.join(os.path.dirname(os.path.abspath(saida)), ".trab_" + cat)
os.makedirs(trab, exist_ok=True)
cfg = {"largura": W, "altura": H, "fps": 30, "duracao_s": dur, "corte_s": corte, "encaixe": encaixe,
       "texto": {"fonte": "Inter Medium", "tamanho": int(H * 0.042)}}
render.montagem(clipes, frase if frase != "-" else "", grade, cfg, os.path.join(RAIZ, "fonts"), trab, saida, logging.getLogger())
