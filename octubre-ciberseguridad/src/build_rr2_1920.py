#!/usr/bin/env python3
"""Las 8 piezas de la grilla en 1080x1920 (formato historia/reel). Salida: ../v5-risk-reduction-portal/1080x1920/"""
import re
from playwright.sync_api import sync_playwright
import build_rr2 as m
import build_v2 as b2
import build_partners as bp

OUT = m.OUT / "1080x1920"
OUT.mkdir(parents=True, exist_ok=True)
SHIFT = 285  # (1920-1350)/2


def tall(html):
    """Lleva una pieza de 1350 a 1920: fondo extendido y contenido centrado verticalmente."""
    html = re.sub(r"(class='cv \w+' style='height:)1350px", r"\g<1>1920px", html, count=1)
    html = html.replace("<div class='grid'></div>",
                        f"<div class='grid'></div><div style='position:absolute;left:0;top:{SHIFT}px;width:1080px;height:1350px'>", 1)
    html = html.replace("<img class='logo'", f"</div><img class='logo' style='top:{80 + SHIFT}px'", 1)
    return html


SECEON = dict(tipo="web", seed=81, wordmark="<b>SECEON</b>", fecha="20 de Octubre 2026", dia="20",
              title="Seceon: SOC autónomo con AI-SIEM y XDR para equipos reducidos")
SAFETICA = dict(tipo="web", seed=111, flip=True, wordmark="<b>SAFETICA</b>", fecha="27 de Octubre 2026", dia="27", title="")

JOBS = [("01_risk-reduction-lanzamiento", tall(m.t_overview())),
        ("02_reel-segura", m.t_reel("identidades")),
        ("03_webinar-seceon", tall(b2.t_webinar(SECEON))),
        ("04_kriptos", tall(m.t_post("datos"))),
        ("05_reel-faronics", m.t_reel("endpoints")),
        ("06_webinar-safetica", tall(b2.t_webinar(SAFETICA))),
        ("07_vicarius", tall(m.t_post("vulnerabilidades"))),
        ("08_reel-aikido", m.t_reel("aplicaciones"))]

with sync_playwright() as pw:
    br = pw.chromium.launch(executable_path="/opt/pw-browsers/chromium", args=["--no-sandbox"])
    for name, html in JOBS:
        pg = br.new_page(viewport={"width": 1080, "height": 1920})
        pg.set_content(html, timeout=120000); pg.wait_for_timeout(500)
        pg.screenshot(path=str(OUT / f"{name}_1080x1920.png")); pg.close()
    br.close()
print("ok", len(JOBS))
