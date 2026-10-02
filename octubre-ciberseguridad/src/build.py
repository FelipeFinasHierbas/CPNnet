#!/usr/bin/env python3
"""Genera la grilla de octubre 2026 (Mes de la Ciberseguridad) de CPNnet Security.

Uso: python3 build.py   ->  escribe PNGs en ../posts, ../reels y ../grilla-octubre.png
"""
import base64, html, pathlib
from playwright.sync_api import sync_playwright
from PIL import Image

ROOT = pathlib.Path(__file__).resolve().parent.parent
LOGO = "data:image/png;base64," + base64.b64encode((ROOT / "assets/logo.png").read_bytes()).decode()

NAVY, ROYAL, AZURE, SKY, ICE = "#1A2E52", "#203C71", "#3A84BD", "#84BAE1", "#EAF2FA"
AMBER = "#F5B83D"  # solo para placeholders por confirmar (no es color de marca)

def _face(w):
    b = base64.b64encode((ROOT / f"assets/fonts/Poppins-{w}.ttf").read_bytes()).decode()
    return f"@font-face{{font-family:'Poppins';font-weight:{w};src:url(data:font/ttf;base64,{b}) format('truetype')}}"
FONTS = "".join(_face(w) for w in (400, 500, 600, 700, 800))

CSS = FONTS + f"""
*{{box-sizing:border-box;margin:0;padding:0}}
body{{font-family:'Poppins',sans-serif;width:1080px;overflow:hidden}}
.dark{{background:radial-gradient(1200px 900px at 85% -10%,#2f5aa6 0%,transparent 55%),linear-gradient(160deg,{ROYAL},{NAVY});color:#fff}}
.light{{background:radial-gradient(900px 700px at 0% 100%,#cfe3f5 0%,transparent 60%),{ICE};color:{NAVY}}}
.canvas{{position:relative;width:1080px;overflow:hidden}}
.grid{{position:absolute;inset:0;opacity:.07;background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);background-size:54px 54px}}
.light .grid{{opacity:.5;background-image:linear-gradient(#cddff0 1px,transparent 1px),linear-gradient(90deg,#cddff0 1px,transparent 1px)}}
.blob{{position:absolute;border-radius:60px;background:linear-gradient(135deg,{SKY},{AZURE});opacity:.9}}
.pad{{position:absolute;inset:0;padding:72px 76px}}
.pill{{display:inline-block;border-radius:999px;padding:12px 28px;font-weight:600;font-size:23px;letter-spacing:.5px;text-transform:uppercase}}
.pill.w{{background:#fff;color:{NAVY}}} .pill.b{{background:{AZURE};color:#fff}} .pill.n{{background:{NAVY};color:#fff}}
.pill.o{{border:2px solid {SKY};color:{SKY}}}
h1{{font-weight:800;line-height:1.08;letter-spacing:-1px}}
.az{{color:{AZURE}}} .dark .az{{color:{SKY}}}
.card{{background:#fff;color:{NAVY};border-radius:44px;padding:44px 48px;box-shadow:0 18px 50px rgba(10,25,60,.28)}}
.foot{{position:absolute;left:76px;right:76px;display:flex;align-items:center;justify-content:space-between}}
.logo{{background:#fff;border-radius:999px;padding:8px 26px 8px 20px;display:flex;align-items:center;height:96px;box-shadow:0 8px 24px rgba(10,25,60,.2)}}
.logo img{{height:78px}}
.cta{{background:{NAVY};color:#fff;border-radius:999px;padding:20px 34px;font-weight:600;font-size:22px;letter-spacing:.6px;text-transform:uppercase}}
.light .cta{{background:{NAVY}}} .dark .cta{{background:#fff;color:{NAVY}}}
.ph{{background:{AMBER};color:#1b1b1b;border-radius:14px;padding:6px 16px;font-weight:700;font-size:26px;display:inline-block}}
.src{{font-size:19px;opacity:.75;font-weight:400}}
ul.chk{{list-style:none}} ul.chk li{{display:flex;gap:20px;align-items:flex-start;font-size:28px;font-weight:500;line-height:1.3;margin:20px 0}}
ul.chk li:before{{content:'';flex:none;width:34px;height:34px;margin-top:5px;border-radius:50%;background:{AZURE} url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><path d='M6 12.5l4 4 8-9' fill='none' stroke='white' stroke-width='3.2' stroke-linecap='round' stroke-linejoin='round'/></svg>") center/70% no-repeat}}
.chip{{display:inline-block;background:#fff;color:{NAVY};border-radius:20px;padding:10px 26px;font-weight:800;font-size:34px;letter-spacing:-.5px}}
.date{{background:#fff;color:{NAVY};border-radius:36px;text-align:center;padding:26px 40px;box-shadow:0 14px 40px rgba(10,25,60,.3)}}
.date b{{display:block;font-size:130px;line-height:1;font-weight:800;color:{AZURE}}}
.date span{{font-size:30px;font-weight:700;text-transform:uppercase;letter-spacing:2px}}
.week{{font-size:21px;font-weight:600;opacity:.8;letter-spacing:1.5px;text-transform:uppercase}}
"""

