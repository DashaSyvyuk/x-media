#!/usr/bin/env python3
"""Prepare invoice.docx template placeholders from original <> markers."""

import io
import re
import sys
import zipfile

# Unique-context replacements on raw OOXML (order matters).
REPLACEMENTS = [
    # Payee block
    (
        '<w:t xml:space="preserve">Отримувач  </w:t></w:r><w:r><w:rPr><w:rFonts w:cs="Calibri"/><w:b/><w:bCs/><w:lang w:val="ru-RU"/></w:rPr><w:t>&lt;&gt;</w:t>',
        '<w:t xml:space="preserve">Отримувач  </w:t></w:r><w:r><w:rPr><w:rFonts w:cs="Calibri"/><w:b/><w:bCs/><w:lang w:val="ru-RU"/></w:rPr><w:t>${payee_name}</w:t>',
    ),
    (
        '<w:t xml:space="preserve">Код </w:t></w:r><w:r><w:rPr><w:rFonts w:eastAsia="Segoe UI" w:cs="Segoe UI"/><w:color w:val="000000"/><w:sz w:val="19"/><w:szCs w:val="19"/><w:shd w:fill="FFFFFF" w:val="clear"/><w:lang w:val="ru-RU"/></w:rPr><w:t>&lt;&gt;</w:t>',
        '<w:t xml:space="preserve">Код </w:t></w:r><w:r><w:rPr><w:rFonts w:eastAsia="Segoe UI" w:cs="Segoe UI"/><w:color w:val="000000"/><w:sz w:val="19"/><w:szCs w:val="19"/><w:shd w:fill="FFFFFF" w:val="clear"/><w:lang w:val="ru-RU"/></w:rPr><w:t>${payee_edrpou}</w:t>',
    ),
    # Header
    (
        'від &lt;&gt; р.',
        'від ${invoice_date} р.',
    ),
    # Supplier
    (
        '<w:t>Постачальник</w:t><w:tab/></w:r><w:r><w:rPr><w:rFonts w:cs="Calibri"/><w:b/><w:bCs/><w:lang w:val="ru-RU"/></w:rPr><w:t>&lt;&gt;</w:t>',
        '<w:t>Постачальник</w:t><w:tab/></w:r><w:r><w:rPr><w:rFonts w:cs="Calibri"/><w:b/><w:bCs/><w:lang w:val="ru-RU"/></w:rPr><w:t>${supplier_name}</w:t>',
    ),
    # Buyer
    (
        '<w:t>Покупець</w:t><w:tab/><w:tab/></w:r><w:r><w:rPr><w:rFonts w:eastAsia="Segoe UI" w:cs="Calibri" w:ascii="Roboto;apple-system;BlinkMacSystemFont;Apple Color Emoji;Segoe UI;Oxygen;Ubuntu;Cantarell;Fira Sans;Droid Sans;Helvetica Neue;sans-serif" w:hAnsi="Roboto;apple-system;BlinkMacSystemFont;Apple Color Emoji;Segoe UI;Oxygen;Ubuntu;Cantarell;Fira Sans;Droid Sans;Helvetica Neue;sans-serif"/><w:b/><w:bCs/><w:i w:val="false"/><w:caps w:val="false"/><w:smallCaps w:val="false"/><w:color w:val="000000"/><w:spacing w:val="0"/><w:sz w:val="19"/><w:szCs w:val="19"/><w:shd w:fill="FFFFFF" w:val="clear"/><w:lang w:val="uk-UA"/></w:rPr><w:t>&lt;&gt;</w:t>',
        '<w:t>Покупець</w:t><w:tab/><w:tab/></w:r><w:r><w:rPr><w:rFonts w:eastAsia="Segoe UI" w:cs="Calibri" w:ascii="Roboto;apple-system;BlinkMacSystemFont;Apple Color Emoji;Segoe UI;Oxygen;Ubuntu;Cantarell;Fira Sans;Droid Sans;Helvetica Neue;sans-serif" w:hAnsi="Roboto;apple-system;BlinkMacSystemFont;Apple Color Emoji;Segoe UI;Oxygen;Ubuntu;Cantarell;Fira Sans;Droid Sans;Helvetica Neue;sans-serif"/><w:b/><w:bCs/><w:i w:val="false"/><w:caps w:val="false"/><w:smallCaps w:val="false"/><w:color w:val="000000"/><w:spacing w:val="0"/><w:sz w:val="19"/><w:szCs w:val="19"/><w:shd w:fill="FFFFFF" w:val="clear"/><w:lang w:val="uk-UA"/></w:rPr><w:t>${buyer_name}</w:t>',
    ),
    # Buyer details (receiver requisites) — keep regular weight, not bold
    (
        '<w:p><w:pPr><w:pStyle w:val="Normal"/><w:rPr><w:rFonts w:cs="Calibri"/><w:b/><w:b/><w:bCs/><w:lang w:val="uk-UA"/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:eastAsia="Segoe UI" w:cs="Calibri"/><w:b/><w:bCs/><w:color w:val="000000"/><w:sz w:val="19"/><w:szCs w:val="19"/><w:shd w:fill="FFFFFF" w:val="clear"/><w:lang w:val="uk-UA"/></w:rPr><w:tab/><w:tab/><w:tab/><w:t>&lt;</w:t></w:r><w:r><w:rPr><w:rFonts w:eastAsia="Segoe UI" w:cs="Calibri" w:ascii="Roboto;apple-system;BlinkMacSystemFont;Apple Color Emoji;Segoe UI;Oxygen;Ubuntu;Cantarell;Fira Sans;Droid Sans;Helvetica Neue;sans-serif" w:hAnsi="Roboto;apple-system;BlinkMacSystemFont;Apple Color Emoji;Segoe UI;Oxygen;Ubuntu;Cantarell;Fira Sans;Droid Sans;Helvetica Neue;sans-serif"/><w:b w:val="false"/><w:bCs w:val="false"/><w:i w:val="false"/><w:caps w:val="false"/><w:smallCaps w:val="false"/><w:color w:val="000000"/><w:spacing w:val="0"/><w:sz w:val="19"/><w:szCs w:val="19"/><w:shd w:fill="FFFFFF" w:val="clear"/><w:lang w:val="uk-UA"/></w:rPr><w:t>&gt;</w:t></w:r></w:p>',
        '<w:p><w:pPr><w:pStyle w:val="Normal"/><w:rPr><w:rFonts w:cs="Calibri"/><w:lang w:val="uk-UA"/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:eastAsia="Segoe UI" w:cs="Calibri"/><w:color w:val="000000"/><w:sz w:val="19"/><w:szCs w:val="19"/><w:shd w:fill="FFFFFF" w:val="clear"/><w:lang w:val="uk-UA"/></w:rPr><w:tab/><w:tab/><w:tab/><w:t>${buyer_details}</w:t></w:r></w:p>',
    ),
    # Totals
    (
        'Всього найменувань &lt;&gt;, на суму &lt;&gt; грн',
        'Всього найменувань ${items_count}, на суму ${total_sum} грн',
    ),
]

