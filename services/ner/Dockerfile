# Dockerfile para Servicio NER (Named Entity Recognition)
# Usa spaCy para detección de entidades y anonimización

FROM python:3.10-slim

# Evitar prompts interactivos
ENV DEBIAN_FRONTEND=noninteractive
ENV PYTHONUNBUFFERED=1
ENV PYTHONDONTWRITEBYTECODE=1

# Directorio de trabajo
WORKDIR /app

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

# Copiar requirements primero (para cache de Docker)
COPY requirements.txt .

# Instalar dependencias Python
RUN pip install --no-cache-dir --upgrade pip && \
    pip install --no-cache-dir flask>=2.0.0 && \
    pip install --no-cache-dir spacy>=3.2.0

# Descargar modelo de español (grande para mejor precisión)
RUN python -m spacy download es_core_news_lg

# Copiar código del servicio
COPY ner_service.py .

# Variables de entorno configurables
ENV SPACY_MODEL=es_core_news_lg
ENV SERVICE_PORT=5001

# Puerto expuesto
EXPOSE 5001

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD python -c "import urllib.request; urllib.request.urlopen('http://localhost:5001/status')" || exit 1

# Comando de inicio
CMD ["python", "ner_service.py", "--mode", "api", "--port", "5001"]
