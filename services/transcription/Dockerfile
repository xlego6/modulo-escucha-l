# Dockerfile para Servicio de Transcripción
# Usa WhisperX para transcripción de audio con soporte GPU opcional

# Imagen base con Python y soporte CUDA (opcional)
# Para CPU: usar python:3.10-slim
# Para GPU: usar nvidia/cuda:11.8-cudnn8-runtime-ubuntu22.04
ARG BASE_IMAGE=python:3.10-slim
FROM ${BASE_IMAGE}

# Evitar prompts interactivos
ENV DEBIAN_FRONTEND=noninteractive
ENV PYTHONUNBUFFERED=1
ENV PYTHONDONTWRITEBYTECODE=1

# Directorio de trabajo
WORKDIR /app

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y --no-install-recommends \
    ffmpeg \
    git \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

# Copiar requirements primero (para cache de Docker)
COPY requirements.txt .

# Instalar dependencias Python
RUN pip install --no-cache-dir --upgrade pip && \
    pip install --no-cache-dir flask>=2.0.0 && \
    pip install --no-cache-dir python-docx>=0.8.11

# Instalar WhisperX desde GitHub
RUN pip install --no-cache-dir git+https://github.com/m-bain/whisperx.git

# Instalar librosa (requerido para diarizacion con pyannote)
RUN pip install --no-cache-dir librosa

# FORZAR PyTorch CPU DESPUES de WhisperX para evitar conflictos de versiones CUDA
# WhisperX instala versiones GPU que causan incompatibilidad torch/torchvision
RUN pip install --no-cache-dir --force-reinstall \
    torch==2.5.1 torchaudio==2.5.1 torchvision==0.20.1 \
    --index-url https://download.pytorch.org/whl/cpu

# Copiar código del servicio
COPY transcription_service.py .

# Variables de entorno configurables
ENV WHISPER_MODEL=base
ENV WHISPER_LANGUAGE=es
ENV WHISPER_COMPUTE_TYPE=int8
ENV SERVICE_PORT=5000
ENV HF_TOKEN=""

# Puerto expuesto
EXPOSE 5000

# Health check (start-period largo para permitir precarga de modelos)
HEALTHCHECK --interval=30s --timeout=10s --start-period=180s --retries=3 \
    CMD python -c "import urllib.request; urllib.request.urlopen('http://localhost:5000/status')" || exit 1

# Comando de inicio
CMD ["python", "transcription_service.py", "--mode", "api", "--port", "5000"]
