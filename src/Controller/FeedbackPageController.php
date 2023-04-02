<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Repository\CategoryRepository;
use App\Repository\FeedbackRepository;
use App\Repository\SettingRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FeedbackPageController extends BaseController
{
    private FeedbackRepository $feedbackRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     * @param FeedbackRepository $feedbackRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        SettingRepository $settingRepository,
        FeedbackRepository $feedbackRepository
    ) {
        parent::__construct($categoryRepository, $settingRepository);
        $this->feedbackRepository = $feedbackRepository;
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
        $feedback = new Feedback();
        $feedback->setAuthor($request->request->get('author'));
        $feedback->setEmail($request->request->get('email'));
        $feedback->setComment($request->request->get('comment'));
        $feedback->setStatus('NEW');

        $this->feedbackRepository->create($feedback);

        return new Response(json_encode([
            'success' => true
        ]));
    }
}
