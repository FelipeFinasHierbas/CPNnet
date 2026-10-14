#!/usr/bin/env python3
"""Extrae los logos de las marcas desde las gráficas de campaña Risk Reduction Q4 (fondo navy -> transparente)."""
import glob, pathlib
import numpy as np
from PIL import Image, ImageFilter

ROOT = pathlib.Path(__file__).resolve().parent.parent
IMG = glob.glob('/tmp/claude-0/-home-user-CPNnet/*/images/')[0]
OUT = ROOT / "assets/logos"; OUT.mkdir(parents=True, exist_ok=True)
BOXES = {  # (archivo, x0, y0, x1, y1) en px de la imagen original 1125x750
    "segura": ("3.jpg", 62, 624, 188, 660), "aikido": ("3.jpg", 238, 623, 362, 660),
    "kriptos": ("3.jpg", 414, 624, 534, 660), "vicarius": ("3.jpg", 584, 616, 722, 668),
    "safetica": ("3.jpg", 762, 626, 888, 662), "faronics": ("3.jpg", 944, 621, 1056, 664),
    "action1": ("4.jpg", 590, 620, 726, 662),
}
for name, (f, x0, y0, x1, y1) in BOXES.items():
    im = Image.open(IMG + f).convert("RGB").crop((x0, y0, x1, y1))
    im = im.resize((im.width * 4, im.height * 4), Image.LANCZOS)
    a = np.asarray(im).astype(float)
    bg = np.median(np.concatenate([a[:3].reshape(-1, 3), a[-3:].reshape(-1, 3)]), axis=0)
    diff = np.abs(a - bg).max(axis=2)
    alpha = np.clip((diff - 42) / 100, 0, 1)
    rgb = np.clip((a - (1 - alpha[..., None]) * bg) / np.maximum(alpha[..., None], .08), 0, 255)
    out = np.dstack([rgb, alpha * 255]).astype(np.uint8)
    img = Image.fromarray(out, "RGBA")
    bbox = img.getchannel("A").point(lambda v: 255 if v > 40 else 0).getbbox()
    if bbox:
        p = 10; img = img.crop((max(0, bbox[0] - p), max(0, bbox[1] - p), min(img.width, bbox[2] + p), min(img.height, bbox[3] + p)))
    img.save(OUT / f"{name}.png"); print(name, img.size)
