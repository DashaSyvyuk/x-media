<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\SettingRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MyOrderController extends BaseController
{
    public const PAGINATION_LIMIT = 5;
    private UserRepository $userRepository;

    private OrderRepository $orderRepository;

    private SettingRepository $settingRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     * @param UserRepository $userRepository
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        SettingRepository $settingRepository,
        UserRepository $userRepository,
        OrderRepository $orderRepository
    ) {
        parent::__construct($categoryRepository, $settingRepository);
        $this->settingRepository = $settingRepository;
        $this->userRepository = $userRepository;
        $this->orderRepository = $orderRepository;
    }

    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();

        $email = $session->get('user');

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->redirectToRoute('index');
        }

        $text = $this->settingRepository->findOneBy(['slug' => 'there_is_no_active_order']);

        $orders = $this->orderRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        $pagination = $paginator->paginate(
            $orders,
            $request->query->getInt('page', 1),
            self::PAGINATION_LIMIT
        );

        return $this->renderTemplate($request, 'my_order/index.html.twig', [
            'user' => $user,
            'pagination' => $pagination,
            'noOrder' => $text,
        ]);
    }

    public function getOrder(string $id, Request $request): Response
    {
        $order = $this->orderRepository->findOneBy(['id' => $id]);

        if (!$order) {
            return $this->redirectToRoute('index');
        }

        return $this->renderTemplate($request, 'my_order/detail.html.twig', [
            'order'        => $order,
            'phoneNumber'  => $this->settingRepository->findOneBy(['slug' => 'phone_number']),
            'email'        => $this->settingRepository->findOneBy(['slug' => 'email']),
            'status' => 3//TODO: get status from order
        ]);
    }
}
