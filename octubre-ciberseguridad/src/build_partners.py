#!/usr/bin/env python3
"""Grilla de octubre 2026 orientada a partners (12 al 30 de octubre).

Soluciones: Segura, Aikido, Kriptos, Vicarius, Safetica, Faronics, Sophos.
Contenido tomado del Catálogo CPNnet Security para canales (problemas que resuelve y puerta de entrada).
Los webinars (Vicarius 13, Seceon 20, Aikido 27) reutilizan las piezas ya aprobadas.
Uso: python3 build_partners.py -> ../v3-partners/{posts,reels,grilla-partners.png}
"""
import html, shutil, pathlib
from playwright.sync_api import sync_playwright
from PIL import Image
import build as b1
import build_v2 as b2

ROOT = b1.ROOT
OUT = ROOT / "v3-partners"
for d in ("posts", "reels"):
    (OUT / d).mkdir(parents=True, exist_ok=True)
NAVY, AZURE, SKY, CYAN, ROYAL = b2.NAVY, b2.AZURE, b2.SKY, b2.CYAN, b2.ROYAL
TAG = "#CPNnetPartners"

CHECK = ("url(\"data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><path d='M6 12.5l4 4 8-9' "
         "fill='none' stroke='white' stroke-width='3.2' stroke-linecap='round' stroke-linejoin='round'/></svg>\")")
EXTRA = f"""
.sol-b{{display:flex;gap:22px;align-items:flex-start;font-size:29px;font-weight:600;line-height:1.3;margin:20px 0}}
.sol-b:before{{content:'';flex:none;width:38px;height:38px;margin-top:3px;border-radius:50%;background:{AZURE} {CHECK} center/70% no-repeat}}
"""


def page(h, theme, body, **kw):
    return b2.page(h, theme, body, **kw).replace("</style>", EXTRA + "</style>", 1)


def foot(y):
    return f"<div class='pill' style='top:{y}px'>{TAG}</div><div class='abs' style='right:0;top:{y+2}px'>{b1.FLAGS}</div>"


def t_sol(p):
    bullets = "".join(f"<div class='sol-b'><div>{html.escape(x)}</div></div>" for x in p["bullets"])
    rings = b2.arc(1040, 30, 260, 220, -28, 18, .5, .1, gid="a1", opacity=.6)
    return page(1350, "lt", f"""
<svg class='arcs' width='1080' height='1350' viewBox='0 0 1080 1350'>{rings}</svg>
<div class='abs lab' style='left:76px;top:292px'>{p['tag']}</div>
<div class='abs' style='left:76px;top:340px;background:#fff;border-radius:22px;padding:10px 30px;font-size:46px;font-weight:800;letter-spacing:-1px;box-shadow:0 12px 34px rgba(20,50,100,.16)'>{p['marca']}</div>
<h1 class='abs' style='left:76px;right:76px;top:446px;font-size:{p.get("hs",48)}px;line-height:1.15;font-weight:800;text-wrap:balance'>{p['titulo']}</h1>
<div class='abs card' style='left:76px;right:76px;top:{p.get("btop",690)}px;padding:14px 44px'>{bullets}</div>
<div class='abs' style='left:76px;right:76px;top:1068px;background:linear-gradient(135deg,{ROYAL},{NAVY});border-radius:32px;padding:26px 40px;color:#fff;box-shadow:0 16px 40px rgba(20,50,100,.3)'>
  <div style='font-size:20px;font-weight:700;letter-spacing:4px;color:{CYAN};text-transform:uppercase'>Tu puerta de entrada</div>
  <div style='font-size:31px;font-weight:700;margin-top:6px;line-height:1.2'>{p['entrada']}</div></div>
{foot(1245)}""")


def t_reel(p):
    steps = "".join(f"<div class='step'><b>{i+1}</b><div>{x}</div></div>" for i, x in enumerate(p["points"]))
    ring = b2.arc(900, 1010, 150, 150, -28, 14, .75, .0, (CYAN, AZURE), 1, 'a1')
    return page(1920, "dk", f"""
<div class='abs' style='left:0;top:0;width:1080px;height:1090px;clip-path:polygon(0 0,100% 0,100% 78%,0 100%)'><canvas id='bg' width='1080' height='1090'></canvas>
  <div class='abs' style='inset:0;background:linear-gradient(180deg,rgba(3,22,43,.55),rgba(3,22,43,.15) 55%,rgba(3,22,43,.55))'></div></div>
<svg class='arcs' width='1080' height='1920' viewBox='0 0 1080 1920'>{ring}</svg>
<div class='abs' style='left:810px;top:920px;width:180px;height:180px;display:flex;align-items:center;justify-content:center'>
  <div style='width:96px;height:96px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 0 40px rgba(111,227,245,.8)'>
   <div style='border-left:34px solid {AZURE};border-top:21px solid transparent;border-bottom:21px solid transparent;margin-left:10px'></div></div></div>
<div class='abs lab' style='left:76px;top:440px'>{p['tag']}</div>
<div class='abs glow' style='left:76px;top:490px;right:90px;font-size:{p.get("hs",84)}px;font-weight:800;line-height:1.05;letter-spacing:-2px'>{p['hook']}</div>
<div class='abs' style='left:76px;right:76px;top:1180px'>{steps}</div>
<div class='abs lab' style='left:76px;top:1560px;color:{SKY}'>Reel · {p['dur']}</div>
{foot(1700)}""", canv=True, seed=p["seed"], fx=.7, fy=.45, flip=p.get("flip", False))


