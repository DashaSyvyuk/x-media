<?php

namespace App\Controller\Admin2;

use App\Entity\Filter;
use App\Form\Admin2\FilterType;
use App\Repository\FilterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class FilterEditController extends AbstractController
{
    public function __construct(
        private readonly FilterRepository $filterRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/filters/new', name: 'admin2_filters_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $filter = new Filter();
        $filter->setTitle('');
        $filter->setPriority(0);
        $filter->setOpenedCount(0);
        $filter->setIsOpened(false);

        $form = $this->createForm(FilterType::class, $filter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($filter);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Фільтр «%s» створено.', $filter->getTitle()));

            return $this->redirectToRoute('admin2_filters_edit', ['id' => $filter->getId()]);
        }

        return $this->render('admin2/filters/edit.html.twig', [
            'filter' => $filter,
            'form'   => $form,
            'isNew'  => true,
        ]);
    }

    #[Route('/admin/filters/{id}/edit', name: 'admin2_filters_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $filter = $this->filterRepository->find($id);
        if (! $filter instanceof Filter) {
            throw $this->createNotFoundException('Фільтр не знайдено.');
        }

        $form = $this->createForm(FilterType::class, $filter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Фільтр «%s» збережено.', $filter->getTitle()));

            return $this->redirectToRoute('admin2_filters_edit', ['id' => $filter->getId()]);
        }

        return $this->render('admin2/filters/edit.html.twig', [
            'filter' => $filter,
            'form'   => $form,
            'isNew'  => false,
        ]);
    }
}
