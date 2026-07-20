#!/usr/bin/env python3
"""Generate admin2 PWA icons from a full-bleed square source PNG (min 512x512).

Usage:
  python3 public/admin2/pwa/generate-icons.py path/to/your-icon.png
"""
from __future__ import annotations

import sys
from pathlib import Path

from PIL import Image

SIZES = [72, 96, 128, 144, 152, 180, 192, 384, 512]
OUT_DIR = Path(__file__).resolve().parent / "icons"
RESAMPLE = getattr(getattr(Image, "Resampling", Image), "LANCZOS", Image.LANCZOS)


def resize_square(img: Image.Image, size: int) -> Image.Image:
    """Source already includes brand background — just scale."""
    return img.convert("RGBA").resize((size, size), RESAMPLE)


def maskable_square(img: Image.Image, size: int) -> Image.Image:
    """Keep safe zone for Android adaptive icons (~80% content)."""
    canvas = Image.new("RGBA", (size, size), (13, 148, 136, 255))  # #0d9488
    inner = int(size * 0.8)
    resized = resize_square(img, inner)
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
        resize_square(img, size).convert("RGB").save(
            OUT_DIR / f"icon-{size}.png",
            optimize=True,
        )

    maskable_square(img, 512).convert("RGB").save(
        OUT_DIR / "icon-maskable-512.png",
        optimize=True,
    )
    maskable_square(img, 192).convert("RGB").save(
        OUT_DIR / "icon-maskable-192.png",
        optimize=True,
    )
    resize_square(img, 32).convert("RGB").save(
        OUT_DIR / "favicon-32.png",
        optimize=True,
    )

    print(f"Icons written to {OUT_DIR}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
