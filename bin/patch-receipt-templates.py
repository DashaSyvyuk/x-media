#!/usr/bin/env python3
"""Apply layout fixes to prepared receipt DOCX templates."""
from __future__ import annotations

import io
import re
import shutil
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
TEMPLATES = ROOT / 'templates/admin2/receipts'
LOGO_PREVIEW = TEMPLATES / 'rozetka-logo.png'
ROZETKA_LOGO_MEDIA = 'word/media/image1.png'
ROZETKA_LOGO_CX = 1_415_415
ROZETKA_LOGO_CY = 1_167_765
ROZETKA_LOGO_PX = 800
P_OPEN = re.compile(r'<w:p(?:\s[^>]*)?>')
P_CLOSE = '</w:p>'


def find_paragraph_start(xml: str, pos: int) -> int:
    starts = [m.start() for m in P_OPEN.finditer(xml, 0, pos + 1)]
    if not starts:
        raise RuntimeError('Paragraph start not found')
    return starts[-1]


def find_paragraph_end(xml: str, start: int) -> int:
    close = xml.find(P_CLOSE, start)
    if close < 0:
        raise RuntimeError('Paragraph end not found')
    return close + len(P_CLOSE)


def patch_docx(
    path: Path,
    transform,
    media_updates: dict[str, bytes] | None = None,
) -> None:
    tmp = path.with_suffix('.patch.tmp.docx')
    with zipfile.ZipFile(path, 'r') as zin:
        xml = zin.read('word/document.xml').decode('utf-8')
        xml = transform(xml)
        with zipfile.ZipFile(tmp, 'w', zipfile.ZIP_DEFLATED) as zout:
            for item in zin.infolist():
                data = zin.read(item.filename)
                if item.filename == 'word/document.xml':
                    data = xml.encode('utf-8')
                elif media_updates and item.filename in media_updates:
                    data = media_updates[item.filename]
                zout.writestr(item, data, compress_type=item.compress_type)
    tmp.replace(path)


DATE_PPR = (
    '<w:pPr><w:pStyle w:val="Heading1"/>'
    '<w:tabs><w:tab w:val="clear" w:pos="720"/>'
    '<w:tab w:val="left" w:pos="1066" w:leader="none"/>'
    '<w:tab w:val="left" w:pos="2312" w:leader="none"/></w:tabs>'
    '<w:ind w:right="115" w:hanging="0"/>'
    '<w:jc w:val="right"/>'
    '<w:rPr></w:rPr></w:pPr>'
)


def clear_seller_fields(xml: str) -> str:
    xml = xml.replace('${seller_name}', '')
    marker = xml.find('Продавець')
    if marker < 0:
        return xml

    start = find_paragraph_start(xml, marker)
    signature = xml.find('(підпис)', start)
    if signature < 0:
        return xml

    end = find_paragraph_end(xml, find_paragraph_start(xml, signature))
    block = xml[start:end]
    block = re.sub(
        r'(<w:t[^>]*>)\s*[^<]+\s*(</w:t></w:r><w:r[^>]*>.*?<w:t[^>]*>\(підпис\))',
        r'\1\2',
        block,
        count=1,
        flags=re.DOTALL,
    )
    return xml[:start] + block + xml[end:]


def merge_check_title(xml: str) -> str:
    marker = xml.find('Товарний')
    if marker < 0:
        return xml
    start = find_paragraph_start(xml, marker)
    end = find_paragraph_end(xml, start)
    title_text = 'Товарний\u00a0чек\u00a0№\u00a0${check_number}'
    ppr = (
        '<w:pPr>'
        '<w:pStyle w:val="Title"/>'
        '<w:spacing w:before="96" w:after="0"/>'
        '<w:ind w:left="0" w:right="0" w:firstLine="0"/>'
        '<w:jc w:val="center"/>'
        '<w:rPr><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr>'
        '</w:pPr>'
    )
    rpr = '<w:rPr><w:color w:val="231F20"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr>'
    new_paragraph = (
        f'<w:p>{ppr}<w:r>'
        f'{rpr}<w:t xml:space="preserve">{title_text}</w:t>'
        f'</w:r></w:p>'
    )
    return xml[:start] + new_paragraph + xml[end:]


def build_date_paragraph(xmedia: bool) -> str:
    if xmedia:
        text_rpr = (
            '<w:rPr><w:rFonts w:ascii="Yu Gothic Light" w:hAnsi="Yu Gothic Light"/>'
            '<w:color w:val="231F20"/><w:sz w:val="16"/></w:rPr>'
        )
    else:
        text_rpr = '<w:rPr><w:color w:val="231F20"/><w:sz w:val="16"/></w:rPr>'

    runs = [
        f'<w:r>{text_rpr}<w:t xml:space="preserve">\u00ab ${{date_day}} \u00bb ${{date_month}} ${{date_year}} \u0440.</w:t></w:r>',
    ]
    return f'<w:p>{DATE_PPR}{"".join(runs)}</w:p>'


