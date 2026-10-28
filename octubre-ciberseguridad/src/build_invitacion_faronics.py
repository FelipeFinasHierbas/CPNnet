#!/usr/bin/env python3
"""Invitación exclusiva Faronics Cloud (5 nov, 11:30 hrs Chile) con la mascota CPNnet.
Salida: ../invitacion-faronics/invitacion-faronics_{1080x1350,1080x1920,1200x628}.png"""
from playwright.sync_api import sync_playwright
import build as b1
import build_v2 as b2
import build_rr as rr
import build_rr3 as r3
import build_rr2 as m

OUT = b1.ROOT / "invitacion-faronics"; OUT.mkdir(exist_ok=True)
NAVY, AZURE, SKY, CYAN = b2.NAVY, b2.AZURE, b2.SKY, b2.CYAN
js = b1.BG_JS.replace("SEED", "47").replace("FX", ".72").replace("FY", ".45").replace("FLIP", "false")
cal = ("<svg width='{s}' height='{s}' viewBox='0 0 24 24' fill='none' stroke='#fff' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'>"
       "<rect x='3' y='5' width='18' height='16' rx='3'/><path d='M3 10h18M8 3v4M16 3v4'/><path d='m9 15.5 2 2 4-4'/></svg>")
clk = ("<svg width='{s}' height='{s}' viewBox='0 0 24 24' fill='none' stroke='#fff' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'>"
       "<circle cx='12' cy='13' r='8'/><path d='M12 9v4.5l3 2M5 4 3 6M19 4l2 2'/></svg>")

L = {
 "1080x1350": dict(W=1080, H=1350, grad="180deg,rgba(3,18,38,.93) 0%,rgba(3,18,38,.74) 55%,rgba(3,18,38,.3) 100%", arc=(780, 1010, 320, 380),
     logo=(80, 70, 210), flags=(80, 90, 40), label=(80, 250, 26), chip=(80, 300, 88), title=(80, 430, 920, 62),
     card=(80, 745, 500, 24, 56), btn=(80, 985, 500, 100, 40), mascot=(580, 740, 540), pill=(80, 62, 28), flagsize=40),
 "1080x1920": dict(W=1080, H=1920, grad="180deg,rgba(3,18,38,.93) 0%,rgba(3,18,38,.76) 55%,rgba(3,18,38,.28) 100%", arc=(720, 1400, 380, 450),
     logo=(80, 110, 220), flags=(80, 135, 40), label=(80, 470, 28), chip=(80, 530, 100), title=(80, 680, 920, 72),
     card=(80, 1060, 520, 26, 60), btn=(80, 1330, 520, 108, 42), mascot=(520, 1050, 760), pill=(80, 74, 28), flagsize=40),
 "1200x628": dict(W=1200, H=628, grad="90deg,rgba(3,18,38,.94) 0%,rgba(3,18,38,.8) 55%,rgba(3,18,38,.2) 100%", arc=(960, 330, 250, 300),
     logo=(56, 28, 140), flags=(56, 40, 30), label=(56, 158, 20), chip=(56, 168, 54), title=(56, 225, 730, 44),
     card=(56, 405, 690, 20, 40), btn=(56, 525, 330, 62, 28), mascot=(860, 36, 570), pill=(0, 0, 0), flagsize=30),
}
TITLE = "<span style='color:%s'>Faronics Cloud</span>: más control y protección para los endpoints" % CYAN


