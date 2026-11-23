"""Teste da montagem sem acessar o Pixabay: usa arquivos locais passados na
linha de comando (vídeos e/ou fotos) e gera um vídeo de amostra."""
import json, logging, os, sys
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from app import render
RAIZ = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
cfg = json.load(open(os.path.join(RAIZ, "config.json"), encoding="utf-8"))
saida, arquivos = sys.argv[1], sys.argv[2:]
logging.basicConfig(level=logging.INFO)
log = logging.getLogger("teste")
mats = [{"tipo": "foto" if a.lower().endswith((".jpg", ".jpeg", ".png")) else "video", "arquivo": os.path.abspath(a), "duracao": float(__import__("subprocess").run(["ffprobe","-v","error","-show_entries","format=duration","-of","csv=p=0",a],capture_output=True,text=True).stdout or 10)} for a in arquivos]
n = len(mats) - len(mats) % 2
textos = [("Gancho", "um lembrete para você hoje", 0, 1)] + [("Frase", "você é suficiente exatamente como é hoje", 2 + 2*k, 3 + 2*k) for k in range((n-2)//2)]
trab = os.path.join(os.path.dirname(os.path.abspath(saida)), ".trabalho_teste")
os.makedirs(trab, exist_ok=True)
print("duração:", render.montar(mats[:n], textos, cfg["video"], cfg["identidade"], os.path.join(RAIZ, "fonts"), trab, saida, log))
