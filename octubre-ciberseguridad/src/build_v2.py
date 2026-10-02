#!/usr/bin/env python3
"""Enfoque 2 · "Portal + arcos" · Mes de la Ciberseguridad · CPNnet Security.

Mismo ADN de la pieza oficial (logo, Montserrat, cian/navy, foto tech, hashtag, banderas) con otra
composición: el arco del logo es el motivo gráfico. Tres familias:
  Lunes  (webinar)  -> foto tech dentro de un portal circular + fecha gigante, fondo navy
  Miércoles (dato)  -> fondo claro, tipografía navy gigante, gráfico de barras en multas
  Viernes (reel)    -> corte diagonal con foto arriba, pasos numerados abajo
Uso: python3 build_v2.py -> ../v2-enfoque-portal/{posts,reels,grilla-octubre.png}
"""
import base64, pathlib
from playwright.sync_api import sync_playwright
from PIL import Image
import build as b1

ROOT = b1.ROOT
OUT = ROOT / "v2-enfoque-portal"
(OUT / "posts").mkdir(parents=True, exist_ok=True); (OUT / "reels").mkdir(parents=True, exist_ok=True)
LOGO_COLOR = "data:image/png;base64," + base64.b64encode((ROOT / "assets/logo.png").read_bytes()).decode()
NAVY, ROYAL, AZURE, SKY, ICE, CYAN = "#1A2E52", "#203C71", "#3A84BD", "#84BAE1", "#EAF2FA", "#6fe3f5"

CSS = b1.FONTS + f"""
*{{box-sizing:border-box;margin:0;padding:0}}
body{{font-family:'Montserrat',sans-serif;width:1080px;overflow:hidden;background:{NAVY}}}
.cv{{position:relative;width:1080px;overflow:hidden}}
.dk{{background:radial-gradient(900px 700px at 80% 20%,#27508f 0%,transparent 60%),linear-gradient(165deg,#0d2347,{NAVY} 60%,#0a1a35);color:#fff}}
.lt{{background:radial-gradient(800px 600px at 0% 100%,#cfe3f5 0%,transparent 60%),linear-gradient(180deg,#f6fafe,{ICE});color:{NAVY}}}
.grid{{position:absolute;inset:0;opacity:.07;background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);background-size:54px 54px}}
.lt .grid{{opacity:.55;background-image:linear-gradient(#d5e5f4 1px,transparent 1px),linear-gradient(90deg,#d5e5f4 1px,transparent 1px)}}
svg.arcs{{position:absolute;inset:0}}
.logo{{position:absolute;left:76px;top:80px;width:176px}}
.abs{{position:absolute}}
.pill{{position:absolute;left:55px;border-radius:999px;padding:12px 34px;font-size:27px;font-weight:600}}
.dk .pill{{background:#2f86ff;color:#fff}} .lt .pill{{background:{NAVY};color:#fff}}
.flags{{position:absolute;right:68px;display:flex;gap:10px}}.flags svg{{width:46px;height:46px}}
.lab{{font-size:24px;font-weight:700;letter-spacing:5px;text-transform:uppercase}}
.dk .lab{{color:{CYAN}}} .lt .lab{{color:{AZURE}}}
.portal{{position:absolute;border-radius:50%;overflow:hidden;box-shadow:0 0 90px rgba(80,210,240,.45),inset 0 0 0 4px rgba(255,255,255,.35)}}
.portal canvas{{position:absolute;inset:0}}
.info{{display:flex;align-items:center;gap:60px}}.info div{{display:flex;gap:20px;align-items:center;font-size:26px;font-weight:500;line-height:1.3}}.info .d{{font-weight:700;font-size:30px}}
.card{{background:#fff;border-radius:36px;box-shadow:0 16px 44px rgba(20,50,100,.16)}}
.glow{{text-shadow:0 0 40px rgba(111,227,245,.75),0 0 110px rgba(58,132,189,.7)}}
.step{{display:flex;align-items:center;gap:26px;font-size:35px;font-weight:700;line-height:1.2;margin:26px 0}}
.step b{{flex:none;width:76px;height:76px;border-radius:50%;background:linear-gradient(135deg,{SKY},{AZURE});display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:800;box-shadow:0 0 28px rgba(111,227,245,.55)}}
.b1 b{{font-weight:800}}
.src{{font-size:19px;font-weight:500;opacity:.65}}
"""

