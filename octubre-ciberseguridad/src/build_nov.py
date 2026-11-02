#!/usr/bin/env python3
"""Grilla de noviembre 2026 para partners (14 piezas) con la mascota CPNnet.
Contenido basado solo en sitios y blogs de las marcas. Salida: ../v7-noviembre/{1080x1350,1080x1920}/ + grilla-noviembre.png
Uso: python3 build_nov.py [n ...]"""
import re, sys, shutil
from playwright.sync_api import sync_playwright
from PIL import Image
import build as b1
import build_v2 as b2
import build_rr as rr
import build_rr2 as m
import build_rr3 as r3
import build_invitacion_faronics as inv

ROOT = b1.ROOT
OUT = ROOT / "v7-noviembre"
for d in ("1080x1350", "1080x1920"):
    (OUT / d).mkdir(parents=True, exist_ok=True)
NAVY, ROYAL, AZURE, SKY, CYAN = m.NAVY, m.ROYAL, m.AZURE, m.SKY, m.CYAN
rr.ICON["soc"] = "<path d='M20 4 6 10v9c0 8 6 13 14 17 8-4 14-9 14-17v-9z'/><path d='M14 20l4 4 8-8'/>"
ICONO = dict(segura="identidades", kriptos="datos", safetica="usuarios", action1="vulnerabilidades", vicarius="vulnerabilidades",
             aikido="aplicaciones", faronics="endpoints", seceon="soc")
AREA = dict(segura="Identidades", kriptos="Datos", safetica="Usuarios", action1="Vulnerabilidades", vicarius="Vulnerabilidades",
            aikido="Aplicaciones", faronics="Endpoints", seceon="Operaciones de seguridad")
HL = lambda t, w: t.replace(w, f"<span class='az'>{w}</span>")

def chip(marca, x, y, w, h, lh):
    if marca == "seceon":
        return (f"<div class='abs chip' style='left:{x}px;top:{y}px;width:{w}px;height:{h}px'>"
                f"<span style='font-size:{int(lh*0.95)}px;font-weight:800;letter-spacing:2px;color:#fff'>SECEON</span></div>")
    return m.logo_chip(marca, x, y, w, h, lh)

