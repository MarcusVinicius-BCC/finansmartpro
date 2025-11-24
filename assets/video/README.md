# Vídeo de Demonstração

## Como adicionar o vídeo de demonstração:

1. **Grave um vídeo** do dashboard do FinanSmart Pro em ação (30-60 segundos)
   - Mostre navegando pelas funcionalidades
   - Exiba gráficos e estatísticas
   - Demonstre a responsividade

2. **Converta o vídeo** para os formatos necessários:
   - **MP4** (H.264) - melhor compatibilidade
   - **WebM** - melhor compressão

3. **Nomeie os arquivos**:
   - `demo.mp4`
   - `demo.webm`

4. **Coloque nesta pasta** (`assets/video/`)

## Ferramentas de conversão online gratuitas:
- https://cloudconvert.com/
- https://www.freeconvert.com/video-converter
- https://www.videosmaller.com/ (para comprimir vídeos maiores)

## Como comprimir seu vídeo de 2 minutos:

### Opção 1: CloudConvert (Recomendado)
1. Acesse https://cloudconvert.com/mp4-converter
2. Faça upload do seu vídeo
3. Clique em "Settings" (engrenagem)
4. Configure:
   - **Video Codec**: H.264
   - **Resolution**: 1280x720 (ou menor se for muito grande)
   - **Video Bitrate**: 2000k (2 Mbps)
   - **Audio Bitrate**: 128k
   - **FPS**: 30
5. Converta para MP4
6. Repita para WebM (opcional, mas recomendado)

### Opção 2: HandBrake (Software Desktop - GRÁTIS)
1. Baixe em https://handbrake.fr/
2. Abra seu vídeo
3. Use o preset "Fast 720p30"
4. Em "Video" tab, ajuste:
   - **Quality**: RF 24-28 (quanto maior, menor o arquivo)
   - **Framerate**: 30 FPS constant
5. Salve como `demo.mp4`

### Opção 3: Online-Convert
1. Acesse https://www.online-convert.com/
2. Escolha "Convert to MP4"
3. Configure:
   - **Change size**: 1280x720
   - **Change bitrate**: 2000 kbps
4. Converta e baixe

## Configurações recomendadas:
- **Resolução**: 1920x1080 ou 1280x720
- **Taxa de bits**: 2-5 Mbps (use 3-4 Mbps para vídeos de 2 minutos)
- **FPS**: 30
- **Duração**: 30 segundos a 3 minutos (2 minutos é ideal!)
- **Tamanho**: Máximo 20MB (comprima se necessário)

## Alternativa: Usar GIF animado
Se preferir usar um GIF em vez de vídeo, você pode:
1. Gravar a tela com ferramentas como ScreenToGif
2. Salvar como `demo.gif`
3. No index.php, substituir `<video>` por `<img src="assets/video/demo.gif">`

## Alternativa 2: Usar vídeo do YouTube
Se hospedar no YouTube:
1. Faça upload do vídeo no YouTube
2. No index.php, substitua o `<video>` por um iframe:
```html
<iframe 
    width="100%" 
    height="100%" 
    src="https://www.youtube.com/embed/SEU_VIDEO_ID?autoplay=1&loop=1&mute=1&playlist=SEU_VIDEO_ID" 
    frameborder="0" 
    allow="autoplay; encrypted-media" 
    allowfullscreen>
</iframe>
```

## Enquanto não tiver vídeo:
O sistema já tem um fallback (ícone de desktop) que será exibido automaticamente.