def arc(cx, cy, rx, ry, rot, sw, frac, off, stops=(SKY, AZURE), opacity=1, gid="g"):
    import math
    c = math.pi * (3 * (rx + ry) - math.sqrt((3 * rx + ry) * (rx + 3 * ry)))
    return (f"<defs><linearGradient id='{gid}' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='{stops[0]}'/><stop offset='1' stop-color='{stops[1]}'/></linearGradient></defs>"
            f"<ellipse cx='{cx}' cy='{cy}' rx='{rx}' ry='{ry}' transform='rotate({rot} {cx} {cy})' fill='none' stroke='url(#{gid})' stroke-width='{sw}' "
            f"stroke-linecap='round' stroke-dasharray='{c*frac:.0f} {c:.0f}' stroke-dashoffset='{-c*off:.0f}' opacity='{opacity}'/>")

def page(h, theme, body, canv=None, seed=1, fx=.6, fy=.4, flip=False, logo=None):
    js = ""
    if canv:  # canvas 'bg' (fondo tech) con el mismo generador de la v1
        js = "<script>" + b1.BG_JS.replace("SEED", str(seed)).replace("FX", str(fx)).replace("FY", str(fy)).replace("FLIP", "true" if flip else "false") + "</script>"
    lg = logo or (b1.LOGO if theme == "dk" else LOGO_COLOR)
    return (f"<html><head><meta charset='utf-8'><style>{CSS}</style></head><body><div class='cv {theme}' style='height:{h}px'>"
            f"<div class='grid'></div>{body}<img class='logo' src='{lg}'></div>{js}</body></html>")

def foot(y, tag): return f"<div class='pill' style='top:{y}px'>{tag}</div><div class='abs' style='right:0;top:{y+2}px'>{b1.FLAGS}</div>"

# ---------------------------------------------------------------- webinar (portal)
def t_webinar(p):
    portal_canvas = "<canvas id='bg' width='680' height='680'></canvas>"
    ring = arc(700, 440, 392, 372, -28, 22, .62, .08, gid="a1") + arc(700, 440, 428, 408, -28, 5, .34, .55, (CYAN, SKY), .8, gid="a2")
    return page(1350, "dk", f"""
<svg class='arcs' width='1080' height='1350' viewBox='0 0 1080 1350'>{ring}</svg>
<div class='portal' style='left:360px;top:100px;width:680px;height:680px'>{portal_canvas}
  <div class='abs' style='inset:0;background:radial-gradient(circle,rgba(3,22,43,.0) 25%,rgba(3,22,43,.55) 100%)'></div>
  <div class='abs glow' style='inset:0;text-align:center;color:#fff'>
    <div style='font-size:34px;font-weight:700;letter-spacing:12px;margin-top:130px;padding-left:12px'>OCT</div>
    <div style='font-size:330px;font-weight:800;line-height:.9;letter-spacing:-12px'>{p['dia']}</div>
    <div style='font-size:36px;font-weight:700;letter-spacing:12px;padding-left:12px'>MARTES</div></div></div>
<div class='abs' style='left:76px;top:330px;width:270px'>
  <div class='lab'>Webinar</div>
  <div style='font-size:21px;font-weight:600;line-height:1.35;margin-top:10px'>Mes de la<br>Ciberseguridad</div>
  <div style='width:70px;height:5px;border-radius:3px;background:{CYAN};margin-top:22px'></div></div>
<div class='abs b1' style='left:76px;top:812px;right:76px'>
  <div style='font-size:60px;line-height:1;font-weight:300;letter-spacing:1px'>{p['wordmark']}</div>
  <h1 style='font-size:48px;line-height:1.16;font-weight:700;margin-top:24px'>{p['title']}</h1></div>
<div class='info abs' style='left:76px;top:1112px'><div class='d'>{b1.CAL}{p['fecha']}</div><div>{b1.CLK}<span>{b1.HORA}</span></div></div>
{foot(1245, '#webinarscpnnet')}""", canv=True, seed=p["seed"], fx=.55, fy=.5, flip=p.get("flip", False))

