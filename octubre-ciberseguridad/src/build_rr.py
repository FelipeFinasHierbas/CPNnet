#!/usr/bin/env python3
"""Grilla de octubre 2026 para partners · campaña CPNnet Risk Reduction Q4 (14 al 30 de octubre).

Seis pilares / seis soluciones (gráficas de campaña): Identidades-Segura, Aplicaciones-Aikido,
Endpoints-Faronics, Datos-Kriptos, Usuarios-Safetica, Vulnerabilidades-Vicarius (Chile/Perú) o Action1 (Colombia).
Contenido técnico del Catálogo CPNnet para canales; promoción "¡Gana el 1%!" tal como figura en la gráfica.
Uso: python3 build_rr.py -> ../v4-risk-reduction/
"""
import base64, re, shutil, pathlib
from playwright.sync_api import sync_playwright
from PIL import Image
import build as b1

ROOT = b1.ROOT
OUT = ROOT / "v4-risk-reduction"
for d in ("posts", "reels"):
    (OUT / d).mkdir(parents=True, exist_ok=True)
SRC3 = ROOT / "v3-partners/posts"
LOGOS = {p.stem: "data:image/png;base64," + base64.b64encode(p.read_bytes()).decode() for p in (ROOT / "assets/logos").glob("*.png")}
_flags = re.findall(r"<svg.*?</svg>", b1.FLAGS, flags=re.S)
FLAG = {"cl": _flags[0], "pe": _flags[1], "us": _flags[2], "co": _flags[3]}
NAVY, AZ, CY = "#0a1a3a", "#3d9be9", "#6fe3f5"
TAGLINE = "Mismas amenazas.<br>Más oportunidades de proteger lo que importa."
FINE = "Promoción exclusiva para canales Partner de CPNnet con relación comercial vigente antes del 1 de octubre de 2026 · Aplican bases y condiciones"

ICON = {  # iconos simples 40x40, trazo blanco
 "identidades": "<circle cx='20' cy='13' r='6'/><path d='M7 34c0-8 6-12 13-12s13 4 13 12'/>",
 "aplicaciones": "<path d='M14 11L5 20l9 9M26 11l9 9-9 9M23 8l-6 24'/>",
 "endpoints": "<rect x='7' y='9' width='26' height='18' rx='2'/><path d='M3 32h34'/>",
 "datos": "<ellipse cx='20' cy='10' rx='11' ry='4'/><path d='M9 10v20c0 2 5 4 11 4s11-2 11-4V10M9 20c0 2 5 4 11 4s11-2 11-4'/>",
 "usuarios": "<circle cx='20' cy='13' r='5'/><circle cx='9' cy='17' r='4'/><circle cx='31' cy='17' r='4'/><path d='M10 32c0-6 4-9 10-9s10 3 10 9M2 30c0-4 3-6 7-6M38 30c0-4-3-6-7-6'/>",
 "vulnerabilidades": "<path d='M20 5L3 34h34z'/><path d='M20 15v10M20 29.5v.5'/>",
}


def icon(name, size=34):
    return (f"<svg width='{size}' height='{size}' viewBox='0 0 40 40' fill='none' stroke='#fff' stroke-width='2.4' "
            f"stroke-linecap='round' stroke-linejoin='round'>{ICON[name]}</svg>")


