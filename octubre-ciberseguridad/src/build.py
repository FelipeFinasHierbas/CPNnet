#!/usr/bin/env python3
"""Grilla Octubre 2026 · Mes de la Ciberseguridad · CPNnet Security.

Estilo basado en la pieza oficial del webinar SonicWall (06-oct-2026): foto tech cian a sangre,
logo blanco arriba a la izquierda, banda oscura translúcida con marca/título/fecha/hora,
#webinarscpnnet y banderas Chile · Perú · EE.UU. · Colombia abajo.

Uso: python3 build.py  ->  ../posts, ../reels, ../grilla-octubre.png
"""
import base64, pathlib
from playwright.sync_api import sync_playwright
from PIL import Image

ROOT = pathlib.Path(__file__).resolve().parent.parent
b64 = lambda p: base64.b64encode((ROOT / p).read_bytes()).decode()
LOGO = "data:image/png;base64," + b64("assets/logo-blanco.png")
FONTS = "".join(
    f"@font-face{{font-family:'Montserrat';font-weight:{w};src:url(data:font/ttf;base64,{b64(f'assets/fonts/Montserrat-{w}.ttf')}) format('truetype')}}"
    for w in (400, 500, 600, 700, 800))

HORA = "11:00hrs&nbsp; Chile<br>09:00 hrs Perú - Colombia"
HASHTAG_WEBINAR = "#webinarscpnnet"

CAL = "<svg viewBox='0 0 48 48' width='52' height='52' fill='none' stroke='#fff' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'><rect x='5' y='9' width='38' height='34' rx='4'/><path d='M5 18h38M14 4v8M24 4v8M34 4v8'/><g fill='#fff' stroke='none'><rect x='11' y='23' width='4' height='4'/><rect x='18' y='23' width='4' height='4'/><rect x='25' y='23' width='4' height='4'/><rect x='11' y='30' width='4' height='4'/><rect x='18' y='30' width='4' height='4'/></g><circle cx='36' cy='36' r='7' fill='#0a2540' stroke='#fff'/><path d='M33 36l2 2 4-4'/></svg>"
CLK = "<svg viewBox='0 0 48 48' width='52' height='52' fill='none' stroke='#fff' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'><circle cx='24' cy='26' r='15'/><path d='M24 17v9l6 4M10 8l-5 5M38 8l5 5'/></svg>"

FLAGS = """<div class='flags'>
<svg viewBox='0 0 40 40'><clipPath id='c1'><circle cx='20' cy='20' r='20'/></clipPath><g clip-path='url(#c1)'><rect width='40' height='20' fill='#fff'/><rect y='20' width='40' height='20' fill='#d52b1e'/><rect width='20' height='20' fill='#0039a6'/><path d='M10 4l2 6h6l-5 4 2 6-5-4-5 4 2-6-5-4h6z' fill='#fff'/></g></svg>
<svg viewBox='0 0 40 40'><clipPath id='c2'><circle cx='20' cy='20' r='20'/></clipPath><g clip-path='url(#c2)'><rect width='40' height='40' fill='#d91023'/><rect x='13' width='14' height='40' fill='#fff'/></g></svg>
<svg viewBox='0 0 40 40'><clipPath id='c3'><circle cx='20' cy='20' r='20'/></clipPath><g clip-path='url(#c3)'><rect width='40' height='40' fill='#fff'/><g fill='#b22234'><rect y='0' width='40' height='3.1'/><rect y='6.2' width='40' height='3.1'/><rect y='12.4' width='40' height='3.1'/><rect y='18.6' width='40' height='3.1'/><rect y='24.8' width='40' height='3.1'/><rect y='31' width='40' height='3.1'/><rect y='37.2' width='40' height='3.1'/></g><rect width='20' height='21.7' fill='#3c3b6e'/></g></svg>
<svg viewBox='0 0 40 40'><clipPath id='c4'><circle cx='20' cy='20' r='20'/></clipPath><g clip-path='url(#c4)'><rect width='40' height='20' fill='#fcd116'/><rect y='20' width='40' height='10' fill='#003893'/><rect y='30' width='40' height='10' fill='#ce1126'/></g></svg></div>"""

