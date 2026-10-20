#!/usr/bin/env python3
"""Grilla del mes con la mascota CPNnet. Vicarius y Action1 en posts independientes (9 piezas).
Salida: ../v6-mascota/{1080x1350,1080x1920}/ + grilla-mascota.png   Uso: python3 build_rr3.py"""
import base64, io, re
from playwright.sync_api import sync_playwright
from PIL import Image
import build_rr2 as m
import build_v2 as b2
import build_rr as rr

ROOT = m.ROOT
OUT = ROOT / "v6-mascota"
for d in ("1080x1350", "1080x1920"):
    (OUT / d).mkdir(parents=True, exist_ok=True)
NAVY, ROYAL, AZURE, SKY, CYAN = m.NAVY, m.ROYAL, m.AZURE, m.SKY, m.CYAN

_im = Image.open(ROOT / "assets/mascota-cpnnet.png")
_im = _im.resize((int(_im.width * 900 / _im.height), 900), Image.LANCZOS)
_buf = io.BytesIO(); _im.save(_buf, "PNG", optimize=True)
MASCOT = "data:image/png;base64," + base64.b64encode(_buf.getvalue()).decode()


def mascot(x, y, h, glow=True, dark=False):
    w = h * _im.width / _im.height
    g = (f"<div class='abs' style='left:{x-40}px;top:{y+h*0.55}px;width:{w+80:.0f}px;height:{h*0.5:.0f}px;border-radius:50%;"
         f"background:radial-gradient(closest-side,{'rgba(111,227,245,.55)' if dark else 'rgba(58,132,189,.30)'},transparent)'></div>") if glow else ""
    return g + f"<img class='abs' src='{MASCOT}' style='left:{x}px;top:{y}px;height:{h}px;filter:drop-shadow(0 18px 30px rgba(10,30,70,.35))'>"


def pilares():
    P = m.pilares()
    P["vicarius"] = dict(nombre="Vulnerabilidades", marca="vicarius", icon="vulnerabilidades",
        q="¿Las está detectando o realmente las está <span class='az'>corrigiendo</span>?", pos="Remediación de vulnerabilidades",
        b=["Patching automatizado y scripting para corregir vulnerabilidades", "Protección sin parche cuando aún no hay una actualización disponible", "Cobertura de más de 20.000 aplicaciones de terceros"],
        entrada="Assessment de remediación")
    P["action1"] = dict(nombre="Vulnerabilidades", marca="action1", icon="vulnerabilidades",
        q="¿Sus <span class='az'>equipos remotos</span> están realmente parchados?", pos="Gestión de parches",
        b=["Patching automatizado de sistema operativo y software de terceros", "Equipos remotos actualizados sin necesidad de VPN", "Reportes de patch compliance para ver qué falta parchear"],
        entrada="Assessment de patch compliance")
    return P


