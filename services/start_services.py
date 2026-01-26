#!/usr/bin/env python3
"""
Script para iniciar todos los servicios de procesamiento
- Transcripcion (WhisperX) - Puerto 5000
- NER (spaCy) - Puerto 5001

Uso:
    python start_services.py                    # Inicia ambos servicios
    python start_services.py --transcription    # Solo transcripcion
    python start_services.py --ner              # Solo NER
"""

import os
import sys
import argparse
import subprocess
import signal
from pathlib import Path

# Directorio base
BASE_DIR = Path(__file__).parent


def start_transcription_service(port: int = 5000):
    """Inicia el servicio de transcripcion"""
    script = BASE_DIR / "transcription" / "transcription_service.py"
    if not script.exists():
        print(f"Error: No se encuentra {script}")
        return None

    print(f"Iniciando servicio de transcripcion en puerto {port}...")
    return subprocess.Popen(
        [sys.executable, str(script), "--mode", "api", "--port", str(port)],
        cwd=str(BASE_DIR / "transcription")
    )


def start_ner_service(port: int = 5001):
    """Inicia el servicio NER"""
    script = BASE_DIR / "ner" / "ner_service.py"
    if not script.exists():
        print(f"Error: No se encuentra {script}")
        return None

    print(f"Iniciando servicio NER en puerto {port}...")

    # Configurar ruta al modelo personalizado si existe (ruta relativa al proyecto)
    model_path = BASE_DIR.parent / "spacyModel"
    env = os.environ.copy()
    if model_path.exists():
        env['SPACY_MODEL_PATH'] = str(model_path)

    return subprocess.Popen(
        [sys.executable, str(script), "--mode", "api", "--port", str(port)],
        cwd=str(BASE_DIR / "ner"),
        env=env
    )


def main():
    parser = argparse.ArgumentParser(description='Iniciar servicios de procesamiento')
    parser.add_argument('--transcription', action='store_true', help='Solo servicio de transcripcion')
    parser.add_argument('--ner', action='store_true', help='Solo servicio NER')
    parser.add_argument('--transcription-port', type=int, default=5000, help='Puerto transcripcion')
    parser.add_argument('--ner-port', type=int, default=5001, help='Puerto NER')

    args = parser.parse_args()

    processes = []

    # Si no se especifica ninguno, iniciar ambos
    if not args.transcription and not args.ner:
        args.transcription = True
        args.ner = True

    try:
        if args.transcription:
            p = start_transcription_service(args.transcription_port)
            if p:
                processes.append(('Transcripcion', p))

        if args.ner:
            p = start_ner_service(args.ner_port)
            if p:
                processes.append(('NER', p))

        if not processes:
            print("No se inicio ningun servicio")
            return

        print("\n" + "=" * 60)
        print("Servicios iniciados:")
        for name, _ in processes:
            print(f"  - {name}")
        print("=" * 60)
        print("\nPresiona Ctrl+C para detener todos los servicios\n")

        # Esperar a que terminen
        for name, p in processes:
            p.wait()

    except KeyboardInterrupt:
        print("\nDeteniendo servicios...")
        for name, p in processes:
            print(f"  Deteniendo {name}...")
            p.terminate()
            p.wait()
        print("Servicios detenidos")


if __name__ == '__main__':
    main()
