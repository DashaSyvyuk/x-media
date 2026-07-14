<?php

namespace App\Service\Admin2;

use App\Entity\FopProfile;
use App\Entity\Order;
use NumberFormatter;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Filesystem\Filesystem;

final class OrderInvoiceGenerator
{
    public function __construct(
        private readonly string $invoiceTemplatesDir,
    ) {
    }

    /**
     * @return array{invoiceDocx: string, deliveryDocx: string}
     */
    public function generate(Order $order, FopProfile $fop, string $receiverName, string $receiverRequisites): array
    {
        $workDir = sys_get_temp_dir() . '/xmedia-invoices/' . bin2hex(random_bytes(8));
        (new Filesystem())->mkdir($workDir);

        $invoicePath = $workDir . '/invoice.docx';
        $deliveryPath = $workDir . '/delivery-note.docx';

        $items = $this->buildItems($order);
        $total = array_sum(array_column($items, 'sum'));
        $values = $this->buildValues($order, $fop, $receiverName, $receiverRequisites, $items, $total);

        $this->fillTemplate($this->templatePath('invoice.docx'), $invoicePath, $values, $items, false);
        $this->fillTemplate($this->templatePath('delivery-note.docx'), $deliveryPath, $values, $items, true);

        return ['invoiceDocx' => $invoicePath, 'deliveryDocx' => $deliveryPath];
    }

    private function templatePath(string $filename): string
    {
        $path = $this->invoiceTemplatesDir . '/' . $filename;
        if (! is_file($path)) {
            throw new \RuntimeException(sprintf('Шаблон документа не знайдено: %s', $filename));
        }

        return $path;
    }

    /**
     * @param array<string, string> $values
     * @param list<array{no: int, title: string, qty: int, price: int, sum: int}> $items
     */
    private function fillTemplate(
        string $templatePath,
        string $outputPath,
        array $values,
        array $items,
        bool $withTableTotal,
    ): void {
        $processor = new TemplateProcessor($templatePath);

        $processor->cloneRow('item_title', max(1, count($items)));
        foreach ($items as $index => $item) {
            $row = $index + 1;
            $processor->setValue('item_no#' . $row, (string) $item['no']);
            $processor->setValue('item_title#' . $row, $item['title']);
            $processor->setValue('item_qty#' . $row, (string) $item['qty']);
            $processor->setValue('item_price#' . $row, $this->formatMoney($item['price']));
            $processor->setValue('item_sum#' . $row, $this->formatMoney($item['sum']));
        }

        foreach ($values as $key => $value) {
            $processor->setValue($key, $value);
        }

        if ($withTableTotal) {
            $processor->setValue('table_total', $values['total_sum']);
        }

        $processor->saveAs($outputPath);
    }

    /**
     * @return list<array{no: int, title: string, qty: int, price: int, sum: int}>
     */
    private function buildItems(Order $order): array
    {
        $items = [];
        $no = 0;

        foreach ($order->getItems() as $item) {
            $qty = max(1, $item->getCount());
            $price = (int) ($item->getPrice() ?? $item->getProduct()->getPrice());
            $items[] = [
                'no'    => ++$no,
                'title' => (string) $item->getProduct()->getTitle(),
                'qty'   => $qty,
                'price' => $price,
                'sum'   => $qty * $price,
            ];
        }

        if ($items === []) {
            $items[] = [
                'no'    => 1,
                'title' => '',
                'qty'   => 1,
                'price' => 0,
                'sum'   => 0,
            ];
        }

        return $items;
    }

    /**
     * @param list<array{no: int, title: string, qty: int, price: int, sum: int}> $items
     *
     * @return array<string, string>
     */
    private function buildValues(
        Order $order,
        FopProfile $fop,
        string $receiverName,
        string $receiverRequisites,
        array $items,
        int $total,
    ): array {
        $today = (new \DateTimeImmutable('now'))->format('d.m.Y');
        $totalFormatted = $this->formatMoney($total);

        return [
            'payee_name'        => $fop->getTitle(),
            'payee_edrpou'      => $fop->getEdrpou(),
            'payee_account'     => $fop->getBankAccount(),
            'invoice_number'    => $order->getOrderNumber(),
            'invoice_date'      => $today,
            'supplier_name'     => $fop->getTitle(),
            'supplier_account'  => $fop->getBankAccount(),
            'supplier_edrpou'   => $fop->getEdrpou(),
            'supplier_address'  => $fop->getAddress(),
            'buyer_name'        => $receiverName,
            'buyer_details'     => $receiverRequisites,
            'items_count'       => (string) count($items),
            'total_sum'         => $totalFormatted,
            'total_words'       => $this->amountInWords($total),
            'table_total'       => $totalFormatted,
        ];
    }

    private function formatMoney(int $amount): string
    {
        return number_format($amount, 2, '.', ' ');
    }

    private function amountInWords(int $amount): string
    {
        $formatter = new NumberFormatter('uk', NumberFormatter::SPELLOUT);
        $words = $formatter->format($amount);
        if (! is_string($words) || $words === '') {
            return '';
        }

        return mb_convert_case($words, MB_CASE_TITLE, 'UTF-8') . ' грн 00 коп.';
    }
}