def t_post(k):
    P = pilares()[k]
    bullets = "".join(f"<div class='sol-b'><div>{b}</div></div>" for b in P["b"])
    rings = b2.arc(1040, 30, 260, 220, -28, 18, .5, .1, gid="a1", opacity=.6)
    body = (f"<svg class='arcs' width='1080' height='1350' viewBox='0 0 1080 1350'>{rings}</svg>"
            + m.head_right(70, False)
            + f"<div class='abs lab' style='left:76px;top:262px'>Mes de la Ciberseguridad · {P['nombre']}</div>"
            + f"<h1 class='abs' style='left:76px;right:76px;top:306px;font-size:52px;line-height:1.08;font-weight:800;text-wrap:balance'>{P['q']}</h1>"
            + m.logo_chip(P["marca"], 76, 474, 330, 92, 56)
            + f"<div class='abs' style='left:440px;top:491px;display:flex;align-items:center;gap:14px'>"
              f"<div style='flex:none;width:58px;height:58px;border-radius:50%;background:{AZURE};display:flex;align-items:center;justify-content:center'>{rr.icon(P.get('icon', k), 32)}</div>"
              f"<div style='font-size:22px;font-weight:800;letter-spacing:1px;text-transform:uppercase;line-height:1.1'>{P['nombre']}</div></div>"
            + mascot(60, 590, 470)
            + f"<div class='abs card' style='left:400px;right:76px;top:590px;padding:24px 32px 10px'>"
              f"<div style='font-size:25px;font-weight:800;color:{AZURE};line-height:1.25;margin-bottom:8px'>{P['pos']}</div>{bullets}</div>"
            + f"<div class='abs' style='left:76px;right:76px;top:1085px;background:linear-gradient(135deg,{ROYAL},{NAVY});border-radius:30px;padding:24px 40px;color:#fff;box-shadow:0 14px 36px rgba(20,50,100,.3)'>"
              f"<div style='font-size:18px;font-weight:700;letter-spacing:4px;color:{CYAN};text-transform:uppercase'>Tu puerta de entrada</div>"
              f"<div style='font-size:31px;font-weight:800;margin-top:6px;line-height:1.2'>{P['entrada']}</div></div>"
            + "<div class='pill' style='top:1262px'>#CPNnetPartners</div>" + m.cta_btn(1262))
    return m.page(1350, "lt", body)


