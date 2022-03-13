<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderPageController extends AbstractController
{
    private CategoryRepository $categoryRepository;

    private OrderRepository $orderRepository;

    private ProductRepository $productRepository;

    private SettingRepository $settingRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param OrderRepository $orderRepository
     * @param ProductRepository $productRepository
     * @param SettingRepository $settingRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        SettingRepository $settingRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->settingRepository = $settingRepository;
    }

    public function create(): Response
    {
        $categories = $this->categoryRepository->findBy([
            'status' => 'ACTIVE'
        ], [
            'position' => 'ASC'
        ]);

        return $this->render('order_page/index.html.twig', [
            'totalPrice' => $this->getTotal(),
            'categories' => $categories,
            'totalCount' => $_COOKIE['totalCount'] ?? 0,
            'phoneNumbers' => $this->settingRepository->findBy(['slug' => 'phone_number']),
            'emails'       => $this->settingRepository->findBy(['slug' => 'email'])
        ]);
    }

    public function post(Request $request): Response
    {
        if (isset($_COOKIE['cart']) && $cart = json_decode($_COOKIE['cart'])) {
            $order = new Order();
            $order->setName($request->request->get('name'));
            $order->setSurname($request->request->get('surname'));
            $order->setAddress($request->request->get('address'));
            $order->setPhone($request->request->get('phone'));
            $order->setEmail($request->request->get('email') ?? '');
            $order->setPaytype($request->request->get('paytype'));
            $order->setStatus('NEW');
            $order->setPaymentStatus(false);
            $order->setComment($request->request->get('comment') ?? '');
            $order->setTotal($this->getTotal());

            foreach ($cart as $item) {
                $orderItem = new OrderItem();
                $orderItem->setOrder($order);
                $orderItem->setCount($item->count);
                $orderItem->setProduct($this->productRepository->findOneBy(['id' => $item->id]));

                $order->addItem($orderItem);
            }

            $this->orderRepository->create($order);

            unset($_COOKIE['cart']);
            setcookie('cart', null, -1, '/');
            unset($_COOKIE['totalCount']);
            setcookie('totalCount', null, -1, '/');

            $categories = $this->categoryRepository->findBy([
                'status' => 'ACTIVE'
            ], [
                'position' => 'ASC'
            ]);

            return $this->render('thank_page/index.html.twig', [
                'order' => $order,
                'categories' => $categories,
                'totalCount' => 0,
                'phoneNumbers' => $this->settingRepository->findBy(['slug' => 'phone_number']),
                'emails'       => $this->settingRepository->findBy(['slug' => 'email'])
            ]);
        }
    }

    private function getTotal()
    {
        $total = 0;

        $cart = json_decode($_COOKIE['cart']);

        foreach ($cart as $item) {
            $total += $item->price * $item->count;
        }

        return $total;
    }
}
