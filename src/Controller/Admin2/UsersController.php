<?php

namespace App\Controller\Admin2;

use App\Repository\UserRepository;
use App\Service\Admin2\Admin2Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN')")]
class UsersController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/users', name: 'admin2_users', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search    = trim((string) $request->query->get('q', ''));
        $sort      = (string) $request->query->get('sort', 'id');
        $direction = (string) $request->query->get('dir', 'DESC');
        $page      = $request->query->getInt('page', 1);
        $perPage   = $this->admin2Paginator->normalizePerPage($request->query->getInt('perPage', Admin2Paginator::DEFAULT_PER_PAGE));

        $query = $this->userRepository->createAdminListQueryBuilder($search, $sort, $direction);
        $pagination = $this->admin2Paginator->paginate($query, $page, $perPage);

        return $this->render('admin2/users/index.html.twig', [
            'pagination'     => $pagination,
            'search'         => $search,
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
        ]);
    }

    #[Route('/admin2/users/{id}/delete', name: 'admin2_users_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_user_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_users', $request->query->all());
        }

        $user = $this->userRepository->find($id);
        if ($user === null) {
            $this->addFlash('error', 'Користувача не знайдено.');

            return $this->redirectToRoute('admin2_users', $request->query->all());
        }

        $name = $user->getName() . ' ' . $user->getSurname();
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Користувача «%s» видалено.', trim($name)));

        return $this->redirectToRoute('admin2_users', $request->query->all());
    }
}