def make(name, c):
    W, H = c["W"], c["H"]; cx, cy, r1, r2 = c["arc"]
    ring = b2.arc(cx, cy, r1, r1, -28, 16, .72, .0, (CYAN, AZURE), 1, 'a1'); ring2 = b2.arc(cx, cy, r2, r2, 20, 6, .35, .2, (SKY, AZURE), .7, 'a2')
    fs = c["flagsize"]
    fl = "".join(rr.FLAG[k].replace("<svg ", "<svg width='%d' height='%d' " % (fs, fs)) for k in ("cl", "pe", "us", "co"))
    lx, ly, lw = c["logo"]; fx, fy, _ = c["flags"]; ax, ay, af = c["label"]; hx, hy, hh = c["chip"]; tx, ty, tw, tf = c["title"]
    kx, ky, kw, kf, ks = c["card"]; bx, by, bw, bh, bf = c["btn"]; mx, my, mh = c["mascot"]; px, pb, pf = c["pill"]
    small = name == "1200x628"
    flags_html = "" if small else f"<div class='abs' style='right:{fx}px;top:{fy}px;display:flex;gap:10px'>{fl}</div>"
    pill = "" if small else f"<div class='abs' style='left:{px}px;bottom:{pb}px;border-radius:999px;background:#2f86ff;padding:12px 34px;font-size:{pf}px;font-weight:600'>#CPNnetPartners</div>"
    chip_html = m.logo_chip("faronics", hx, hy, int(hh * 3.4), hh, int(hh * 0.5))
    row = lambda icon, txt: (f"<div style='display:flex;align-items:center;gap:{ks*0.4:.0f}px'>{icon.format(s=ks)}<div style='font-size:{kf*1.35:.0f}px;font-weight:700;white-space:nowrap'>{txt}</div></div>")
    card = (f"<div class='abs' style='left:{kx}px;top:{ky}px;width:{kw}px;padding:{kf*0.9:.0f}px {kf*1.3:.0f}px;border-radius:{kf*1.2:.0f}px;background:rgba(32,60,113,.78);border:2px solid rgba(132,186,225,.4);"
            f"box-shadow:0 20px 50px rgba(0,10,30,.5);display:flex;flex-direction:{'row' if small else 'column'};gap:{kf*0.9:.0f}px;{'width:'+str(kw)+'px;' if small else ''}'>"
            + row(cal, "5 de noviembre") + row(clk, "11:30 hrs Chile") + "</div>")
    btn = (f"<div class='abs' style='left:{bx}px;top:{by}px;width:{bw}px;height:{bh}px;border-radius:{bh//2}px;background:linear-gradient(135deg,#2a5aa8,#3f86d0);"
           f"display:flex;align-items:center;justify-content:center;font-size:{bf}px;font-weight:800;box-shadow:0 12px 34px rgba(32,60,113,.5)'>Reserva tu lugar</div>")
    return f"""<html><head><meta charset='utf-8'><style>{b1.FONTS}
*{{box-sizing:border-box;margin:0;padding:0}}
body{{width:{W}px;height:{H}px;overflow:hidden;background:{NAVY};font-family:'Montserrat',sans-serif;color:#fff}}
.abs{{position:absolute}}
.chip{{background:linear-gradient(135deg,{b2.ROYAL},{NAVY});border-radius:26px;display:flex;align-items:center;justify-content:center;box-shadow:0 12px 32px rgba(0,10,30,.4)}}
</style></head><body>
<canvas id='bg' class='abs' width='{W}' height='{H}' style='left:0;top:0'></canvas>
<div class='abs' style='inset:0;background:linear-gradient({c["grad"]})'></div>
<div class='abs' style='inset:0;opacity:.07;background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);background-size:60px 60px'></div>
<svg class='abs' style='left:0;top:0' width='{W}' height='{H}' viewBox='0 0 {W} {H}'>{ring}{ring2}</svg>
<img class='abs' src='{b1.LOGO}' style='left:{lx}px;top:{ly}px;width:{lw}px'>
{flags_html}
<div class='abs' style='left:{ax}px;top:{ay}px;font-size:{af}px;font-weight:700;letter-spacing:{af*0.28:.0f}px;color:{CYAN};text-transform:uppercase'>Invitación exclusiva</div>
{chip_html if not small else ''}
<h1 class='abs' style='left:{tx}px;top:{ty}px;width:{tw}px;font-size:{tf}px;line-height:1.08;font-weight:800;letter-spacing:-1.5px;text-shadow:0 6px 40px rgba(0,10,30,.7)'>{TITLE}</h1>
{r3.mascot(mx, my, mh, dark=True)}
{card}{btn}{pill}
<script>{js}</script></body></html>"""


with sync_playwright() as pw:
    br = pw.chromium.launch(executable_path="/opt/pw-browsers/chromium", args=["--no-sandbox"])
    for name, c in L.items():
        pg = br.new_page(viewport={"width": c["W"], "height": c["H"]})
        pg.set_content(make(name, c), timeout=120000); pg.wait_for_timeout(800)
        pg.screenshot(path=str(OUT / f"invitacion-faronics_{name}.png")); pg.close()
    br.close()
print("ok")