CSS = b1.FONTS + f"""
*{{box-sizing:border-box;margin:0;padding:0}}
body{{font-family:'Montserrat',sans-serif;width:1080px;overflow:hidden;background:{NAVY}}}
.cv{{position:relative;width:1080px;overflow:hidden;color:#fff;background:radial-gradient(900px 700px at 85% 75%,#1b4a8f 0%,transparent 60%),linear-gradient(165deg,#0d2347,{NAVY} 60%,#071430)}}
canvas{{position:absolute;inset:0}}
.abs{{position:absolute}}
.az{{color:{AZ}}}
.kick{{font-size:23px;font-weight:600;letter-spacing:2px;text-transform:uppercase}}
.kick b{{font-weight:800;letter-spacing:1px}} .kick .sep{{opacity:.5;margin:0 8px}}
h1{{font-weight:800;line-height:1.08;letter-spacing:-1px}}
.hex{{clip-path:polygon(50% 0,100% 25%,100% 75%,50% 100%,0 75%,0 25%);background:linear-gradient(160deg,rgba(150,200,255,.55),rgba(80,140,230,.25))}}
.hex>div{{position:absolute;inset:3px;clip-path:polygon(50% 0,100% 25%,100% 75%,50% 100%,0 75%,0 25%);background:linear-gradient(160deg,#2c5cae,#3b82cc);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:0 22px}}
.hex.dk>div{{background:linear-gradient(160deg,#102a5c,#173a78)}}
.pname{{font-weight:800;letter-spacing:.5px;text-transform:uppercase}}
.bul{{display:flex;gap:16px;align-items:flex-start;font-size:23px;font-weight:600;line-height:1.28;margin:12px 0}}
.bul:before{{content:'';flex:none;width:12px;height:12px;margin-top:10px;border-radius:50%;background:{CY}}}
.band{{border-radius:28px;background:linear-gradient(135deg,#2a5aa8,#3f86d0);box-shadow:0 14px 40px rgba(0,0,0,.3)}}
.promo{{display:flex;align-items:center;gap:26px;padding:0 34px}}
.promo .big{{font-size:46px;font-weight:800;white-space:nowrap}}
.promo .sep{{width:2px;align-self:stretch;margin:22px 0;background:rgba(255,255,255,.4)}}
.promo .tx{{font-size:21px;font-weight:500;line-height:1.3}} .promo .tx b{{font-weight:800}}
.fine{{font-size:13px;line-height:1.4;opacity:.65;text-align:center}}
.country{{display:flex;align-items:center;gap:14px;justify-content:flex-end;font-size:19px;font-weight:600;letter-spacing:2px}}
.country svg{{width:38px;height:38px}}
.tag{{font-size:13.5px;line-height:1.35;letter-spacing:1px;text-transform:uppercase;text-align:right;color:#a8c8f0;margin-top:10px}}
"""

DOTS_JS = """
function rng(a){return function(){a|=0;a=a+0x6D2B79F5|0;let t=Math.imul(a^a>>>15,1|a);t=t+Math.imul(t^t>>>7,61|t)^t;return((t^t>>>14)>>>0)/4294967296}}
const c=document.getElementById('bg'),x=c.getContext('2d'),W=c.width,H=c.height,R=rng(SEED);
for(let r=0;r<4;r++){for(let i=0;i<650;i++){const t=i/650,px=W*(0.1+1.0*t),sp=40+110*Math.sin(t*3.1);
 const py=H*(BASE+0.07*Math.sin(t*6.5+r*1.3))+(R()-.5)*sp*(0.5+R());
 x.fillStyle='rgba(111,227,245,'+(.1+R()*.55)+')';x.beginPath();x.arc(px,py,.7+R()*1.6,0,7);x.fill()}}
for(let i=0;i<260;i++){x.fillStyle='rgba(111,227,245,'+(.05+R()*.3)+')';x.fillRect(W*(.55+R()*.45),H*(.02+R()*.25),1.5,1.5)}
"""


def page(h, body, seed=3, base=.84):
    js = DOTS_JS.replace("SEED", str(seed)).replace("BASE", str(base))
    return (f"<html><head><meta charset='utf-8'><style>{CSS}</style></head><body><div class='cv' style='height:{h}px'>"
            f"<canvas id='bg' width='1080' height='{h}'></canvas>{body}</div><script>{js}</script></body></html>")


def header(top, co=False):
    flags = FLAG["co"] if co else FLAG["cl"] + FLAG["pe"]
    name = "COLOMBIA" if co else "CHILE &nbsp;|&nbsp; PERÚ"
    return (f"<img class='abs' src='{b1.LOGO}' style='left:76px;top:{top}px;width:176px'>"
            f"<div class='abs' style='right:76px;top:{top+6}px;width:520px'><div class='country'>{name}{flags}</div><div class='tag'>{TAGLINE}</div></div>")


