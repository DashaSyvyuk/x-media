#!/usr/bin/env python3
"""Generate admin2 PWA icons from a source PNG/SVG (min 512x512).

Usage:
  python3 public/admin2/pwa/generate-icons.py path/to/your-icon.png
"""
from __future__ import annotations

import sys
from pathlib import Path

from PIL import Image

SIZES = [72, 96, 128, 144, 152, 180, 192, 384, 512]
OUT_DIR = Path(__file__).resolve().parent / "icons"


def fit_square(img: Image.Image, size: int, maskable: bool = False) -> Image.Image:
    canvas = Image.new("RGBA", (size, size), (15, 23, 42, 255))
    pad = int(size * (0.18 if maskable else 0.08))
    inner = size - pad * 2
    resized = img.convert("RGBA")
    resized.thumbnail((inner, inner), Image.Resampling.LANCZOS)
    x = (size - resized.width) // 2
    y = (size - resized.height) // 2
    canvas.paste(resized, (x, y), resized)
    return canvas


def main() -> int:
    if len(sys.argv) != 2:
        print(__doc__ or "")
        return 1

    source = Path(sys.argv[1])
    if not source.is_file():
        print(f"File not found: {source}")
        return 1

    img = Image.open(source)
    OUT_DIR.mkdir(parents=True, exist_ok=True)

    for size in SIZES:
        fit_square(img, size).convert("RGB").save(OUT_DIR / f"icon-{size}.png")

    fit_square(img, 512, maskable=True).convert("RGB").save(OUT_DIR / "icon-maskable-512.png")
    fit_square(img, 192, maskable=True).convert("RGB").save(OUT_DIR / "icon-maskable-192.png")
    fit_square(img, 32).convert("RGB").save(OUT_DIR / "favicon-32.png")

    print(f"Icons written to {OUT_DIR}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