def page(w, h, theme, body):
    return f"""<html><head><meta charset='utf-8'><style>{CSS}</style></head>
<body><div class='canvas {theme}' style='height:{h}px'><div class='grid'></div>{body}</div></body></html>"""

def logo_foot(top, label="Contacta a tu ejecutivo CPNnet"):
    return f"""<div class='foot' style='top:{top}px'><div class='logo'><img src='{LOGO}'></div>
<div class='cta'>{label}</div></div>"""

# ------------------------------------------------------------------ plantillas
def t_webinar(p):
    bullets = "".join(f"<li>{html.escape(b)}</li>" for b in p["bullets"])
    return page(1080, 1350, "dark", f"""
<div class='blob' style='width:520px;height:300px;right:-90px;top:-80px;transform:rotate(-8deg)'></div>
<div class='blob' style='width:420px;height:240px;left:-110px;bottom:230px;opacity:.35'></div>
<div class='pad'>
  <span class='pill w'>Webinar · Mes de la Ciberseguridad</span>
  <div style='display:flex;gap:36px;align-items:center;margin-top:60px'>
    <div class='date'><span>{p['mes']}</span><b>{p['dia']}</b><span>{p['dow']}</span></div>
    <div><div class='chip'>{p['brand']}</div>
      <div style='font-size:28px;font-weight:500;margin-top:18px;line-height:1.3'>{p['cat']}</div>
      <div style='margin-top:18px'><span class='ph'>Hora: [por confirmar]</span></div></div>
  </div>
  <h1 style='font-size:66px;margin-top:44px'>{p['title']}</h1>
  <div class='card' style='margin-top:36px;padding:30px 44px'><ul class='chk' style='margin:-10px 0'>{bullets}</ul></div>
</div>
<div class='week' style='position:absolute;right:76px;top:1085px'></div>
{logo_foot(1196, p.get('cta','Inscríbete gratis'))}""")

def t_stat(p):
    return page(1080, 1350, p.get("theme", "light"), f"""
<div class='blob' style='width:360px;height:200px;right:-90px;top:-80px;transform:rotate(-8deg)'></div>
<div class='pad'>
  <span class='pill b'>{p['tag']}</span>
  <h1 style='font-size:52px;margin-top:40px;max-width:900px'>{p['kicker']}</h1>
  <div style='font-size:{p.get("bigsize",250)}px;font-weight:800;line-height:1;letter-spacing:-8px;margin:34px 0 6px' class='az'>{p['big']}</div>
  <div style='font-size:36px;font-weight:600;line-height:1.25;max-width:900px'>{p['caption']}</div>
  <div class='card' style='margin-top:36px;padding:30px 40px'>
    <div style='font-size:28px;font-weight:700;color:{AZURE};text-transform:uppercase;letter-spacing:1px'>Qué significa</div>
    <div style='font-size:28px;font-weight:500;line-height:1.35;margin-top:6px'>{p['takeaway']}</div></div>
  <div class='src' style='margin-top:22px'>Fuente: {p['src']}</div>
</div>
{logo_foot(1196)}""")

