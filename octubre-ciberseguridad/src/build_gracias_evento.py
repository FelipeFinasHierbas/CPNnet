#!/usr/bin/env python3
"""Post LinkedIn 1080x1350 de agradecimiento a Manuel Díaz / Grupo Bitácora por el summit. Salida: ../posts-especiales/"""
import base64, sys
from playwright.sync_api import sync_playwright
import build as b1
import build_rr as rr
import build_rr3 as r3

ROOT = b1.ROOT; OUT = ROOT / "posts-especiales"; OUT.mkdir(exist_ok=True)
b64 = lambda p: "data:image/png;base64," + base64.b64encode((ROOT / p).read_bytes()).decode()
LG = {k: b64(f"assets/evento/{k}.png") for k in ("bitacora", "kriptos", "think")}
NAVY, CYAN, AZURE, SKY = "#0a1a35", "#6fe3f5", "#3A84BD", "#84BAE1"
flags = "".join(rr.FLAG[k].replace("<svg ", "<svg width='34' height='34' ") for k in ("cl", "pe", "us", "co"))

def make(evento):
    js = b1.BG_JS.replace("SEED", "52").replace("FX", ".78").replace("FY", ".4").replace("FLIP", "false")
    card = lambda k, w, h: f"<div style='width:{w}px;height:{h}px;border-radius:26px;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 14px 34px rgba(0,0,0,.35)'><img src='{LG[k]}' style='max-width:{w-44}px;max-height:{h-34}px;mix-blend-mode:multiply'></div>"
    return f"""<html><head><meta charset='utf-8'><style>{b1.FONTS}
*{{box-sizing:border-box;margin:0;padding:0}}
body{{width:1080px;height:1350px;overflow:hidden;background:{NAVY};font-family:'Montserrat',sans-serif;color:#fff}}
.abs{{position:absolute}}
</style></head><body>
<canvas id='bg' class='abs' width='1080' height='1350' style='left:0;top:0'></canvas>
<div class='abs' style='inset:0;background:linear-gradient(180deg,rgba(6,18,40,.9) 0%,rgba(6,18,40,.72) 55%,rgba(6,18,40,.5) 100%)'></div>
<div class='abs' style='inset:0;opacity:.06;background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);background-size:60px 60px'></div>
<img class='abs' src='{b1.LOGO}' style='left:76px;top:64px;width:200px'>
<div class='abs' style='right:76px;top:82px;display:flex;gap:8px'>{flags}</div>
<div class='abs' style='left:76px;top:262px;font-size:24px;font-weight:700;letter-spacing:6px;color:{CYAN};text-transform:uppercase'>{evento}</div>
<h1 class='abs' style='left:76px;top:312px;font-size:112px;line-height:1;font-weight:800;letter-spacing:-3px'>¡Gracias,<br><span style='color:{CYAN}'>Manuel Díaz!</span></h1>
<div class='abs' style='left:76px;top:568px;width:900px;font-size:29px;font-weight:600;line-height:1.3'>Director Ejecutivo de Revista Bitácora · Grupo Bitácora</div>
<div class='abs' style='left:76px;top:640px;width:620px;font-size:30px;font-weight:500;line-height:1.35;opacity:.95'>Por invitarnos a conversar sobre protección de datos, confianza digital y los desafíos del Open Banking.</div>
<div class='abs' style='left:76px;top:840px;font-size:18px;font-weight:700;letter-spacing:4px;color:{SKY};text-transform:uppercase'>Fue un gusto participar junto a</div>
<div class='abs' style='left:76px;top:884px;display:flex;gap:18px'>{card('kriptos',250,124)}{card('think',200,124)}</div>
<div class='abs' style='left:76px;top:1040px;width:620px;padding:22px 30px;border-radius:28px;background:linear-gradient(135deg,#2a5aa8,#3f86d0);font-size:29px;font-weight:700;line-height:1.28;box-shadow:0 14px 36px rgba(0,0,0,.35)'>Conocer, clasificar y proteger mejor su información.</div>
{r3.mascot(700, 650, 560, dark=True)}
<div class='abs' style='left:76px;top:1262px;border-radius:999px;background:rgba(255,255,255,.14);border:2px solid {CYAN}88;padding:12px 32px;font-size:26px;font-weight:700'>#CPNnetSecurity</div>
<div class='abs' style='right:76px;top:1252px;width:300px;height:78px;border-radius:22px;background:#fff;display:flex;align-items:center;justify-content:center'><img src='{LG["bitacora"]}' style='max-height:62px;max-width:270px;mix-blend-mode:multiply'></div>
<script>{js}</script></body></html>"""

if __name__ == "__main__":
    with sync_playwright() as pw:
        br = pw.chromium.launch(executable_path="/opt/pw-browsers/chromium", args=["--no-sandbox"])
        for name, ev in (("data-trust-summit", "Data & Trust Summit 2026"), ("governance-resilience-summit", "Governance & Resilience Summit 2026")):
            pg = br.new_page(viewport={"width": 1080, "height": 1350}); pg.set_content(make(ev), timeout=120000); pg.wait_for_timeout(700)
            pg.screenshot(path=str(OUT / f"linkedin-gracias-bitacora_{name}_1080x1350.png")); pg.close()
        br.close()
    print("ok")