# ---- datos de las piezas (fuentes: sitios/blogs de cada marca)
P = {
 "domum": dict(marca="segura", q=HL("¿Cómo acceden sus proveedores a los sistemas críticos?", "proveedores"), pos="Acceso de terceros",
     b=["Acceso desde el navegador, sin VPN, con enlace único que vence", "Sesión en contenedor aislado, grabada y con monitoreo en vivo", "Control por sistema, horario y ubicación"],
     entrada="Revisión de accesos de terceros", seed=31),
 "kriptos_ia": dict(marca="kriptos", q=HL("¿Qué información puede ver Copilot en su empresa?", "Copilot"), pos="Gobierno de datos para la IA",
     b=["Etiqueta documentos como AI-Safe, AI-Restricted o AI-Prohibited", "Se integra con Microsoft Purview para que Copilot use solo datos apropiados", "Clasifica también datos no personales: propiedad intelectual y modelos financieros"],
     entrada="Data Discovery Assessment"),
 "offboarding": dict(marca="safetica", q=HL("¿Qué se llevan los colaboradores que se van?", "se van"), pos="Salida de colaboradores y riesgo interno",
     b=["Un buen offboarding: recuperar equipos, revocar accesos y cambiar contraseñas", "Detecta descargas masivas atípicas de quien se va", "Bloquea envíos a USB o a nubes personales"],
     entrada="DLP/Insider Risk Assessment"),
 "action1_remoto": dict(marca="action1", q=HL("¿Cómo parchea equipos remotos sin VPN?", "sin VPN"), pos="Gestión de parches",
     b=["Agente con conexión saliente a la nube: sin VPN ni servidor local", "Parchea sistema operativo y software de terceros", "Si el equipo estaba apagado, recibe la actualización al reconectarse"],
     entrada="Assessment de patch compliance", seed=33),
 "seceon_mssp": dict(marca="seceon", q=HL("¿Cómo opera un MSSP la seguridad de muchos clientes?", "MSSP"), pos="Plataforma para MSSP",
     b=["Modelo multi-tenant y multi-nivel desde una sola instancia", "aiSIEM y aiXDR sobre un mismo motor de IA", "Aislamiento y roles por cliente y por nivel"],
     entrada="Demo de la plataforma para tu operación"),
 "aikido_dev": dict(marca="aikido", q=HL("¿Qué parte de su desarrollo está sin vigilancia?", "sin vigilancia"), pos="Seguridad de código a nube",
     b=["Código, dependencias, secretos y nube en una sola plataforma", "Detecta claves y contraseñas expuestas en el código", "CSPM sin agentes para AWS, Azure y GCP"],
     entrada="Demo conectando uno o pocos repositorios"),
 "vicarius_sinparche": dict(marca="vicarius", q=HL("¿Qué hace cuando no existe un parche?", "no existe un parche"), pos="Remediación de vulnerabilidades",
     b=["Patching automatizado, también de aplicaciones de terceros", "Protección sin parche mientras llega la actualización", "Scripting para corregir configuraciones"],
     entrada="Assessment de remediación", seed=35),
 "segura_epm": dict(marca="segura", q=HL("¿Cuántos usuarios tienen permisos de administrador en sus equipos?", "administrador"), pos="Mínimo privilegio en endpoints",
     b=["Elevación de privilegios a demanda y justo a tiempo", "Elevación por rol en Windows y Linux", "Secretos de DevOps administrados en bóveda"],
     entrada="PAM Assessment"),
 "faronics_restore": dict(marca="faronics", q=HL("Detectar no basta: ¿puede recuperar sus equipos?", "recuperar"), pos="Recuperación de endpoints",
     b=["Un reinicio devuelve el equipo a su estado base", "No reemplaza al antivirus ni al EDR: es una capa de recuperación", "Actualizaciones programadas: descongelar, parchear y volver a congelar"],
     entrada="Piloto en 10 a 20 equipos de alto soporte"),
 "kriptos_acceso": dict(marca="kriptos", q=HL("¿Quién puede ver qué en sus documentos?", "ver qué"), pos="Gobierno de acceso a datos",
     b=["Visibilidad de quién accede a documentos, carpetas y unidades", "Detecta datos sobreexpuestos y accesos de riesgo", "Corrige permisos a escala"],
     entrada="Data Discovery Assessment", seed=37),
}
for k, v in P.items():
    v["nombre"] = AREA[v["marca"]]; v["icon"] = ICONO[v["marca"]]


