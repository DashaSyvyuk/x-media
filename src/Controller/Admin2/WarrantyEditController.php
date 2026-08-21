<?php

namespace App\Controller\Admin2;

use App\Entity\Warranty;
use App\Form\Admin2\WarrantyType;
use App\Repository\WarrantyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class WarrantyEditController extends AbstractController
{
    public function __construct(
        private readonly WarrantyRepository $warrantyRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/warranties/{id}/edit', name: 'admin2_warranties_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $warranty = $this->warrantyRepository->find($id);
        if (! $warranty instanceof Warranty) {
            throw $this->createNotFoundException('Гарантію не знайдено.');
        }

        $form = $this->createForm(WarrantyType::class, $warranty);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Гарантію #%d збережено.', $warranty->getId()));

            return $this->redirectToRoute('admin2_warranties');
        }

        return $this->render('admin2/warranties/edit.html.twig', [
            'warranty' => $warranty,
            'form'     => $form,
        ]);
    }
}
