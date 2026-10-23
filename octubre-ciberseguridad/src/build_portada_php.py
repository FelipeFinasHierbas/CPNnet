#!/usr/bin/env python3
"""Portada 1920x1080 'Alerta de seguridad: vulnerabilidades críticas en PHP' con la mascota CPNnet.
Salida: ../portadas/portada_alerta-php_1920x1080.png"""
import base64, io
from playwright.sync_api import sync_playwright
from PIL import Image
import build as b1
import build_v2 as b2
import build_rr as rr
import build_rr3 as r3

OUT = b1.ROOT / "portadas"; OUT.mkdir(exist_ok=True)
NAVY, AZURE, SKY, CYAN, AMBER = b2.NAVY, b2.AZURE, b2.SKY, b2.CYAN, "#ffb454"
flags = "".join(rr.FLAG[c].replace("<svg ", "<svg width='54' height='54' ") for c in ("cl", "pe", "us", "co"))
ring = b2.arc(1480, 540, 470, 470, -28, 16, .72, .0, (CYAN, AZURE), 1, 'a1')
ring2 = b2.arc(1480, 540, 540, 540, 20, 6, .35, .2, (SKY, AZURE), .7, 'a2')
warn = ("<svg width='46' height='46' viewBox='0 0 24 24'><path d='M12 3 2.5 20h19L12 3z' fill='none' stroke='#0a1a35' stroke-width='2.2' stroke-linejoin='round'/>"
        "<path d='M12 10v4.5' stroke='#0a1a35' stroke-width='2.4' stroke-linecap='round'/><circle cx='12' cy='17.3' r='1.3' fill='#0a1a35'/></svg>")
js = b1.BG_JS.replace("SEED", "33").replace("FX", ".75").replace("FY", ".45").replace("FLIP", "false")
html = f"""<html><head><meta charset='utf-8'><style>{b1.FONTS}
*{{box-sizing:border-box;margin:0;padding:0}}
body{{width:1920px;height:1080px;overflow:hidden;background:{NAVY};font-family:'Montserrat',sans-serif;color:#fff}}
.abs{{position:absolute}}
</style></head><body>
<canvas id='bg' class='abs' width='1920' height='1080' style='left:0;top:0'></canvas>
<div class='abs' style='inset:0;background:linear-gradient(90deg,rgba(3,18,38,.92) 0%,rgba(3,18,38,.78) 38%,rgba(3,18,38,.15) 70%,rgba(3,18,38,.35) 100%)'></div>
<div class='abs' style='inset:0;opacity:.07;background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);background-size:60px 60px'></div>
<svg class='abs' style='left:0;top:0' width='1920' height='1080' viewBox='0 0 1920 1080'>{ring}{ring2}</svg>
<img class='abs' src='{b1.LOGO}' style='left:100px;top:76px;width:250px'>
<div class='abs' style='right:100px;top:92px;display:flex;gap:12px'>{flags}</div>
<div class='abs' style='left:100px;top:300px;display:flex;align-items:center;gap:20px'>
  <div style='width:70px;height:70px;border-radius:50%;background:{AMBER};display:flex;align-items:center;justify-content:center;box-shadow:0 0 40px rgba(255,180,84,.65)'>{warn}</div>
  <div style='font-size:28px;font-weight:700;letter-spacing:8px;color:{AMBER};text-transform:uppercase'>CPNnet Security</div></div>
<h1 class='abs' style='left:100px;top:400px;width:1060px;font-size:88px;line-height:1.06;font-weight:800;letter-spacing:-2px;text-shadow:0 6px 40px rgba(0,10,30,.7)'>
  Alerta de seguridad: <span style='color:{AMBER}'>vulnerabilidades críticas</span> en <span style='color:{CYAN}'>PHP</span> y acciones recomendadas</h1>
{r3.mascot(1150, 110, 900, dark=True)}
<div class='abs' style='left:100px;bottom:80px;border-radius:999px;background:#2f86ff;padding:14px 38px;font-size:30px;font-weight:600'>#CPNnetSecurity</div>
<script>{js}</script></body></html>"""
with sync_playwright() as pw:
    br = pw.chromium.launch(executable_path="/opt/pw-browsers/chromium", args=["--no-sandbox"])
    pg = br.new_page(viewport={"width": 1920, "height": 1080})
    pg.set_content(html, timeout=120000); pg.wait_for_timeout(800)
    pg.screenshot(path=str(OUT / "portada_alerta-php_1920x1080.png")); br.close()
print("ok")