def t_post(key):
    d = P[key]; bullets = "".join(f"<div class='sol-b'><div>{b}</div></div>" for b in d["b"])
    rings = b2.arc(1040, 30, 260, 220, -28, 18, .5, .1, gid="a1", opacity=.6)
    fs = 52 if len(re.sub(r"<[^>]+>", "", d["q"])) <= 48 else 43
    body = (f"<svg class='arcs' width='1080' height='1350' viewBox='0 0 1080 1350'>{rings}</svg>" + m.head_right(70, False)
            + f"<div class='abs lab' style='left:76px;top:262px'>Noviembre · {d['nombre']}</div>"
            + f"<h1 class='abs' style='left:76px;right:76px;top:306px;font-size:{fs}px;line-height:1.08;font-weight:800;text-wrap:balance'>{d['q']}</h1>"
            + chip(d["marca"], 76, 474, 330, 92, 56)
            + f"<div class='abs' style='left:440px;top:491px;display:flex;align-items:center;gap:14px'><div style='flex:none;width:58px;height:58px;border-radius:50%;background:{AZURE};display:flex;align-items:center;justify-content:center'>{rr.icon(d['icon'], 32)}</div>"
              f"<div style='font-size:22px;font-weight:800;letter-spacing:1px;text-transform:uppercase;line-height:1.1'>{d['nombre']}</div></div>"
            + r3.mascot(60, 590, 470)
            + f"<div class='abs card' style='left:400px;right:76px;top:590px;padding:24px 32px 10px'><div style='font-size:25px;font-weight:800;color:{AZURE};line-height:1.25;margin-bottom:8px'>{d['pos']}</div>{bullets}</div>"
            + f"<div class='abs' style='left:76px;right:76px;top:1085px;background:linear-gradient(135deg,{ROYAL},{NAVY});border-radius:30px;padding:24px 40px;color:#fff;box-shadow:0 14px 36px rgba(20,50,100,.3)'>"
              f"<div style='font-size:18px;font-weight:700;letter-spacing:4px;color:{CYAN};text-transform:uppercase'>Tu puerta de entrada</div>"
              f"<div style='font-size:31px;font-weight:800;margin-top:6px;line-height:1.2'>{d['entrada']}</div></div>"
            + "<div class='pill' style='top:1262px'>#CPNnetPartners</div>" + m.cta_btn(1262))
    return m.page(1350, "lt", body)


def t_reel(key):
    d = P[key]
    steps = "".join(f"<div class='step' style='margin:24px 0;font-size:34px'><b>{i+1}</b><div>{x}</div></div>" for i, x in enumerate(d["b"]))
    ring = b2.arc(900, 1010, 150, 150, -28, 14, .75, .0, (CYAN, AZURE), 1, 'a1')
    qh = d["q"].replace("class='az'", "style='color:#7fd8ee'")
    body = (f"<div class='abs' style='left:0;top:0;width:1080px;height:1090px;clip-path:polygon(0 0,100% 0,100% 78%,0 100%)'><canvas id='bg' width='1080' height='1090'></canvas>"
            f"<div class='abs' style='inset:0;background:linear-gradient(180deg,rgba(3,22,43,.55),rgba(3,22,43,.15) 55%,rgba(3,22,43,.55))'></div></div>"
            f"<svg class='arcs' width='1080' height='1920' viewBox='0 0 1080 1920'>{ring}</svg>"
            + m.head_right(300, False, dark=True)
            + f"<div class='abs lab' style='left:76px;top:470px'>Noviembre · {d['nombre']}</div>"
            + f"<div class='abs' style='left:76px;top:520px;right:90px;font-size:66px;font-weight:800;line-height:1.06;letter-spacing:-2px;text-shadow:0 3px 22px rgba(0,16,40,.85)'>{qh}</div>"
            + chip(d["marca"], 76, 930, 300, 120, 60)
            + f"<div class='abs' style='left:76px;right:76px;top:1160px'>{steps}</div>" + m.cta_band(1520, 96)
            + r3.mascot(715, 700, 440, dark=True))
    return m.page(1920, "dk", body, canv=True, seed=d.get("seed", 31), fx=.7, fy=.45, flip=d.get("seed", 31) % 4 == 1)


def t_cierre():
    h = r3.t_overview()
    h = h.replace("¿Dónde está la próxima <span class='az'>brecha</span> de tus clientes?", "Noviembre: ¿dónde <span class='az'>empezamos</span> con tu cliente?")
    return h.replace("CPNnet | Mes de la Ciberseguridad", "CPNnet | Cierre de noviembre")


def tall(html, shift=285):
    html = re.sub(r"(class='cv \w+' style='height:)1350px", r"\g<1>1920px", html, count=1)
    html = html.replace("<div class='grid'></div>", f"<div class='grid'></div><div style='position:absolute;left:0;top:{shift}px;width:1080px;height:1350px'>", 1)
    return html.replace("<img class='logo'", f"</div><img class='logo' style='top:{80 + shift}px'", 1)


def inv_html(name, **kw):
    return inv.make(name, inv.L[name], **kw)

