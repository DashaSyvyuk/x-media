<?php

namespace App\Service\Admin2;

use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class OrderReceiptGenerator
{
    public function __construct(
        private readonly string $receiptTemplatesDir,
        private readonly string $sofficeBinary = 'soffice',
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function generatePdf(array $payload): string
    {
        $template = ($payload['template'] ?? '') === 'rozetka' ? 'rozetka.docx' : 'x-media.docx';
        $templatePath = $this->receiptTemplatesDir . '/' . $template;
        if (! is_file($templatePath)) {
            throw new \RuntimeException(sprintf('Шаблон чека не знайдено: %s', $template));
        }

        $workDir = sys_get_temp_dir() . '/xmedia-receipts/' . bin2hex(random_bytes(8));
        (new Filesystem())->mkdir($workDir);

        $docxPath = $workDir . '/receipt.docx';
        $this->fillTemplate($templatePath, $docxPath, $payload);

        $pdfPath = $this->convertToPdf($docxPath, $workDir);

        return $pdfPath;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function fillTemplate(string $templatePath, string $outputPath, array $payload): void
    {
        $processor = new TemplateProcessor($templatePath);
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];

        $processor->cloneRow('item_name', max(1, count($items)));
        foreach (array_values($items) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $row = $index + 1;
            $processor->setValue('item_no#' . $row, (string) ($item['no'] ?? $row));
            $processor->setValue('item_name#' . $row, (string) ($item['name'] ?? ''));
            $processor->setValue('warranty#' . $row, (string) ($item['warranty'] ?? 24));
            $processor->setValue('qty#' . $row, (string) ($item['qty'] ?? 1));
            $processor->setValue('price#' . $row, $this->formatMoney((int) ($item['price'] ?? 0)));
            $processor->setValue('item_sum#' . $row, $this->formatMoney((int) ($item['sum'] ?? 0)));
        }

        $processor->setValue('check_number', (string) ($payload['checkNumber'] ?? ''));
        $processor->setValue('date_day', (string) ($payload['dateDay'] ?? ''));
        $processor->setValue('date_month', (string) ($payload['dateMonth'] ?? ''));
        $processor->setValue('date_year', (string) ($payload['dateYear'] ?? ''));
        $processor->setValue('total', $this->formatMoney((int) ($payload['total'] ?? 0)));
        $processor->setValue('total_words', (string) ($payload['totalWords'] ?? ''));

        $processor->saveAs($outputPath);
    }

    private function convertToPdf(string $docxPath, string $workDir): string
    {
        $process = new Process([
            $this->sofficeBinary,
            '--headless',
            '--norestore',
            '--convert-to',
            'pdf',
            '--outdir',
            $workDir,
            $docxPath,
        ]);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Не вдалося конвертувати чек у PDF: ' . trim($process->getErrorOutput()));
        }

        $pdfPath = $workDir . '/receipt.pdf';
        if (! is_file($pdfPath)) {
            throw new \RuntimeException('PDF-файл чека не створено.');
        }

        return $pdfPath;
    }

    private function formatMoney(int $amount): string
    {
        return number_format($amount, 0, '.', ' ');
    }
}
