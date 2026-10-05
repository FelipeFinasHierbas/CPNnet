#!/usr/bin/env python3
"""Gráfica del webinar Vicarius (13-oct-2026) en 3 formatos: 1920x1080, 1080x1350 y 1080x1920.

Mismo lenguaje "portal + arcos" de la grilla de octubre. El logo de Vicarius se usa sin modificar,
sobre tarjeta blanca. Cambiar TITULO y volver a ejecutar para actualizar las tres piezas.
Uso: python3 build_vicarius.py -> ../vicarius-webinar/
"""
import base64, pathlib
from playwright.sync_api import sync_playwright
import build as b1
import build_v2 as b2

ROOT = b1.ROOT
OUT = ROOT / "vicarius-webinar"; OUT.mkdir(exist_ok=True)
VIC = "data:image/jpeg;base64," + base64.b64encode((ROOT / "assets/vicarius-logo.jpg").read_bytes()).decode()

# TÍTULO PROVISORIO: reemplazar por el nombre oficial del webinar.
TITULO = "De la vulnerabilidad a la remediación, con o sin parche"
FECHA, DIA = "13 de Octubre 2026", "13"
SEED = 51


def vic_card(h, x, y):
    return (f"<div class='abs' style='left:{x}px;top:{y}px;background:#fff;border-radius:{h*.3:.0f}px;"
            f"padding:{h*.16:.0f}px {h*.36:.0f}px;box-shadow:0 14px 40px rgba(0,0,0,.3)'><img src='{VIC}' style='height:{h}px;display:block'></div>")


def portal(cx, cy, d, svg_w, svg_h):
    k = d / 680
    r = d / 2
    ring = (b2.arc(cx, cy, r + 52 * k, r + 32 * k, -28, 22 * k, .62, .08, gid="a1")
            + b2.arc(cx, cy, r + 88 * k, r + 68 * k, -28, 5 * k, .34, .55, (b2.CYAN, b2.SKY), .8, gid="a2"))
    return (f"<svg class='arcs' width='{svg_w}' height='{svg_h}' viewBox='0 0 {svg_w} {svg_h}'>{ring}</svg>"
            f"<div class='portal' style='left:{cx-r:.0f}px;top:{cy-r:.0f}px;width:{d}px;height:{d}px'><canvas id='bg' width='{d}' height='{d}'></canvas>"
            f"<div class='abs' style='inset:0;background:radial-gradient(circle,rgba(3,22,43,0) 25%,rgba(3,22,43,.55) 100%)'></div>"
            f"<div class='abs glow' style='inset:0;text-align:center;color:#fff'>"
            f"<div style='font-size:{34*k:.0f}px;font-weight:700;letter-spacing:{12*k:.0f}px;margin-top:{130*k:.0f}px;padding-left:{12*k:.0f}px'>OCT</div>"
            f"<div style='font-size:{330*k:.0f}px;font-weight:800;line-height:.9;letter-spacing:-{12*k:.0f}px'>{DIA}</div>"
            f"<div style='font-size:{36*k:.0f}px;font-weight:700;letter-spacing:{12*k:.0f}px;padding-left:{12*k:.0f}px'>MARTES</div></div></div>")


def info(x, y, scale=1.0):
    return (f"<div class='info abs' style='left:{x}px;top:{y}px;gap:{60*scale:.0f}px'>"
            f"<div class='d' style='font-size:{30*scale:.0f}px'>{b1.CAL}{FECHA}</div>"
            f"<div style='font-size:{26*scale:.0f}px'>{b1.CLK}<span>{b1.HORA}</span></div></div>")


def footer(y, x_pill=55, right=0):
    return (f"<div class='pill' style='top:{y}px;left:{x_pill}px'>#webinarscpnnet</div>"
            f"<div class='abs' style='right:{right}px;top:{y+2}px'>{b1.FLAGS}</div>")


def kicker(x, y):
    return f"<div class='abs lab' style='left:{x}px;top:{y}px'>Webinar · Mes de la Ciberseguridad</div>"


def post_4x5():
    body = (portal(700, 440, 680, 1080, 1350) + kicker(76, 330).replace("Webinar · Mes de la Ciberseguridad", "Webinar")
            + f"<div class='abs' style='left:76px;top:362px;width:270px;font-size:21px;font-weight:600;line-height:1.35'>Mes de la<br>Ciberseguridad</div>"
            + vic_card(84, 76, 812)
            + f"<h1 class='abs' style='left:76px;right:76px;top:960px;font-size:44px;line-height:1.16;font-weight:700'>{TITULO}</h1>"
            + info(76, 1122) + footer(1245))
    return b2.page(1350, "dk", body, canv=True, seed=SEED, fx=.55, fy=.5, flip=True)


def landscape():
    W, H = 1920, 1080
    body = (portal(1530, 540, 700, W, H)
            + vic_card(96, 100, 300)
            + kicker(100, 248)
            + f"<h1 class='abs' style='left:100px;width:1000px;top:470px;font-size:62px;line-height:1.14;font-weight:700;text-wrap:balance'>{TITULO}</h1>"
            + info(100, 800, 1.15)
            + f"<div class='pill' style='top:940px;left:78px'>#webinarscpnnet</div>"
            + f"<div class='abs' style='left:410px;top:942px'>{b1.FLAGS}</div>")
    html = b2.page(H, "dk", body, canv=True, seed=SEED, fx=.55, fy=.5, flip=True)
    css = ("body{width:1920px}.cv{width:1920px}.logo{left:100px;top:44px;width:176px}"
           ".flags{position:static;right:auto}.flags svg{width:48px;height:48px}")
    return html.replace("</style>", css + "</style>", 1)


def story():
    W, H = 1080, 1920
    body = (portal(540, 700, 600, W, H)
            + kicker(76, 1060)
            + vic_card(84, 76, 1115)
            + f"<h1 class='abs' style='left:76px;right:76px;top:1262px;font-size:46px;line-height:1.16;font-weight:700'>{TITULO}</h1>"
            + info(76, 1445) + footer(1545))
    html = b2.page(H, "dk", body, canv=True, seed=SEED, fx=.55, fy=.5, flip=True)
    return html.replace("</style>", ".logo{left:76px;top:250px}</style>", 1)


FORMATS = {"vicarius_webinar_1920x1080.png": (landscape, 1920, 1080),
           "vicarius_webinar_1080x1350.png": (post_4x5, 1080, 1350),
           "vicarius_webinar_1080x1920.png": (story, 1080, 1920)}

if __name__ == "__main__":
    with sync_playwright() as pw:
        br = pw.chromium.launch(executable_path="/opt/pw-browsers/chromium", args=["--no-sandbox"])
        for name, (fn, w, h) in FORMATS.items():
            pg = br.new_page(viewport={"width": w, "height": h})
            pg.set_content(fn(), timeout=120000); pg.wait_for_timeout(600)
            pg.screenshot(path=str(OUT / name)); pg.close(); print("ok", name)
        br.close()
