# Servicio de Transcripcion

Servicio de transcripcion automatica basado en WhisperX con soporte para:
- Transcripcion con GPU/CUDA
- Diarizacion de hablantes (identificacion de quien habla)
- API REST para integracion con Laravel

## Requisitos

- Python 3.9+
- CUDA 11.x (para GPU) o CPU
- ~8GB VRAM para modelo large-v2

## Instalacion

```bash
# Crear entorno virtual
python -m venv venv
source venv/bin/activate  # Linux/Mac
venv\Scripts\activate     # Windows

# Instalar dependencias base
pip install flask torch

# Instalar WhisperX
pip install git+https://github.com/m-bain/whisperx.git

# Para diarizacion (opcional, requiere token de HuggingFace)
pip install pyannote.audio
```

## Configuracion

Variables de entorno:
- `WHISPER_MODEL`: Modelo a usar (default: large-v2)
- `WHISPER_LANGUAGE`: Idioma (default: es)
- `HF_TOKEN`: Token de HuggingFace para diarizacion

## Uso

### Modo API (recomendado para integracion)

```bash
python transcription_service.py --mode api --port 5000
```

Endpoints:
- `GET /status` - Estado del servicio
- `POST /transcribe` - Transcribir archivo (sincrono)
- `POST /transcribe/async` - Transcribir archivo (asincrono)
- `GET /job/<job_id>` - Estado de trabajo asincrono

### Modo Single (transcribir un archivo)

```bash
python transcription_service.py --mode single -i audio.mp3 -o resultado.json
```

## Ejemplo de uso desde PHP/Laravel

```php
// Llamar al servicio de transcripcion
$response = Http::post('http://localhost:5000/transcribe', [
    'audio_path' => '/path/to/audio.mp3',
    'with_diarization' => true
]);

$result = $response->json();
if ($result['success']) {
    $transcripcion = $result['text'];
    $segmentos = $result['segments'];
}
```

## Docker

El servicio puede ejecutarse en Docker para aislamiento:

```dockerfile
FROM python:3.10-slim

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y ffmpeg git

# Copiar archivos
COPY requirements.txt .
RUN pip install -r requirements.txt
RUN pip install git+https://github.com/m-bain/whisperx.git

COPY transcription_service.py .

EXPOSE 5000
CMD ["python", "transcription_service.py", "--mode", "api"]
```