# ---------------------------------------------------------------- dato / ley (claro)
def t_stat(p):
    rings = arc(960, 760, 250, 230, -28, 18, .45, .62, gid="a1", opacity=.55) + arc(960, 760, 282, 262, -28, 4, .28, .15, (AZURE, SKY), .5, gid="a2")
    return page(1350, "lt", f"""
<svg class='arcs' width='1080' height='1350' viewBox='0 0 1080 1350'>{rings}</svg>
<div class='abs lab' style='left:76px;top:300px'>{p['tag']}</div>
<div class='abs' style='left:76px;top:350px;right:120px;font-size:42px;font-weight:600;line-height:1.25'>{p['kicker']}</div>
<div class='abs' style='left:60px;top:510px;font-size:{p.get("bigsize",330)}px;font-weight:800;line-height:.95;letter-spacing:-12px;color:{NAVY}'>{p['big']}</div>
<div class='abs' style='left:76px;top:{p.get("captop",830)}px;width:{p.get("capw",620)}px;height:8px;border-radius:4px;background:linear-gradient(90deg,{AZURE},{SKY},rgba(132,186,225,0))'></div>
<div class='abs' style='left:76px;top:{p.get("captop",830)+34}px;right:90px;font-size:38px;font-weight:700;line-height:1.22'>{p['caption']}</div>
<div class='abs card' style='left:76px;right:76px;top:1000px;padding:30px 40px'><div style='font-size:27px;font-weight:500;line-height:1.38'>{p['takeaway']}</div></div>
<div class='abs src' style='left:76px;top:1160px'>Fuente: {p['src']}</div>
{foot(1245, '#MesDeLaCiberseguridad')}""")

def t_list(p):  # gráfico de barras proporcional a UTM
    mx = max(v for v, _, _ in p["bars"])
    bars = ""
    for i, (v, lab, txt) in enumerate(p["bars"]):
        w = 160 + 560 * v / mx
        bars += f"""<div class='abs' style='left:76px;top:{505+i*140}px;right:76px'>
          <div style='font-size:26px;font-weight:600;margin-bottom:10px'>{lab}</div>
          <div style='width:{w:.0f}px;height:74px;border-radius:37px;background:linear-gradient(90deg,{SKY},{AZURE},{ROYAL});display:flex;align-items:center;justify-content:flex-end;padding-right:30px;color:#fff;font-weight:800;font-size:34px;box-shadow:0 12px 30px rgba(32,60,113,.25)'>{txt}</div></div>"""
    return page(1350, "lt", f"""
<svg class='arcs' width='1080' height='1350' viewBox='0 0 1080 1350'>{arc(1040, 30, 260, 220, -28, 18, .5, .1, gid='a1', opacity=.6)}</svg>
<div class='abs lab' style='left:76px;top:300px'>{p['tag']}</div>
<div class='abs' style='left:76px;top:345px;right:90px;font-size:50px;font-weight:800;line-height:1.14'>{p['title']}</div>{bars}
<div class='abs' style='left:76px;right:76px;top:1088px;font-size:25px;font-weight:600;line-height:1.35'>{p['foot']}</div>
<div class='abs src' style='left:76px;top:1185px'>Fuente: {p['src']}</div>
{foot(1245, '#MesDeLaCiberseguridad')}""")

def t_agenda(p):
    rows = "".join(f"""<div class='abs card' style='left:76px;right:76px;top:{470+i*160}px;height:132px;display:flex;align-items:center;gap:30px;padding:0 34px'>
      <div style='flex:none;width:110px;height:96px;border-radius:22px;background:linear-gradient(135deg,{SKY},{AZURE});color:#fff;text-align:center'>
        <div style='font-size:17px;font-weight:700;letter-spacing:3px;margin-top:10px'>OCT</div><div style='font-size:50px;font-weight:800;line-height:.95'>{d}</div></div>
      <div><div style='font-size:36px;font-weight:800'>{n}</div><div style='font-size:21px;font-weight:500;opacity:.75;margin-top:2px'>{c}</div></div></div>""" for i, (d, n, c) in enumerate(p["items"]))
    return page(1350, "lt", f"""
<svg class='arcs' width='1080' height='1350' viewBox='0 0 1080 1350'>{arc(1040, 30, 260, 220, -28, 18, .5, .1, gid='a1', opacity=.6)}</svg>
<div class='abs lab' style='left:76px;top:290px'>Octubre · Mes de la Ciberseguridad</div>
<div class='abs' style='left:76px;top:345px;font-size:68px;font-weight:800;line-height:1.05;white-space:nowrap'>4 martes. <span style='color:{AZURE}'>4 webinars.</span></div>{rows}
<div class='abs' style='left:76px;top:1130px;font-size:25px;font-weight:600;color:{AZURE}'>11:00 hrs Chile · 09:00 hrs Perú - Colombia</div>
{foot(1245, '#webinarscpnnet')}""")

