#!/usr/bin/env python3
"""Banner de correo: Stellar Cyber, Visionario en el primer Magic Quadrant de Gartner para ISOC.
Salida: ../correo-stellar-gartner/stellar-gartner_{1200x628,1080x1350}.png"""
from playwright.sync_api import sync_playwright
import build as b1
import build_v2 as b2
import build_rr3 as r3

OUT = b1.ROOT / "correo-stellar-gartner"; OUT.mkdir(exist_ok=True)
NAVY, AZURE, SKY, CYAN = b2.NAVY, b2.AZURE, b2.SKY, b2.CYAN
js = b1.BG_JS.replace("SEED", "52").replace("FX", ".72").replace("FY", ".45").replace("FLIP", "false")

# Mini Magic Quadrant: 4 cuadrantes, Visionarios resaltado
def mq(s):
    h = s / 2
    return f"""<svg width='{s}' height='{s}' viewBox='0 0 200 200' style='filter:drop-shadow(0 12px 30px rgba(0,10,30,.5))'>
<rect x='4' y='4' width='192' height='192' rx='18' fill='rgba(32,60,113,.55)' stroke='rgba(132,186,225,.45)' stroke-width='2'/>
<rect x='8' y='8' width='90' height='90' rx='14' fill='rgba(78,214,255,.22)' stroke='{CYAN}' stroke-width='2.5'/>
<path d='M100 8v184M8 100h184' stroke='rgba(132,186,225,.5)' stroke-width='1.5'/>
<text x='53' y='34' text-anchor='middle' font-size='10' font-weight='800' fill='{CYAN}' letter-spacing='1'>VISIONARIOS</text>
<text x='147' y='34' text-anchor='middle' font-size='9' font-weight='600' fill='#9fb8d8' letter-spacing='1'>LÍDERES</text>
<text x='53' y='186' text-anchor='middle' font-size='9' font-weight='600' fill='#9fb8d8' letter-spacing='1'>NICHO</text>
<text x='147' y='186' text-anchor='middle' font-size='9' font-weight='600' fill='#9fb8d8' letter-spacing='1'>CAPACES</text>
<circle cx='53' cy='62' r='20' fill='{CYAN}' opacity='.25'/><circle cx='53' cy='62' r='11' fill='{CYAN}'/>
<path d='M53 55v14M46 62h14' stroke='{NAVY}' stroke-width='2.5' stroke-linecap='round'/>
<circle cx='140' cy='70' r='4' fill='#6f8fbc'/><circle cx='160' cy='128' r='4' fill='#6f8fbc'/><circle cx='80' cy='140' r='4' fill='#6f8fbc'/><circle cx='125' cy='150' r='4' fill='#6f8fbc'/>
</svg>"""

