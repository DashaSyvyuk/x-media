<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Repository\SettingRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MyOrderController extends BaseController
{
    public const PAGINATION_LIMIT = 5;

    /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     * @param UserRepository $userRepository
     * @param OrderRepository $orderRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly SettingRepository $settingRepository,
        private readonly UserRepository $userRepository,
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository, $this->productRepository);
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

        $monthNamesUa = [
            '01' => 'січня',
            '02' => 'лютого',
            '03' => 'березня',
            '04' => 'квітня',
            '05' => 'травня',
            '06' => 'червня',
            '07' => 'липня',
            '08' => 'серпня',
            '09' => 'вересня',
            '10' => 'жовтня',
            '11' => 'листопада',
            '12' => 'грудня',
        ];

        return $this->renderTemplate($request, 'my_order/index.html.twig', [
            'user' => $user,
            'pagination' => $pagination,
            'noOrder' => $text,
            'monthNamesUa' => $monthNamesUa,
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
            'status'       => Order::GROUPED_STATUSES[$order->getStatus()],
        ]);
    }
}
