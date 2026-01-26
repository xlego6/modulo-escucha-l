#!/usr/bin/env python3
"""
Servicio de Deteccion de Entidades Nombradas (NER) para modulo-escucha-lite
Basado en spaCy con modelo en español

Entidades detectadas:
- PER: Personas
- LOC: Lugares
- ORG: Organizaciones
- DATE: Fechas
- EVENT: Eventos
- GUN: Armas (entidad personalizada)
- MISC: Miscelaneos

Uso:
    python ner_service.py --mode api --port 5001
    python ner_service.py --mode single --input texto.txt --output resultado.json
"""

import os
import sys
import json
import argparse
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Optional
from flask import Flask, request, jsonify

# Verificar dependencias
try:
    import spacy
    SPACY_AVAILABLE = True
except ImportError:
    SPACY_AVAILABLE = False
    print("ADVERTENCIA: spaCy no disponible. Instalar con: pip install spacy")

app = Flask(__name__)

# Cache del modelo
_nlp_cache = None


class NERService:
    """Servicio de deteccion de entidades usando spaCy"""

    # Tipos de entidades y sus etiquetas amigables
    ENTITY_LABELS = {
        'PER': 'Persona',
        'LOC': 'Lugar',
        'ORG': 'Organizacion',
        'DATE': 'Fecha',
        'EVENT': 'Evento',
        'GUN': 'Arma',
        'MISC': 'Miscelaneo'
    }

    def __init__(self, model_path: Optional[str] = None):
        """
        Inicializa el servicio NER

        Args:
            model_path: Ruta al modelo spaCy personalizado
        """
        self.model_path = model_path
        self.nlp = None

    def load_model(self):
        """Carga el modelo spaCy"""
        global _nlp_cache

        if _nlp_cache is not None:
            self.nlp = _nlp_cache
            return

        if not SPACY_AVAILABLE:
            raise RuntimeError("spaCy no esta instalado")

        if self.model_path and Path(self.model_path).exists():
            print(f"Cargando modelo personalizado desde: {self.model_path}")
            self.nlp = spacy.load(self.model_path)
        else:
            # Intentar cargar modelo por defecto
            try:
                print("Cargando modelo es_core_news_lg...")
                self.nlp = spacy.load("es_core_news_lg")
            except OSError:
                print("Modelo es_core_news_lg no encontrado, usando es_core_news_sm...")
                try:
                    self.nlp = spacy.load("es_core_news_sm")
                except OSError:
                    raise RuntimeError(
                        "No se encontro ningun modelo de spaCy. "
                        "Instalar con: python -m spacy download es_core_news_lg"
                    )

        _nlp_cache = self.nlp
        print(f"Modelo cargado: {self.nlp.meta['name']}")

    def detect_entities(self, text: str) -> Dict:
        """
        Detecta entidades en un texto

        Args:
            text: Texto a analizar

        Returns:
            Diccionario con entidades detectadas
        """
        if not SPACY_AVAILABLE:
            return {
                "success": False,
                "error": "spaCy no esta instalado"
            }

        if self.nlp is None:
            self.load_model()

        try:
            # Procesar texto
            doc = self.nlp(text)

            # Extraer entidades
            entities = []
            for ent in doc.ents:
                entities.append({
                    "text": ent.text,
                    "type": ent.label_,
                    "type_name": self.ENTITY_LABELS.get(ent.label_, ent.label_),
                    "start": ent.start_char,
                    "end": ent.end_char
                })

            # Agrupar por tipo
            entities_by_type = {}
            for ent in entities:
                etype = ent['type']
                if etype not in entities_by_type:
                    entities_by_type[etype] = []
                entities_by_type[etype].append(ent)

            # Estadisticas
            stats = {
                "total": len(entities),
                "by_type": {k: len(v) for k, v in entities_by_type.items()}
            }

            return {
                "success": True,
                "entities": entities,
                "entities_by_type": entities_by_type,
                "stats": stats,
                "text_length": len(text),
                "processed_at": datetime.now().isoformat()
            }

        except Exception as e:
            return {
                "success": False,
                "error": str(e)
            }

    def anonymize_text(
        self,
        text: str,
        entity_types: List[str] = None,
        replacement_format: str = "brackets"
    ) -> Dict:
        """
        Anonimiza un texto reemplazando entidades

        Args:
            text: Texto a anonimizar
            entity_types: Tipos de entidades a anonimizar (default: PER, LOC)
            replacement_format: Formato de reemplazo (brackets, numbered, redacted, asterisks)

        Returns:
            Diccionario con texto anonimizado
        """
        if entity_types is None:
            entity_types = ['PER', 'LOC']

        if not SPACY_AVAILABLE:
            return {
                "success": False,
                "error": "spaCy no esta instalado"
            }

        if self.nlp is None:
            self.load_model()

        try:
            doc = self.nlp(text)

            # Recolectar entidades a reemplazar (ordenadas por posicion descendente)
            replacements = []
            counters = {}

            for ent in doc.ents:
                if ent.label_ in entity_types:
                    # Contador por tipo
                    if ent.label_ not in counters:
                        counters[ent.label_] = 0
                    counters[ent.label_] += 1

                    # Generar reemplazo
                    if replacement_format == "brackets":
                        replacement = f"[{ent.label_}]"
                    elif replacement_format == "numbered":
                        replacement = f"[{ent.label_}_{counters[ent.label_]}]"
                    elif replacement_format == "redacted":
                        replacement = "[REDACTADO]"
                    elif replacement_format == "asterisks":
                        replacement = "*" * len(ent.text)
                    else:
                        replacement = f"[{ent.label_}]"

                    replacements.append({
                        "start": ent.start_char,
                        "end": ent.end_char,
                        "original": ent.text,
                        "replacement": replacement,
                        "type": ent.label_
                    })

            # Ordenar por posicion descendente para reemplazar de atras hacia adelante
            replacements.sort(key=lambda x: x['start'], reverse=True)

            # Aplicar reemplazos
            anonymized = text
            for rep in replacements:
                anonymized = anonymized[:rep['start']] + rep['replacement'] + anonymized[rep['end']:]

            # Estadisticas
            stats = {
                "total_replaced": len(replacements),
                "by_type": counters
            }

            return {
                "success": True,
                "original_text": text,
                "anonymized_text": anonymized,
                "replacements": replacements,
                "stats": stats,
                "entity_types_filtered": entity_types,
                "replacement_format": replacement_format,
                "processed_at": datetime.now().isoformat()
            }

        except Exception as e:
            return {
                "success": False,
                "error": str(e)
            }

    def get_status(self) -> Dict:
        """Retorna el estado del servicio"""
        model_info = {}
        if self.nlp:
            model_info = {
                "name": self.nlp.meta.get('name', 'unknown'),
                "version": self.nlp.meta.get('version', 'unknown'),
                "lang": self.nlp.meta.get('lang', 'unknown'),
                "labels": list(self.nlp.get_pipe("ner").labels) if self.nlp.has_pipe("ner") else []
            }

        return {
            "spacy_available": SPACY_AVAILABLE,
            "model_loaded": self.nlp is not None,
            "model_info": model_info,
            "supported_entities": self.ENTITY_LABELS
        }