CSS = FONTS + """
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Montserrat',sans-serif;width:1080px;overflow:hidden;background:#04152b}
.cv{position:relative;width:1080px;overflow:hidden;color:#fff}
canvas{position:absolute;inset:0}
.logo{position:absolute;left:80px;top:86px;width:176px}
.band{position:absolute;left:0;right:0;background:linear-gradient(180deg,rgba(3,22,43,.9),rgba(4,24,48,.86));padding:0 68px}
.brand{font-size:62px;letter-spacing:1px;line-height:1;font-weight:300}
.brand b{font-weight:800}
.brand i{display:inline-block;width:46px;height:10px;border-radius:50%;background:#f58220;transform:rotate(-14deg) translate(-96px,12px);margin-right:-46px}
.kick{font-size:25px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#7fd8ee}
h1{font-weight:700;line-height:1.16}
.info{display:flex;align-items:center;gap:66px}
.info div{display:flex;gap:22px;align-items:center;font-size:26px;font-weight:500;line-height:1.3}
.info .d{font-weight:700;font-size:30px}
.pill{position:absolute;left:55px;background:#2f86ff;color:#fff;border-radius:999px;padding:12px 34px;font-size:27px;font-weight:600}
.flags{position:absolute;right:68px;display:flex;gap:10px}.flags svg{width:46px;height:46px}
.big{position:absolute;left:68px;font-weight:800;line-height:.9;letter-spacing:-6px;color:#fff;text-shadow:0 0 50px rgba(88,220,255,.8),0 0 120px rgba(40,170,230,.6)}
.cap{position:absolute;left:72px;right:72px;font-weight:600;line-height:1.2;text-shadow:0 2px 16px rgba(0,20,40,.8)}
.row{display:flex;align-items:center;gap:22px;margin:15px 0;font-size:31px;font-weight:600}
.row b{flex:none;white-space:nowrap;width:96px;height:68px;border-radius:14px;background:#2f86ff;display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:800}
.row small{display:block;font-size:22px;font-weight:500;opacity:.8}
.src{font-size:19px;opacity:.7;font-weight:500}
.play{width:104px;height:104px;border-radius:50%;background:rgba(255,255,255,.95);display:flex;align-items:center;justify-content:center;box-shadow:0 0 50px rgba(88,220,255,.8)}
.play i{border-left:36px solid #1b6fd1;border-top:22px solid transparent;border-bottom:22px solid transparent;margin-left:10px}
"""

BG_JS = """
function rng(a){return function(){a|=0;a=a+0x6D2B79F5|0;let t=Math.imul(a^a>>>15,1|a);t=t+Math.imul(t^t>>>7,61|t)^t;return((t^t>>>14)>>>0)/4294967296}}
const c=document.getElementById('bg'),x=c.getContext('2d'),W=c.width,H=c.height,R=rng(SEED);
let g=x.createLinearGradient(0,0,W*.7,H);g.addColorStop(0,'#0d5568');g.addColorStop(.4,'#0b3b59');g.addColorStop(1,'#04152b');x.fillStyle=g;x.fillRect(0,0,W,H);
const fx=FX*W,fy=FY*H;
function glow(px,py,r,col){const q=x.createRadialGradient(px,py,0,px,py,r);q.addColorStop(0,col);q.addColorStop(1,'rgba(0,0,0,0)');x.fillStyle=q;x.fillRect(0,0,W,H)}
glow(fx,fy,W*.75,'rgba(70,210,235,.55)');glow(W*(FLIP?0.12:0.88),H*.05,W*.6,'rgba(235,150,70,.38)');
// pared de datos en perspectiva (lado FLIP?izq:der) y desenfoque en el lado opuesto
function layer(fn,blur){const o=document.createElement('canvas');o.width=W;o.height=H;fn(o.getContext('2d'));x.save();x.filter='blur('+blur+'px)';x.drawImage(o,0,0);x.restore()}
function wall(side,blur,scaleMul,alpha){layer(function(q){
 for(let i=0;i<70;i++){const t=i/70;const y=H*(-.05+t*.78);let px=side>0?W*(.38+R()*.05):W*(.62-R()*.05);
  const sk=side>0?-.22:.22;const sc=(.3+t*t*1.1)*scaleMul;let n=0;
  while((side>0?px<W*1.05:px>-W*.05)&&n++<200){const w=(3+R()*16)*sc,h=(5+R()*5)*sc;const a=(.1+R()*.6)*alpha;
   q.fillStyle=R()<.12?'rgba(255,255,255,'+a+')':'rgba(110,235,250,'+a+')';q.fillRect(side>0?px:px-w,y+(px-W/2)*sk,w,h);px+=side*(w+(2+R()*9)*sc);if(R()<.05)px+=side*(30+R()*90)*sc}}},blur)}
wall(FLIP?-1:1,.5,1.15,.85);wall(FLIP?1:-1,14,3.2,.4);
// bokeh ámbar/cian en 3 niveles de desenfoque
[1.5,4,8].forEach(function(bl){layer(function(q){for(let i=0;i<16;i++){const px=R()*W,py=R()*H*.8,r=5+R()*R()*26;
 q.fillStyle=R()<.7?'rgba(255,196,110,'+(.35+R()*.55)+')':'rgba(150,245,255,'+(.3+R()*.5)+')';q.beginPath();q.arc(px,py,r,0,7);q.fill()}},bl)});
// halftone alrededor del foco
for(let i=0;i<1500;i++){const a=R()*6.283,d=Math.pow(R(),.7)*W*.42;x.fillStyle='rgba(150,250,255,'+(.15+R()*.55)+')';x.fillRect(fx+Math.cos(a)*d,fy+Math.sin(a)*d*.8,1.8,1.8)}
glow(fx,fy,W*.28,'rgba(210,255,255,.45)');
// viñeta + oscurecido inferior
let v=x.createLinearGradient(0,H*.55,0,H);v.addColorStop(0,'rgba(2,12,26,0)');v.addColorStop(1,'rgba(2,12,26,.85)');x.fillStyle=v;x.fillRect(0,0,W,H);
let v2=x.createLinearGradient(0,0,0,H*.2);v2.addColorStop(0,'rgba(2,16,34,.55)');v2.addColorStop(1,'rgba(2,16,34,0)');x.fillStyle=v2;x.fillRect(0,0,W,H*.2);
"""

