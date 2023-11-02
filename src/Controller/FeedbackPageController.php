<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Repository\CategoryRepository;
use App\Repository\FeedbackRepository;
use App\Repository\SettingRepository;
use App\Service\Feedback\CreateService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FeedbackPageController extends BaseController
{
    private FeedbackRepository $feedbackRepository;

    private CreateService $createService;

    /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     * @param FeedbackRepository $feedbackRepository
     * @param CreateService $createService
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        SettingRepository $settingRepository,
        FeedbackRepository $feedbackRepository,
        CreateService $createService
    ) {
        parent::__construct($categoryRepository, $settingRepository);
        $this->feedbackRepository = $feedbackRepository;
        $this->createService = $createService;
    }

    public function index(PaginatorInterface $paginator, Request $request): Response
    {
        $feedbacks = $this->feedbackRepository->findActiveFeedbacks();

        $pagination = $paginator->paginate(
            $feedbacks,
            $request->query->getInt('page', 1),
            10
        );

        return $this->renderTemplate($request, 'feedback_page/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    public function post(Request $request): Response
    {
        $this->createService->run([
            ...$request->request->all(),
            'status' => 'NEW',
        ]);

        return new Response(json_encode([
            'success' => true
        ]));
    }
}