def make(W, H, portrait):
    cx, cy, r1, r2 = (700, 1050, 360, 430) if portrait else (900, 330, 250, 300)
    ring = b2.arc(cx, cy, r1, r1, -28, 16, .72, .0, (CYAN, AZURE), 1, 'a1') + b2.arc(cx, cy, r2, r2, 20, 6, .35, .2, (SKY, AZURE), .7, 'a2')
    if portrait:
        P = dict(lx=80, ly=70, lw=210, kx=80, ky=250, kf=26, chx=80, chy=298, chh=70, tx=80, ty=410, tw=920, tf=70,
                 sx=80, sy=670, sf=30, sw=900, mq=360, mqx=100, mqy=830, bx=80, by=1235, bw=560, bh=96, bf=34,
                 mh=470, mx=590, my=800, grad="180deg,rgba(3,18,38,.93) 0%,rgba(3,18,38,.76) 55%,rgba(3,18,38,.35) 100%")
    else:
        P = dict(lx=56, ly=28, lw=140, kx=56, ky=140, kf=18, chx=56, chy=176, chh=50, tx=56, ty=250, tw=700, tf=40,
                 sx=56, sy=398, sf=21, sw=650, mq=250, mqx=900, mqy=60, bx=56, by=530, bw=420, bh=62, bf=25,
                 mh=0, mx=0, my=0, grad="90deg,rgba(3,18,38,.94) 0%,rgba(3,18,38,.82) 60%,rgba(3,18,38,.35) 100%")
    chip = (f"<div class='abs' style='left:{P['chx']}px;top:{P['chy']}px;height:{P['chh']}px;padding:0 {P['chh']*0.45:.0f}px;border-radius:{P['chh']*0.3:.0f}px;"
            f"background:linear-gradient(135deg,{b2.ROYAL},{NAVY});display:flex;align-items:center;gap:{P['chh']*0.2:.0f}px;box-shadow:0 12px 32px rgba(0,10,30,.4);border:2px solid rgba(132,186,225,.35)'>"
            f"<svg width='{P['chh']*0.6:.0f}' height='{P['chh']*0.6:.0f}' viewBox='0 0 24 24' fill='none' stroke='{CYAN}' stroke-width='2' stroke-linecap='round'><path d='m12 2 2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4z'/></svg>"
            f"<span style='font-size:{P['chh']*0.44:.0f}px;font-weight:800;letter-spacing:.5px'>Stellar&nbsp;Cyber</span></div>")
    title = f"Gartner reconoce a <span style='color:{CYAN}'>Stellar Cyber</span> como Visionario en ISOC"
    sub = ("Primer Magic Quadrant para Integrated Security Operations Center (ISOC) Systems: "
           "una oportunidad para abrir nuevas conversaciones y comenzar 2027 con ventaja.")
    btn = (f"<div class='abs' style='left:{P['bx']}px;top:{P['by']}px;width:{P['bw']}px;height:{P['bh']}px;border-radius:{P['bh']//2}px;background:linear-gradient(135deg,#2a5aa8,#3f86d0);"
           f"display:flex;align-items:center;justify-content:center;font-size:{P['bf']}px;font-weight:800;box-shadow:0 12px 34px rgba(32,60,113,.5)'>Conversemos de negocio →</div>")
    mascot = r3.mascot(P['mx'], P['my'], P['mh'], dark=True) if portrait else ""
    pill = ""
    return f"""<html><head><meta charset='utf-8'><style>{b1.FONTS}
*{{box-sizing:border-box;margin:0;padding:0}}
body{{width:{W}px;height:{H}px;overflow:hidden;background:{NAVY};font-family:'Montserrat',sans-serif;color:#fff}}
.abs{{position:absolute}}
</style></head><body>
<canvas id='bg' class='abs' width='{W}' height='{H}' style='left:0;top:0'></canvas>
<div class='abs' style='inset:0;background:linear-gradient({P['grad']})'></div>
<div class='abs' style='inset:0;opacity:.07;background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);background-size:60px 60px'></div>
<svg class='abs' style='left:0;top:0' width='{W}' height='{H}' viewBox='0 0 {W} {H}'>{ring}</svg>
<img class='abs' src='{b1.LOGO}' style='left:{P['lx']}px;top:{P['ly']}px;width:{P['lw']}px'>
<div class='abs' style='left:{P['kx']}px;top:{P['ky']}px;font-size:{P['kf']}px;font-weight:700;letter-spacing:{P['kf']*0.28:.0f}px;color:{CYAN};text-transform:uppercase'>Gartner · Magic Quadrant ISOC</div>
{chip}
<h1 class='abs' style='left:{P['tx']}px;top:{P['ty']}px;width:{P['tw']}px;font-size:{P['tf']}px;line-height:1.1;font-weight:800;letter-spacing:-1.5px;text-shadow:0 6px 40px rgba(0,10,30,.7)'>{title}</h1>
<div class='abs' style='left:{P['sx']}px;top:{P['sy']}px;width:{P['sw']}px;font-size:{P['sf']}px;line-height:1.4;font-weight:500;color:#cfe0f5'>{sub}</div>
<div class='abs' style='left:{P['mqx']}px;top:{P['mqy']}px'>{mq(P['mq'])}</div>
{mascot}{btn}{pill}
<script>{js}</script></body></html>"""

if __name__ == '__main__':
    with sync_playwright() as pw:
        br = pw.chromium.launch(executable_path="/opt/pw-browsers/chromium", args=["--no-sandbox"])
        for name, (W, H, portrait) in {"1200x628": (1200, 628, False), "1080x1350": (1080, 1350, True)}.items():
            pg = br.new_page(viewport={"width": W, "height": H})
            pg.set_content(make(W, H, portrait), timeout=120000); pg.wait_for_timeout(800)
            pg.screenshot(path=str(OUT / f"stellar-gartner_{name}.png")); pg.close()
        br.close()
    print("ok")
