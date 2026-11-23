# tiktok_auto — 3 vídeos por dia com material do Pixabay

Gera todos os dias 3 vídeos verticais 1080×1920, de 15 a 35 s e **sem música**. Cada vídeo tem:
- clipes e fotos do Pixabay (API oficial);
- movimento suave nas fotos;
- transições cruzadas presas a uma grade de 100 BPM (troca de cena a cada 2,4 s);
- uma cor única aplicada a tudo;
- frases em português;
- legenda, hashtags e créditos prontos.

## Instalar no Windows (uma vez)

1. Instale o Git ou baixe este repositório e deixe a pasta `tiktok_auto` num lugar fixo.
2. Clique duas vezes em `tiktok_auto\windows\INSTALAR.bat`. O instalador:
   - instala Python e ffmpeg pelo `winget`, se faltarem;
   - pede a chave do Pixabay, que fica **criptografada com DPAPI** em `%LOCALAPPDATA%\TikTokAuto` e nunca vai para o repositório nem para os logs;
   - registra no Agendador de Tarefas a tarefa **"TikTokAuto - videos diarios"**, que roda todo dia às 08:00 e 5 min depois de cada login. Se o PC estava desligado no horário, ela roda quando ligar;
   - roda uma vez para testar.
3. Para mudar o horário: `powershell -ExecutionPolicy Bypass -File windows\instalar.ps1 -Horario 07:30`.
4. Para remover: `windows\desinstalar.ps1`.

## Onde ficam os arquivos

```
%USERPROFILE%\Videos\TikTokAuto\
  2026-11-23\
    video_1.mp4  video_2.mp4  video_3.mp4
    PUBLICACOES.md      ← título, legenda, hashtags e fontes/licenças de cada vídeo
    originais\video_N\  ← fotos e vídeos originais baixados do Pixabay
    estado.json         ← progresso (permite retomar se for interrompido)
    PROBLEMAS.txt       ← só existe se algo faltou
  historico.json        ← material, frases e temas já usados (evita repetição)
  logs\                 ← um log por dia + log do agendador
```

## Regras que o programa segue

- **Material:**
  - só vídeos retrato ≥ 1080×1920, ou paisagem 4K (para recortar em 9:16 sem perder nitidez);
  - fotos verticais ≥ 1080×1920 no original, no máximo 3 por vídeo;
  - tags de IA, ilustração, 3D e animação são descartadas.
- **Repetição:**
  - um material não se repete por 90 dias;
  - uma frase não se repete por 45 dias;
  - o tema não se repete no dia seguinte.
- **Falta de material:** se não houver material aprovado suficiente, o vídeo **não é gerado** e o motivo vai para `PROBLEMAS.txt`. Nada de imagem ruim ou inventada.
- **Recuperação:** rodar de novo no mesmo dia retoma do ponto em que parou e não refaz o que já está pronto.
- **Licença:** Pixabay Content License (uso gratuito, inclusive comercial, sem atribuição obrigatória). Mesmo assim, as fontes ficam registradas em `PUBLICACOES.md`.

## Personalizar

- `config.json`:
  - quantidade de vídeos;
  - BPM da grade, número de cenas e transição;
  - fonte, tamanhos e cores do texto;
  - grade de cor;
  - hashtag da marca (`hashtag_marca`).
- `data/temas.json`:
  - temas e buscas no Pixabay (em inglês, que retorna mais resultados);
  - ganchos, frases e hashtags. Acrescente frases para aumentar a variedade.

## Rodar manualmente / testar

```
set PIXABAY_API_KEY=...   (ou PIXABAY_KEY_FILE=caminho\arquivo)
python run.py --saida C:\pasta --data 2026-11-23 --quantidade 1
python tests\teste_render.py amostra.mp4 foto.jpg clipe1.mp4 ...   (montagem sem API)
```

Códigos de saída:
- **0:** tudo pronto;
- **2:** algum vídeo faltou (ver `PROBLEMAS.txt`);
- **3:** problema com a chave;
- **1:** erro geral.

## Fonte tipográfica

`fonts/CormorantGaramond-SemiBoldItalic.ttf`: Cormorant Garamond, SIL Open Font License 1.1, https://github.com/CatharsisFonts/Cormorant.
