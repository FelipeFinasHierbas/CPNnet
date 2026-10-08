"""Demo guionada de la variante The Forest: reutiliza demo/template.html con la marca y los escenarios de variants/the-forest/.
Genera demo/forest-chat.html (un solo archivo)."""
import base64, json, re
from pathlib import Path
here = Path(__file__).parent
V = here.parent / "variants/the-forest"
assets = here.parent / "wordpress-plugin/cpnnet-asistente/assets"
b64 = lambda path, mime: f"data:{mime};base64," + base64.b64encode(path.read_bytes()).decode()
fonts = lambda css: re.sub(r'url\("fonts/(montserrat-latin-\d+-normal\.woff2)"\)', lambda m: f'url("{b64(assets / "fonts" / m.group(1), "font/woff2")}")', css)
brand = json.loads((V / "brand.json").read_text(encoding="utf-8"))
c = brand["colors"]
css = fonts((assets / "chat.css").read_text(encoding="utf-8")).replace("#2b4c8c", c["primary"]).replace("#1b2f5e", c["dark"]).replace("#4f9bd6", c["accent"]).replace("#f4f7fb", c["bg"])
s = (here / "template.html").read_text(encoding="utf-8")
s = s.replace("/*CSS*/", css).replace("__LOGO_WHITE__", b64(V / "img/forest-logo-white.png", "image/png"))
s = s.replace("--p:#2b4c8c;--d:#1b2f5e;--a:#4f9bd6;--bg:#f4f7fb", f"--p:{c['primary']};--d:{c['dark']};--a:{c['accent']};--bg:{c['bg']}")
s = re.sub(r"var WELCOME='.*?';", lambda m: "var WELCOME=" + json.dumps(brand["welcome"], ensure_ascii=False) + ";", s, 1)
scen = (V / "demo/scenarios.json").read_text(encoding="utf-8")
s = re.sub(r"var SCENARIOS=\[.*?\n\];\nvar cur=", lambda m: "var SCENARIOS=" + scen + ";\nvar cur=", s, 1, flags=re.S)
s = s.replace("CPNnet Security", brand["name"]).replace("el catálogo de CPNnet", "el material de The Forest").replace("equipo comercial de CPNnet", "equipo comercial de The Forest")
s = s.replace('href="index.html">← Todas las demos</a>', 'href="#" style="visibility:hidden">·</a>')
(here / "forest-chat.html").write_text(s, encoding="utf-8")
print("demo/forest-chat.html", len(s) // 1024, "KB; quedan 'CPNnet':", s.count("CPNnet"))