V = ROOT / "v2-enfoque-portal"
PIECES = [
 dict(n="01", fecha="Lun 12 oct", tipo="copia", nombre="webinar-vicarius", src=ROOT / "vicarius-webinar/vicarius_webinar_1080x1350.png"),
 dict(n="02", fecha="Mié 14 oct", tipo="sol", nombre="kriptos", marca="Kriptos", tag="Para partners · Gestión de riesgo · Datos",
      titulo="¿Saben exactamente dónde está su información sensible y quién puede verla?",
      bullets=["Descubre y clasifica datos no estructurados con IA", "Detecta permisos excesivos en Microsoft 365 y Google", "Base para DLP y para gobernar el uso de IA"],
      entrada="Data Discovery Assessment"),
 dict(n="03", fecha="Vie 16 oct", tipo="reel", nombre="reel-aikido", seed=61, flip=True, tag="Para partners · Aikido", hook="Seguridad que los desarrolladores sí usan",
      points=["Código, dependencias, secretos y cloud en una plataforma", "Menos ruido y corrección asistida con AutoFix", "Demo de entrada: uno o pocos repositorios"], dur="30 s"),
 dict(n="04", fecha="Lun 19 oct", tipo="copia", nombre="webinar-seceon", src=V / "posts/07_webinar-seceon.png"),
 dict(n="05", fecha="Mié 21 oct", tipo="sol", nombre="segura", marca="Segura", tag="Para partners · Protección · Identidad",
      titulo="¿Quién accede a los sistemas críticos de tu cliente, a qué, y qué hace?",
      bullets=["Cuentas privilegiadas en bóveda, con rotación de credenciales", "Acceso de terceros controlado, con sesiones grabadas", "Mínimo privilegio en endpoints, secretos DevOps y cloud"],
      entrada="PAM Assessment o revisión de accesos de terceros"),
 dict(n="06", fecha="Vie 23 oct", tipo="reel", nombre="reel-faronics", seed=71, tag="Para partners · Faronics", hook="Reinicia y vuelve a empezar",
      points=["Deep Freeze restaura el equipo a un estado conocido", "Menos tickets en laboratorios, kioscos y salas", "Piloto de entrada: 10 a 20 equipos"], dur="30 s"),
 dict(n="07", fecha="Lun 26 oct", tipo="web", nombre="webinar-safetica", seed=111, flip=True, wordmark="<b>SAFETICA</b>", fecha_txt="27 de Octubre 2026", dia="27", fecha_pub="27 de Octubre 2026",
      title=""),
 dict(n="08", fecha="Mié 28 oct", tipo="sol", nombre="sophos", marca="Sophos", tag="Para partners · Protección · Red y operaciones",
      titulo="Red, acceso y respuesta en una sola arquitectura", hs=54, btop=630,
      bullets=["Firewall, SD-WAN, switching, wireless y ZTNA desde Sophos Central", "Red y endpoint comparten estado para aislar equipos comprometidos", "XDR y MDR para detectar y responder"],
      entrada="Renovación de firewall, sucursales nuevas o reemplazo de VPN por ZTNA"),
 dict(n="09", fecha="Vie 30 oct", tipo="reel", nombre="reel-cierre-partners", seed=81, flip=True, tag="Cierre del mes · Para partners", hook="Tu próxima oportunidad empieza con un assessment",
      hs=76, points=["Datos: Kriptos y Safetica", "Accesos: Segura · Exposición: Vicarius · Código: Aikido", "Endpoints: Faronics · Red y SOC: Sophos"], dur="30–45 s"),
]


def render():
    shots = {}
    with sync_playwright() as pw:
        br = pw.chromium.launch(executable_path="/opt/pw-browsers/chromium", args=["--no-sandbox"])
        for p in PIECES:
            sub = "reels" if p["tipo"] == "reel" else "posts"
            out = OUT / sub / f"{p['n']}_{p['nombre']}.png"
            if p["tipo"] == "copia":
                shutil.copy(p["src"], out)
            elif p["tipo"] == "web":
                p2 = dict(p, fecha=p["fecha_pub"])
                pg = br.new_page(viewport={"width": 1080, "height": 1350})
                pg.set_content(b2.t_webinar(p2), timeout=120000); pg.wait_for_timeout(500)
                pg.screenshot(path=str(out)); pg.close()
            else:
                h = 1920 if p["tipo"] == "reel" else 1350
                pg = br.new_page(viewport={"width": 1080, "height": h})
                pg.set_content({"sol": t_sol, "reel": t_reel}[p["tipo"]](p), timeout=120000); pg.wait_for_timeout(500)
                pg.screenshot(path=str(out)); pg.close()
            shots[p["n"]] = out
        br.close()
    return shots


def grid(shots):
    order = [p["n"] for p in PIECES]
    W, H, G = 540, 675, 12
    sheet = Image.new("RGB", (3 * W + 4 * G, 3 * H + 4 * G), (234, 242, 250))
    for i, n in enumerate(order):
        im = Image.open(shots[n]).convert("RGB")
        if im.height > 1350:
            t = (im.height - 1350) // 2; im = im.crop((0, t, 1080, t + 1350))
        r, c = divmod(i, 3)
        sheet.paste(im.resize((W, H)), (G + c * (W + G), G + r * (H + G)))
    sheet.save(OUT / "grilla-partners.png", optimize=True)


if __name__ == "__main__":
    s = render(); grid(s); print("ok", len(s))
