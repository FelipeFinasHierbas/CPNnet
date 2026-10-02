"""Genera demo/index.html (archivo único, sin dependencias) inlinando el CSS real del widget."""
from pathlib import Path
here = Path(__file__).parent
css = (here.parent / "wordpress-plugin/cpnnet-asistente/assets/chat.css").read_text(encoding="utf-8")
html = (here / "template.html").read_text(encoding="utf-8").replace("/*CSS*/", css)
(here / "index.html").write_text(html, encoding="utf-8")
print("demo/index.html", len(html), "bytes")
