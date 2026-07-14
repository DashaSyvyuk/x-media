#!/usr/bin/env python3
"""Inject PHPWord placeholders into receipt DOCX templates."""
from __future__ import annotations

import re
import shutil
import subprocess
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SRC_XMEDIA = Path('/home/roma/Завантаження/x-media.docx')
SRC_ROZETKA = Path('/home/roma/Завантаження/Чек розетка.docx')
OUT_DIR = ROOT / 'templates/admin2/receipts'

SPLIT_AMOUNT_PATTERN = (
    r'(<w:t[^>]*>)67(</w:t></w:r><w:r>.*?<w:t[^>]*>)\s*(</w:t></w:r><w:r>.*?<w:t[^>]*>)4(</w:t></w:r><w:r>.*?<w:t[^>]*>)99(</w:t>)'
)


def replace_row_texts_in_order(segment: str, values: list[str]) -> str:
    index = 0

    def repl(match: re.Match[str]) -> str:
        nonlocal index
        if index >= len(values):
            return match.group(0)
        value = values[index]
        index += 1
        return f'{match.group(1)}{value}{match.group(3)}'

    return re.sub(r'(<w:t[^>]*>)([^<]*)(</w:t>)', repl, segment)


def replace_product_row(xml: str, marker: str, placeholders: list[str]) -> str:
    idx = xml.find(marker)
    if idx < 0:
        raise RuntimeError(f'Marker not found: {marker}')
    row_start = xml.rfind('<w:tr', 0, idx)
    row_end = xml.find('</w:tr>', idx) + len('</w:tr>')
    row = xml[row_start:row_end]
    new_row = replace_row_texts_in_order(row, placeholders)
    return xml[:row_start] + new_row + xml[row_end:]


def replace_total_amount(xml: str, amount: str, placeholder: str) -> str:
    if amount not in xml:
        raise RuntimeError(f'Total amount not found: {amount}')
    return xml.replace(amount, placeholder, 1)


def replace_split_amount(xml: str, placeholder: str, count: int = 1) -> str:
    for _ in range(count):
        xml, replaced = re.subn(
            SPLIT_AMOUNT_PATTERN,
            rf'\1{placeholder}\5',
            xml,
            count=1,
            flags=re.DOTALL,
        )
        if replaced == 0:
            raise RuntimeError(f'Split amount not found for {placeholder}')
    return xml


def collapse_words_runs(xml: str, first_fragment: str, last_fragment: str, placeholder: str) -> str:
    start = xml.find(f'<w:t>{first_fragment}</w:t>')
    if start < 0:
        start = xml.find(f'<w:t xml:space="preserve">{first_fragment}</w:t>')
    if start < 0:
        raise RuntimeError(f'Words start not found: {first_fragment}')
    end_match = re.search(
        rf'(<w:t[^>]*>)({re.escape(last_fragment)})(</w:t>)',
        xml[start:],
    )
    if end_match is None:
        raise RuntimeError(f'Words end not found: {last_fragment}')
    end = start + end_match.end()
    run_start = xml.rfind('<w:r', 0, start)
    run_end = xml.find('</w:r>', end) + len('</w:r>')
    rpr_match = re.search(r'(<w:rPr>.*?</w:rPr>)', xml[run_start:run_end], re.DOTALL)
    rpr = rpr_match.group(1) if rpr_match else ''
    new_run = f'<w:r>{rpr}<w:t>{placeholder}</w:t></w:r>'
    return xml[:run_start] + new_run + xml[run_end:]


def write_docx(src: Path, dst: Path, transform) -> None:
    tmp = dst.with_suffix('.tmp.docx')
    shutil.copyfile(src, tmp)
    with zipfile.ZipFile(tmp, 'r') as zin:
        xml = zin.read('word/document.xml').decode('utf-8')
        xml = transform(xml)
        with zipfile.ZipFile(dst, 'w') as zout:
            for item in zin.infolist():
                data = zin.read(item.filename)
                if item.filename == 'word/document.xml':
                    data = xml.encode('utf-8')
                zout.writestr(item, data)
    tmp.unlink()


def prepare_xmedia(xml: str) -> str:
    xml = xml.replace('8347621', '${check_number}')
    xml = xml.replace('<w:t xml:space="preserve"> 23</w:t>', '<w:t xml:space="preserve">${date_day}</w:t>')
    xml = xml.replace('<w:t> 23</w:t>', '<w:t>${date_day}</w:t>')
    xml = xml.replace('<w:t>_</w:t>', '')
    xml = xml.replace('<w:t xml:space="preserve"> січня </w:t>', '<w:t xml:space="preserve">${date_month}</w:t>')
    xml = xml.replace('<w:t>2026 р.</w:t>', '<w:t>${date_year} р.</w:t>')
    xml = replace_product_row(
        xml,
        'Відеокарта Gigabyte Radeon RX 9070 XT Gaming OC 16GB GDDR6',
        ['${item_no}', '${item_name}', '${warranty}', '${qty}', '${price}', '${item_sum}'],
    )
    xml = replace_total_amount(xml, '36 499', '${total}')
    xml = xml.replace(
        'Тридцять шість тисяч чотириста дев’яносто дев’ять гривень',
        '${total_words}',
    )
    return xml


def prepare_rozetka(xml: str) -> str:
    xml = xml.replace('8344796', '${check_number}')
    xml = xml.replace('<w:t>08</w:t>', '<w:t>${date_day}</w:t>')
    xml = xml.replace('<w:t>_</w:t>', '')
    xml = xml.replace('<w:t>листопада</w:t>', '<w:t>${date_month}</w:t>')
    xml = xml.replace('<w:t>2025 р.</w:t>', '<w:t>${date_year} р.</w:t>')
    xml = xml.replace(
        'Ноутбук MSI Katana 17 i7-13620H/32GB/1TB+1TB RTX4050 144Hz (Katana 17 B13VEK-1261XPL)',
        '${item_name}',
    )
    xml = xml.replace('<w:t>U</w:t>', '')
    xml = xml.replace('<w:t>S Vivobook 15 X1504VA i5 (X1504VA-</w:t>', '')
    xml = replace_product_row(
        xml,
        '${item_name}',
        ['${item_no}', '${item_name}', '${warranty}', '${qty}'],
    )
    xml = replace_split_amount(xml, '${price}', 1)
    xml = replace_split_amount(xml, '${item_sum}', 1)
    xml = replace_split_amount(xml, '${total}', 1)
    xml = collapse_words_runs(xml, 'Шістдесят сім', ' девʼяносто дев’ять гривень', '${total_words}')
    return xml


def main() -> None:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    write_docx(SRC_XMEDIA, OUT_DIR / 'x-media.docx', prepare_xmedia)
    write_docx(SRC_ROZETKA, OUT_DIR / 'rozetka.docx', prepare_rozetka)
    print('Prepared receipt templates in', OUT_DIR)

    subprocess.run(['python3', str(ROOT / 'bin/patch-receipt-templates.py')], check=True)


if __name__ == '__main__':
    main()
