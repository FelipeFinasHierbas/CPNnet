#!/usr/bin/env python3
"""Grilla de noviembre 2026 (v2): cada pieza con la paleta y el patrón de su marca + mascota CPNnet.
Colores de marca según Brandfetch (referenciales); Kriptos y Seceon usan la paleta CPNnet (sin dato).
Salida: ../v7-noviembre/{1080x1350,1080x1920}/, ../remotion-reels/public/bg_*.jpg  Uso: python3 build_nov2.py [n ...]"""
import re, random, sys, math
from playwright.sync_api import sync_playwright
from PIL import Image
import build as b1
import build_rr as rr
import build_rr2 as m
import build_rr3 as r3

ROOT = b1.ROOT
OUT = ROOT / "v7-noviembre"
PUB = ROOT / "remotion-reels/public"
for d in ("1080x1350", "1080x1920"): (OUT / d).mkdir(parents=True, exist_ok=True)
rr.ICON["soc"] = "<path d='M20 4 6 10v9c0 8 6 13 14 17 8-4 14-9 14-17v-9z'/><path d='M14 20l4 4 8-8'/>"
CP = dict(dark="#0a1a35", base="#203C71", acc="#6fe3f5", acc2="#3A84BD", hi="#9AEEFA", onacc="#0a1a35")
BR = {
 "segura":   dict(dark="#001F1B", base="#003A32", acc="#00D1B5", acc2="#00A892", hi="#4DE8D0", onacc="#012B25", pat="rings", nombre="Segura", area="Identidades", icon="identidades"),
 "aikido":   dict(dark="#010024", base="#3D3192", acc="#7C6FF5", acc2="#5A4BDB", hi="#B9B1FF", onacc="#ffffff", pat="diag", nombre="Aikido", area="Aplicaciones", icon="aplicaciones"),
 "safetica": dict(dark="#052B4D", base="#0A6FC2", acc="#00BDA5", acc2="#009C88", hi="#A5F3FF", onacc="#032a26", pat="waves", nombre="Safetica", area="Usuarios", icon="usuarios"),
 "faronics": dict(dark="#0E1317", base="#2B1216", acc="#D52027", acc2="#A8171D", hi="#FF7378", onacc="#ffffff", pat="squares", nombre="Faronics", area="Endpoints", icon="endpoints"),
 "action1":  dict(dark="#021E33", base="#02457A", acc="#00CCFF", acc2="#00A6D1", hi="#7BE6FF", onacc="#021E33", pat="dots", nombre="Action1", area="Vulnerabilidades", icon="vulnerabilidades"),
 "vicarius": dict(dark="#0E1A38", base="#182954", acc="#0EC0A4", acc2="#0A9C85", hi="#5FE3CE", onacc="#08211e", pat="hex", nombre="Vicarius", area="Vulnerabilidades", icon="vulnerabilidades"),
 "sophos":   dict(dark="#0A143A", base="#2006F7", acc="#7A8CFF", acc2="#5468F0", hi="#B9C2FF", onacc="#0A143A", pat="shields", nombre="Sophos", area="Red y endpoint", icon="soc"),
 "sonicwall": dict(dark="#0A1233", base="#285AE6", acc="#F5F5F5", acc2="#D5DCE8", hi="#BFD0FF", onacc="#0A1233", pat="bricks", nombre="SonicWall", area="Seguridad de red", icon="soc"),
 "kriptos":  dict(CP, pat="tiles", nombre="Kriptos", area="Datos", icon="datos"),
 "seceon":   dict(CP, pat="radar", nombre="Seceon", area="Operaciones de seguridad", icon="soc"),
}
def rgb(h): h = h.lstrip("#"); return ",".join(str(int(h[i:i+2], 16)) for i in (0, 2, 4))

