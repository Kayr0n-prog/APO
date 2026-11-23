"""Cliente mínimo da API oficial do Pixabay (sem dependências externas).

A chave é lida da variável de ambiente PIXABAY_API_KEY ou de um arquivo
fora do repositório (ver ler_chave). Ela nunca é escrita em logs: toda URL
registrada passa por `mascarar`.
"""
import json
import os
import time
import urllib.parse
import urllib.request
import urllib.error

API_IMG = "https://pixabay.com/api/"
API_VID = "https://pixabay.com/api/videos/"
UA = "tiktok-auto/1.0"


class ErroChave(Exception):
    pass


def ler_chave():
    chave = os.environ.get("PIXABAY_API_KEY", "").strip()
    if not chave:
        caminho = os.environ.get("PIXABAY_KEY_FILE", "")
        if caminho and os.path.isfile(caminho):
            with open(caminho, encoding="utf-8") as f:
                chave = f.read().strip()
    if not chave:
        raise ErroChave(
            "Chave do Pixabay não encontrada. Defina PIXABAY_API_KEY "
            "(o instalador do Windows faz isso de forma criptografada).")
    return chave


def mascarar(texto, chave):
    return texto.replace(chave, "***") if chave else texto


class Pixabay:
    def __init__(self, chave, log, cache_dir=None):
        self.chave = chave
        self.log = log
        self.cache_dir = cache_dir
        self._ultimo = 0.0

    def _get(self, base, params, tentativas=4):
        params = dict(params, key=self.chave)
        url = base + "?" + urllib.parse.urlencode(params)
        cache = None
        if self.cache_dir:
            os.makedirs(self.cache_dir, exist_ok=True)
            nome = urllib.parse.quote(base.rsplit("/", 2)[-2] + "_" + urllib.parse.urlencode(
                sorted((k, v) for k, v in params.items() if k != "key")), safe="")[:180]
            cache = os.path.join(self.cache_dir, nome + ".json")
            if os.path.isfile(cache) and time.time() - os.path.getmtime(cache) < 20 * 3600:
                with open(cache, encoding="utf-8") as f:
                    return json.load(f)
        for t in range(tentativas):
            espera = 0.8 - (time.time() - self._ultimo)
            if espera > 0:
                time.sleep(espera)
            self._ultimo = time.time()
            try:
                req = urllib.request.Request(url, headers={"User-Agent": UA})
                with urllib.request.urlopen(req, timeout=30) as r:
                    dados = json.loads(r.read().decode("utf-8"))
                if cache:
                    with open(cache, "w", encoding="utf-8") as f:
                        json.dump(dados, f)
                return dados
            except urllib.error.HTTPError as e:
                if e.code in (400, 401, 403):
                    corpo = e.read().decode("utf-8", "replace")[:200]
                    raise ErroChave("Pixabay recusou a requisição (%s): %s" % (e.code, mascarar(corpo, self.chave)))
                self.log.warning("Pixabay HTTP %s (tentativa %d) %s", e.code, t + 1, mascarar(url, self.chave))
            except Exception as e:  # rede instável
                self.log.warning("Pixabay falhou (tentativa %d): %s", t + 1, mascarar(str(e), self.chave))
            time.sleep(2 ** t * 2)
        raise RuntimeError("Pixabay indisponível após %d tentativas" % tentativas)

    def buscar_videos(self, termo, pagina=1):
        d = self._get(API_VID, {"q": termo, "safesearch": "true", "video_type": "film", "per_page": 50,
                                "page": pagina, "order": "popular"})
        return d.get("hits", [])

    def buscar_fotos(self, termo, pagina=1):
        d = self._get(API_IMG, {"q": termo, "image_type": "photo", "orientation": "vertical",
                                "safesearch": "true", "per_page": 50, "page": pagina,
                                "order": "popular", "min_width": 700, "min_height": 1200})
        return d.get("hits", [])


def baixar(url, destino, log, tentativas=4):
    if os.path.isfile(destino) and os.path.getsize(destino) > 0:
        return destino
    tmp = destino + ".part"
    for t in range(tentativas):
        try:
            req = urllib.request.Request(url, headers={"User-Agent": UA})
            with urllib.request.urlopen(req, timeout=120) as r, open(tmp, "wb") as f:
                while True:
                    bloco = r.read(1 << 20)
                    if not bloco:
                        break
                    f.write(bloco)
            os.replace(tmp, destino)
            return destino
        except Exception as e:
            log.warning("download falhou (tentativa %d) %s: %s", t + 1, os.path.basename(destino), e)
            time.sleep(2 ** t * 2)
    raise RuntimeError("não foi possível baixar " + os.path.basename(destino))