# Remaining bare <> in document order after context replacements
SEQUENTIAL_MARKERS = [
    'payee_account',
    'supplier_account',
    'supplier_edrpou',
    'supplier_address',
    'item_title',
    'item_qty',
    'item_price',
    'item_sum',
    'total_words',
]


def prepare(src: str, dst: str) -> None:
    with zipfile.ZipFile(src, 'r') as zin:
        entries = [(item, zin.read(item.filename)) for item in zin.infolist()]
        xml = next(d for i, d in entries if i.filename == 'word/document.xml').decode('utf-8')

        xml = re.sub(
            r'(<w:t[^>]*>)№25(</w:t></w:r><w:r[^>]*><w:rPr>[\s\S]*?</w:rPr><w:t[^>]*>)/06-26',
            r'\1№${invoice_number}\2',
            xml,
            count=1,
        )

        for old, new in REPLACEMENTS:
            if old not in xml:
                raise RuntimeError(f'Missing expected fragment: {old[:60]}...')
            xml = xml.replace(old, new, 1)

        for marker in SEQUENTIAL_MARKERS:
            if '&lt;&gt;' not in xml:
                raise RuntimeError(f'No placeholder left for {marker}')
            xml = xml.replace('&lt;&gt;', '${' + marker + '}', 1)

        xml = xml.replace(
            '>1</w:t></w:r></w:p></w:tc><w:tc',
            '>${item_no}</w:t></w:r></w:p></w:tc><w:tc',
            1,
        )

        if '&lt;&gt;' in xml or '&lt;</w:t>' in xml:
            raise RuntimeError('Some placeholders remain unmapped')

        buf = io.BytesIO()
        with zipfile.ZipFile(buf, 'w', zipfile.ZIP_DEFLATED) as zout:
            for item, data in entries:
                if item.filename == 'word/document.xml':
                    data = xml.encode('utf-8')
                zout.writestr(item, data)

        with open(dst, 'wb') as f:
            f.write(buf.getvalue())


if __name__ == '__main__':
    src = sys.argv[1] if len(sys.argv) > 1 else '/tmp/docx-inspect/invoice.docx'
    dst = sys.argv[2] if len(sys.argv) > 2 else 'templates/admin2/invoices/invoice.docx'
    prepare(src, dst)
    print('OK', dst)
