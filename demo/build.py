"""Genera las páginas de la demo, cada una en un solo archivo (sin dependencias): chat.html e index.html (inicio).
Inlinan el CSS real del widget, Montserrat y el logo. El panel (panel.html) lo genera build_dashboard.sh."""
import base64, re
from pathlib import Path
here = Path(__file__).parent
assets = here.parent / "wordpress-plugin/cpnnet-asistente/assets"
b64 = lambda path, mime: f"data:{mime};base64," + base64.b64encode(path.read_bytes()).decode()
fonts = lambda css: re.sub(r'url\("fonts/(montserrat-latin-\d+-normal\.woff2)"\)', lambda m: f'url("{b64(assets / "fonts" / m.group(1), "font/woff2")}")', css)
logo = b64(assets / "img/cpnnet-logo-white.png", "image/png")
chat_css = fonts((assets / "chat.css").read_text(encoding="utf-8"))

chat = (here / "template.html").read_text(encoding="utf-8").replace("/*CSS*/", chat_css).replace("__LOGO_WHITE__", logo)
(here / "chat.html").write_text(chat, encoding="utf-8")

# la portada solo necesita las @font-face (las primeras 5 líneas de chat.css)
face_css = fonts("\n".join((assets / "chat.css").read_text(encoding="utf-8").splitlines()[:5]))
home = (here / "landing.template.html").read_text(encoding="utf-8").replace("/*FONTS*/", face_css).replace("__LOGO_WHITE__", logo)
(here / "index.html").write_text(home, encoding="utf-8")
print("demo/chat.html", len(chat) // 1024, "KB · demo/index.html", len(home) // 1024, "KB")