def t_list(p):
    rows = "".join(f"""<div style='display:flex;gap:28px;align-items:center;margin:12px 0'>
      <div style='flex:none;min-width:200px;text-align:center;background:{NAVY};color:#fff;border-radius:28px;padding:12px 12px'>
        <div style='font-size:{p.get("numsize",64)}px;font-weight:800;line-height:1'>{n}</div>
        <div style='font-size:21px;font-weight:600;color:{SKY};margin-top:4px'>{u}</div></div>
      <div style='font-size:31px;font-weight:600;line-height:1.28'>{t}</div></div>""" for n, u, t in p["rows"])
    return page(1080, 1350, p.get("theme", "dark"), f"""
<div class='blob' style='width:360px;height:200px;right:-90px;top:-80px;transform:rotate(-8deg)'></div>
<div class='pad'>
  <span class='pill {"w" if p.get("theme","dark")=="dark" else "b"}'>{p['tag']}</span>
  <h1 style='font-size:68px;margin-top:46px;max-width:900px'>{p['title']}</h1>
  <div class='card' style='margin-top:40px;padding:30px 40px'>{rows}</div>
  <div style='margin-top:20px;font-size:27px;font-weight:500;line-height:1.35'>{p['foot']}</div>
  <div class='src' style='margin-top:8px'>Fuente: {p['src']}</div>
</div>
{logo_foot(1196)}""")

def t_agenda(p):
    items = "".join(f"""<div style='display:flex;align-items:center;gap:26px;margin:16px 0'>
      <div style='flex:none;width:130px;text-align:center;background:{AZURE};color:#fff;border-radius:24px;padding:12px 0'>
        <div style='font-size:20px;font-weight:600;letter-spacing:1px'>OCT</div><div style='font-size:58px;font-weight:800;line-height:1'>{d}</div></div>
      <div><div style='font-size:42px;font-weight:800'>{b}</div><div style='font-size:24px;font-weight:500;opacity:.85'>{c}</div></div></div>""" for d, b, c in p["items"])
    return page(1080, 1350, "dark", f"""
<div class='blob' style='width:560px;height:320px;right:-120px;top:-100px;transform:rotate(-8deg)'></div>
<div class='pad'>
  <span class='pill w'>Octubre · Mes de la Ciberseguridad</span>
  <h1 style='font-size:84px;margin-top:46px'>4 martes.<br><span class='az'>4 webinars.</span></h1>
  <div style='font-size:30px;font-weight:500;margin-top:18px;opacity:.92'>Con las marcas líderes que representamos en Latinoamérica.</div>
  <div class='card' style='margin-top:40px;padding:30px 44px'>{items}</div>
</div>
{logo_foot(1196,'Regístrate')}""")

def t_reel(p):
    pts = "".join(f"<div style='display:flex;gap:18px;align-items:center;margin:14px 0;font-size:34px;font-weight:600'><div style='width:16px;height:16px;border-radius:50%;background:{SKY};flex:none'></div>{html.escape(x)}</div>" for x in p["points"])
    return page(1080, 1920, "dark", f"""
<div class='blob' style='width:700px;height:400px;right:-180px;top:-120px;transform:rotate(-8deg)'></div>
<div class='blob' style='width:560px;height:320px;left:-160px;bottom:380px;opacity:.3'></div>
<div style='position:absolute;left:0;right:0;top:420px;height:1350px'><div class='pad' style='padding-top:0'>
  <span class='pill w'>{p['tag']}</span>
  <h1 style='font-size:88px;margin-top:44px'>{p['hook']}</h1>
  <div style='margin-top:36px'>{pts}</div>
  <div style='margin-top:40px;display:flex;align-items:center;gap:24px'>
    <div style='width:120px;height:120px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 12px 30px rgba(0,0,0,.3)'>
      <div style='border-left:42px solid {AZURE};border-top:26px solid transparent;border-bottom:26px solid transparent;margin-left:12px'></div></div>
    <div style='font-size:30px;font-weight:700;letter-spacing:1px;text-transform:uppercase'>REEL · {p['dur']}</div></div>
</div></div>
<div class='foot' style='top:1640px'><div class='logo'><img src='{LOGO}'></div><div class='cta'>cpnnetsecurity.com</div></div>""")

TEMPLATES = dict(webinar=t_webinar, stat=t_stat, list=t_list, agenda=t_agenda, reel=t_reel)