# n, fecha, tipo, nombre, generador
PIECES = [
 ("01", "Lun 2 nov", "inv", "invitacion-faronics-cloud", lambda n: inv_html(n)),
 ("02", "Mié 4 nov", "inv", "recordatorio-faronics-cloud", lambda n: inv_html(n, kicker="Mañana te esperamos", fecha="Mañana · 5 de noviembre", btn_text="Reserva tu lugar")),
 ("03", "Jue 5 nov", "inv", "hoy-faronics-cloud", lambda n: inv_html(n, kicker="Hoy te esperamos", fecha="Hoy · 5 de noviembre", btn_text="Te esperamos hoy")),
 ("04", "Vie 6 nov", "reel", "reel-segura-domum", lambda: t_reel("domum")),
 ("05", "Lun 9 nov", "post", "kriptos-ia-copilot", lambda: t_post("kriptos_ia")),
 ("06", "Mié 11 nov", "post", "safetica-offboarding", lambda: t_post("offboarding")),
 ("07", "Vie 13 nov", "reel", "reel-action1-remotos", lambda: t_reel("action1_remoto")),
 ("08", "Lun 16 nov", "post", "seceon-mssp", lambda: t_post("seceon_mssp")),
 ("09", "Mié 18 nov", "post", "aikido-desarrollo", lambda: t_post("aikido_dev")),
 ("10", "Vie 20 nov", "reel", "reel-vicarius-sin-parche", lambda: t_reel("vicarius_sinparche")),
 ("11", "Lun 23 nov", "post", "segura-privilegios", lambda: t_post("segura_epm")),
 ("12", "Mié 25 nov", "post", "faronics-recuperacion", lambda: t_post("faronics_restore")),
 ("13", "Vie 27 nov", "reel", "reel-kriptos-acceso", lambda: t_reel("kriptos_acceso")),
 ("14", "Lun 30 nov", "post", "cierre-noviembre", lambda: t_cierre()),
]

if __name__ == "__main__":
    only = set(sys.argv[1:])
    with sync_playwright() as pw:
        br = pw.chromium.launch(executable_path="/opt/pw-browsers/chromium", args=["--no-sandbox"])
        for n, fecha, tipo, name, fn in PIECES:
            if only and n not in only: continue
            jobs = []
            if tipo == "inv":
                jobs = [("1080x1350", 1350, fn("1080x1350")), ("1080x1920", 1920, fn("1080x1920"))]
            elif tipo == "reel":
                jobs = [("1080x1920", 1920, fn())]
            else:
                h = fn(); jobs = [("1080x1350", 1350, h), ("1080x1920", 1920, tall(h))]
            for folder, hh, doc in jobs:
                pg = br.new_page(viewport={"width": 1080, "height": hh}); pg.set_content(doc, timeout=120000); pg.wait_for_timeout(600)
                pg.screenshot(path=str(OUT / folder / f"{n}_{name}_{folder}.png")); pg.close()
        br.close()
    W, H, G = 405, 506, 10
    cols = 3; rows = (len(PIECES) + cols - 1) // cols
    sheet = Image.new("RGB", (cols * W + (cols + 1) * G, rows * H + (rows + 1) * G), (234, 242, 250))
    for i, (n, fecha, tipo, name, fn) in enumerate(PIECES):
        f = OUT / ("1080x1920" if tipo == "reel" else "1080x1350") / f"{n}_{name}_{'1080x1920' if tipo == 'reel' else '1080x1350'}.png"
        if not f.exists(): continue
        im = Image.open(f).convert("RGB")
        if im.height > 1350: t = (im.height - 1350) // 2; im = im.crop((0, t, 1080, t + 1350))
        sheet.paste(im.resize((W, H)), (G + (i % cols) * (W + G), G + (i // cols) * (H + G)))
    sheet.save(OUT / "grilla-noviembre.png", optimize=True)
    print("ok")