def pattern(kind, c, w, h, seed=3):
    R = random.Random(seed); a = c["acc"]; out = []
    if kind == "rings":
        for r in range(100, 1400, 95): out.append(f"<circle cx='{w*.9:.0f}' cy='{h*.12:.0f}' r='{r}' fill='none' stroke='{a}' stroke-opacity='{max(.04, .22 - r/9000):.2f}' stroke-width='2'/>")
    elif kind == "diag":
        for x in range(-h, w + h, 78): out.append(f"<line x1='{x}' y1='0' x2='{x+h}' y2='{h}' stroke='{c['hi']}' stroke-opacity='.07' stroke-width='3'/>")
        for i in range(6): out.append(f"<text x='{R.randint(40,w-160)}' y='{R.randint(80,h-40)}' font-size='64' font-family='Montserrat' font-weight='800' fill='{c['hi']}' fill-opacity='.08'>{R.choice(['&lt;/&gt;','{ }','&lt;/&gt;','//'])}</text>")
    elif kind == "squares":
        s = 84
        for gx in range(0, w // s + 1):
            for gy in range(0, h // s + 1):
                if R.random() < .16: out.append(f"<rect x='{gx*s+8}' y='{gy*s+8}' width='{s-16}' height='{s-16}' rx='6' fill='{a}' fill-opacity='{R.choice([.07,.12,.18])}'/>")
                elif R.random() < .35: out.append(f"<rect x='{gx*s+8}' y='{gy*s+8}' width='{s-16}' height='{s-16}' rx='6' fill='none' stroke='#fff' stroke-opacity='.06' stroke-width='2'/>")
    elif kind == "dots":
        pts = [(R.randint(0, w), R.randint(0, h)) for _ in range(46)]
        for i, p in enumerate(pts):
            for q in pts[i+1:]:
                if math.dist(p, q) < 260: out.append(f"<line x1='{p[0]}' y1='{p[1]}' x2='{q[0]}' y2='{q[1]}' stroke='{a}' stroke-opacity='.16' stroke-width='2'/>")
        for p in pts: out.append(f"<circle cx='{p[0]}' cy='{p[1]}' r='{R.randint(4,9)}' fill='{a}' fill-opacity='.35'/>")
    elif kind == "hex":
        r = 56
        for gx in range(-1, int(w / (r * 1.5)) + 2):
            for gy in range(-1, int(h / (r * 1.74)) + 2):
                cx = gx * r * 1.5; cy = gy * r * 1.74 + (r * .87 if gx % 2 else 0)
                pts = " ".join(f"{cx + r*math.cos(math.pi/3*k):.0f},{cy + r*math.sin(math.pi/3*k):.0f}" for k in range(6))
                out.append(f"<polygon points='{pts}' fill='{a if R.random()<.06 else 'none'}' fill-opacity='.18' stroke='{a}' stroke-opacity='.13' stroke-width='2'/>")
    elif kind == "waves":
        for k in range(14):
            y0 = h * (k / 13); d = f"M0,{y0:.0f} " + " ".join(f"Q{x+110},{y0 + (70 if (x//220)%2==0 else -70) + k*4:.0f} {x+220},{y0:.0f}" for x in range(0, w + 220, 220))
            out.append(f"<path d='{d}' fill='none' stroke='{c['hi']}' stroke-opacity='{.05 + .01*(k%4):.2f}' stroke-width='3'/>")
    elif kind == "tiles":
        for _ in range(26):
            x, y, rot = R.randint(-40, w), R.randint(-40, h), R.randint(-25, 25)
            out.append(f"<g transform='rotate({rot} {x} {y})'><rect x='{x}' y='{y}' width='110' height='140' rx='14' fill='{a}' fill-opacity='.06' stroke='{a}' stroke-opacity='.18' stroke-width='2'/>"
                       f"<rect x='{x+16}' y='{y+22}' width='60' height='8' rx='4' fill='{a}' fill-opacity='.22'/><rect x='{x+16}' y='{y+44}' width='78' height='8' rx='4' fill='{a}' fill-opacity='.14'/><rect x='{x+16}' y='{y+66}' width='46' height='8' rx='4' fill='{a}' fill-opacity='.14'/></g>")
    elif kind == "shields":
        for gx in range(-1, 9):
            for gy in range(-1, int(h / 150) + 2):
                x = gx * 150 + (75 if gy % 2 else 0); y = gy * 150
                out.append(f"<path d='M{x+60},{y+20} L{x+100},{y+36} V{y+72} C{x+100},{y+98} {x+82},{y+112} {x+60},{y+122} C{x+38},{y+112} {x+20},{y+98} {x+20},{y+72} V{y+36} Z' fill='{a if R.random()<.08 else 'none'}' fill-opacity='.16' stroke='{a}' stroke-opacity='.14' stroke-width='2'/>")
    elif kind == "bricks":
        bh, bw = 54, 150
        for r in range(int(h / bh) + 2):
            for cx in range(-1, int(w / bw) + 2):
                x = cx * bw + (bw / 2 if r % 2 else 0)
                out.append(f"<rect x='{x:.0f}' y='{r*bh}' width='{bw-6}' height='{bh-6}' rx='5' fill='{c['hi'] if R.random()<.07 else 'none'}' fill-opacity='.14' stroke='{c['hi']}' stroke-opacity='.10' stroke-width='2'/>")
    elif kind == "radar":
        cx, cy = w * .82, h * .18
        for r in range(90, 1500, 110): out.append(f"<circle cx='{cx:.0f}' cy='{cy:.0f}' r='{r}' fill='none' stroke='{a}' stroke-opacity='.12' stroke-width='2'/>")
        for ang in range(0, 360, 30): out.append(f"<line x1='{cx:.0f}' y1='{cy:.0f}' x2='{cx + 1500*math.cos(math.radians(ang)):.0f}' y2='{cy + 1500*math.sin(math.radians(ang)):.0f}' stroke='{a}' stroke-opacity='.07' stroke-width='2'/>")
    return f"<svg class='abs' style='left:0;top:0' width='{w}' height='{h}' viewBox='0 0 {w} {h}'>{''.join(out)}</svg>"

CSS = lambda H, c: b1.FONTS + f"""
*{{box-sizing:border-box;margin:0;padding:0}}
body{{width:1080px;height:{H}px;overflow:hidden;font-family:'Montserrat',sans-serif;color:#fff;background:{c['dark']}}}
.abs{{position:absolute}} .hl{{color:{c['hi']}}}
.chip{{display:flex;align-items:center;justify-content:center;border-radius:28px;background:rgba(0,0,0,.34);border:2px solid {c['acc']}66;box-shadow:0 14px 36px rgba(0,0,0,.35)}}
"""
def check(c, size=34, onlight=False):
    return (f"<div style='flex:none;width:{size}px;height:{size}px;border-radius:50%;background:{c['acc']};display:flex;align-items:center;justify-content:center'>"
            f"<svg width='{size*.62:.0f}' height='{size*.62:.0f}' viewBox='0 0 24 24'><path d='M6 12.5l4 4 8-9' fill='none' stroke='{c['onacc']}' stroke-width='3.4' stroke-linecap='round' stroke-linejoin='round'/></svg></div>")
WORD = {"seceon": "SECEON", "sophos": "SOPHOS", "sonicwall": "SONICWALL"}
def logo_chip(marca, x, y, w, h, lh):
    if marca in WORD:
        k = .95 if marca != "sonicwall" else .72
        return f"<div class='abs chip' style='left:{x}px;top:{y}px;width:{w}px;height:{h}px'><span style='font-size:{int(lh*k)}px;font-weight:800;letter-spacing:3px'>{WORD[marca]}</span></div>"
    return f"<div class='abs chip' style='left:{x}px;top:{y}px;width:{w}px;height:{h}px'><img src='{m.LOGOS[marca]}' style='height:{lh}px;max-width:{w-48}px;object-fit:contain'></div>"
def flags(size=34):
    return "".join(rr.FLAG[k].replace("<svg ", f"<svg width='{size}' height='{size}' ") for k in ("cl", "pe", "us", "co"))
def header(c, y=64):
    return (f"<img class='abs' src='{b1.LOGO}' style='left:76px;top:{y}px;width:200px'>"
            f"<div class='abs' style='right:76px;top:{y+18}px;display:flex;align-items:center;gap:12px'><span style='font-size:15px;font-weight:600;letter-spacing:1.5px;opacity:.85'>CHILE · PERÚ · COLOMBIA</span><span style='display:flex;gap:8px'>{flags(34)}</span></div>")
def doc(H, c, bg, body):
    return f"<html><head><meta charset='utf-8'><style>{CSS(H, c)}</style></head><body><div class='abs' style='left:0;top:0;width:1080px;height:{H}px;overflow:hidden'>{bg}{body}</div></body></html>"

def dark_bg(c, H, kind=None, seed=3):
    return (f"<div class='abs' style='inset:0;background:radial-gradient(900px 700px at 90% 8%,{c['acc']}38,transparent 62%),linear-gradient(165deg,{c['dark']} 0%,{c['base']} 120%)'></div>"
            + pattern(kind or c['pat'], c, 1080, H, seed))

# ------------------------------------------------------------------ contenido por pieza
D = {
 "domum": dict(marca="segura", q="¿Cómo acceden sus <span class='hl'>proveedores</span> a los sistemas críticos?", pos="Acceso de terceros",
   ctx="Segura propone reemplazar las VPN tradicionales por acceso desde el navegador, con la sesión grabada.",
   b=["Acceso sin VPN, con enlace único que vence", "Sesión en contenedor aislado, grabada y con monitoreo en vivo", "Control por sistema, horario y ubicación"], entrada="Revisión de accesos de terceros", seed=5),
 "kriptos_ia": dict(marca="kriptos", q="¿Qué información puede ver <span class='hl'>Copilot</span> en su empresa?", pos="Gobierno de datos para la IA",
   ctx="Antes de habilitar la IA, Kriptos clasifica el contenido para que solo use datos apropiados.",
   b=["Etiqueta documentos como AI-Safe, AI-Restricted o AI-Prohibited", "Se integra con Microsoft Purview para limitar lo que Copilot puede mostrar", "Clasifica también propiedad intelectual y modelos financieros"], entrada="Data Discovery Assessment"),
 "offboarding": dict(marca="safetica", q="¿Qué se llevan los colaboradores que <span class='hl'>se van</span>?", pos="Salida de colaboradores y riesgo interno",
   ctx="Safetica recomienda desactivar accesos de inmediato y resguardar los datos de equipos y cuentas.",
   b=["Plan de salida: recuperar equipos, revocar accesos y cambiar contraseñas", "Detecta descargas masivas atípicas de quien se va", "Bloquea envíos a USB o a nubes personales"], entrada="DLP/Insider Risk Assessment"),
 "action1_remoto": dict(marca="action1", q="¿Cómo parchea equipos remotos <span class='hl'>sin VPN</span>?", pos="Gestión de parches",
   ctx="Action1 se administra desde el navegador y sus agentes se conectan a la nube: sin VPN ni servidor local.",
   b=["Agente con conexión saliente a la nube", "Parchea sistema operativo y software de terceros", "Si el equipo estaba apagado, recibe la actualización al reconectarse"], entrada="Assessment de patch compliance", seed=7),
 "seceon_mssp": dict(marca="seceon", q="¿Cómo opera un <span class='hl'>MSSP</span> la seguridad de muchos clientes?", pos="Plataforma para MSSP",
   ctx="Seceon describe una jerarquía de MSSP, MSSP regional y cliente final, gestionada desde una sola plataforma.",
   b=["Modelo multi-tenant y multi-nivel desde una sola instancia", "aiSIEM y aiXDR sobre un mismo motor de IA", "Aislamiento y roles por cliente y por nivel"], entrada="Demo de la plataforma para tu operación"),
 "aikido_dev": dict(marca="aikido", q="¿Qué parte de su desarrollo está <span class='hl'>sin vigilancia</span>?", pos="Seguridad de código a nube",
   ctx="Aikido reúne en una sola plataforma lo que suele estar repartido en varias herramientas.",
   b=["Código, dependencias, secretos y nube en un mismo lugar", "Detecta claves y contraseñas expuestas en el código", "CSPM sin agentes para AWS, Azure y GCP"], entrada="Demo conectando uno o pocos repositorios"),
 "vicarius_sinparche": dict(marca="vicarius", q="¿Qué hace cuando <span class='hl'>no existe un parche</span>?", pos="Remediación de vulnerabilidades",
   ctx="vRx cubre Windows, macOS y Linux y puede proteger una aplicación vulnerable mientras llega la actualización.",
   b=["Patching automatizado, también de aplicaciones de terceros", "Protección sin parche mientras llega la actualización", "Scripting para corregir configuraciones"], entrada="Assessment de remediación", seed=9),
 "segura_epm": dict(marca="segura", q="¿Cuántos usuarios son <span class='hl'>administradores</span> de sus equipos?", pos="Mínimo privilegio en endpoints",
   ctx="Segura ofrece elevación de privilegios justo a tiempo y a demanda para Windows y Linux.",
   b=["Elevación de privilegios a demanda y justo a tiempo", "Elevación por rol en Windows y Linux", "Secretos de DevOps administrados en bóveda"], entrada="PAM Assessment"),
 "faronics_restore": dict(marca="faronics", q="Detectar no basta: ¿puede <span class='hl'>recuperar</span> sus equipos?", pos="Recuperación de endpoints",
   ctx="Faronics lo plantea como una capa más: no reemplaza al antivirus ni al EDR, asegura la recuperación.",
   b=["Un reinicio devuelve el equipo a su estado base", "Actualizaciones programadas: descongelar, parchear y volver a congelar", "Administración en la nube con Deep Freeze Cloud"], entrada="Piloto en 10 a 20 equipos de alto soporte"),
 "sonicwall_atp": dict(marca="sonicwall", q="¿Qué pasa con los archivos <span class='hl'>desconocidos</span> que llegan a su red?", pos="Sandbox en la nube",
   ctx="SonicWall describe Capture ATP como un sandbox en la nube con varios motores de análisis, incluida RTDMI.",
   b=["Ejecuta los archivos sospechosos en un entorno aislado", "Solo envía los que no se conocen como limpios ni maliciosos", "El veredicto vuelve al firewall"], entrada="Revisión de su firewall SonicWall", seed=13),
 "sonicwall_cse": dict(marca="sonicwall", q="¿Su firewall protege a quienes trabajan <span class='hl'>fuera de la oficina</span>?", pos="Cloud Secure Edge",
   ctx="SonicWall plantea Cloud Secure Edge como complemento de sus firewalls, no como reemplazo.",
   b=["Lleva la inspección de Capture ATP a los equipos remotos, con un agente", "ZTNA en la nube para reemplazar VPN heredadas", "Acceso de mínimo privilegio según identidad, dispositivo y recurso"], entrada="Demo de Cloud Secure Edge"),
 "sophos_red": dict(marca="sophos", q="¿Su red y sus equipos <span class='hl'>se hablan</span> entre sí?", pos="Red y endpoint conectados",
   ctx="Sophos Central comparte amenazas y salud de los dispositivos entre firewall, endpoint y ZTNA, desde una sola consola.",
   b=["Security Heartbeat: estado rojo, ámbar o verde de cada dispositivo en tiempo real", "Si el endpoint detecta una amenaza, firewall y ZTNA limitan su acceso", "Al limpiarse, el equipo vuelve a verde y recupera el acceso"], entrada="Renovación de firewall o ZTNA"),
 "sophos_ransom": dict(marca="sophos", q="¿Cómo empieza un ataque de <span class='hl'>ransomware</span>?", pos="State of Ransomware 2026",
   ctx="Informe global de Sophos 2026, con 2.158 organizaciones de 17 países, incluido Chile.",
   b=["79% de los ataques parte de identidades comprometidas", "Correo malicioso (26%) y phishing (24%) suman la mitad de los casos", "XDR y MDR de Sophos correlacionan firewall y endpoint"], entrada="Revisión de identidades, firewall y respuesta", seed=15),
 "kriptos_acceso": dict(marca="kriptos", q="¿Quién puede <span class='hl'>ver qué</span> en sus documentos?", pos="Gobierno de acceso a datos",
   ctx="Kriptos cruza la sensibilidad del documento, el comportamiento del usuario y el contexto de uso compartido.",
   b=["Visibilidad de quién accede a documentos, carpetas y unidades", "Detecta datos sobreexpuestos y accesos de riesgo", "Corrige permisos a escala"], entrada="Data Discovery Assessment", seed=11),
}
for v in D.values(): v.update(BR[v["marca"]] | {"marca": v["marca"]})

def fs_for(q): return 54 if len(re.sub(r"<[^>]+>", "", q)) <= 46 else 46

def post_A(d, H, dy):
    c = d; bullets = "".join(f"<div style='display:flex;gap:18px;align-items:flex-start;padding:16px 22px;border-radius:22px;background:rgba(255,255,255,.07);margin-bottom:12px'>{check(c,34)}<div style='font-size:25px;font-weight:600;line-height:1.28'>{b}</div></div>" for b in d["b"])
    ico = rr.icon(c['icon'], 32).replace("stroke='#fff'", "stroke='" + c['onacc'] + "'")
    body = (header(c, 64 + dy) + logo_chip(d["marca"], 76, 218 + dy, 380, 112, 66)
      + f"<div class='abs' style='left:490px;top:{245+dy}px;display:flex;align-items:center;gap:14px'><div style='width:58px;height:58px;border-radius:50%;background:{c['acc']};display:flex;align-items:center;justify-content:center'>{ico}</div><div style='font-size:21px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;line-height:1.15;max-width:360px'>{d['area']}</div></div>"
      + f"<h1 class='abs' style='left:76px;right:76px;top:{345+dy}px;font-size:{fs_for(d['q'])}px;line-height:1.08;font-weight:800;letter-spacing:-1px;text-wrap:balance'>{d['q']}</h1>"
      + f"<div class='abs' style='left:76px;right:76px;top:{505+dy}px;padding:20px 28px;border-radius:26px;background:rgba(0,0,0,.28);border:2px solid {c['acc']}55'><div style='font-size:15px;font-weight:700;letter-spacing:4px;color:{c['hi']};text-transform:uppercase;margin-bottom:6px'>{d['pos']}</div><div style='font-size:26px;font-weight:500;line-height:1.3'>{d['ctx']}</div></div>"
      + f"<div class='abs' style='left:76px;width:620px;top:{680+dy}px'>{bullets}</div>"
      + r3.mascot(700, 690 + dy, 420, dark=True)
      + f"<div class='abs' style='left:76px;right:76px;top:{1110+dy}px;border-radius:28px;background:linear-gradient(135deg,{c['acc']},{c['acc2']});padding:20px 38px;color:{c['onacc']}'><div style='font-size:17px;font-weight:800;letter-spacing:4px;text-transform:uppercase;opacity:.8'>Tu puerta de entrada</div><div style='font-size:30px;font-weight:800;margin-top:4px'>{d['entrada']}</div></div>"
      + footer(c, 1262 + dy))
    return body

def footer(c, y):
    return (f"<div class='abs' style='left:76px;top:{y}px;border-radius:999px;background:rgba(255,255,255,.14);border:2px solid {c['acc']}88;padding:12px 32px;font-size:26px;font-weight:700'>#CPNnetPartners</div>"
            f"<div class='abs' style='right:76px;top:{y}px;border-radius:999px;background:{c['acc']};color:{c['onacc']};padding:14px 32px;font-size:22px;font-weight:800'>Habla con tu ejecutivo CPNnet</div>")

def post_B(d, H, dy):
    c = d; light = "#F2F6FA"; ink = "#14213D"
    bullets = "".join(f"<div style='display:flex;gap:18px;align-items:flex-start;padding:11px 18px;border-radius:20px;background:#fff;border:2px solid {c['acc']}55;margin-bottom:10px;box-shadow:0 8px 22px rgba(20,33,61,.08)'>{check(c,32)}<div style='font-size:23px;font-weight:600;line-height:1.25;color:{ink}'>{b}</div></div>" for b in d["b"])
    panel_h = 575 + dy
    body = (f"<div class='abs' style='left:0;top:{panel_h}px;width:1080px;height:{H-panel_h}px;background:{light}'></div>"
      + f"<div class='abs' style='left:0;top:0;width:1080px;height:{panel_h}px;background:radial-gradient(800px 600px at 88% 10%,{c['acc']}55,transparent 65%),linear-gradient(160deg,{c['dark']},{c['base']})'></div>"
      + pattern(c['pat'], c, 1080, panel_h, 4)
      + f"<div class='abs' style='left:0;top:{panel_h-6}px;width:1080px;height:6px;background:{c['acc']}'></div>"
      + header(c, 64 + dy) + logo_chip(d["marca"], 76, 216 + dy, 400, 124, 74)
      + f"<div class='abs' style='left:510px;top:{252+dy}px;font-size:21px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;color:{c['hi']};max-width:480px;line-height:1.2'>{d['area']}</div>"
      + f"<h1 class='abs' style='left:76px;right:76px;top:{350+dy}px;font-size:{fs_for(d['q'])}px;line-height:1.08;font-weight:800;letter-spacing:-1px;text-wrap:balance'>{d['q']}</h1>"
      + f"<div class='abs' style='left:76px;right:76px;top:{610+dy}px;font-size:26px;font-weight:600;line-height:1.3;color:{ink}'><span style='color:{c['base']};font-weight:800;letter-spacing:3px;font-size:15px;text-transform:uppercase;display:block;margin-bottom:6px'>{d['pos']}</span>{d['ctx']}</div>"
      + f"<div class='abs' style='left:370px;right:76px;top:{765+dy}px'>{bullets}</div>"
      + r3.mascot(40, 770 + dy, 330, glow=True, dark=False)
      + f"<div class='abs' style='left:76px;right:76px;top:{1135+dy}px;border-radius:28px;background:linear-gradient(135deg,{c['dark']},{c['base']});padding:16px 38px;color:#fff'><div style='font-size:17px;font-weight:800;letter-spacing:4px;text-transform:uppercase;color:{c['hi']}'>Tu puerta de entrada</div><div style='font-size:30px;font-weight:800;margin-top:4px'>{d['entrada']}</div></div>"
      + f"<div class='abs' style='left:76px;top:{1262+dy}px;border-radius:999px;background:{c['base']};color:#fff;padding:12px 32px;font-size:26px;font-weight:700'>#CPNnetPartners</div>"
      + f"<div class='abs' style='right:76px;top:{1262+dy}px;border-radius:999px;background:{c['acc']};color:{c['onacc']};padding:14px 32px;font-size:22px;font-weight:800'>Habla con tu ejecutivo CPNnet</div>")
    return body

def page_post(key, H, style):
    d = D[key]; dy = 0 if H == 1350 else 285
    c = d
    bg = dark_bg(c, H, seed=d.get("seed", 3)) if style == "A" else f"<div class='abs' style='inset:0;background:{c['dark']}'></div>"
    body = post_A(d, H, dy) if style == "A" else post_B(d, H, dy)
    return doc(H, c, bg, body)

def page_reel(key):
    d = D[key]; c = d; H = 1920
    steps = "".join(f"<div style='display:flex;gap:26px;align-items:center;margin:22px 0'><div style='flex:none;width:72px;height:72px;border-radius:50%;background:{c['acc']};color:{c['onacc']};display:flex;align-items:center;justify-content:center;font-size:34px;font-weight:800'>{i+1}</div><div style='font-size:34px;font-weight:700;line-height:1.2'>{b}</div></div>" for i, b in enumerate(d["b"]))
    body = (header(c, 110) + f"<div class='abs' style='left:76px;top:470px;font-size:24px;font-weight:700;letter-spacing:5px;text-transform:uppercase;color:{c['hi']}'>Noviembre · {d['area']}</div>"
      + f"<div class='abs' style='left:76px;top:520px;right:90px;font-size:68px;font-weight:800;line-height:1.06;letter-spacing:-2px;text-shadow:0 3px 22px rgba(0,0,0,.6)'>{d['q']}</div>"
      + logo_chip(d["marca"], 76, 900, 360, 130, 76)
      + r3.mascot(715, 700, 440, dark=True)
      + f"<div class='abs' style='left:76px;right:76px;top:1120px'>{steps}</div>"
      + f"<div class='abs' style='left:76px;right:76px;top:1520px;height:96px;border-radius:28px;background:linear-gradient(135deg,{c['acc']},{c['acc2']});color:{c['onacc']};display:flex;align-items:center;justify-content:center;font-size:34px;font-weight:800'>Habla con tu ejecutivo CPNnet</div>"
      + f"<div class='abs' style='left:76px;top:1660px;font-size:26px;font-weight:700;opacity:.9'>#CPNnetPartners</div>")
    return doc(H, c, dark_bg(c, H, seed=d.get("seed", 3)), body)

def page_event(H, kicker, fecha, btn):
    c = dict(BR["faronics"], area="Endpoints"); dy = 0 if H == 1350 else 285
    cal = "<svg width='46' height='46' viewBox='0 0 24 24' fill='none' stroke='#fff' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'><rect x='3' y='5' width='18' height='16' rx='3'/><path d='M3 10h18M8 3v4M16 3v4'/><path d='m9 15.5 2 2 4-4'/></svg>"
    clk = "<svg width='46' height='46' viewBox='0 0 24 24' fill='none' stroke='#fff' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='13' r='8'/><path d='M12 9v4.5l3 2M5 4 3 6M19 4l2 2'/></svg>"
    card = lambda ic, t: f"<div style='flex:1;display:flex;align-items:center;gap:16px;padding:22px 24px;border-radius:26px;background:rgba(0,0,0,.3);border:2px solid {c['acc']}77'>{ic}<div style='font-size:30px;font-weight:800;line-height:1.15'>{t}</div></div>"
    body = (header(c, 64 + dy) + f"<div class='abs' style='left:76px;top:{232+dy}px;font-size:26px;font-weight:800;letter-spacing:7px;text-transform:uppercase;color:{c['hi']}'>{kicker}</div>"
      + logo_chip("faronics", 76, 282 + dy, 400, 124, 76)
      + f"<h1 class='abs' style='left:76px;right:76px;top:{435+dy}px;font-size:60px;line-height:1.08;font-weight:800;letter-spacing:-1.5px'><span class='hl'>Faronics Cloud</span>: más control y protección para los endpoints</h1>"
      + f"<div class='abs' style='left:76px;top:{680+dy}px;width:520px;display:flex;flex-direction:column;gap:14px'>{card(cal, fecha)}{card(clk, '11:30 hrs Chile')}</div>"
      + f"<div class='abs' style='left:76px;width:560px;top:{900+dy}px;font-size:23px;font-weight:500;line-height:1.3;opacity:.95'>Deep Freeze Cloud permite administrar equipos remotos desde una sola consola web, sin VPN.</div>"
      + f"<div class='abs' style='left:76px;width:520px;top:{1090+dy}px;height:104px;border-radius:52px;background:linear-gradient(135deg,{c['acc']},{c['acc2']});display:flex;align-items:center;justify-content:center;font-size:40px;font-weight:800;box-shadow:0 14px 36px rgba(213,32,39,.45)'>{btn}</div>"
      + r3.mascot(650, 690 + dy, 520, dark=True)
      + f"<div class='abs' style='left:76px;top:{1262+dy}px;border-radius:999px;background:rgba(255,255,255,.14);border:2px solid {c['acc']}88;padding:12px 32px;font-size:26px;font-weight:700'>#CPNnetPartners</div>")
    return doc(H, c, dark_bg(c, H, seed=2), body)

def page_cierre(H):
    dy = 0 if H == 1350 else 285; c = dict(CP, pat="tiles")
    order = ["segura", "aikido", "sophos", "kriptos", "safetica", "vicarius", "action1", "seceon", "sonicwall", "pronto"]
    tiles = ""
    for i, k in enumerate(order):
        b = BR.get(k) or dict(CP, area='Más marcas para partners'); x = 76 if i % 2 == 0 else 76 + 456; y = 470 + dy + (i // 2) * 138
        logo = (f"<img src='{m.LOGOS[k]}' style='height:44px;max-width:190px;object-fit:contain'>" if (k in m.LOGOS) else (f"<span style='font-size:30px;font-weight:800;letter-spacing:3px;color:#fff'>PRÓXIMAMENTE</span>" if k == "pronto" else f"<span style='font-size:{36 if k=='sonicwall' else 40}px;font-weight:800;letter-spacing:2px;color:#fff'>{WORD[k]}</span>"))
        tiles += (f"<div class='abs' style='left:{x}px;top:{y}px;width:448px;height:126px;border-radius:26px;overflow:hidden;background:linear-gradient(135deg,{b['dark']},{b['base']});box-shadow:0 12px 30px rgba(20,33,61,.22)'>"
                  f"<div style='position:absolute;right:-30px;top:-30px;width:130px;height:130px;border-radius:50%;background:{b['acc']};opacity:.35'></div>"
                  f"<div style='position:absolute;left:26px;top:16px;display:flex;align-items:center;height:54px'>{logo}</div>"
                  f"<div style='position:absolute;left:26px;bottom:14px;font-size:17px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;color:{b['hi']}'>{b['area']}</div></div>")
    rings = pattern("tiles", dict(CP, acc="#3A84BD"), 1080, H, 6)
    body = (f"<div class='abs' style='inset:0;background:#F2F6FA'></div>" + rings
      + f"<img class='abs' src='{b2_logo()}' style='left:76px;top:{64+dy}px;width:200px'>"
      + f"<div class='abs' style='right:76px;top:{82+dy}px;display:flex;gap:8px'>{flags(34)}</div>"
      + f"<div class='abs' style='left:76px;top:{250+dy}px;font-size:24px;font-weight:700;letter-spacing:6px;color:#3A84BD;text-transform:uppercase'>CPNnet | Cierre de noviembre</div>"
      + f"<h1 class='abs' style='left:76px;width:640px;top:{296+dy}px;font-size:52px;line-height:1.08;font-weight:800;color:#1A2E52'>Noviembre: ¿dónde <span style='color:#3A84BD'>empezamos</span> con tu cliente?</h1>"
      + r3.mascot(780, 110 + dy, 372, dark=False) + tiles
      + f"<div class='abs' style='left:76px;top:{1262+dy}px;border-radius:999px;background:#1A2E52;color:#fff;padding:12px 32px;font-size:26px;font-weight:700'>#CPNnetPartners</div>"
      + f"<div class='abs' style='right:76px;top:{1262+dy}px;border-radius:999px;background:linear-gradient(135deg,#2a5aa8,#3f86d0);color:#fff;padding:14px 32px;font-size:22px;font-weight:800'>Habla con tu ejecutivo CPNnet</div>")
    return doc(H, dict(CP, dark="#F2F6FA"), "", body).replace("color:#fff;background:#F2F6FA", "color:#1A2E52;background:#F2F6FA")

def b2_logo():
    import build_v2 as b2; return b2.LOGO_COLOR

PIECES = [
 ("01", "sonicwall-capture-atp", "both", lambda H: page_post("sonicwall_atp", H, "A")),
 ("02", "sophos-red-endpoint", "both", lambda H: page_post("sophos_red", H, "B")),
 ("03", "reel-segura-domum", "reel", lambda H: page_reel("domum")),
 ("04", "kriptos-ia-copilot", "both", lambda H: page_post("kriptos_ia", H, "B")),
 ("05", "safetica-offboarding", "both", lambda H: page_post("offboarding", H, "A")),
 ("06", "reel-action1-remotos", "reel", lambda H: page_reel("action1_remoto")),
 ("07", "seceon-mssp", "both", lambda H: page_post("seceon_mssp", H, "B")),
 ("08", "aikido-desarrollo", "both", lambda H: page_post("aikido_dev", H, "A")),
 ("09", "reel-vicarius-sin-parche", "reel", lambda H: page_reel("vicarius_sinparche")),
 ("10", "segura-privilegios", "both", lambda H: page_post("segura_epm", H, "B")),
 ("11", "sonicwall-cloud-secure-edge", "both", lambda H: page_post("sonicwall_cse", H, "A")),
 ("12", "reel-sophos-ransomware", "reel", lambda H: page_reel("sophos_ransom")),
 ("13", "cierre-noviembre", "both", lambda H: page_cierre(H)),
]

if __name__ == "__main__":
    only = set(sys.argv[1:])
    with sync_playwright() as pw:
        br = pw.chromium.launch(executable_path="/opt/pw-browsers/chromium", args=["--no-sandbox"])
        # fondos con patrón para los reels animados (Remotion)
        for k, c in BR.items():
            if only: break
            pg = br.new_page(viewport={"width": 1080, "height": 1920})
            pg.set_content(doc(1920, c, dark_bg(c, 1920, seed=5), ""), timeout=60000); pg.wait_for_timeout(300)
            pg.screenshot(path=str(PUB / f"bgm_{k}.jpg"), type="jpeg", quality=90); pg.close()
        for n, name, kind, fn in PIECES:
            if only and n not in only: continue
            jobs = [(1920, "1080x1920")] if kind == "reel" else [(1350, "1080x1350"), (1920, "1080x1920")]
            for H, folder in jobs:
                pg = br.new_page(viewport={"width": 1080, "height": H}); pg.set_content(fn(H), timeout=120000); pg.wait_for_timeout(600)
                pg.screenshot(path=str(OUT / folder / f"{n}_{name}_{folder}.png")); pg.close()
        br.close()
    W, Hh, G = 405, 506, 10; cols = 3; rows = (len(PIECES) + cols - 1) // cols
    sheet = Image.new("RGB", (cols * W + (cols + 1) * G, rows * Hh + (rows + 1) * G), (234, 242, 250))
    for i, (n, name, kind, fn) in enumerate(PIECES):
        sz = "1080x1920" if kind == "reel" else "1080x1350"
        f = OUT / sz / f"{n}_{name}_{sz}.png"
        if not f.exists(): continue
        im = Image.open(f).convert("RGB")
        if im.height > 1350: t = (im.height - 1350) // 2; im = im.crop((0, t, 1080, t + 1350))
        sheet.paste(im.resize((W, Hh)), (G + (i % cols) * (W + G), G + (i // cols) * (Hh + G)))
    sheet.save(OUT / "grilla-noviembre.png", optimize=True); print("ok")