# ------------------------------------------------------------------ contenido
POSTS = [
 dict(id="00", fecha="Vie 02 oct (bonus de lanzamiento)", tipo="agenda", formato="Post",
      items=[("06", "SonicWall", "Seguridad de red · NGFW · SD-WAN"), ("13", "Vicarius", "Gestión de exposición y remediación"),
             ("20", "Seceon", "AI-SIEM · XDR · SOC autónomo"), ("27", "Aikido Security", "Seguridad de aplicaciones · Code-to-Runtime")]),
 dict(id="01", fecha="Lun 05 oct", tipo="webinar", formato="Post", brand="SonicWall", mes="OCT", dia="06", dow="Martes",
      cat="Seguridad de red · Firewalls NGFW, SD-WAN y acceso seguro", title="Sucursales, nube y trabajo híbrido: <span class='az'>¿tu perímetro sigue en pie?</span>",
      bullets=["Firewalls de nueva generación y servicios de seguridad", "Secure SD-WAN: conectar sedes con seguridad integrada", "Cloud Secure Edge: acceso privado/ZTNA por usuario"],
      nota_titulo="El título y texto definitivos del webinar los debe entregar SonicWall/Juliana (pendiente según reunión MKT del 25/09). Titular propuesto como borrador."),
 dict(id="02", fecha="Mié 07 oct", tipo="stat", formato="Post", tag="Chile en cifras", kicker="Cada semana, cada organización chilena recibe en promedio…",
      big="1.706", caption="intentos de ciberataque por semana (marzo 2026)", bigsize=270,
      takeaway="Con tantos intentos, no se trata de <b>si</b> te atacarán, sino de qué tan preparada está tu red cuando ocurra. Visibilidad y control en el perímetro son el primer paso.",
      src="Check Point Research, vía Revista Seguridad & Defensa (10-04-2026)"),
 dict(id="03", fecha="Vie 09 oct", tipo="reel", formato="Reel", tag="Ley 21.663 · en 60 segundos", hook="¿Sufriste un incidente? <span class='az'>El reloj ya corre.</span>",
      points=["3 horas: alerta temprana", "72 horas: actualización", "15 días: informe final"], dur="30–45 s"),
 dict(id="04", fecha="Lun 12 oct", tipo="webinar", formato="Post", brand="Vicarius", mes="OCT", dia="13", dow="Martes",
      cat="Gestión de exposición · Remediación de vulnerabilidades", title="Encontrar vulnerabilidades es fácil. <span class='az'>Cerrarlas, no tanto.</span>",
      bullets=["Priorización de riesgo y backlog bajo control", "Patching, scripting y protección sin parche", "Un mismo flujo para TI y Seguridad"],
      nota_titulo="Título borrador; confirmar con Pablo (coordinación de temas, según reunión MKT)."),
 dict(id="05", fecha="Mié 14 oct", tipo="stat", formato="Post", tag="Ransomware en Chile", kicker="Agosto 2026 fue el mes con más víctimas de ransomware registradas en el país:",
      big="10", caption="organizaciones chilenas afectadas en un solo mes", bigsize=260,
      takeaway="Los atacantes aprovechan vulnerabilidades sin corregir. Reducir la ventana de exposición —con parches o protección sin parche— corta rutas de entrada.",
      src="Cronup, reporte de ransomware Chile, agosto 2026", theme="dark"),
 dict(id="06", fecha="Vie 16 oct", tipo="reel", formato="Reel", tag="Gestión de exposición", hook="Parche, script o <span class='az'>protección sin parche</span>",
      points=["3 caminos para cerrar una brecha", "Cuando no hay parche, no hay excusa", "Con Vicarius"], dur="30 s"),
 dict(id="07", fecha="Lun 19 oct", tipo="webinar", formato="Post", brand="Seceon", mes="OCT", dia="20", dow="Martes",
      cat="Operaciones de seguridad · AI-SIEM, XDR y SOC autónomo", title="Un SOC pequeño, miles de alertas: <span class='az'>cómo automatizar la respuesta</span>",
      bullets=["AI-SIEM + XDR + postura en una sola plataforma", "Multi-tenant para MSP/MSSP y servicios 24/7", "Licenciamiento por activos: costos predecibles"],
      nota_titulo="Título borrador; confirmar con Pablo. Asunto de la reunión menciona “Sis” = Seceon."),
 dict(id="08", fecha="Mié 21 oct", tipo="list", formato="Post", tag="Ley Marco de Ciberseguridad", title="Multas de la Ley 21.663: <span class='az'>lo que está en juego</span>",
      rows=[("5.000", "UTM", "Infracciones leves"), ("10.000", "UTM", "Infracciones graves"), ("20.000", "UTM", "Infracciones gravísimas"), ("40.000", "UTM", "Tope para Operadores de Importancia Vital")],
      foot="A julio de 2026, la ANCI había calificado <b>1.154 Operadores de Importancia Vital</b>. Detectar y responder a tiempo es parte del cumplimiento.",
      src="Ley 21.663; resumen NetProvider (2026)", theme="light", numsize=56),
 dict(id="09", fecha="Vie 23 oct", tipo="reel", formato="Reel", tag="Operaciones de seguridad", hook="Tu SOC no necesita <span class='az'>más alertas</span>",
      points=["Necesita las que importan", "Correlación + IA + automatización", "Con Seceon"], dur="30 s"),
 dict(id="10", fecha="Lun 26 oct", tipo="webinar", formato="Post", brand="Aikido Security", mes="OCT", dia="27", dow="Martes",
      cat="Seguridad de aplicaciones · DevSecOps", title="Del código a la nube: <span class='az'>seguridad que los devs sí usan</span>",
      bullets=["Código, dependencias, secretos, IaC y contenedores", "Menos ruido: reachability y auto-triage", "AutoFix: del hallazgo al pull request"],
      nota_titulo="Título borrador; confirmar con Pablo."),
 dict(id="11", fecha="Mié 28 oct", tipo="stat", formato="Post", tag="Ley 21.719 · Datos personales", kicker="Entra en vigencia plena el 1 de diciembre de 2026. Faltan…",
      big="34", caption="días para la nueva Ley de Protección de Datos Personales", bigsize=260, theme="light",
      takeaway="Nueva Agencia de Protección de Datos y multas de hasta <b>20.000 UTM</b>. Proteger datos sensibles y controlar accesos deja de ser opcional.",
      src="Ley 21.719 (vigencia general 01-12-2026); resúmenes Idonea, Araya (2026)"),
 dict(id="12", fecha="Vie 30 oct", tipo="reel", formato="Reel", tag="Cierre del mes", hook="Cierra octubre con un plan: <span class='az'>Protección, Riesgo y Operaciones</span>",
      points=["3 dominios · 6 categorías", "+18 años acompañando a Latam", "Habla con tu ejecutivo CPNnet"], dur="30–45 s"),
]

