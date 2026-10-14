#!/usr/bin/env python3
"""Grilla de octubre para partners · Risk Reduction Q4 en el estilo "portal + arcos" (aprobado).

Mismo contenido que build_rr.py (6 pilares, logos de campaña, promo del 1%), con la estética de la grilla
de octubre: fondo claro con arcos para posts, foto tech en diagonal para reels, webinars en portal.
Uso: python3 build_rr2.py -> ../v5-risk-reduction-portal/
"""
import shutil
from playwright.sync_api import sync_playwright
from PIL import Image
import build as b1
import build_v2 as b2
import build_rr as rr

ROOT = b1.ROOT
OUT = ROOT / "v5-risk-reduction-portal"
for d in ("posts", "reels"):
    (OUT / d).mkdir(parents=True, exist_ok=True)
NAVY, ROYAL, AZURE, SKY, CYAN = b2.NAVY, b2.ROYAL, b2.AZURE, b2.SKY, b2.CYAN
LOGOS, ICON, FINE = rr.LOGOS, rr.ICON, rr.FINE
SRC3 = ROOT / "v3-partners/posts"

EXTRA = f"""
.sol-b{{display:flex;gap:18px;align-items:flex-start;font-size:26px;font-weight:600;line-height:1.3;margin:17px 0}}
.sol-b:before{{content:'';flex:none;width:30px;height:30px;margin-top:1px;border-radius:50%;background:{AZURE} url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><path d='M6 12.5l4 4 8-9' fill='none' stroke='white' stroke-width='3.2' stroke-linecap='round' stroke-linejoin='round'/></svg>") center/70% no-repeat}}
.chip{{background:linear-gradient(135deg,{ROYAL},{NAVY});border-radius:26px;display:flex;align-items:center;justify-content:center;box-shadow:0 12px 32px rgba(20,50,100,.28)}}
.promo{{background:linear-gradient(135deg,#2a5aa8,#3f86d0);border-radius:28px;display:flex;align-items:center;gap:26px;padding:0 34px;color:#fff;box-shadow:0 14px 36px rgba(32,60,113,.3)}}
.promo .big{{font-size:44px;font-weight:800;white-space:nowrap}} .promo .sep{{width:2px;align-self:stretch;margin:20px 0;background:rgba(255,255,255,.45)}}
.promo .tx{{font-size:20px;font-weight:500;line-height:1.3}} .promo .tx b{{font-weight:800}}
.fine{{font-size:13px;line-height:1.4;opacity:.7;text-align:center}}
.ocard{{background:#fff;border-radius:30px;box-shadow:0 14px 40px rgba(20,50,100,.14);padding:22px 26px}}
.hf svg{{width:38px;height:38px;flex:none}}
.tagl{{font-size:13px;line-height:1.35;letter-spacing:1px;text-transform:uppercase;text-align:right;margin-top:8px}}
"""


QS = {"identidades": "¿Quién tiene <span class='az'>acceso</span> a sus sistemas críticos?",
      "aplicaciones": "¿Qué riesgos llegan a <span class='az'>producción</span> con su software?",
      "endpoints": "¿Puede <span class='az'>mantener y recuperar</span> sus equipos en un estado seguro?",
      "datos": "¿Sabe realmente dónde está su <span class='az'>información sensible</span>?",
      "usuarios": "¿Qué ocurre con la información en manos de sus <span class='az'>usuarios</span>?",
      "vulnerabilidades": "¿Las está detectando o realmente las está <span class='az'>corrigiendo</span>?"}


