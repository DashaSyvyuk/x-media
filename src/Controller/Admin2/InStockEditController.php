<?php

namespace App\Controller\Admin2;

use App\Entity\InStock;
use App\Form\Admin2\InStockType;
use App\Repository\InStockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class InStockEditController extends AbstractController
{
    public function __construct(
        private readonly InStockRepository $inStockRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/in-stock/new', name: 'admin2_in_stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $stock = new InStock();

        return $this->handleForm($request, $stock, true);
    }

    #[Route('/admin/in-stock/{id}/edit', name: 'admin2_in_stock_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $stock = $this->inStockRepository->find($id);
        if (! $stock instanceof InStock) {
            throw $this->createNotFoundException('Запис наявності не знайдено.');
        }

        return $this->handleForm($request, $stock, false);
    }

    private function handleForm(Request $request, InStock $stock, bool $isNew): Response
    {
        $form = $this->createForm(InStockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isNew) {
                $this->entityManager->persist($stock);
            }

            $this->entityManager->flush();
            $this->addFlash('success', $isNew ? 'Запис наявності створено.' : 'Запис наявності збережено.');

            return $this->redirectToRoute('admin2_in_stock_edit', ['id' => $stock->getId()]);
        }

        return $this->render('admin2/in_stock/edit.html.twig', [
            'stock' => $stock,
            'form'  => $form,
            'isNew' => $isNew,
        ]);
    }
}