def kicker(y, pilar):
    return f"<div class='abs kick' style='left:76px;top:{y}px'><b>CPNnet</b><span class='sep'>|</span><span class='az'>Risk Reduction Q4</span><span class='sep'>·</span>{pilar}</div>"


def promo(y, h=106, small=False):
    return (f"<div class='abs band promo' style='left:76px;right:76px;top:{y}px;height:{h}px'><div class='big'>¡GANA EL 1%!</div><div class='sep'></div>"
            f"<div class='tx'>Tus vendedores ganan el 1% de las ventas netas elegibles.<br><b>10 oct – 31 dic 2026</b></div></div>")


def fine(y):
    return f"<div class='abs fine' style='left:76px;right:76px;top:{y}px'>{FINE}</div>"


# ------------------------------------------------------------------ pilares
def pilares(co=False):
    vuln = dict(marca="action1", nombre="Vulnerabilidades") if co else dict(marca="vicarius", nombre="Vulnerabilidades")
    return {
     "identidades": dict(nombre="Identidades", marca="segura", q="¿Quién tiene <span class='az'>acceso</span> a sus sistemas críticos?",
        pos="Protección de accesos privilegiados",
        b=["Cuentas privilegiadas en bóveda, con rotación de credenciales", "Acceso de terceros controlado, con sesiones grabadas", "Mínimo privilegio en endpoints, secretos DevOps y cloud"],
        entrada="PAM Assessment o revisión de accesos de terceros"),
     "aplicaciones": dict(nombre="Aplicaciones", marca="aikido", q="¿Qué riesgos llegan a <span class='az'>producción</span> con su software?",
        pos="Seguridad en aplicaciones",
        b=["Código, dependencias, secretos, IaC y contenedores en una plataforma", "Menos ruido: reachability y auto-triage", "AutoFix: del hallazgo al pull request"],
        entrada="Demo conectando uno o pocos repositorios"),
     "endpoints": dict(nombre="Endpoints", marca="faronics", q="¿Puede <span class='az'>mantener y recuperar</span> sus equipos en un estado seguro?",
        pos="Control y recuperación de endpoints",
        b=["Deep Freeze restaura el equipo a un estado conocido al reiniciar", "Deep Freeze Cloud: administración, despliegue y actualizaciones", "Menos tickets en laboratorios, kioscos y salas"],
        entrada="Piloto en 10 a 20 equipos de alto soporte"),
     "datos": dict(nombre="Datos", marca="kriptos", q="¿Sabe realmente dónde está su <span class='az'>información sensible</span>?",
        pos="Visibilidad y clasificación de datos",
        b=["Descubre y clasifica datos no estructurados con IA", "Muestra quién accede y detecta permisos excesivos en Microsoft 365 y Google", "Base para DLP y para gobernar el uso de IA"],
        entrada="Data Discovery Assessment"),
     "usuarios": dict(nombre="Usuarios", marca="safetica", q="¿Qué ocurre con la información en manos de sus <span class='az'>usuarios</span>?",
        pos="Protección de la información y riesgo interno",
        b=["DLP e Insider Risk en una sola propuesta", "Dato, identidad y comportamiento para distinguir lo normal de lo riesgoso", "Control de USB, correo, web y cloud"],
        entrada="DLP/Insider Risk Assessment"),
     "vulnerabilidades": (dict(nombre="Vulnerabilidades", marca="action1", q="¿Las está detectando o realmente las está <span class='az'>corrigiendo</span>?",
        pos="Gestión de vulnerabilidades y remediación de endpoints",
        b=["Automatiza el patching en Windows, macOS, Linux y software de terceros", "Gestiona equipos remotos sin depender de la VPN", "Patch compliance con visibilidad en tiempo real"],
        entrada="Assessment de patch compliance o piloto") if co else
        dict(nombre="Vulnerabilidades", marca="vicarius", q="¿Las está detectando o realmente las está <span class='az'>corrigiendo</span>?",
        pos="Priorización y remediación de vulnerabilidades",
        b=["Prioriza el backlog y lo conecta con acciones de remediación", "Patching, scripting y protección sin parche", "TI y Seguridad trabajando en un mismo flujo"],
        entrada="Assessment de remediación o prueba en un grupo de equipos")),
    }


