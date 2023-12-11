<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\FeedbackRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use App\Service\Feedback\CreateService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FeedbackPageController extends BaseController
{
    /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     * @param FeedbackRepository $feedbackRepository
     * @param ProductRepository $productRepository
     * @param CreateService $createService
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly SettingRepository $settingRepository,
        private readonly FeedbackRepository $feedbackRepository,
        private readonly ProductRepository $productRepository,
        private readonly CreateService $createService
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository, $this->productRepository);
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
