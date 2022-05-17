<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\CategoryRepository;
use App\Repository\DeliveryTypeRepository;
use App\Repository\NovaPoshtaCityRepository;
use App\Repository\NovaPoshtaOfficeRepository;
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

    private DeliveryTypeRepository $deliveryTypeRepository;

    private NovaPoshtaCityRepository $novaPoshtaCityRepository;

    private NovaPoshtaOfficeRepository $novaPoshtaOfficeRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param OrderRepository $orderRepository
     * @param ProductRepository $productRepository
     * @param SettingRepository $settingRepository
     * @param DeliveryTypeRepository $deliveryTypeRepository
     * @param NovaPoshtaCityRepository $novaPoshtaCityRepository
     * @param NovaPoshtaOfficeRepository $novaPoshtaOfficeRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        SettingRepository $settingRepository,
        DeliveryTypeRepository $deliveryTypeRepository,
        NovaPoshtaCityRepository $novaPoshtaCityRepository,
        NovaPoshtaOfficeRepository $novaPoshtaOfficeRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->settingRepository = $settingRepository;
        $this->deliveryTypeRepository = $deliveryTypeRepository;
        $this->novaPoshtaCityRepository = $novaPoshtaCityRepository;
        $this->novaPoshtaOfficeRepository = $novaPoshtaOfficeRepository;
    }

    public function index(): Response
    {
        if (!empty($_COOKIE['cart'])) {
            $categories = $this->categoryRepository->findBy(['status' => 'ACTIVE'], ['position' => 'ASC']);

            $totalCart = $this->getTotalCart($_COOKIE['cart']);

            return $this->render('order_page/index.html.twig', [
                'totalPrice' => $totalCart['totalPrice'],
                'categories' => $categories,
                'totalCount' => $totalCart['totalCount'],
                'phoneNumbers' => $this->settingRepository->findBy(['slug' => 'phone_number']),
                'emails' => $this->settingRepository->findBy(['slug' => 'email']),
                'products' => $totalCart['products'],
                'deliveryTypes' => $this->deliveryTypeRepository->findAll(),
                'cities' => $this->novaPoshtaCityRepository->getCitiesWithOffices()
            ]);
        } else {
            return $this->redirectToRoute('homepage');
        }
    }

    public function post(Request $request): Response
    {
        if (isset($_COOKIE['cart'])) {
            $totalCart = $this->getTotalCart($_COOKIE['cart']);

            $order = new Order();
            $order->setName($request->request->get('name'));
            $order->setSurname($request->request->get('surname'));
            $order->setAddress($this->getAddress($request->request));
            $order->setPhone($request->request->get('phone'));
            $order->setEmail($request->request->get('email') ?? '');
            $order->setPaytype($request->request->get('paytype'));
            $order->setDeltype($request->request->get('deltype'));
            $order->setStatus('NEW');
            $order->setPaymentStatus(false);
            $order->setComment($request->request->get('comment') ?? '');
            $order->setTotal($totalCart['totalPrice']);

            foreach ($totalCart['products'] as $item) {
                $orderItem = new OrderItem();
                $orderItem->setOrder($order);
                $orderItem->setCount($item->count);
                $orderItem->setProduct($this->productRepository->findOneBy(['id' => $item->getId()]));

                $order->addItem($orderItem);
            }

            $this->orderRepository->create($order);

            unset($_COOKIE['cart']);
            unset($_COOKIE['totalCount']);

            setcookie('cart', null, -1, '/');
            setcookie('totalCount', null, -1, '/');

            return $this->render('thank_page/index.html.twig', [
                'order' => $order,
                'categories' => $this->categoryRepository->findBy(['status' => 'ACTIVE'], ['position' => 'ASC']),
                'totalCount' => 0,
                'phoneNumbers' => $this->settingRepository->findBy(['slug' => 'phone_number']),
                'emails' => $this->settingRepository->findBy(['slug' => 'email'])
            ]);
        }
    }

    private function getTotalCart($cart) {
        $products = [];
        $totalCount = 0;
        $totalPrice = 0;

        foreach (json_decode($cart) as $item) {
            if ($item->id && $item->id > 0 && $item->count && $item->count > 0) {
                $product = $this->productRepository->findOneBy(['id' => $item->id]);

                if ($product) {
                    $product->count = $item->count;
                    $totalCount += $item->count;
                    $totalPrice += $product->getPrice() * $item->count;
                    $products[] = $product;
                }
            }
        }

        return [
            'products' => $products,
            'totalPrice' => $totalPrice,
            'totalCount' => $totalCount
        ];
    }

    private function getAddress($data): string
    {
        $address = $data->get('address');
        $city = $this->novaPoshtaCityRepository->findOneBy(['ref' => $data->get('city')]);
        $office = $this->novaPoshtaOfficeRepository->findOneBy(['ref' => $data->get('office')]);

        return $city ? $city . ', ' . $office : $address;
    }
}
