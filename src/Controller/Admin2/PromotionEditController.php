<?php

namespace App\Controller\Admin2;

use App\Entity\Promotion;
use App\Form\Admin2\PromotionType;
use App\Repository\PromotionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class PromotionEditController extends AbstractController
{
    public function __construct(
        private readonly PromotionRepository $promotionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/promotions/new', name: 'admin2_promotions_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $promotion = new Promotion();
        $promotion->setTitle('');
        $promotion->setSlug('');
        $promotion->setDescription('');
        $promotion->setStatus(Promotion::ACTIVE);
        $promotion->setActiveFrom(new \DateTime());
        $promotion->setActiveTo((new \DateTime())->modify('+1 month'));

        return $this->handleForm($request, $promotion, true);
    }

    #[Route('/admin/promotions/{id}/edit', name: 'admin2_promotions_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $promotion = $this->promotionRepository->find($id);
        if (! $promotion instanceof Promotion) {
            throw $this->createNotFoundException('Акцію не знайдено.');
        }

        return $this->handleForm($request, $promotion, false);
    }

    private function handleForm(Request $request, Promotion $promotion, bool $isNew): Response
    {
        $form = $this->createForm(PromotionType::class, $promotion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isNew) {
                $this->entityManager->persist($promotion);
            }

            $this->entityManager->flush();

            $this->addFlash(
                'success',
                $isNew
                    ? sprintf('Акцію «%s» створено.', $promotion->getTitle())
                    : sprintf('Акцію «%s» збережено.', $promotion->getTitle()),
            );

            return $this->redirectToRoute('admin2_promotions_edit', ['id' => $promotion->getId()]);
        }

        return $this->render('admin2/promotions/edit.html.twig', [
            'promotion' => $promotion,
            'form'      => $form,
            'isNew'     => $isNew,
        ]);
    }
}