def pilares(co=False):
    """Contenido basado en blogs y páginas oficiales de cada marca (ver fuentes en el documento)."""
    return {
     "identidades": dict(nombre="Identidades", marca="segura", q=QS["identidades"], pos="Protección de accesos privilegiados",
        b=["Cuentas privilegiadas en bóveda, con rotación de credenciales", "Acceso de terceros sin VPN y con sesiones grabadas", "Mínimo privilegio en endpoints y gestión de secretos DevOps"],
        entrada="PAM Assessment o revisión de accesos de terceros"),
     "aplicaciones": dict(nombre="Aplicaciones", marca="aikido", q=QS["aplicaciones"], pos="Seguridad en aplicaciones",
        b=["Código, dependencias y runtime en una sola plataforma", "AutoTriage: prioriza según alcanzabilidad (reachability)", "AutoFix: propone la versión segura y abre el pull request"],
        entrada="Demo conectando uno o pocos repositorios"),
     "endpoints": dict(nombre="Endpoints", marca="faronics", q=QS["endpoints"], pos="Control y recuperación de endpoints",
        b=["Deep Freeze restaura el equipo a su estado base con cada reinicio", "Pensado para laboratorios, kioscos y salas de capacitación", "Deep Freeze Cloud para administrar equipos remotos"],
        entrada="Piloto en 10 a 20 equipos de alto soporte"),
     "datos": dict(nombre="Datos", marca="kriptos", q=QS["datos"], pos="Visibilidad y clasificación de datos",
        b=["Clasifica documentos con varios modelos de IA, no con palabras clave", "Aplica etiquetas nativas de Microsoft Purview y Google Workspace", "Detecta permisos excesivos y archivos sobreexpuestos"],
        entrada="Data Discovery Assessment"),
     "usuarios": dict(nombre="Usuarios", marca="safetica", q=QS["usuarios"], pos="Protección de la información y riesgo interno",
        b=["DLP con Insider Risk integrado en una sola propuesta", "Detecta patrones de riesgo: descargas masivas, copias y accesos fuera de horario", "Colaboradores salientes, contratistas y trabajo remoto"],
        entrada="DLP/Insider Risk Assessment"),
     "vulnerabilidades": dict(nombre="Vulnerabilidades", marca="vicarius", marca2="action1", q=QS["vulnerabilidades"],
        pos="Priorización y remediación de vulnerabilidades",
        b=["Vicarius (Chile y Perú): patching, scripting y protección sin parche", "Action1 (Colombia): patching automatizado, también de software de terceros y equipos remotos sin VPN", "Ambas cierran el ciclo: del hallazgo a la corrección"],
        entrada="Assessment de remediación (Vicarius) o de patch compliance (Action1)"),
    }


def page(h, theme, body, **kw):
    return b2.page(h, theme, body, **kw).replace("</style>", EXTRA + "</style>", 1)


def head_right(top, co, dark=False):
    flags = rr.FLAG["cl"] + rr.FLAG["pe"] + rr.FLAG["us"] + rr.FLAG["co"]
    name = "CHILE · PERÚ · COLOMBIA"
    col = "#a8c8f0" if dark else AZURE
    return (f"<div class='abs' style='right:76px;top:{top}px;width:520px'><div style='display:flex;align-items:center;gap:14px;justify-content:flex-end;font-size:17px;font-weight:600;letter-spacing:1.5px'>{name}"
            f"<span class='hf' style='display:flex;gap:8px'>{flags}</span></div></div>")


def cta_btn(y):
    return (f"<div class='abs' style='right:76px;top:{y}px;height:58px;padding:0 30px;border-radius:999px;background:linear-gradient(135deg,#2a5aa8,#3f86d0);color:#fff;"
            f"font-size:22px;font-weight:700;display:flex;align-items:center;box-shadow:0 10px 26px rgba(32,60,113,.3)'>Habla con tu ejecutivo CPNnet</div>")


def cta_band(y, h=92):
    return (f"<div class='abs promo' style='left:76px;right:76px;top:{y}px;height:{h}px;justify-content:center'><div class='big' style='font-size:34px'>Habla con tu ejecutivo CPNnet</div></div>")


def promo_unused(y, h=96):
    return (f"<div class='abs promo' style='left:76px;right:76px;top:{y}px;height:{h}px'><div class='big'>¡GANA EL 1%!</div><div class='sep'></div>"
            f"<div class='tx'>Tus vendedores ganan el 1% de las ventas netas elegibles.<br><b>10 oct – 31 dic 2026</b></div></div>")


def fine(y, dark=False):
    return f"<div class='abs fine' style='left:76px;right:76px;top:{y}px;color:{'#fff' if dark else NAVY}'>{FINE}</div>"


def logo_chip(marca, x, y, w, h, lh):
    return f"<div class='abs chip' style='left:{x}px;top:{y}px;width:{w}px;height:{h}px'><img src='{LOGOS[marca]}' style='height:{lh}px;max-width:{w-40}px;object-fit:contain'></div>"