def page(h, seed, fx, fy, flip, body, scrim=None):
    js = BG_JS.replace("SEED", str(seed)).replace("FX", str(fx)).replace("FY", str(fy)).replace("FLIP", "true" if flip else "false")
    return f"""<html><head><meta charset='utf-8'><style>{CSS}</style></head><body>
<div class='cv' style='height:{h}px'><canvas id='bg' width='1080' height='{h}'></canvas>
<img class='logo' src='{LOGO}'>{'' if scrim is None else f"<div style='position:absolute;left:0;right:0;top:{scrim[0]}px;height:{scrim[1]}px;background:linear-gradient(90deg,rgba(2,14,30,.78),rgba(2,14,30,.45) 70%,rgba(2,14,30,0))'></div>"}{body}</div><script>{js}</script></body></html>"""

def footer(y, tag):
    return f"<div class='pill' style='top:{y}px'>{tag}</div><div style='position:absolute;right:0;top:{y+2}px'>{FLAGS}</div>"

# ------------------------------------------------------------------ plantillas
def t_webinar(p, h=1350):
    top = 705
    wordmark = p["wordmark"]
    return page(h, p["seed"], .8, .62, p.get("flip", False), f"""
<div class='band' style='top:{top}px;height:410px'>
  <div class='brand' style='margin-top:48px'>{wordmark}</div>
  <h1 style='font-size:47px;margin-top:26px'>{p['title']}</h1>
  <div class='info' style='position:absolute;left:68px;top:322px'>
    <div class='d'>{CAL}{p['fecha']}</div><div>{CLK}<span>{HORA}</span></div></div>
</div>{footer(1228, HASHTAG_WEBINAR)}""")

def t_stat(p, h=1350):
    return page(h, p["seed"], .78, .35, p.get("flip", False), f"""
<div class='kick' style='position:absolute;left:68px;top:340px'>{p['tag']}</div>
<div class='cap' style='top:385px;font-size:40px;max-width:760px'>{p['kicker']}</div>
<div class='big' style='top:520px;font-size:{p.get("bigsize",260)}px'>{p['big']}</div>
<div class='band' style='top:{p.get("bandtop",795)}px;height:{p.get("bandh",320)}px'>
  <h1 style='font-size:40px;margin-top:40px'>{p['caption']}</h1>
  <div style='font-size:27px;font-weight:500;line-height:1.35;margin-top:18px;color:#cfe9f5'>{p['takeaway']}</div>
  <div class='src' style='position:absolute;left:68px;bottom:20px'>Fuente: {p['src']}</div>
</div>{footer(1228, '#MesDeLaCiberseguridad')}""", scrim=(300,470))

