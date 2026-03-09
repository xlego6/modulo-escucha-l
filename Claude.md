NO ALTERAR las siguientes carpetas:
C:\mcl\modulo-escucha (NO alterar, sólo lectura para desarrollo y aprendizaje)
C:\mcl\modulo-de-captura (NO alterar, sólo lectura para desarrollo y aprendizaje)


Todo el desarrollo debe hacerse en C:\mcl\modulo-escucha-l

modulo-escucha-l se basa en las aplicaciones modulo-escucha, modulo-de-captura y sus componentes. modulo-de-captura es una aplicación muy sólida y completa, por lo cual es imperativo imitar lo más fielmente posible el estilo de escritura de código, su organización y estructura.
C:\mcl\modulo-escucha es una versión dockerizada de modulo-de-captura. C:\mcl\modulo-de-captura contiene todo el código original, por si es necesario explorar en detalle cómo está compuesta alguna funcionalidad.
Es una aplicación compleja, así que es importante verificar la conexión entre módulos, vistas y base de datos para saber que todo funciona.

El objetivo es crear una aplicación sencilla, ligera, pero que contenga todos los módulos, bases de datos y funcionalidades necesarias
Estadísticas generales de entrevistas.
Mapa
Descargar datos en excel
Buscadora
Personas entrevistadas
Recolectar entrevistas, cargar metadatos de un solo tipo de entrevista (podemos tomar víctimas, familiares o testigos y no usar el resto.)
Gestionar archivos adjuntos
Ver las entrevistas propias, y ver otras entrevistas según permisos
Vsualizar adjuntos
Gestionar transcripciones (asignación de entrevistas, asignación de etiquetado, resúmenes y seguimiento a entrevistas)
Gestionar permisos de acceso
Gestión de catálogos
Gestionar usuarios
revisión de entrevistas
accesos otorgados
desclasificación
forzar generar exceles
traza de actividad
mi perfil
ayuda

El proyecto actual está alojado en C:\mcl\modulo-escucha-l

  ---
Fase 5: Estrategia de Migración de Código

  Para cada funcionalidad:
  1. Identificar controladores/modelos en modulo-de-captura/
  2. Copiar y simplificar (quitar dependencias innecesarias)
  3. Adaptar vistas Blade
  4. Probar integración

Fuente principal
Barlow
Color principal (hex)
"#ebc01a"
Paleta de colores secundarios (hex)
"#73c0c3"
"#709fc3
"#817dc3"
"#b882c3"
"#ee7cba"
"#ee133b"
"#ee8047"
"#f0c75f"
"#91bd5e"
"#7a784e"
"#7a784e"
"#a29163"
"#a27d64"
"#96511c"
"#ab5c1e"
"#c05640"
"#d77528"
"#daa913"
"#cba677"
"#e2ceb7"
"#fbfaee"
"#000000"
"#595959"
"#7f7f7f"
"#a5a5a5"
use relative paths instead of absolute paths

## Desarrollo

1. Revisar codigo en `../modulo-de-captura/` o `../www/`
2. Copiar y simplificar controladores/modelos necesarios
3. Adaptar vistas Blade