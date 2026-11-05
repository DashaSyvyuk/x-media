<?php

namespace App\Controller\Admin2;

use App\Entity\AdminPlan;
use App\Entity\AdminPlanBoard;
use App\Entity\AdminUser;
use App\Form\Admin2\AdminPlanType;
use App\Repository\AdminPlanBoardRepository;
use App\Repository\AdminPlanRepository;
use App\Repository\AdminUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class PlanningController extends AbstractController
{
    public function __construct(
        private readonly AdminPlanRepository $adminPlanRepository,
        private readonly AdminPlanBoardRepository $boardRepository,
        private readonly AdminUserRepository $adminUserRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/planning', name: 'admin2_planning', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $boards = $this->boardRepository->findOrdered();
        if ($boards === []) {
            $boards = [$this->boardRepository->ensureDefaultBoard()];
        }

        $boardId = $request->query->getInt('board', 0);
        $activeBoard = null;
        foreach ($boards as $board) {
            if ($board->getId() === $boardId) {
                $activeBoard = $board;
                break;
            }
        }
        $activeBoard ??= $boards[0];

        $columns = [
            AdminPlan::STATUS_NEW         => [],
            AdminPlan::STATUS_IN_PROGRESS => [],
            AdminPlan::STATUS_COMPLETED   => [],
        ];
        foreach ($this->adminPlanRepository->findForBoard($activeBoard) as $plan) {
            $status = $plan->getStatus();
            if (! isset($columns[$status])) {
                $status = AdminPlan::STATUS_NEW;
            }
            $columns[$status][] = $plan;
        }

        /** @var AdminUser $user */
        $user = $this->getUser();
        $plan = new AdminPlan();
        $plan->setBoard($activeBoard);
        $plan->setStatus(AdminPlan::STATUS_NEW);
        $plan->setAssignee($user);

        return $this->render('admin2/planning/index.html.twig', [
            'boards'       => $boards,
            'activeBoard'  => $activeBoard,
            'columns'      => $columns,
            'statusLabels' => AdminPlan::STATUSES,
            'statusColors' => AdminPlan::STATUS_COLORS,
            'boardColors'  => AdminPlanBoard::COLORS,
            'today'        => new \DateTimeImmutable('today'),
            'createForm'   => $this->createForm(AdminPlanType::class, $plan, [
                'current_user' => $user,
            ])->createView(),
            'admins'       => $this->adminUserRepository->findActiveOrderedByName(),
        ]);
    }

    #[Route('/admin/planning/boards', name: 'admin2_planning_board_store', methods: ['POST'])]
    public function storeBoard(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('admin2_plan_board', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_planning');
        }

        $title = trim((string) $request->request->get('title', ''));
        $color = trim((string) $request->request->get('color', 'teal'));
        if ($title === '') {
            $this->addFlash('error', 'Вкажіть назву дошки.');

            return $this->redirectToRoute('admin2_planning');
        }

        $board = (new AdminPlanBoard())
            ->setTitle($title)
            ->setColor($color)
            ->setPosition($this->boardRepository->getNextPosition());

        $this->entityManager->persist($board);
        $this->entityManager->flush();
        $this->addFlash('success', sprintf('Дошку «%s» створено.', $board->getTitle()));

        return $this->redirectToRoute('admin2_planning', ['board' => $board->getId()]);
    }

    #[Route('/admin/planning/boards/{id}', name: 'admin2_planning_board_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateBoard(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_plan_board', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_planning', ['board' => $id]);
        }

        $board = $this->findBoard($id);
        $title = trim((string) $request->request->get('title', ''));
        $color = trim((string) $request->request->get('color', $board->getColor()));
        if ($title === '') {
            $this->addFlash('error', 'Вкажіть назву дошки.');

            return $this->redirectToRoute('admin2_planning', ['board' => $id]);
        }

        $board->setTitle($title)->setColor($color);
        $this->entityManager->flush();
        $this->addFlash('success', 'Дошку оновлено.');

        return $this->redirectToRoute('admin2_planning', ['board' => $board->getId()]);
    }

    #[Route('/admin/planning/boards/{id}/delete', name: 'admin2_planning_board_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteBoard(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_plan_board', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_planning', ['board' => $id]);
        }

        $boards = $this->boardRepository->findOrdered();
        if (count($boards) <= 1) {
            $this->addFlash('error', 'Не можна видалити останню дошку.');

            return $this->redirectToRoute('admin2_planning', ['board' => $id]);
        }

        $board = $this->findBoard($id);
        $title = $board->getTitle();
        $this->entityManager->remove($board);
        $this->entityManager->flush();
        $this->addFlash('success', sprintf('Дошку «%s» видалено.', $title));

        return $this->redirectToRoute('admin2_planning');
    }

    #[Route('/admin/planning/boards/{id}/move', name: 'admin2_planning_board_move', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function moveBoard(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_plan_board', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_planning', ['board' => $id]);
        }

        $direction = (string) $request->request->get('direction', '');
        $boards = $this->boardRepository->findOrdered();
        $index = null;
        foreach ($boards as $i => $board) {
            if ($board->getId() === $id) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            throw $this->createNotFoundException('Дошку не знайдено.');
        }

        $swapWith = $direction === 'up' ? $index - 1 : ($direction === 'down' ? $index + 1 : -1);
        if ($swapWith >= 0 && $swapWith < count($boards)) {
            $a = $boards[$index];
            $b = $boards[$swapWith];
            $posA = $a->getPosition();
            $a->setPosition($b->getPosition());
            $b->setPosition($posA);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('admin2_planning', ['board' => $id]);
    }

    #[Route('/admin/planning', name: 'admin2_planning_store', methods: ['POST'])]
    public function store(Request $request): Response
    {
        /** @var AdminUser $user */
        $user = $this->getUser();
        $boardId = $request->request->getInt('board_id');
        $board = $this->findBoard($boardId);

        $plan = new AdminPlan();
        $plan->setBoard($board);
        $plan->setCreatedBy($user);
        $plan->setAssignee($user);

        $form = $this->createForm(AdminPlanType::class, $plan, ['current_user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plan->setPosition(
                $this->adminPlanRepository->getNextPosition($board, $plan->getStatus())
            );
            $this->entityManager->persist($plan);
            $this->entityManager->flush();
            $this->addFlash('success', 'Картку створено.');
        } else {
            $this->addFlash('error', 'Перевірте поля картки.');
        }

        return $this->redirectToRoute('admin2_planning', ['board' => $board->getId()]);
    }

    #[Route('/admin/planning/{id}', name: 'admin2_planning_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_plan_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToPlanning($request);
        }

        $plan = $this->findPlan($id);
        $payload = $request->request->all('admin_plan');
        $title = trim((string) ($payload['title'] ?? ''));
        $dateRaw = trim((string) ($payload['scheduledDate'] ?? ''));
        $status = trim((string) ($payload['status'] ?? AdminPlan::STATUS_NEW));
        $assigneeId = (int) ($payload['assignee'] ?? 0);
        $body = trim((string) ($payload['body'] ?? ''));

        if ($title === '' || ! isset(AdminPlan::STATUSES[$status])) {
            $this->addFlash('error', 'Перевірте поля картки.');

            return $this->redirectToPlanning($request, $plan->getBoard()->getId());
        }

        $assignee = null;
        if ($assigneeId > 0) {
            $assignee = $this->adminUserRepository->find($assigneeId);
            if (! $assignee instanceof AdminUser) {
                $this->addFlash('error', 'Невірний виконавець.');

                return $this->redirectToPlanning($request, $plan->getBoard()->getId());
            }
        }

        $date = null;
        if ($dateRaw !== '') {
            try {
                $date = new \DateTime($dateRaw);
                $date->setTime(0, 0);
            } catch (\Exception) {
                $this->addFlash('error', 'Невірна дата.');

                return $this->redirectToPlanning($request, $plan->getBoard()->getId());
            }
        }

        $oldStatus = $plan->getStatus();
        $plan->setTitle($title);
        $plan->setScheduledDate($date);
        $plan->setAssignee($assignee);
        $plan->setBody($body !== '' ? $body : null);
        $plan->setStatus($status);

        if ($oldStatus !== $status) {
            $plan->setPosition(
                $this->adminPlanRepository->getNextPosition($plan->getBoard(), $status)
            );
        }

        $this->entityManager->flush();
        $this->addFlash('success', 'Картку оновлено.');

        return $this->redirectToPlanning($request, $plan->getBoard()->getId());
    }

    #[Route('/admin/planning/{id}/status', name: 'admin2_planning_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function changeStatus(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_plan_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToPlanning($request);
        }

        $plan = $this->findPlan($id);
        $status = trim((string) $request->request->get('status', ''));
        if (! isset(AdminPlan::STATUSES[$status])) {
            $this->addFlash('error', 'Невірний статус.');

            return $this->redirectToPlanning($request, $plan->getBoard()->getId());
        }

        $plan->setStatus($status);
        $plan->setPosition(
            $this->adminPlanRepository->getNextPosition($plan->getBoard(), $status)
        );
        $this->entityManager->flush();

        return $this->redirectToPlanning($request, $plan->getBoard()->getId());
    }

    #[Route('/admin/planning/{id}/move', name: 'admin2_planning_move', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function moveCard(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_plan_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToPlanning($request);
        }

        $plan = $this->findPlan($id);
        $direction = (string) $request->request->get('direction', '');
        $neighbors = $this->adminPlanRepository->findNeighbors($plan);
        $index = null;
        foreach ($neighbors as $i => $item) {
            if ($item->getId() === $plan->getId()) {
                $index = $i;
                break;
            }
        }

        if ($index !== null) {
            $swapWith = $direction === 'up' ? $index - 1 : ($direction === 'down' ? $index + 1 : -1);
            if ($swapWith >= 0 && $swapWith < count($neighbors)) {
                $other = $neighbors[$swapWith];
                $posA = $plan->getPosition();
                $plan->setPosition($other->getPosition());
                $other->setPosition($posA);
                $this->entityManager->flush();
            }
        }

        return $this->redirectToPlanning($request, $plan->getBoard()->getId());
    }

    #[Route(
        '/admin/planning/{id}/complete',
        name: 'admin2_planning_complete',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function complete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_plan_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToPlanning($request);
        }

        $plan = $this->findPlan($id);
        if ($plan->isCompleted()) {
            $plan->setStatus(AdminPlan::STATUS_IN_PROGRESS);
        } else {
            $plan->setStatus(AdminPlan::STATUS_COMPLETED);
        }
        $plan->setPosition(
            $this->adminPlanRepository->getNextPosition($plan->getBoard(), $plan->getStatus())
        );
        $this->entityManager->flush();

        return $this->redirectToPlanning($request, $plan->getBoard()->getId());
    }

    #[Route(
        '/admin/planning/{id}/delete',
        name: 'admin2_planning_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_plan_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToPlanning($request);
        }

        $plan = $this->findPlan($id);
        $boardId = $plan->getBoard()->getId();
        $title = $plan->getTitle();
        $this->entityManager->remove($plan);
        $this->entityManager->flush();
        $this->addFlash('success', sprintf('Картку «%s» видалено.', $title));

        return $this->redirectToPlanning($request, $boardId);
    }

    private function findPlan(int $id): AdminPlan
    {
        $plan = $this->adminPlanRepository->find($id);
        if (! $plan instanceof AdminPlan) {
            throw $this->createNotFoundException('Картку не знайдено.');
        }

        return $plan;
    }

    private function findBoard(int $id): AdminPlanBoard
    {
        $board = $this->boardRepository->find($id);
        if (! $board instanceof AdminPlanBoard) {
            throw $this->createNotFoundException('Дошку не знайдено.');
        }

        return $board;
    }

    private function redirectToPlanning(Request $request, ?int $boardId = null): Response
    {
        $board = $boardId
            ?? $request->request->getInt('board')
            ?: $request->query->getInt('board');

        return $this->redirectToRoute(
            'admin2_planning',
            $board > 0 ? ['board' => $board] : []
        );
    }
}