def render():
    shots = {}
    with sync_playwright() as pw:
        b = pw.chromium.launch(executable_path="/opt/pw-browsers/chromium", args=["--no-sandbox"])
        for p in POSTS:
            h = 1920 if p["tipo"] == "reel" else 1350
            pg = b.new_page(viewport={"width": 1080, "height": h})
            pg.set_content(TEMPLATES[p["tipo"]](p)); pg.wait_for_load_state("networkidle"); pg.wait_for_timeout(400)
            sub = "reels" if p["tipo"] == "reel" else "posts"
            name = {"agenda": "calendario-webinars", "webinar": "webinar-" + p.get("brand", "").lower().replace(" ", "-"), "stat": "dato", "list": "ley", "reel": "reel-portada"}[p["tipo"]]
            out = ROOT / sub / f"{p['id']}_{name}.png"
            pg.screenshot(path=str(out)); pg.close(); shots[p["id"]] = out
        b.close()
    return shots

def grid(shots):
    # 3 columnas, orden cronológico invertido por filas como en un perfil (más reciente arriba)
    order = [p["id"] for p in POSTS if p["id"] != "00"]
    rows = [order[i:i + 3] for i in range(0, 12, 3)]
    W, H, G = 540, 675, 12
    sheet = Image.new("RGB", (3 * W + 4 * G, 4 * H + 5 * G), (234, 242, 250))
    for r, row in enumerate(rows):
        for c, pid in enumerate(row):
            im = Image.open(shots[pid]).convert("RGB")
            if im.height > 1350:  # reel: recorte central 4:5 como en la grilla de IG
                t = (im.height - 1350) // 2; im = im.crop((0, t, 1080, t + 1350))
            sheet.paste(im.resize((W, H)), (G + c * (W + G), G + r * (H + G)))
    sheet.save(ROOT / "grilla-octubre.png", optimize=True)

if __name__ == "__main__":
    s = render(); grid(s); print("ok", len(s))
