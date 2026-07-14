<?php

namespace App\Controller\Admin2;

use App\Repository\SliderRepository;
use App\Service\Admin2\Admin2Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class SlidersController extends AbstractController
{
    public function __construct(
        private readonly SliderRepository $sliderRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/sliders', name: 'admin2_sliders', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search    = trim((string) $request->query->get('q', ''));
        $sort      = (string) $request->query->get('sort', 'id');
        $direction = (string) $request->query->get('dir', 'DESC');
        $page      = $request->query->getInt('page', 1);
        $perPage   = $this->admin2Paginator->normalizePerPage($request->query->getInt('perPage', Admin2Paginator::DEFAULT_PER_PAGE));

        $query = $this->sliderRepository->createAdminListQueryBuilder($search, $sort, $direction);
        $pagination = $this->admin2Paginator->paginate($query, $page, $perPage);

        return $this->render('admin2/sliders/index.html.twig', [
            'pagination'     => $pagination,
            'search'         => $search,
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
        ]);
    }

    #[Route('/admin2/sliders/{id}/delete', name: 'admin2_sliders_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_slider_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_sliders', $request->query->all());
        }

        $slider = $this->sliderRepository->find($id);
        if ($slider === null) {
            $this->addFlash('error', 'Слайд не знайдено.');

            return $this->redirectToRoute('admin2_sliders', $request->query->all());
        }

        $title = $slider->getTitle() ?: '#' . $slider->getId();
        $this->entityManager->remove($slider);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Слайд «%s» видалено.', $title));

        return $this->redirectToRoute('admin2_sliders', $request->query->all());
    }
}
