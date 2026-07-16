<?php

namespace App\Controller\Admin2;

use App\Entity\AdminPlan;
use App\Entity\AdminUser;
use App\Form\Admin2\AdminPlanType;
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
        private readonly AdminUserRepository $adminUserRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/planning', name: 'admin2_planning', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $showCompleted = $request->query->get('show') === 'completed';
        $today = new \DateTimeImmutable('today');

        $leftByDate = [];
        $rightByDate = [];
        $leftCount = 0;
        $rightCount = 0;

        foreach ($this->adminPlanRepository->findForBoard($showCompleted) as $plan) {
            $bucket = $plan->getDayBucket($today);
            $dateKey = $plan->getScheduledDate()->format('Y-m-d');

            if ($bucket === 'future') {
                $rightByDate[$dateKey][] = $plan;
                ++$rightCount;
            } else {
                $leftByDate[$dateKey][] = $plan;
                ++$leftCount;
            }
        }

        $todayKey = $today->format('Y-m-d');
        uksort($leftByDate, static function (string $a, string $b) use ($todayKey): int {
            $aToday = $a === $todayKey;
            $bToday = $b === $todayKey;
            if ($aToday !== $bToday) {
                return $aToday ? 1 : -1;
            }

            // Прострочені: найдавніші зверху
            return $a <=> $b;
        });

        if ($showCompleted) {
            krsort($rightByDate);
        } else {
            ksort($rightByDate);
        }

        /** @var AdminUser $user */
        $user = $this->getUser();
        $plan = new AdminPlan();
        $plan->setScheduledDate(\DateTime::createFromImmutable($today));
        $plan->setAssignee($user);

        return $this->render('admin2/planning/index.html.twig', [
            'leftByDate'    => $leftByDate,
            'rightByDate'   => $rightByDate,
            'leftCount'     => $leftCount,
            'rightCount'    => $rightCount,
            'today'         => $today,
            'todayLabel'    => $this->ukDateLabel($today),
            'showCompleted' => $showCompleted,
            'createForm'    => $this->createForm(AdminPlanType::class, $plan, [
                'current_user' => $user,
            ])->createView(),
            'admins'        => $this->adminUserRepository->findActiveOrderedByName(),
        ]);
    }

    #[Route('/admin/planning', name: 'admin2_planning_store', methods: ['POST'])]
    public function store(Request $request): Response
    {
        /** @var AdminUser $user */
        $user = $this->getUser();
        $plan = new AdminPlan();
        $plan->setAssignee($user);
        $plan->setCreatedBy($user);

        $form = $this->createForm(AdminPlanType::class, $plan, ['current_user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($plan);
            $this->entityManager->flush();
            $this->addFlash('success', 'План створено.');
        } else {
            $this->addFlash('error', 'Перевірте поля плану.');
        }

        return $this->redirectToRoute('admin2_planning');
    }

    #[Route('/admin/planning/{id}', name: 'admin2_planning_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_plan_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_planning', $this->returnQuery($request));
        }

        $plan = $this->findPlan($id);
        $payload = $request->request->all('admin_plan');
        $title = trim((string) ($payload['title'] ?? ''));
        $dateRaw = trim((string) ($payload['scheduledDate'] ?? ''));
        $assigneeId = (int) ($payload['assignee'] ?? 0);
        $body = trim((string) ($payload['body'] ?? ''));

        $assignee = $this->adminUserRepository->find($assigneeId);
        $assigneeAllowed = $assignee instanceof AdminUser
            && ($assignee->isActive() || $assignee->getId() === $plan->getAssignee()->getId());
        if ($title === '' || $dateRaw === '' || ! $assigneeAllowed) {
            $this->addFlash('error', 'Перевірте поля плану.');

            return $this->redirectToRoute('admin2_planning', $this->returnQuery($request));
        }

        try {
            $date = new \DateTime($dateRaw);
            $date->setTime(0, 0);
        } catch (\Exception) {
            $this->addFlash('error', 'Невірна дата.');

            return $this->redirectToRoute('admin2_planning', $this->returnQuery($request));
        }

        $plan->setTitle($title);
        $plan->setScheduledDate($date);
        $plan->setAssignee($assignee);
        $plan->setBody($body !== '' ? $body : null);
        $this->entityManager->flush();
        $this->addFlash('success', 'План оновлено.');

        return $this->redirectToRoute('admin2_planning', $this->returnQuery($request));
    }

    #[Route('/admin/planning/{id}/complete', name: 'admin2_planning_complete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function complete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_plan_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_planning', $this->returnQuery($request));
        }

        $plan = $this->findPlan($id);
        if ($plan->isCompleted()) {
            $plan->setCompletedAt(null);
            $this->addFlash('success', 'План повернуто в активні.');
        } else {
            $plan->setCompletedAt(new \DateTime());
            $this->addFlash('success', 'План позначено виконаним.');
        }

        $this->entityManager->flush();

        return $this->redirectToRoute('admin2_planning', $this->returnQuery($request));
    }

    #[Route('/admin/planning/{id}/delete', name: 'admin2_planning_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_plan_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_planning', $this->returnQuery($request));
        }

        $plan = $this->findPlan($id);
        $title = $plan->getTitle();
        $this->entityManager->remove($plan);
        $this->entityManager->flush();
        $this->addFlash('success', sprintf('План «%s» видалено.', $title));

        return $this->redirectToRoute('admin2_planning', $this->returnQuery($request));
    }

    private function findPlan(int $id): AdminPlan
    {
        $plan = $this->adminPlanRepository->find($id);
        if (! $plan instanceof AdminPlan) {
            throw $this->createNotFoundException('План не знайдено.');
        }

        return $plan;
    }

    /**
     * @return array<string, string>
     */
    private function returnQuery(Request $request): array
    {
        $show = (string) $request->request->get('show', $request->query->get('show', ''));

        return $show === 'completed' ? ['show' => 'completed'] : [];
    }

    private function ukDateLabel(\DateTimeImmutable $date): string
    {
        $days = [
            1 => 'понеділок', 2 => 'вівторок', 3 => 'середа', 4 => 'четвер',
            5 => 'пʼятниця', 6 => 'субота', 7 => 'неділя',
        ];
        $months = [
            1 => 'січня', 2 => 'лютого', 3 => 'березня', 4 => 'квітня',
            5 => 'травня', 6 => 'червня', 7 => 'липня', 8 => 'серпня',
            9 => 'вересня', 10 => 'жовтня', 11 => 'листопада', 12 => 'грудня',
        ];

        return sprintf(
            '%s, %d %s %d',
            $days[(int) $date->format('N')],
            (int) $date->format('j'),
            $months[(int) $date->format('n')],
            (int) $date->format('Y'),
        );
    }
}
