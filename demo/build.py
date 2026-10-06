"""Genera demo/index.html (archivo único, sin dependencias): inlina el CSS real del widget, Montserrat y el logo."""
import base64, re
from pathlib import Path
here = Path(__file__).parent
assets = here.parent / "wordpress-plugin/cpnnet-asistente/assets"
b64 = lambda path, mime: f"data:{mime};base64," + base64.b64encode(path.read_bytes()).decode()
css = (assets / "chat.css").read_text(encoding="utf-8")
css = re.sub(r'url\("fonts/(montserrat-latin-\d+-normal\.woff2)"\)', lambda m: f'url("{b64(assets / "fonts" / m.group(1), "font/woff2")}")', css)
html = (here / "template.html").read_text(encoding="utf-8").replace("/*CSS*/", css)
html = html.replace("__LOGO_WHITE__", b64(assets / "img/cpnnet-logo-white.png", "image/png"))
(here / "index.html").write_text(html, encoding="utf-8")
print("demo/index.html", len(html) // 1024, "KB")