def hex_pilar(k, pil, w, h, x, y, name_px=24, logo_h=0, dark=False):
    lg = f"<img src='{LOGOS[pil['marca']]}' style='height:{logo_h}px;margin-top:8px'>" if logo_h else ""
    return (f"<div class='abs hex{' dk' if dark else ''}' style='left:{x}px;top:{y}px;width:{w}px;height:{h}px'><div>"
            f"{icon(k, int(name_px*1.5))}<div class='pname' style='font-size:{name_px}px;margin-top:6px'>{pil['nombre']}</div>{lg}</div></div>")


# ------------------------------------------------------------------ plantillas
def t_post(k, co=False):
    P = pilares(co)[k]
    bullets = "".join(f"<div class='bul'><div>{b}</div></div>" for b in P["b"])
    body = (header(64, co) + kicker(262, P["nombre"])
            + f"<h1 class='abs' style='left:76px;right:76px;top:306px;font-size:62px'>{P['q']}</h1>"
            + hex_pilar(k, P, 330, 380, 76, 548, 27)
            + f"<img class='abs' src='{LOGOS[P['marca']]}' style='left:450px;top:552px;height:{60 if P['marca']!='vicarius' else 76}px'>"
            + f"<div class='abs' style='left:450px;right:76px;top:636px;font-size:23px;font-weight:800;color:{CY};line-height:1.25'>{P['pos']}</div>"
            + f"<div class='abs' style='left:450px;right:76px;top:690px'>{bullets}</div>"
            + f"<div class='abs band' style='left:76px;right:76px;top:974px;height:118px;padding:20px 38px;background:linear-gradient(135deg,#143a7a,#0e2a5c)'>"
              f"<div style='font-size:17px;font-weight:700;letter-spacing:4px;color:{CY};text-transform:uppercase'>Tu puerta de entrada</div>"
              f"<div style='font-size:28px;font-weight:800;margin-top:4px;line-height:1.15'>{P['entrada']}</div></div>"
            + promo(1114) + fine(1246))
    return page(1350, body, seed={"identidades": 3, "aplicaciones": 5, "endpoints": 7, "datos": 9, "usuarios": 11, "vulnerabilidades": 13}[k])


def t_reel(k):
    P = pilares()[k]
    body = (header(300) + kicker(480, P["nombre"])
            + f"<h1 class='abs' style='left:76px;right:76px;top:530px;font-size:76px'>{P['q']}</h1>"
            + hex_pilar(k, P, 420, 484, 330, 820, 34)
            + f"<img class='abs' src='{LOGOS[P['marca']]}' style='left:0;right:0;margin:0 auto;top:1330px;height:72px'>"
            + promo(1470, 100))
    return page(1920, body, seed={"identidades": 21, "aplicaciones": 23, "endpoints": 25}[k], base=.88)


