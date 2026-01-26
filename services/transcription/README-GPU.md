# Transcripción con GPU NVIDIA

## Problema Actual

El servicio de transcripción está configurado para usar GPU (`large-v3-turbo` + CUDA), pero crashea por incompatibilidad de cuDNN:

```
Unable to load libcudnn_ops.so.9
Invalid handle. Cannot load symbol cudnnCreateTensorDescriptor
```

## Causa

`faster-whisper` requiere cuDNN 9.1+ pero la imagen Docker `pytorch/pytorch:2.5.1-cuda12.1-cudnn9-runtime` tiene una versión incompatible de las librerías cuDNN operations.

## Solución Temporal: Usar CPU

Para que funcione de inmediato, cambiar a CPU en `docker-compose.yml`:

```yaml
environment:
  - WHISPER_MODEL=large-v3-turbo
  - WHISPER_LANGUAGE=es
  - WHISPER_DEVICE=cpu              # Cambiar a cpu
  - WHISPER_COMPUTE_TYPE=int8       # Cambiar a int8 para CPU
  - SERVICE_PORT=5000
```

Luego reconstruir:
```bash
docker-compose down
docker-compose build transcription
docker-compose up -d transcription
```

## Solución Definitiva: Arreglar cuDNN para GPU

### Opción 1: Imagen NVIDIA CUDA oficial (Recomendado)

Modificar `services/transcription/Dockerfile.gpu`:

```dockerfile
# Usar imagen oficial NVIDIA con cuDNN 9.1
FROM nvidia/cuda:12.1.0-cudnn9-runtime-ubuntu22.04

# Instalar Python 3.11
RUN apt-get update && apt-get install -y \
    python3.11 python3-pip ffmpeg git \
    && rm -rf /var/lib/apt/lists/*

# Instalar PyTorch con CUDA 12.1
RUN pip3 install --no-cache-dir \
    torch torchvision torchaudio --index-url https://download.pytorch.org/whl/cu121

# Resto del Dockerfile...
```

### Opción 2: Instalar cuDNN 9.1 manualmente

```dockerfile
FROM pytorch/pytorch:2.5.1-cuda12.1-cudnn9-runtime

# Descargar e instalar cuDNN 9.1
RUN apt-get update && apt-get install -y wget && \
    wget https://developer.download.nvidia.com/compute/cudnn/redist/cudnn/linux-x86_64/cudnn-linux-x86_64-9.1.0.70_cuda12-archive.tar.xz && \
    tar -xvf cudnn-linux-x86_64-9.1.0.70_cuda12-archive.tar.xz && \
    cp cudnn-*-archive/lib/libcudnn* /usr/local/cuda/lib64/ && \
    cp cudnn-*-archive/include/cudnn*.h /usr/local/cuda/include/ && \
    rm -rf cudnn-*

# Resto del Dockerfile...
```

### Opción 3: Usar solo CUDA sin cuDNN (VAD desactivado)

Si el VAD (Voice Activity Detection) no es crítico:

```python
# En transcription_service.py, modificar:
segments, info = self.model.transcribe(
    str(audio_path),
    language=self.language,
    beam_size=5,
    vad_filter=False,  # Desactivar VAD que requiere cuDNN
)
```

## Verificar GPU Funcional

Después de aplicar la solución:

```bash
# 1. Reconstruir
docker-compose build transcription
docker-compose up -d transcription

# 2. Verificar GPU detectada
curl http://localhost:5000/status | jq

# Debe mostrar:
# "device": "cuda"
# "gpu": { "name": "NVIDIA GeForce RTX 3070" }

# 3. Probar transcripción
curl -X POST http://localhost:5000/transcribe \
  -H "Content-Type: application/json" \
  -d '{"audio_path": "/app/storage/test.mp3", "with_diarization": false}'
```

## Rendimiento

| Modo | Velocidad | Precisión | Uso VRAM |
|------|-----------|-----------|----------|
| CPU (int8) | ~0.3x realtime | Buena | 0 GB |
| GPU (float16) | ~2-5x realtime | Mejor | ~2-4 GB |
| GPU (int8) | ~3-8x realtime | Buena | ~1-2 GB |

**Ejemplo**: Audio de 10 minutos
- CPU: ~30-40 minutos
- GPU: ~2-5 minutos

## Modelos Disponibles

Configurados en `docker-compose.yml` o interfaz web:

- `large-v3-turbo`: **Predeterminado** - Balance óptimo velocidad/precisión
- `large-v3`: Máxima precisión, más lento
- `large-v2`: Versión estable anterior
- `medium`: Más rápido, menor precisión
- `small`: Muy rápido, precisión básica
- `base`: Minimalista

## Estado Actual

- ✅ Configuración web completa (modelo, dispositivo, idioma)
- ✅ `faster-whisper` instalado
- ✅ GPU detectada (RTX 3070)
- ❌ cuDNN incompatible → crashea al transcribir
- ⚠️ Usando CPU temporalmente

## Referencias

- [faster-whisper docs](https://github.com/guillaumekln/faster-whisper)
- [NVIDIA cuDNN](https://developer.nvidia.com/cudnn)
- [PyTorch CUDA](https://pytorch.org/get-started/locally/)
