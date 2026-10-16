#!/usr/bin/env python3
"""Reels animados (MP4 1080x1920, 15 s, 24 fps, sin audio) a partir de las portadas de build_rr2.
Uso: python3 build_reels_mp4.py <identidades|endpoints|aplicaciones> -> ../v5-risk-reduction-portal/mp4/"""
import sys, subprocess, shutil, tempfile, pathlib
from playwright.sync_api import sync_playwright
import build_rr2 as m

NAMES = {"identidades": "02_reel-segura", "endpoints": "05_reel-faronics", "aplicaciones": "08_reel-aikido"}
FPS, DUR = 24, 15.0
OUT = m.OUT / "mp4"; OUT.mkdir(exist_ok=True)

SETUP = """
() => {
  const cv = document.querySelector('.cv'); const k = [...cv.children];
  // 0 grid, 1 foto, 2 arco, 3 play, 4 banderas, 5 etiqueta, 6 titular, 7 logo marca, 8 pasos, 9 cta, 10 logo CPNnet
  k[3].style.display = 'none';
  const T = DUR * 1000, anims = [];
  const A = (el, kf, delay, dur, easing='cubic-bezier(.2,.7,.2,1)') => anims.push(el.animate(kf, {duration: dur, delay, fill: 'both', easing}));
  const rise = [{opacity: 0, transform: 'translateY(40px)'}, {opacity: 1, transform: 'translateY(0)'}];
  const fade = [{opacity: 0}, {opacity: 1}];
  A(k[1].querySelector('canvas'), [{transform: 'scale(1)'}, {transform: 'scale(1.10)'}], 0, T, 'linear');
  A(k[2], [{transform: 'rotate(0deg)', transformOrigin: '900px 1010px'}, {transform: 'rotate(50deg)', transformOrigin: '900px 1010px'}], 0, T, 'linear');
  A(k[10], fade, 0, 600); A(k[4], fade, 200, 600); A(k[5], rise, 500, 700);
  A(k[6], rise, 900, 900);
  A(k[7], rise, 3800, 700);
  [...k[8].querySelectorAll('.step')].forEach((s, i) => A(s, rise, 4800 + i * 3000, 800));
  A(k[9], rise, 13300, 700);
  window.seek = (t) => anims.forEach(a => { a.pause(); a.currentTime = t; });
}
""".replace("DUR", str(DUR))

def main(key):
    html = m.t_reel(key)
    tmp = pathlib.Path(tempfile.mkdtemp())
    with sync_playwright() as pw:
        br = pw.chromium.launch(executable_path="/opt/pw-browsers/chromium", args=["--no-sandbox"])
        pg = br.new_page(viewport={"width": 1080, "height": 1920})
        pg.set_content(html, timeout=120000); pg.wait_for_timeout(800)
        pg.evaluate(SETUP)
        n = int(FPS * DUR)
        for i in range(n):
            pg.evaluate("t => window.seek(t)", i * 1000 / FPS)
            pg.screenshot(path=str(tmp / f"f{i:04d}.jpg"), type="jpeg", quality=93)
        br.close()
    out = OUT / f"{NAMES[key]}_1080x1920.mp4"
    subprocess.run(["ffmpeg", "-y", "-loglevel", "error", "-framerate", str(FPS), "-i", str(tmp / "f%04d.jpg"),
                    "-c:v", "libx264", "-pix_fmt", "yuv420p", "-crf", "18", "-movflags", "+faststart", str(out)], check=True)
    shutil.rmtree(tmp)
    print("ok", out)

if __name__ == "__main__":
    main(sys.argv[1])