def t_overview(co=False):
    PL = pilares(co)
    w, h = 250, 288
    gx, gy = (1080 - 3 * w - 12) // 2, 444
    dx, dy = w + 6, int(h * .75) + 4
    cells = [("identidades", gx + w // 2, gy), ("aplicaciones", gx + w // 2 + dx, gy),
             ("endpoints", gx, gy + dy), ("datos", gx + 2 * dx, gy + dy),
             ("usuarios", gx + w // 2, gy + 2 * dy), ("vulnerabilidades", gx + w // 2 + dx, gy + 2 * dy)]
    hexes = "".join(hex_pilar(k, PL[k], w, h, x, y, 20, 34 if PL[k]["marca"] != "vicarius" else 44) for k, x, y in cells)
    center = (f"<div class='abs hex dk' style='left:{gx + dx}px;top:{gy + dy}px;width:{w}px;height:{h}px'>"
              f"<div><svg width='46' height='52' viewBox='0 0 40 46' fill='none' stroke='{AZ}' stroke-width='2.4'><path d='M20 3l15 6v13c0 9-6 16-15 21C11 38 5 31 5 22V9z'/></svg>"
              f"<div style='font-size:21px;font-weight:800;margin-top:6px'>CPNnet</div><div style='font-size:19px;font-weight:800;color:{AZ};line-height:1.1'>Risk<br>Reduction</div></div></div>")
    body = (header(64, co) + kicker(250, "")
            .replace("<span class='sep'>·</span>", "")
            + f"<h1 class='abs' style='left:76px;right:76px;top:298px;font-size:56px'>¿Dónde está la próxima <span class='az'>brecha</span> de tus clientes?</h1>"
            + hexes + center + promo(1172, 92) + fine(1282))
    return page(1350, body, seed=31, base=.9)


# ------------------------------------------------------------------ grilla
PIECES = [
 dict(n="01", fecha="Mié 14 oct", tipo="overview", nombre="risk-reduction-lanzamiento"),
 dict(n="02", fecha="Vie 16 oct", tipo="reel", k="identidades", nombre="reel-segura"),
 dict(n="03", fecha="Lun 19 oct", tipo="copia", src=SRC3 / "04_webinar-seceon.png", nombre="webinar-seceon"),
 dict(n="04", fecha="Mié 21 oct", tipo="post", k="datos", nombre="kriptos"),
 dict(n="05", fecha="Vie 23 oct", tipo="reel", k="endpoints", nombre="reel-faronics"),
 dict(n="06", fecha="Lun 26 oct", tipo="copia", src=SRC3 / "07_webinar-safetica.png", nombre="webinar-safetica"),
 dict(n="07", fecha="Mié 28 oct", tipo="post", k="vulnerabilidades", nombre="vicarius"),
 dict(n="08", fecha="Vie 30 oct", tipo="reel", k="aplicaciones", nombre="reel-aikido"),
]
EXTRA_CO = [dict(n="01", tipo="overview", co=True, nombre="risk-reduction-lanzamiento-colombia"),
            dict(n="07", tipo="post", k="vulnerabilidades", co=True, nombre="action1-colombia")]


def html_for(p):
    if p["tipo"] == "overview": return t_overview(p.get("co", False))
    if p["tipo"] == "post": return t_post(p["k"], p.get("co", False))
    return t_reel(p["k"])


def render():
    shots = {}
    with sync_playwright() as pw:
        br = pw.chromium.launch(executable_path="/opt/pw-browsers/chromium", args=["--no-sandbox"])
        for p in PIECES + EXTRA_CO:
            sub = "reels" if p["tipo"] == "reel" else "posts"
            out = OUT / sub / f"{p['n']}_{p['nombre']}.png"
            if p["tipo"] == "copia":
                shutil.copy(p["src"], out)
            else:
                h = 1920 if p["tipo"] == "reel" else 1350
                pg = br.new_page(viewport={"width": 1080, "height": h})
                pg.set_content(html_for(p), timeout=120000); pg.wait_for_timeout(400)
                pg.screenshot(path=str(out)); pg.close()
            if p not in EXTRA_CO: shots[p["n"]] = out
        br.close()
    return shots


def grid(shots):
    W, H, G = 540, 675, 12
    sheet = Image.new("RGB", (3 * W + 4 * G, 3 * H + 4 * G), (234, 242, 250))
    order = [p["n"] for p in PIECES]
    for i, n in enumerate(order):
        im = Image.open(shots[n]).convert("RGB")
        if im.height > 1350:
            t = (im.height - 1350) // 2; im = im.crop((0, t, 1080, t + 1350))
        r, c = divmod(i, 3)
        sheet.paste(im.resize((W, H)), (G + c * (W + G), G + r * (H + G)))
    sheet.save(OUT / "grilla-risk-reduction.png", optimize=True)


if __name__ == "__main__":
    s = render(); grid(s); print("ok", len(s))
