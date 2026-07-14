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

#[Security("is_granted('ROLE_SUPER_ADMIN')")]
final class OrderInvoiceController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderRepository $orderRepository,
        private readonly OrderInvoiceGenerator $invoiceGenerator,
    ) {
    }

    #[Route('/admin2/invoices/fops', name: 'admin2_invoices_fops', methods: ['GET'])]
    public function fops(): JsonResponse
    {
        $items = [];
        foreach ($this->entityManager->getRepository(FopProfile::class)->findBy([], ['id' => 'DESC']) as $fop) {
            $items[] = [
                'id' => $fop->getId(),
                'title' => $fop->getTitle(),
            ];
        }

        return $this->json(['items' => $items]);
    }

    #[Route('/admin2/orders/{id}/invoice', name: 'admin2_orders_invoice_generate', methods: ['POST'])]
    public function generate(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('invoice_generate', (string) $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['error' => 'Невірний CSRF-токен.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode((string) $request->getContent(), true);
        if (! is_array($payload)) {
            return $this->json(['error' => 'Невірні дані.'], Response::HTTP_BAD_REQUEST);
        }

        $order = $this->orderRepository->find($id);
        if (! $order instanceof Order) {
            return $this->json(['error' => 'Замовлення не знайдено.'], Response::HTTP_NOT_FOUND);
        }

        $fopId = (int) ($payload['fopId'] ?? 0);
        $receiverName = trim((string) ($payload['receiverName'] ?? ''));
        $receiverReq = trim((string) ($payload['receiverRequisites'] ?? ''));

        if ($fopId <= 0 || $receiverName === '' || $receiverReq === '') {
            return $this->json(['error' => 'Заповніть усі поля.'], Response::HTTP_BAD_REQUEST);
        }

        $fop = $this->entityManager->getRepository(FopProfile::class)->find($fopId);
        if (! $fop instanceof FopProfile) {
            return $this->json(['error' => 'ФОП не знайдено.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $paths = $this->invoiceGenerator->generate($order, $fop, $receiverName, $receiverReq);

            $zipPath = sys_get_temp_dir() . '/xmedia-invoices/' . bin2hex(random_bytes(8)) . '.zip';
            @mkdir(dirname($zipPath), 0777, true);
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                throw new \RuntimeException('Не вдалося створити ZIP.');
            }

            $zip->addFile($paths['invoiceDocx'], sprintf('Рахунок_%s.docx', $order->getOrderNumber()));
            $zip->addFile($paths['deliveryDocx'], sprintf('Видаткова_%s.docx', $order->getOrderNumber()));
            $zip->close();

            $response = new Response((string) file_get_contents($zipPath));
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', 'attachment; filename="invoice_' . $order->getOrderNumber() . '.zip"');

            return $response;
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}