def replace_date_paragraph(xml: str, xmedia: bool) -> str:
    marker = -1
    for token in ('${date_year}', '2026 \u0440.', '2025 \u0440.'):
        marker = xml.find(token)
        if marker >= 0:
            break
    if marker < 0:
        return xml

    start = find_paragraph_start(xml, marker)
    end = find_paragraph_end(xml, start)
    return xml[:start] + build_date_paragraph(xmedia) + xml[end:]


def replace_total_words(xml: str) -> str:
    marker = xml.find('Всього')
    if marker < 0:
        return xml

    para_start = find_paragraph_start(xml, marker)
    para_end = find_paragraph_end(xml, para_start)
    paragraph = xml[para_start:para_end]
    sum_marker = paragraph.find('суму')
    if sum_marker < 0:
        return xml

    prefix_end = paragraph.find('</w:r>', sum_marker) + len('</w:r>')
    prefix = paragraph[:prefix_end]
    words_run = (
        '<w:r><w:rPr><w:color w:val="231F20"/>'
        '<w:rFonts w:ascii="Microsoft Sans Serif" w:hAnsi="Microsoft Sans Serif"/>'
        '<w:sz w:val="20"/></w:rPr>'
        '<w:t xml:space="preserve"> ${total_words}</w:t></w:r>'
    )
    new_paragraph = prefix + words_run + P_CLOSE
    return xml[:para_start] + new_paragraph + xml[para_end:]


def crop_logo_png(data: bytes) -> tuple[bytes, int, int]:
    from PIL import Image

    image = Image.open(io.BytesIO(data))
    gray = image.convert('L')
    width, height = gray.size
    top = bottom = left = right = None

    for y in range(height):
        if min(gray.getpixel((x, y)) for x in range(0, width, max(1, width // 40))) < 240:
            top = y if top is None else top
            bottom = y

    for x in range(width):
        if min(gray.getpixel((x, y)) for y in range(0, height, max(1, height // 40))) < 240:
            left = x if left is None else left
            right = x

    pad = 8
    top = max(0, (top or 0) - pad)
    bottom = min(height, (bottom or height - 1) + pad)
    left = max(0, (left or 0) - pad)
    right = min(width, (right or width - 1) + pad)

    cropped = image.crop((left, top, right, bottom))
    output = io.BytesIO()
    cropped.save(output, format='PNG')
    return output.getvalue(), cropped.width, cropped.height


def adjust_rozetka_logo(xml: str, v_offset: int, cx: int, cy: int) -> str:
    def repl(match: re.Match[str]) -> str:
        block = match.group(0)
        block = re.sub(
            r'(<wp:positionV relativeFrom="paragraph"><wp:posOffset>)(-?\d+)(</wp:posOffset>)',
            rf'\g<1>{v_offset}\3',
            block,
            count=1,
        )
        block = re.sub(
            r'(<wp:extent cx=")(\d+)(" cy=")(\d+)(")',
            lambda m: f'{m.group(1)}{cx}{m.group(3)}{cy}{m.group(5)}',
            block,
            count=1,
        )
        return block

    return re.sub(
        r'<wp:anchor[^>]*>.*?name="Image1".*?</wp:anchor>',
        repl,
        xml,
        count=1,
        flags=re.DOTALL,
    )


def patch_xmedia(xml: str) -> str:
    xml = clear_seller_fields(xml)
    xml = merge_check_title(xml)
    xml = replace_date_paragraph(xml, xmedia=True)
    return xml


def patch_rozetka(xml: str, logo_cx: int, logo_cy: int) -> str:
    xml = clear_seller_fields(xml)
    xml = merge_check_title(xml)
    xml = replace_date_paragraph(xml, xmedia=False)
    xml = replace_total_words(xml)
    xml = adjust_rozetka_logo(xml, -240000, logo_cx, logo_cy)
    return xml


def patch_rozetka_docx(path: Path) -> None:
    with zipfile.ZipFile(path, 'r') as zin:
        logo_data = zin.read(ROZETKA_LOGO_MEDIA)
    cropped_logo, crop_w, crop_h = crop_logo_png(logo_data)
    LOGO_PREVIEW.write_bytes(cropped_logo)
    logo_cx = int(ROZETKA_LOGO_CX * crop_w / ROZETKA_LOGO_PX)
    logo_cy = int(ROZETKA_LOGO_CY * crop_h / ROZETKA_LOGO_PX)
    patch_docx(
        path,
        lambda xml: patch_rozetka(xml, logo_cx, logo_cy),
        media_updates={ROZETKA_LOGO_MEDIA: cropped_logo},
    )


def main() -> None:
    patch_docx(TEMPLATES / 'x-media.docx', patch_xmedia)
    patch_rozetka_docx(TEMPLATES / 'rozetka.docx')
    print('Patched receipt templates.')


if __name__ == '__main__':
    main()
