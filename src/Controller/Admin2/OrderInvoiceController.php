<?php

namespace App\Controller\Admin2;

use App\Entity\FopProfile;
use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\Admin2\OrderInvoiceGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use ZipArchive;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
final class OrderInvoiceController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderRepository $orderRepository,
        private readonly OrderInvoiceGenerator $invoiceGenerator,
    ) {
    }

    #[Route('/admin/invoices/fops', name: 'admin2_invoices_fops', methods: ['GET'])]
    public function fops(): JsonResponse
    {
        $items = [];
        foreach ($this->entityManager->getRepository(FopProfile::class)->findBy([], ['id' => 'DESC']) as $fop) {
            $items[] = [
                'id'    => $fop->getId(),
                'title' => $fop->getTitle(),
            ];
        }

        return $this->json(['items' => $items]);
    }

    #[Route('/admin/orders/{id}/invoice', name: 'admin2_orders_invoice_generate', methods: ['POST'])]
    public function generateLocal(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('invoice_generate', (string) $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['error' => 'Невірний CSRF-токен.'], Response::HTTP_FORBIDDEN);
        }

        $payload = $this->decodePayload($request);
        if ($payload === null) {
            return $this->json(['error' => 'Невірні дані.'], Response::HTTP_BAD_REQUEST);
        }

        $order = $this->orderRepository->find($id);
        if (! $order instanceof Order) {
            // Stale clients may POST Rozetka ids to the local endpoint.
            [$fop, $receiverName, $receiverReq, $error] = $this->resolveInvoiceInput($payload);
            if ($error !== null || ! $fop instanceof FopProfile) {
                return $this->json(
                    ['error' => $error ?? 'Замовлення не знайдено.'],
                    $error !== null ? Response::HTTP_BAD_REQUEST : Response::HTTP_NOT_FOUND,
                );
            }

            try {
                $paths = $this->invoiceGenerator->generateForRozetka($id, $fop, $receiverName, $receiverReq);

                return $this->zipResponse($paths, (string) $id);
            } catch (\Throwable $e) {
                return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        [$fop, $receiverName, $receiverReq, $error] = $this->resolveInvoiceInput($payload);
        if ($error !== null || ! $fop instanceof FopProfile) {
            return $this->json(['error' => $error ?? 'ФОП не знайдено.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $paths = $this->invoiceGenerator->generate($order, $fop, $receiverName, $receiverReq);

            return $this->zipResponse($paths, $order->getOrderNumber());
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/admin/rozetka-orders/{id}/invoice', name: 'admin2_rozetka_orders_invoice_generate', methods: ['POST'])]
    public function generateRozetka(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('invoice_generate', (string) $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['error' => 'Невірний CSRF-токен.'], Response::HTTP_FORBIDDEN);
        }

        $payload = $this->decodePayload($request);
        if ($payload === null) {
            return $this->json(['error' => 'Невірні дані.'], Response::HTTP_BAD_REQUEST);
        }

        [$fop, $receiverName, $receiverReq, $error] = $this->resolveInvoiceInput($payload);
        if ($error !== null || ! $fop instanceof FopProfile) {
            return $this->json(['error' => $error ?? 'ФОП не знайдено.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $paths = $this->invoiceGenerator->generateForRozetka($id, $fop, $receiverName, $receiverReq);

            return $this->zipResponse($paths, (string) $id);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodePayload(Request $request): ?array
    {
        $payload = json_decode((string) $request->getContent(), true);

        return is_array($payload) ? $payload : null;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{0: ?FopProfile, 1: string, 2: string, 3: ?string}
     */
    private function resolveInvoiceInput(array $payload): array
    {
        $fopId = (int) ($payload['fopId'] ?? 0);
        $receiverName = trim((string) ($payload['receiverName'] ?? ''));
        $receiverReq = trim((string) ($payload['receiverRequisites'] ?? ''));

        if ($fopId <= 0 || $receiverName === '' || $receiverReq === '') {
            return [null, '', '', 'Заповніть усі поля.'];
        }

        $fop = $this->entityManager->getRepository(FopProfile::class)->find($fopId);
        if (! $fop instanceof FopProfile) {
            return [null, '', '', 'ФОП не знайдено.'];
        }

        return [$fop, $receiverName, $receiverReq, null];
    }

    /**
     * @param array{invoiceDocx: string, deliveryDocx: string} $paths
     */
    private function zipResponse(array $paths, string $orderNumber): Response
    {
        $zipPath = sys_get_temp_dir() . '/xmedia-invoices/' . bin2hex(random_bytes(8)) . '.zip';
        @mkdir(dirname($zipPath), 0777, true);
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Не вдалося створити ZIP.');
        }

        $safeNumber = preg_replace('/[^\w\-]+/u', '_', $orderNumber) ?: 'order';
        $zip->addFile($paths['invoiceDocx'], sprintf('Рахунок_%s.docx', $safeNumber));
        $zip->addFile($paths['deliveryDocx'], sprintf('Видаткова_%s.docx', $safeNumber));
        $zip->close();

        $response = new Response((string) file_get_contents($zipPath));
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="invoice_' . $safeNumber . '.zip"',
        );

        return $response;
    }
}
