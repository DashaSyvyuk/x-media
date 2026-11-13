<?php

namespace App\Controller\Admin2;

use App\Entity\AdminPlan;
use App\Entity\AdminPlanBoard;
use App\Entity\AdminPlanColumn;
use App\Entity\AdminUser;
use App\Form\Admin2\AdminPlanType;
use App\Repository\AdminPlanBoardRepository;
use App\Repository\AdminPlanColumnRepository;
use App\Repository\AdminPlanRepository;
use App\Repository\AdminUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class PlanningController extends AbstractController
{
    public function __construct(
        private readonly AdminPlanRepository $adminPlanRepository,
        private readonly AdminPlanBoardRepository $boardRepository,
        private readonly AdminPlanColumnRepository $columnRepository,
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

        $boardColumns = $this->columnRepository->ensureDefaultsForBoard($activeBoard);
        $columns = [];
        $columnMeta = [];
        foreach ($boardColumns as $column) {
            $columns[$column->getId()] = [];
            $columnMeta[$column->getId()] = [
                'id'       => $column->getId(),
                'title'    => $column->getTitle(),
                'color'    => $column->getColor(),
                'colorHex' => $column->getColorHex(),
                'isDone'   => $column->isDone(),
            ];
        }

        foreach ($this->adminPlanRepository->findForBoard($activeBoard) as $plan) {
            $columnId = $plan->getPlanColumn()->getId();
            if (! isset($columns[$columnId])) {
                $columnId = $boardColumns[0]->getId();
            }
            $columns[$columnId][] = $plan;
        }

        /** @var AdminUser $user */
        $user = $this->getUser();
        $plan = new AdminPlan();
        $plan->setBoard($activeBoard);
        $plan->setPlanColumn($boardColumns[0]);
        $plan->setAssignee($user);

        return $this->render('admin2/planning/index.html.twig', [
            'boards'       => $boards,
            'activeBoard'  => $activeBoard,
            'boardColumns' => $boardColumns,
            'columns'      => $columns,
            'columnMeta'   => $columnMeta,
            'boardColors'  => AdminPlanBoard::COLORS,
            'columnColors' => AdminPlanColumn::COLORS,
            'today'        => new \DateTimeImmutable('today'),
            'createForm'   => $this->createForm(AdminPlanType::class, $plan, [
                'current_user' => $user,
                'columns'      => $boardColumns,
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
        $this->boardRepository->seedDefaultColumns($board);
        $this->entityManager->flush();
        $this->addFlash('success', sprintf('Дошку «%s» створено.', $board->getTitle()));

        return $this->redirectToRoute('admin2_planning', ['board' => $board->getId()]);
    }

    #[Route(
        '/admin/planning/boards/{id}',
        name: 'admin2_planning_board_update',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
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

    #[Route(
        '/admin/planning/boards/{id}/delete',
        name: 'admin2_planning_board_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
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

    #[Route(
        '/admin/planning/boards/{id}/move',
        name: 'admin2_planning_board_move',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
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

    #[Route(
        '/admin/planning/boards/{boardId}/columns',
        name: 'admin2_planning_column_store',
        methods: ['POST'],
        requirements: ['boardId' => '\d+'],
    )]
    public function storeColumn(Request $request, int $boardId): Response
    {
        if (! $this->isCsrfTokenValid('admin2_plan_column', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_planning', ['board' => $boardId]);
        }

        $board = $this->findBoard($boardId);
        $title = trim((string) $request->request->get('title', ''));
        $color = trim((string) $request->request->get('color', 'blue'));
        $isDone = $request->request->getBoolean('isDone');
        if ($title === '') {
            $this->addFlash('error', 'Вкажіть назву стовпця.');

            return $this->redirectToRoute('admin2_planning', ['board' => $boardId]);
        }

        $column = (new AdminPlanColumn())
            ->setBoard($board)
            ->setTitle($title)
            ->setColor($color)
            ->setIsDone($isDone)
            ->setPosition($this->columnRepository->getNextPosition($board));

        $this->entityManager->persist($column);
        $this->entityManager->flush();
        $this->addFlash('success', sprintf('Стовпець «%s» створено.', $column->getTitle()));

        return $this->redirectToRoute('admin2_planning', ['board' => $boardId]);
    }

    #[Route(
        '/admin/planning/columns/{id}',
        name: 'admin2_planning_column_update',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function updateColumn(Request $request, int $id): Response
    {
        $column = $this->findColumn($id);
        $boardId = $column->getBoard()->getId();

        if (! $this->isCsrfTokenValid('admin2_plan_column', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_planning', ['board' => $boardId]);
        }

        $title = trim((string) $request->request->get('title', ''));
        $color = trim((string) $request->request->get('color', $column->getColor()));
        $isDone = $request->request->getBoolean('isDone');
        if ($title === '') {
            $this->addFlash('error', 'Вкажіть назву стовпця.');

            return $this->redirectToRoute('admin2_planning', ['board' => $boardId]);
        }

        $column->setTitle($title)->setColor($color)->setIsDone($isDone);
        foreach ($column->getPlans() as $plan) {
            $plan->setPlanColumn($column);
        }
        $this->entityManager->flush();
        $this->addFlash('success', 'Стовпець оновлено.');

        return $this->redirectToRoute('admin2_planning', ['board' => $boardId]);
    }

    #[Route(
        '/admin/planning/columns/{id}/delete',
        name: 'admin2_planning_column_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function deleteColumn(Request $request, int $id): Response
    {
        $column = $this->findColumn($id);
        $board = $column->getBoard();
        $boardId = $board->getId();

        if (! $this->isCsrfTokenValid('admin2_plan_column', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_planning', ['board' => $boardId]);
        }

        $columns = $this->columnRepository->findForBoard($board);
        if (count($columns) <= 1) {
            $this->addFlash('error', 'Не можна видалити останній стовпець.');

            return $this->redirectToRoute('admin2_planning', ['board' => $boardId]);
        }

        if ($this->adminPlanRepository->countForColumn($column) > 0) {
            $this->addFlash(
                'error',
                'Спочатку перемістіть або видаліть картки зі стовпця.',
            );

            return $this->redirectToRoute('admin2_planning', ['board' => $boardId]);
        }

        $title = $column->getTitle();
        $this->entityManager->remove($column);
        $this->entityManager->flush();
        $this->addFlash('success', sprintf('Стовпець «%s» видалено.', $title));

        return $this->redirectToRoute('admin2_planning', ['board' => $boardId]);
    }

    #[Route(
        '/admin/planning/columns/{id}/move',
        name: 'admin2_planning_column_move',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function moveColumn(Request $request, int $id): Response
    {
        $column = $this->findColumn($id);
        $board = $column->getBoard();
        $boardId = $board->getId();

        if (! $this->isCsrfTokenValid('admin2_plan_column', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_planning', ['board' => $boardId]);
        }

        $direction = (string) $request->request->get('direction', '');
        $columns = $this->columnRepository->findForBoard($board);
        $index = null;
        foreach ($columns as $i => $item) {
            if ($item->getId() === $id) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            throw $this->createNotFoundException('Стовпець не знайдено.');
        }

        $swapWith = $direction === 'up' ? $index - 1 : ($direction === 'down' ? $index + 1 : -1);
        if ($swapWith >= 0 && $swapWith < count($columns)) {
            $a = $columns[$index];
            $b = $columns[$swapWith];
            $posA = $a->getPosition();
            $a->setPosition($b->getPosition());
            $b->setPosition($posA);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('admin2_planning', ['board' => $boardId]);
    }

    #[Route('/admin/planning', name: 'admin2_planning_store', methods: ['POST'])]
    public function store(Request $request): Response
    {
        /** @var AdminUser $user */
        $user = $this->getUser();
        $boardId = $request->request->getInt('board_id');
        $board = $this->findBoard($boardId);
        $boardColumns = $this->columnRepository->ensureDefaultsForBoard($board);

        $plan = new AdminPlan();
        $plan->setBoard($board);
        $plan->setPlanColumn($boardColumns[0]);
        $plan->setCreatedBy($user);
        $plan->setAssignee($user);

        $form = $this->createForm(AdminPlanType::class, $plan, [
            'current_user' => $user,
            'columns'      => $boardColumns,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($plan->getPlanColumn()->getBoard()->getId() !== $board->getId()) {
                $this->addFlash('error', 'Невірний стовпець.');

                return $this->redirectToRoute('admin2_planning', ['board' => $board->getId()]);
            }
            $plan->setPosition(
                $this->adminPlanRepository->getNextPosition($plan->getPlanColumn())
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
        $columnId = (int) ($payload['planColumn'] ?? 0);
        $assigneeId = (int) ($payload['assignee'] ?? 0);
        $body = trim((string) ($payload['body'] ?? ''));

        $column = $this->columnRepository->find($columnId);
        if ($title === '' || ! $column instanceof AdminPlanColumn
            || $column->getBoard()->getId() !== $plan->getBoard()->getId()) {
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

        $oldColumnId = $plan->getPlanColumn()->getId();
        $plan->setTitle($title);
        $plan->setScheduledDate($date);
        $plan->setAssignee($assignee);
        $plan->setBody($body !== '' ? $body : null);
        $plan->setPlanColumn($column);

        if ($oldColumnId !== $column->getId()) {
            $plan->setPosition($this->adminPlanRepository->getNextPosition($column));
        }

        $this->entityManager->flush();
        $this->addFlash('success', 'Картку оновлено.');

        return $this->redirectToPlanning($request, $plan->getBoard()->getId());
    }

    #[Route(
        '/admin/planning/{id}/status',
        name: 'admin2_planning_status',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function changeStatus(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_plan_action', (string) $request->request->get('_token'))) {
            if ($this->wantsJson($request)) {
                return $this->jsonError('Невірний CSRF-токен.');
            }
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToPlanning($request);
        }

        $plan = $this->findPlan($id);
        $columnId = $request->request->getInt('status');
        if ($columnId <= 0) {
            $columnId = $request->request->getInt('column_id');
        }
        $column = $this->columnRepository->find($columnId);
        if (! $column instanceof AdminPlanColumn
            || $column->getBoard()->getId() !== $plan->getBoard()->getId()) {
            if ($this->wantsJson($request)) {
                return $this->jsonError('Невірний стовпець.');
            }
            $this->addFlash('error', 'Невірний стовпець.');

            return $this->redirectToPlanning($request, $plan->getBoard()->getId());
        }

        $plan->setPlanColumn($column);
        $plan->setPosition($this->adminPlanRepository->getNextPosition($column));
        $this->entityManager->flush();

        if ($this->wantsJson($request)) {
            return $this->jsonOk([
                'id'          => $plan->getId(),
                'status'      => (string) $column->getId(),
                'statusLabel' => $column->getTitle(),
                'statusColor' => $column->getColorHex(),
                'isDone'      => $column->isDone(),
            ]);
        }

        return $this->redirectToPlanning($request, $plan->getBoard()->getId());
    }

    #[Route(
        '/admin/planning/{id}/move',
        name: 'admin2_planning_move',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function moveCard(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_plan_action', (string) $request->request->get('_token'))) {
            if ($this->wantsJson($request)) {
                return $this->jsonError('Невірний CSRF-токен.');
            }
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

        $moved = false;
        if ($index !== null) {
            $swapWith = $direction === 'up' ? $index - 1 : ($direction === 'down' ? $index + 1 : -1);
            if ($swapWith >= 0 && $swapWith < count($neighbors)) {
                $other = $neighbors[$swapWith];
                $posA = $plan->getPosition();
                $plan->setPosition($other->getPosition());
                $other->setPosition($posA);
                $this->entityManager->flush();
                $moved = true;
            }
        }

        if ($this->wantsJson($request)) {
            return $this->jsonOk([
                'id'        => $plan->getId(),
                'direction' => $direction,
                'moved'     => $moved,
            ]);
        }

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

    private function findColumn(int $id): AdminPlanColumn
    {
        $column = $this->columnRepository->find($id);
        if (! $column instanceof AdminPlanColumn) {
            throw $this->createNotFoundException('Стовпець не знайдено.');
        }

        return $column;
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

    private function wantsJson(Request $request): bool
    {
        return $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept', ''), 'application/json');
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function jsonOk(array $extra = []): JsonResponse
    {
        return new JsonResponse(['ok' => true] + $extra);
    }

    private function jsonError(string $message, int $status = 400): JsonResponse
    {
        return new JsonResponse(['ok' => false, 'message' => $message], $status);
    }
}
