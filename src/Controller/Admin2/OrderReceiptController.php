<?php

namespace App\Controller\Admin2;

use App\Service\Admin2\OrderReceiptDataBuilder;
use App\Service\Admin2\OrderReceiptGenerator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class OrderReceiptController extends AbstractController
{
    public function __construct(
        private readonly OrderReceiptDataBuilder $dataBuilder,
        private readonly OrderReceiptGenerator $receiptGenerator,
    ) {
    }

    #[Route('/admin/receipts/data/{type}/{id}', name: 'admin2_receipts_data', methods: ['GET'])]
    public function data(string $type, int $id): JsonResponse
    {
        try {
            $payload = match ($type) {
                'local'   => $this->dataBuilder->buildForLocal($id),
                'rozetka' => $this->dataBuilder->buildForRozetka($id),
                default   => throw new \RuntimeException('Невідомий тип замовлення.'),
            };
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($payload);
    }

    #[Route('/admin/receipts/amount-words', name: 'admin2_receipts_amount_words', methods: ['GET'])]
    public function amountWords(Request $request): JsonResponse
    {
        $amount = max(0, $request->query->getInt('amount'));

        return $this->json([
            'totalWords' => $this->dataBuilder->formatAmountWords($amount),
        ]);
    }

    #[Route('/admin/receipts/generate', name: 'admin2_receipts_generate', methods: ['POST'])]
    public function generate(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('receipt_generate', (string) $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['error' => 'Невірний CSRF-токен.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode((string) $request->getContent(), true);
        if (! is_array($payload)) {
            return $this->json(['error' => 'Невірні дані чека.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $payload = $this->normalizePayload($payload);
            $docxPath = $this->receiptGenerator->generateDocx($payload);
            $filename = $this->sanitizeFilename((string) ($payload['filename'] ?? 'receipt')) . '.docx';

            $response = new Response((string) file_get_contents($docxPath));
            $response->headers->set(
                'Content-Type',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            );
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

            return $response;
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function normalizePayload(array $payload): array
    {
        $items = [];
        foreach ($payload['items'] ?? [] as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $price = max(0, (int) ($item['price'] ?? 0));
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $items[] = [
                'no'       => max(1, (int) ($item['no'] ?? ($index + 1))),
                'name'     => trim((string) ($item['name'] ?? '')),
                'warranty' => max(0, (int) ($item['warranty'] ?? 24)),
                'qty'      => $qty,
                'price'    => $price,
                'sum'      => max(0, (int) ($item['sum'] ?? ($price * $qty))),
            ];
        }

        $total = max(0, (int) ($payload['total'] ?? 0));
        if ($total <= 0) {
            $total = array_sum(array_column($items, 'sum'));
        }

        $totalWords = trim((string) ($payload['totalWords'] ?? ''));
        if ($totalWords === '') {
            $totalWords = $this->dataBuilder->formatAmountWords($total);
        }

        $template = (string) ($payload['template'] ?? 'xmedia');
        if (! in_array($template, ['xmedia', 'rozetka'], true)) {
            throw new \RuntimeException('Невірний шаблон чека.');
        }

        return [
            'template'    => $template,
            'filename'    => $this->sanitizeFilename((string) ($payload['filename'] ?? 'receipt')),
            'checkNumber' => trim((string) ($payload['checkNumber'] ?? '')),
            'dateDay'     => trim((string) ($payload['dateDay'] ?? '')),
            'dateMonth'   => (string) ($payload['dateMonth'] ?? ''),
            'dateYear'    => trim((string) ($payload['dateYear'] ?? '')),
            'items'       => $items,
            'total'       => $total,
            'totalWords'  => $totalWords,
        ];
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^\w\-]+/u', '_', $filename) ?? 'receipt';

        return trim($filename, '_') !== '' ? trim($filename, '_') : 'receipt';
    }
}