def t_overview():
    PL = pilares()
    order = ["identidades", "aplicaciones", "endpoints", "datos", "usuarios", "vulnerabilidades"]
    qs = {"identidades": "¿Quién tiene acceso a sus sistemas críticos?", "aplicaciones": "¿Qué riesgos llegan a producción con su software?",
          "endpoints": "¿Puede mantener y recuperar sus equipos en un estado seguro?", "datos": "¿Sabe dónde está su información sensible?",
          "usuarios": "¿Qué ocurre con la información en manos de sus usuarios?", "vulnerabilidades": "¿Las está detectando o realmente las está corrigiendo?"}
    cards = ""
    for i, k in enumerate(order):
        x = 76 if i % 2 == 0 else 76 + 438 + 14
        y = 500 + (i // 2) * 232
        mk = PL[k]["marca"]
        lh = 24 if mk not in ("vicarius", "action1") else 30
        extra = (f"<div class='chip' style='position:absolute;left:{26+50+lh*3.4:.0f}px;bottom:18px;height:44px;padding:0 20px;border-radius:16px;box-shadow:none'><img src='{m.LOGOS['action1']}' style='height:26px'></div>" if k == "vulnerabilidades" else "")
        cards += (f"<div class='abs ocard' style='left:{x}px;top:{y}px;width:438px;height:216px'>"
                  f"<div style='display:flex;align-items:center;gap:12px'><div style='flex:none;width:46px;height:46px;border-radius:50%;background:{AZURE};display:flex;align-items:center;justify-content:center'>{rr.icon(k, 26)}</div>"
                  f"<div style='font-size:21px;font-weight:800;letter-spacing:.5px;text-transform:uppercase'>{PL[k]['nombre']}</div></div>"
                  f"<div style='font-size:20px;font-weight:600;line-height:1.28;margin-top:12px;height:78px'>{qs[k]}</div>"
                  f"<div class='chip' style='position:absolute;left:26px;bottom:18px;height:44px;padding:0 20px;border-radius:16px;box-shadow:none'><img src='{m.LOGOS[mk]}' style='height:{lh}px'></div>{extra}</div>")
    rings = b2.arc(1040, 30, 260, 220, -28, 18, .5, .1, gid="a1", opacity=.6)
    body = (f"<svg class='arcs' width='1080' height='1350' viewBox='0 0 1080 1350'>{rings}</svg>" + m.head_right(70, False)
            + "<div class='abs lab' style='left:76px;top:262px'>CPNnet | Mes de la Ciberseguridad</div>"
            + "<h1 class='abs' style='left:76px;width:640px;top:306px;font-size:52px;line-height:1.08;font-weight:800'>¿Dónde está la próxima <span class='az'>brecha</span> de tus clientes?</h1>"
            + mascot(780, 118, 372)
            + cards + "<div class='pill' style='top:1262px'>#CPNnetPartners</div>" + m.cta_btn(1262))
    return m.page(1350, "lt", body)


def t_reel(k):
    html = m.t_reel(k)
    # la mascota reemplaza el botón de play
    html = re.sub(r"<div class='abs' style='left:810px;top:920px;width:180px;height:180px.*?</div></div></div>", "", html, count=1, flags=re.S)
    return html.replace("<img class='logo'", mascot(715, 700, 440, dark=True) + "<img class='logo'", 1)


def t_web(p):
    return b2.t_webinar(p).replace("<img class='logo'", mascot(36, 470, 330, dark=True) + "<img class='logo'", 1)


def tall(html, shift=285):
    html = re.sub(r"(class='cv \w+' style='height:)1350px", r"\g<1>1920px", html, count=1)
    html = html.replace("<div class='grid'></div>", f"<div class='grid'></div><div style='position:absolute;left:0;top:{shift}px;width:1080px;height:1350px'>", 1)
    return html.replace("<img class='logo'", f"</div><img class='logo' style='top:{80 + shift}px'", 1)


SECEON = dict(seed=81, wordmark="<b>SECEON</b>", fecha="20 de Octubre 2026", dia="20", title="Seceon: SOC autónomo con AI-SIEM y XDR para equipos reducidos")
SAFETICA = dict(seed=111, flip=True, wordmark="<b>SAFETICA</b>", fecha="27 de Octubre 2026", dia="27", title="")
PIECES = [
    ("01", "risk-reduction-lanzamiento", "tall", t_overview),
    ("02", "reel-segura", "reel", lambda: t_reel("identidades")),
    ("03", "webinar-seceon", "tall", lambda: t_web(SECEON)),
    ("04", "kriptos", "tall", lambda: t_post("datos")),
    ("05", "reel-faronics", "reel", lambda: t_reel("endpoints")),
    ("06", "webinar-safetica", "tall", lambda: t_web(SAFETICA)),
    ("07", "vicarius", "tall", lambda: t_post("vicarius")),
    ("08", "action1", "tall", lambda: t_post("action1")),
    ("09", "reel-aikido", "reel", lambda: t_reel("aplicaciones")),
]

if __name__ == "__main__":
    import sys
    only = set(sys.argv[1:])
    with sync_playwright() as pw:
        br = pw.chromium.launch(executable_path="/opt/pw-browsers/chromium", args=["--no-sandbox"])
        for n, name, kind, fn in PIECES:
            if only and n not in only: continue
            html = fn()
            jobs = [("1080x1920", 1920, html)] if kind == "reel" else [("1080x1350", 1350, html), ("1080x1920", 1920, tall(html))]
            for folder, h, doc in jobs:
                pg = br.new_page(viewport={"width": 1080, "height": h})
                pg.set_content(doc, timeout=120000); pg.wait_for_timeout(500)
                pg.screenshot(path=str(OUT / folder / f"{n}_{name}_{folder}.png")); pg.close()
        br.close()
    # grilla 3x3 (los reels se recortan al centro 4:5)
    W, H, G = 540, 675, 12
    sheet = Image.new("RGB", (3 * W + 4 * G, 3 * H + 4 * G), (234, 242, 250))
    for i, (n, name, kind, _) in enumerate(PIECES):
        f = OUT / ("1080x1920" if kind == "reel" else "1080x1350") / f"{n}_{name}_{'1080x1920' if kind == 'reel' else '1080x1350'}.png"
        im = Image.open(f).convert("RGB")
        if im.height > 1350: t = (im.height - 1350) // 2; im = im.crop((0, t, 1080, t + 1350))
        sheet.paste(im.resize((W, H)), (G + (i % 3) * (W + G), G + (i // 3) * (H + G)))
    sheet.save(OUT / "grilla-mascota.png", optimize=True)
    print("ok")
