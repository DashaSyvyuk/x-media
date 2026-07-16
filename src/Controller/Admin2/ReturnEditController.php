<?php

namespace App\Controller\Admin2;

use App\Entity\ReturnProduct;
use App\Form\Admin2\ReturnProductType;
use App\Repository\ReturnProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class ReturnEditController extends AbstractController
{
    public function __construct(
        private readonly ReturnProductRepository $returnProductRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/returns/{id}/edit', name: 'admin2_returns_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $return = $this->returnProductRepository->find($id);
        if (! $return instanceof ReturnProduct) {
            throw $this->createNotFoundException('Повернення не знайдено.');
        }

        $form = $this->createForm(ReturnProductType::class, $return);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Повернення #%d збережено.', $return->getId()));

            return $this->redirectToRoute('admin2_returns_edit', ['id' => $return->getId()]);
        }

        return $this->render('admin2/returns/edit.html.twig', [
            'return' => $return,
            'form'   => $form,
        ]);
    }
}