# ---------------------------------------------------------------- reel (diagonal)
def t_reel(p):
    steps = "".join(f"<div class='step'><b>{i+1}</b><div>{x}</div></div>" for i, x in enumerate(p["points"]))
    ring = arc(900, 1010, 150, 150, -28, 14, .75, .0, (CYAN, AZURE), 1, 'a1')
    return page(1920, "dk", f"""
<div class='abs' style='left:0;top:0;width:1080px;height:1090px;clip-path:polygon(0 0,100% 0,100% 78%,0 100%)'><canvas id='bg' width='1080' height='1090'></canvas>
  <div class='abs' style='inset:0;background:linear-gradient(180deg,rgba(3,22,43,.55),rgba(3,22,43,.15) 55%,rgba(3,22,43,.55))'></div></div>
<svg class='arcs' width='1080' height='1920' viewBox='0 0 1080 1920'>{ring}</svg>
<div class='abs' style='left:810px;top:920px;width:180px;height:180px;display:flex;align-items:center;justify-content:center'>
  <div style='width:96px;height:96px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 0 40px rgba(111,227,245,.8)'>
   <div style='border-left:34px solid {AZURE};border-top:21px solid transparent;border-bottom:21px solid transparent;margin-left:10px'></div></div></div>
<div class='abs lab' style='left:76px;top:440px'>{p['tag']}</div>
<div class='abs glow' style='left:76px;top:490px;right:90px;font-size:88px;font-weight:800;line-height:1.05;letter-spacing:-2px'>{p['hook']}</div>
<div class='abs' style='left:76px;right:76px;top:1180px'>{steps}</div>
<div class='abs lab' style='left:76px;top:1560px;color:{SKY}'>Reel · {p['dur']}</div>
{foot(1700, '#MesDeLaCiberseguridad')}""", canv=True, seed=p["seed"], fx=.7, fy=.45, flip=p.get("flip", False))

TEMPL = dict(webinar=t_webinar, stat=t_stat, list=t_list, agenda=t_agenda, reel=t_reel)
# reutiliza textos de la v1, con la lista de multas adaptada a barras
POSTS = []
for q in b1.POSTS:
    q = dict(q)
    if q["tipo"] == "list":
        q["bars"] = [(5000, "Infracciones leves", "5.000 UTM"), (10000, "Infracciones graves", "10.000 UTM"),
                     (20000, "Infracciones gravísimas", "20.000 UTM"), (40000, "Operadores de Importancia Vital (tope)", "40.000 UTM")]
        q["title"] = "Multas de la Ley 21.663: lo que está en juego"
    if q["tipo"] == "stat":  # ajustes de composición por pieza
        q["bigsize"] = {"02": 300, "05": 380, "11": 380}.get(q["id"], 330); q["captop"] = 830
    if q["tipo"] == "webinar":
        q["dia"] = q["fecha"][:2]
    POSTS.append(q)
NAMES = {"agenda": "calendario-webinars", "stat": "dato", "list": "ley", "reel": "reel-portada"}

def render():
    shots = {}
    with sync_playwright() as pw:
        br = pw.chromium.launch(executable_path="/opt/pw-browsers/chromium", args=["--no-sandbox"])
        for p in POSTS:
            h = 1920 if p["tipo"] == "reel" else 1350
            pg = br.new_page(viewport={"width": 1080, "height": h})
            pg.set_content(TEMPL[p["tipo"]](p), timeout=120000); pg.wait_for_timeout(500)
            name = ("webinar-" + p["wordmark"].replace("<b>", "").replace("</b>", "").split()[0].lower()) if p["tipo"] == "webinar" else NAMES[p["tipo"]]
            out = OUT / ("reels" if p["tipo"] == "reel" else "posts") / f"{p['id']}_{name}.png"
            pg.screenshot(path=str(out)); pg.close(); shots[p["id"]] = out
        br.close()
    return shots

def grid(shots):
    order = [p["id"] for p in POSTS if p["id"] != "00"]
    rows = [order[i:i + 3] for i in range(0, 12, 3)]
    W, H, G = 540, 675, 12
    sheet = Image.new("RGB", (3 * W + 4 * G, 4 * H + 5 * G), (234, 242, 250))
    for r, row in enumerate(rows):
        for c, pid in enumerate(row):
            im = Image.open(shots[pid]).convert("RGB")
            if im.height > 1350:
                t = (im.height - 1350) // 2; im = im.crop((0, t, 1080, t + 1350))
            sheet.paste(im.resize((W, H)), (G + c * (W + G), G + r * (H + G)))
    sheet.save(OUT / "grilla-octubre.png", optimize=True)

if __name__ == "__main__":
    s = render(); grid(s); print("ok", len(s))