# ------------------------------------------------------------------ post de solución (claro)
def t_post(k, co=False):
    P = pilares()[k]
    bullets = "".join(f"<div class='sol-b'><div>{b}</div></div>" for b in P["b"])
    rings = b2.arc(1040, 30, 260, 220, -28, 18, .5, .1, gid="a1", opacity=.6)
    lh = 64 if P["marca"] not in ("vicarius", "action1") else 72
    body = (f"<svg class='arcs' width='1080' height='1350' viewBox='0 0 1080 1350'>{rings}</svg>"
            + head_right(70, co)
            + f"<div class='abs lab' style='left:76px;top:262px'>Risk Reduction Q4 · {P['nombre']}</div>"
            + f"<h1 class='abs' style='left:76px;right:76px;top:306px;font-size:62px;line-height:1.08;font-weight:800;text-wrap:balance'>{P['q']}</h1>"
            + (logo_chip(P["marca"], 76, 580, 330, 84, 52) + logo_chip(P["marca2"], 76, 680, 330, 84, 40) if P.get("marca2") else logo_chip(P["marca"], 76, 580, 330, 170, lh + 8))
            + f"<div class='abs' style='left:76px;top:{790 if P.get('marca2') else 776}px;width:330px;display:flex;align-items:center;gap:14px'>"
              f"<div style='flex:none;width:58px;height:58px;border-radius:50%;background:{AZURE};display:flex;align-items:center;justify-content:center'>{rr.icon(k, 32)}</div>"
              f"<div style='font-size:22px;font-weight:800;letter-spacing:1px;text-transform:uppercase;line-height:1.1'>{P['nombre']}</div></div>"
            + f"<div class='abs card' style='left:440px;right:76px;top:580px;padding:26px 34px 12px'>"
              f"<div style='font-size:25px;font-weight:800;color:{AZURE};line-height:1.25;margin-bottom:8px'>{P['pos']}</div>{bullets}</div>"
            + f"<div class='abs' style='left:76px;right:76px;top:1050px;background:linear-gradient(135deg,{ROYAL},{NAVY});border-radius:30px;padding:26px 40px;color:#fff;box-shadow:0 14px 36px rgba(20,50,100,.3)'>"
              f"<div style='font-size:18px;font-weight:700;letter-spacing:4px;color:{CYAN};text-transform:uppercase'>Tu puerta de entrada</div>"
              f"<div style='font-size:31px;font-weight:800;margin-top:6px;line-height:1.2'>{P['entrada']}</div></div>"
            + f"<div class='pill' style='top:1262px'>#CPNnetPartners</div>" + cta_btn(1262))
    return page(1350, "lt", body)