def t_list(p, h=1350):
    rows = "".join(f"<div class='row'><b style='width:{p.get('bw',250)}px'>{n}</b><div>{t}</div></div>" for n, t in p["rows"])
    return page(h, p["seed"], .8, .3, p.get("flip", False), f"""
<div class='kick' style='position:absolute;left:68px;top:340px'>{p['tag']}</div>
<div class='cap' style='top:385px;font-size:50px;font-weight:700;max-width:860px'>{p['title']}</div>
<div class='band' style='top:{p.get("bandtop",640)}px;height:{p.get("bandh",470)}px;padding-top:26px'>{rows}
  <div style='font-size:24px;font-weight:500;line-height:1.35;color:#cfe9f5;margin-top:14px'>{p['foot']}</div>
  <div class='src' style='margin-top:6px'>Fuente: {p['src']}</div></div>{footer(1228, '#MesDeLaCiberseguridad')}""", scrim=(300,340))

def t_agenda(p, h=1350):
    rows = "".join(f"<div class='row'><b>{d}<small style='margin-left:6px;font-size:16px'>OCT</small></b><div>{n}<small>{c}</small></div></div>" for d, n, c in p["items"])
    return page(h, p["seed"], .82, .3, False, f"""
<div class='kick' style='position:absolute;left:68px;top:330px'>Octubre · Mes de la Ciberseguridad</div>
<div class='cap' style='top:375px;font-size:60px;font-weight:800;max-width:860px'>4 martes.<br>4 webinars.</div>
<div class='band' style='top:640px;height:470px;padding-top:24px'>{rows}
 <div style='font-size:24px;font-weight:600;margin-top:10px;color:#7fd8ee'>11:00 hrs Chile · 09:00 hrs Perú - Colombia</div></div>{footer(1228, HASHTAG_WEBINAR)}""")

def t_reel(p):
    pts = "".join(f"<div class='row' style='margin:12px 0;font-size:34px'><span style='width:14px;height:14px;border-radius:50%;background:#7fd8ee;flex:none'></span>{x}</div>" for x in p["points"])
    return page(1920, p["seed"], .78, .3, p.get("flip", False), f"""
<div class='kick' style='position:absolute;left:68px;top:470px'>{p['tag']}</div>
<div class='cap' style='top:520px;font-size:78px;font-weight:800;line-height:1.08'>{p['hook']}</div>
<div class='band' style='top:1010px;height:560px;padding-top:44px'>{pts}
  <div style='display:flex;align-items:center;gap:26px;margin-top:34px'><div class='play'><i></i></div>
   <div style='font-size:28px;font-weight:700;letter-spacing:2px;text-transform:uppercase'>Reel · {p['dur']}</div></div></div>
{footer(1640, '#MesDeLaCiberseguridad')}""", scrim=(430,560))

