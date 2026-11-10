# Sistema de Historias Clinicas Odontologicas

Este proyecto fue desarrollado utilizando PHP 8, MySQL y JavaScript puro (Fetch API para peticiones asincronas). Para el diseno se utilizo exclusivamente Bootstrap 5 mediante CDN, cumpliendo con la regla de no usar archivos CSS propios. La generacin de reportes se maneja con FPDF.

## Pasos para la instalacion en XAMPP

1. Copia la carpeta completa del proyecto (`clinica`) y pegala en la ruta `C:\xampp\htdocs\`.
2. Descarga la libreria desde https://www.fpdf.org. Extrae el archivo `fpdf.php` y la carpeta `font`, y colocalos dentro de la ruta `clinica/fpdf/`.
3. Abre phpMyAdmin, asegurate de tener creada la base de datos `clinica_odontologica` y ejecuta el script que esta en `sql/preparar_bd.sql`.
4. Si tu XAMPP tiene contrasena en MySQL, edita el archivo `config/conexion.php` para poner tus credenciales (por defecto usa el usuario root sin clave).
5. Abre tu navegador e ingresa a `http://localhost/clinica/`.

**Credenciales de acceso por defecto:**
- Usuario: admin
- Clave: 12345

## Organizacin de los archivos

- `config/`: Archivos para la conexion a la base de datos mediante PDO y funciones de ayuda.
- `api/`: Scripts de PHP que procesan las consultas AJAX (login, pacientes, procedimientos e historias).
- `js/`: Loica del Frontend para evitar recargar la pagina.
- `reporte/`: Contiene el archivo `historia_pdf.php` encargado de construir el documento con FPDF.
- En la raiz estan `index.php` (pantalla de inicio de sesion) y `panel.php` (interfaz principal de trabajo).

*Nota: Se requiere conexion a internet para que los estilos de Bootstrap carguen correctamente desde el CDN.*