# ------------------------------------------------------------------ lanzamiento (claro, 6 tarjetas)
def t_overview(co=False):
    PL = pilares()
    order = ["identidades", "aplicaciones", "endpoints", "datos", "usuarios", "vulnerabilidades"]
    qs = {"identidades": "¿Quién tiene acceso a sus sistemas críticos?", "aplicaciones": "¿Qué riesgos llegan a producción con su software?",
          "endpoints": "¿Puede mantener y recuperar sus equipos en un estado seguro?", "datos": "¿Sabe dónde está su información sensible?",
          "usuarios": "¿Qué ocurre con la información en manos de sus usuarios?", "vulnerabilidades": "¿Las está detectando o realmente las está corrigiendo?"}
    cards = ""
    for i, k in enumerate(order):
        x = 76 if i % 2 == 0 else 76 + 438 + 14
        y = 458 + (i // 2) * 240
        m = PL[k]["marca"]
        lh = 24 if m not in ("vicarius", "action1") else 30
        extra = (f"<div class='chip' style='position:absolute;left:{26+50+lh*3.4:.0f}px;bottom:20px;height:44px;padding:0 20px;border-radius:16px;box-shadow:none'><img src='{LOGOS[PL[k]['marca2']]}' style='height:26px'></div>" if PL[k].get("marca2") else "")
        cards += (f"<div class='abs ocard' style='left:{x}px;top:{y}px;width:438px;height:224px'>"
                  f"<div style='display:flex;align-items:center;gap:12px'><div style='flex:none;width:46px;height:46px;border-radius:50%;background:{AZURE};display:flex;align-items:center;justify-content:center'>{rr.icon(k, 26)}</div>"
                  f"<div style='font-size:21px;font-weight:800;letter-spacing:.5px;text-transform:uppercase'>{PL[k]['nombre']}</div></div>"
                  f"<div style='font-size:20px;font-weight:600;line-height:1.28;margin-top:12px;height:78px'>{qs[k]}</div>"
                  f"<div class='chip' style='position:absolute;left:26px;bottom:20px;height:44px;padding:0 20px;border-radius:16px;box-shadow:none'><img src='{LOGOS[m]}' style='height:{lh}px'></div>{extra}</div>")
    rings = b2.arc(1040, 30, 260, 220, -28, 18, .5, .1, gid="a1", opacity=.6)
    body = (f"<svg class='arcs' width='1080' height='1350' viewBox='0 0 1080 1350'>{rings}</svg>" + head_right(70, co)
            + f"<div class='abs lab' style='left:76px;top:262px'>CPNnet | Risk Reduction Q4</div>"
            + f"<h1 class='abs' style='left:76px;right:76px;top:306px;font-size:56px;line-height:1.08;font-weight:800'>¿Dónde está la próxima <span class='az'>brecha</span> de tus clientes?</h1>"
            + cards + f"<div class='pill' style='top:1262px'>#CPNnetPartners</div>" + cta_btn(1262))
    return page(1350, "lt", body)


# ------------------------------------------------------------------ reel (foto en diagonal)
def t_reel(k):
    P = pilares()[k]
    steps = "".join(f"<div class='step' style='margin:24px 0;font-size:34px'><b>{i+1}</b><div>{x}</div></div>" for i, x in enumerate(P["b"]))
    ring = b2.arc(900, 1010, 150, 150, -28, 14, .75, .0, (CYAN, AZURE), 1, 'a1')
    lh = 60 if P["marca"] != "vicarius" else 70
    qh = P["q"].replace("class='az'", "style='color:#7fd8ee'")
    body = (f"<div class='abs' style='left:0;top:0;width:1080px;height:1090px;clip-path:polygon(0 0,100% 0,100% 78%,0 100%)'><canvas id='bg' width='1080' height='1090'></canvas>"
            f"<div class='abs' style='inset:0;background:linear-gradient(180deg,rgba(3,22,43,.55),rgba(3,22,43,.15) 55%,rgba(3,22,43,.55))'></div></div>"
            f"<svg class='arcs' width='1080' height='1920' viewBox='0 0 1080 1920'>{ring}</svg>"
            f"<div class='abs' style='left:810px;top:920px;width:180px;height:180px;display:flex;align-items:center;justify-content:center'>"
            f"<div style='width:96px;height:96px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 0 40px rgba(111,227,245,.8)'>"
            f"<div style='border-left:34px solid {AZURE};border-top:21px solid transparent;border-bottom:21px solid transparent;margin-left:10px'></div></div></div>"
            + head_right(300, False, dark=True)
            + f"<div class='abs lab' style='left:76px;top:470px'>Risk Reduction Q4 · {P['nombre']}</div>"
            + f"<div class='abs' style='left:76px;top:520px;right:90px;font-size:70px;font-weight:800;line-height:1.06;letter-spacing:-2px;text-shadow:0 3px 22px rgba(0,16,40,.85)'>{qh}</div>"
            + logo_chip(P["marca"], 76, 930, 300, 120, lh)
            + f"<div class='abs' style='left:76px;right:76px;top:1160px'>{steps}</div>"
            + cta_band(1520, 96))
    return page(1920, "dk", body, canv=True, seed={"identidades": 21, "aplicaciones": 23, "endpoints": 25}[k], fx=.7, fy=.45, flip=k == "aplicaciones")


PIECES = [
 dict(n="01", tipo="overview", nombre="risk-reduction-lanzamiento"),
 dict(n="02", tipo="reel", k="identidades", nombre="reel-segura"),
 dict(n="03", tipo="copia", src=SRC3 / "04_webinar-seceon.png", nombre="webinar-seceon"),
 dict(n="04", tipo="post", k="datos", nombre="kriptos"),
 dict(n="05", tipo="reel", k="endpoints", nombre="reel-faronics"),
 dict(n="06", tipo="copia", src=SRC3 / "07_webinar-safetica.png", nombre="webinar-safetica"),
 dict(n="07", tipo="post", k="vulnerabilidades", nombre="vicarius"),
 dict(n="08", tipo="reel", k="aplicaciones", nombre="reel-aikido"),
]
EXTRA_CO = []


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
                pg.set_content(html_for(p), timeout=120000); pg.wait_for_timeout(500)
                pg.screenshot(path=str(out)); pg.close()
            if p not in EXTRA_CO: shots[p["n"]] = out
        br.close()
    return shots


def grid(shots):
    W, H, G = 540, 675, 12
    sheet = Image.new("RGB", (3 * W + 4 * G, 3 * H + 4 * G), (234, 242, 250))
    for i, n in enumerate([p["n"] for p in PIECES]):
        im = Image.open(shots[n]).convert("RGB")
        if im.height > 1350:
            t = (im.height - 1350) // 2; im = im.crop((0, t, 1080, t + 1350))
        r, c = divmod(i, 3)
        sheet.paste(im.resize((W, H)), (G + c * (W + G), G + r * (H + G)))
    sheet.save(OUT / "grilla-risk-reduction.png", optimize=True)


if __name__ == "__main__":
    s = render(); grid(s); print("ok", len(s))