# Instancia global del servicio
service = None


def get_service():
    """Obtiene o crea la instancia del servicio"""
    global service
    if service is None:
        model_path = os.environ.get('SPACY_MODEL_PATH')
        service = NERService(model_path=model_path)
    return service


# ============ API REST ============

@app.route('/status', methods=['GET'])
def api_status():
    """Estado del servicio"""
    svc = get_service()
    return jsonify(svc.get_status())


@app.route('/detect', methods=['POST'])
def api_detect():
    """
    Detecta entidades en un texto

    Body JSON:
        text: Texto a analizar

    Returns:
        Lista de entidades detectadas
    """
    data = request.get_json()
    if not data or 'text' not in data:
        return jsonify({"success": False, "error": "text requerido"}), 400

    svc = get_service()
    result = svc.detect_entities(data['text'])

    if result['success']:
        return jsonify(result)
    else:
        return jsonify(result), 500


@app.route('/anonymize', methods=['POST'])
def api_anonymize():
    """
    Anonimiza un texto

    Body JSON:
        text: Texto a anonimizar
        entity_types: Lista de tipos a anonimizar (opcional)
        replacement_format: Formato (brackets, numbered, redacted, asterisks)

    Returns:
        Texto anonimizado
    """
    data = request.get_json()
    if not data or 'text' not in data:
        return jsonify({"success": False, "error": "text requerido"}), 400

    svc = get_service()
    result = svc.anonymize_text(
        data['text'],
        entity_types=data.get('entity_types'),
        replacement_format=data.get('replacement_format', 'brackets')
    )

    if result['success']:
        return jsonify(result)
    else:
        return jsonify(result), 500


# ============ CLI ============

def main():
    parser = argparse.ArgumentParser(description='Servicio de deteccion de entidades NER')

    parser.add_argument(
        '--mode',
        choices=['api', 'single', 'anonymize'],
        default='api',
        help='Modo: api (servidor REST), single (detectar en archivo), anonymize (anonimizar)'
    )

    parser.add_argument('--port', type=int, default=5001, help='Puerto para modo API')
    parser.add_argument('--host', default='0.0.0.0', help='Host para modo API')
    parser.add_argument('--input', '-i', help='Archivo de texto de entrada')
    parser.add_argument('--output', '-o', help='Archivo de salida JSON')
    parser.add_argument('--model', help='Ruta al modelo spaCy personalizado')
    parser.add_argument(
        '--entity-types',
        nargs='+',
        default=['PER', 'LOC'],
        help='Tipos de entidades a anonimizar'
    )
    parser.add_argument(
        '--format',
        choices=['brackets', 'numbered', 'redacted', 'asterisks'],
        default='brackets',
        help='Formato de reemplazo para anonimizacion'
    )

    args = parser.parse_args()

    if args.mode in ['single', 'anonymize']:
        if not args.input:
            print("Error: --input requerido")
            sys.exit(1)

        # Leer archivo de entrada
        input_path = Path(args.input)
        if not input_path.exists():
            print(f"Error: Archivo no encontrado: {input_path}")
            sys.exit(1)

        with open(input_path, 'r', encoding='utf-8') as f:
            text = f.read()

        svc = NERService(model_path=args.model)

        if args.mode == 'single':
            print(f"Detectando entidades en: {args.input}")
            result = svc.detect_entities(text)
        else:  # anonymize
            print(f"Anonimizando: {args.input}")
            result = svc.anonymize_text(
                text,
                entity_types=args.entity_types,
                replacement_format=args.format
            )

        if args.output:
            with open(args.output, 'w', encoding='utf-8') as f:
                json.dump(result, f, ensure_ascii=False, indent=2)
            print(f"Resultado guardado en: {args.output}")
        else:
            print(json.dumps(result, ensure_ascii=False, indent=2))

    else:  # modo api
        print(f"Iniciando servidor API NER en {args.host}:{args.port}")
        print("Endpoints:")
        print(f"  GET  /status    - Estado del servicio")
        print(f"  POST /detect    - Detectar entidades")
        print(f"  POST /anonymize - Anonimizar texto")

        app.run(host=args.host, port=args.port, debug=False)


if __name__ == '__main__':
    main()
