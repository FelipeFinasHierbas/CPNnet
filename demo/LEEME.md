# Demos del asistente: cómo subirlas a un hosting

1. Generar el paquete: `./demo/build_all.sh` (crea `dist/asistente-demo.zip`).
2. En cPanel > Administrador de archivos, ir a la carpeta del sitio donde se quiera publicar (por ejemplo `public_html/old-site/`).
3. Subir `asistente-demo.zip` y usar **Extraer**. Queda la carpeta `asistente-demo/`.
4. Abrir `https://TU-SITIO/…/asistente-demo/`.

Notas:
- Son archivos HTML estáticos: WordPress los sirve tal cual (no pasan por PHP ni por la base de datos).
- Llevan `noindex` (meta, `robots.txt` y cabecera) para que los buscadores no las indexen. Si la demo no debe ser pública, protegerla con cPanel > **Privacidad del directorio** (usuario y contraseña).
- Para retirarla, borrar la carpeta `asistente-demo/`.