TEMPLATES = dict(webinar=t_webinar, stat=t_stat, list=t_list, agenda=t_agenda, reel=t_reel)
SW = "SONIC<b>WALL</b>"
POSTS = [
 dict(id="00", fecha="Vie 02 oct (bonus)", tipo="agenda", seed=11, items=[
   ("06", "SonicWall", "SonicWall como aliado estratégico en la Ley de Protección de Datos"), ("13", "Vicarius", "Gestión de exposición y remediación"),
   ("20", "Seceon", "AI-SIEM · XDR · SOC autónomo"), ("27", "Aikido Security", "Seguridad de aplicaciones · Code-to-Runtime")]),
 dict(id="01", tipo="webinar", seed=21, wordmark=SW, fecha="06 de Octubre 2026",
      title="SonicWall como aliado estratégico en la Ley de Protección de Datos"),
 dict(id="02", tipo="stat", seed=31, tag="Chile en cifras", kicker="Cada semana, una organización en Chile recibe en promedio…",
      big="1.706", caption="intentos de ciberataque por semana (marzo 2026)", bigsize=270,
      takeaway="No se trata de si te atacarán, sino de qué tan preparada está tu red cuando ocurra.",
      src="Check Point Research, vía Revista Seguridad &amp; Defensa (10-04-2026)"),
 dict(id="03", tipo="reel", seed=41, tag="Ley 21.663 · en 60 segundos", hook="¿Sufriste un incidente?<br>El reloj ya corre.",
      points=["3 horas: alerta temprana", "72 horas: actualización", "15 días: informe final"], dur="30–45 s"),
 dict(id="04", tipo="webinar", seed=51, flip=True, wordmark="<b>VICARIUS</b>", fecha="13 de Octubre 2026",
      title="Vicarius: de la vulnerabilidad a la remediación, con o sin parche"),
 dict(id="05", tipo="stat", seed=61, flip=True, tag="Ransomware en Chile", kicker="Agosto 2026 fue el mes con más víctimas de ransomware en el país:",
      big="10", caption="organizaciones chilenas afectadas en un solo mes", bigsize=300,
      takeaway="Reducir la ventana de exposición —con parches o protección sin parche— corta rutas de entrada.",
      src="Cronup, reporte de ransomware Chile, agosto 2026"),
 dict(id="06", tipo="reel", seed=71, flip=True, tag="Gestión de exposición", hook="Parche, script o<br>protección sin parche",
      points=["3 caminos para cerrar una brecha", "Cuando no hay parche, no hay excusa", "Con Vicarius"], dur="30 s"),
 dict(id="07", tipo="webinar", seed=81, wordmark="<b>SECEON</b>", fecha="20 de Octubre 2026",
      title="Seceon: SOC autónomo con AI-SIEM y XDR para equipos reducidos"),
 dict(id="08", tipo="list", seed=91, tag="Ley Marco de Ciberseguridad", title="Multas de la Ley 21.663: lo que está en juego",
      rows=[("5.000 UTM", "Infracciones leves"), ("10.000 UTM", "Infracciones graves"), ("20.000 UTM", "Infracciones gravísimas"), ("40.000 UTM", "Operadores de Importancia Vital")],
      foot="A julio de 2026, la ANCI había calificado 1.154 Operadores de Importancia Vital.", src="Ley 21.663; resumen NetProvider (2026)"),
 dict(id="09", tipo="reel", seed=101, tag="Operaciones de seguridad", hook="Tu SOC no necesita<br>más alertas",
      points=["Necesita las que importan", "Correlación + IA + automatización", "Con Seceon"], dur="30 s"),
 dict(id="10", tipo="webinar", seed=111, flip=True, wordmark="<b>AIKIDO</b> SECURITY", fecha="27 de Octubre 2026",
      title="Aikido Security: seguridad del código a la nube, pensada para desarrolladores"),
 dict(id="11", tipo="stat", seed=121, tag="Ley 21.719 · Datos personales", kicker="Vigencia plena el 1 de diciembre de 2026. Faltan…",
      big="34", caption="días para la nueva Ley de Protección de Datos Personales", bigsize=300,
      takeaway="Nueva Agencia de Protección de Datos y multas de hasta 20.000 UTM.",
      src="Ley 21.719; resúmenes Idonea, Araya (2026)"),
 dict(id="12", tipo="reel", seed=131, flip=True, tag="Cierre del mes", hook="Cierra octubre con un plan",
      points=["Protección · Gestión de Riesgo · Operaciones", "+18 años acompañando a Latam", "Habla con tu ejecutivo CPNnet"], dur="30–45 s"),
]
NAMES = {"agenda": "calendario-webinars", "stat": "dato", "list": "ley", "reel": "reel-portada"}

def render():
    shots = {}
    with sync_playwright() as pw:
        b = pw.chromium.launch(executable_path="/opt/pw-browsers/chromium", args=["--no-sandbox"])
        for p in POSTS:
            h = 1920 if p["tipo"] == "reel" else 1350
            pg = b.new_page(viewport={"width": 1080, "height": h})
            pg.set_content(TEMPLATES[p["tipo"]](p), timeout=120000); pg.wait_for_timeout(500)
            name = "webinar-" + p["wordmark"].replace("<b>", "").replace("</b>", "").split()[0].lower() if p["tipo"] == "webinar" else NAMES[p["tipo"]]
            out = ROOT / ("reels" if p["tipo"] == "reel" else "posts") / f"{p['id']}_{name}.png"
            pg.screenshot(path=str(out)); pg.close(); shots[p["id"]] = out
        b.close()
    return shots

def grid(shots):
    order = [p["id"] for p in POSTS if p["id"] != "00"]
    rows = [order[i:i + 3] for i in range(0, 12, 3)]
    W, H, G = 540, 675, 12
    sheet = Image.new("RGB", (3 * W + 4 * G, 4 * H + 5 * G), (4, 21, 43))
    for r, row in enumerate(rows):
        for c, pid in enumerate(row):
            im = Image.open(shots[pid]).convert("RGB")
            if im.height > 1350:
                t = (im.height - 1350) // 2; im = im.crop((0, t, 1080, t + 1350))
            sheet.paste(im.resize((W, H)), (G + c * (W + G), G + r * (H + G)))
    sheet.save(ROOT / "grilla-octubre.png", optimize=True)

if __name__ == "__main__":
    s = render(); grid(s); print("ok", len(s))
