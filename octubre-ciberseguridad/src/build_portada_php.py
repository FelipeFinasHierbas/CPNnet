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
TITLE = ("Alerta de seguridad: <span style='color:%s'>vulnerabilidades críticas</span> en <span style='color:%s'>PHP</span> y acciones recomendadas" % (AMBER, CYAN))

LAYOUTS = {
 "1920x1080": dict(W=1920, H=1080, grad="90deg,rgba(3,18,38,.92) 0%,rgba(3,18,38,.78) 38%,rgba(3,18,38,.15) 70%,rgba(3,18,38,.35) 100%",
     arc=(1480, 540, 470, 540), logo=(100, 76, 250), flags=(100, 92, 54), chip=(100, 300, 70, 28), title=(100, 400, 1060, 88),
     mascot=(1150, 110, 900), pill=(100, 80, 30)),
 "1080x1350": dict(W=1080, H=1350, grad="180deg,rgba(3,18,38,.93) 0%,rgba(3,18,38,.74) 50%,rgba(3,18,38,.25) 100%",
     arc=(770, 960, 330, 390), logo=(80, 70, 210), flags=(80, 90, 40), chip=(80, 250, 62, 24), title=(80, 340, 920, 70),
     mascot=(480, 700, 600), pill=(80, 62, 28)),
 "1080x1920": dict(W=1080, H=1920, grad="180deg,rgba(3,18,38,.93) 0%,rgba(3,18,38,.76) 52%,rgba(3,18,38,.25) 100%",
     arc=(700, 1380, 380, 450), logo=(80, 110, 220), flags=(80, 135, 40), chip=(80, 440, 66, 26), title=(80, 540, 920, 80),
     mascot=(390, 995, 850), pill=(80, 74, 28)),
}


def make(name, L):
    W, H = L["W"], L["H"]
    cx, cy, r1, r2 = L["arc"]
    ring = b2.arc(cx, cy, r1, r1, -28, 16, .72, .0, (CYAN, AZURE), 1, 'a1')
    ring2 = b2.arc(cx, cy, r2, r2, 20, 6, .35, .2, (SKY, AZURE), .7, 'a2')
    fl = "".join(rr.FLAG[c].replace("<svg ", "<svg width='%d' height='%d' " % (L["flags"][2], L["flags"][2])) for c in ("cl", "pe", "us", "co"))
    lx, ly, lw = L["logo"]; fx_, fy_, _ = L["flags"]; chx, chy, chs, chf = L["chip"]; tx, ty, tw, tf = L["title"]
    mx, my, mh = L["mascot"]; px, pb, pf = L["pill"]
    js2 = js
    flags_right = f"right:{fx_}px;top:{fy_}px" if name != "1920x1080" else f"right:100px;top:92px"
    return f"""<html><head><meta charset='utf-8'><style>{b1.FONTS}
*{{box-sizing:border-box;margin:0;padding:0}}
body{{width:{W}px;height:{H}px;overflow:hidden;background:{NAVY};font-family:'Montserrat',sans-serif;color:#fff}}
.abs{{position:absolute}}
</style></head><body>
<canvas id='bg' class='abs' width='{W}' height='{H}' style='left:0;top:0'></canvas>
<div class='abs' style='inset:0;background:linear-gradient({L["grad"]})'></div>
<div class='abs' style='inset:0;opacity:.07;background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);background-size:60px 60px'></div>
<svg class='abs' style='left:0;top:0' width='{W}' height='{H}' viewBox='0 0 {W} {H}'>{ring}{ring2}</svg>
<img class='abs' src='{b1.LOGO}' style='left:{lx}px;top:{ly}px;width:{lw}px'>
<div class='abs' style='{flags_right};display:flex;gap:10px'>{fl}</div>
<div class='abs' style='left:{chx}px;top:{chy}px;display:flex;align-items:center;gap:18px'>
  <div style='width:{chs}px;height:{chs}px;border-radius:50%;background:{AMBER};display:flex;align-items:center;justify-content:center;box-shadow:0 0 40px rgba(255,180,84,.65)'>{warn}</div>
  <div style='font-size:{chf}px;font-weight:700;letter-spacing:7px;color:{AMBER};text-transform:uppercase'>CPNnet Security</div></div>
<h1 class='abs' style='left:{tx}px;top:{ty}px;width:{tw}px;font-size:{tf}px;line-height:1.06;font-weight:800;letter-spacing:-2px;text-shadow:0 6px 40px rgba(0,10,30,.7)'>{TITLE}</h1>
{r3.mascot(mx, my, mh, dark=True)}
<div class='abs' style='left:{px}px;bottom:{pb}px;border-radius:999px;background:#2f86ff;padding:12px 34px;font-size:{pf}px;font-weight:600'>#CPNnetSecurity</div>
<script>{js2}</script></body></html>"""


with sync_playwright() as pw:
    br = pw.chromium.launch(executable_path="/opt/pw-browsers/chromium", args=["--no-sandbox"])
    for name, L in LAYOUTS.items():
        pg = br.new_page(viewport={"width": L["W"], "height": L["H"]})
        pg.set_content(make(name, L), timeout=120000); pg.wait_for_timeout(800)
        pg.screenshot(path=str(OUT / f"portada_alerta-php_{name}.png")); pg.close()
    br.close()
print("ok")
