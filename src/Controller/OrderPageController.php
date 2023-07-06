<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\DeliveryTypeRepository;
use App\Repository\NovaPoshtaCityRepository;
use App\Repository\NovaPoshtaOfficeRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderPageController extends BaseController
{
    private SettingRepository $settingRepository;

    private OrderRepository $orderRepository;

    private ProductRepository $productRepository;

    private DeliveryTypeRepository $deliveryTypeRepository;

    private NovaPoshtaCityRepository $novaPoshtaCityRepository;

    private NovaPoshtaOfficeRepository $novaPoshtaOfficeRepository;

    private UserRepository $userRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param OrderRepository $orderRepository
     * @param ProductRepository $productRepository
     * @param SettingRepository $settingRepository
     * @param DeliveryTypeRepository $deliveryTypeRepository
     * @param NovaPoshtaCityRepository $novaPoshtaCityRepository
     * @param NovaPoshtaOfficeRepository $novaPoshtaOfficeRepository
     * @param UserRepository $userRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        SettingRepository $settingRepository,
        DeliveryTypeRepository $deliveryTypeRepository,
        NovaPoshtaCityRepository $novaPoshtaCityRepository,
        NovaPoshtaOfficeRepository $novaPoshtaOfficeRepository,
        UserRepository $userRepository
    ) {
        parent::__construct($categoryRepository, $settingRepository);
        $this->settingRepository = $settingRepository;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->deliveryTypeRepository = $deliveryTypeRepository;
        $this->novaPoshtaCityRepository = $novaPoshtaCityRepository;
        $this->novaPoshtaOfficeRepository = $novaPoshtaOfficeRepository;
        $this->userRepository = $userRepository;
    }

    public function index(Request $request): Response
    {
        if (!empty($_COOKIE['cart'])) {
            $city = null;

            $session = $request->getSession();

            if ($email = $session->get('user')) {
                $user = $this->userRepository->findOneBy(['email' => $email]);

                if ($user->getNovaPoshtaCity()) {
                    $city = $this->novaPoshtaCityRepository->findOneBy(['ref' => $user->getNovaPoshtaCity()]);
                }
            }

            $totalCart = $this->getTotalCart($_COOKIE['cart']);

            return $this->renderTemplate($request, 'order_page/index.html.twig', [
                'totalPrice' => $totalCart['totalPrice'],
                'products' => $totalCart['products'],
                'deliveryTypes' => $this->deliveryTypeRepository->findBy(['enabled' => true]),
                'cities' => $this->novaPoshtaCityRepository->getCitiesWithOffices(),
                'offices' => $city ? $city->getOffices() : null,
                'user' => $user ?? null
            ]);
        } else {
            return $this->redirectToRoute('index');
        }
    }

    /**
     * @throws OptimisticLockException
     * @throws TransportExceptionInterface
     * @throws ORMException
     */
    public function post(Request $request, MailerInterface $mailer): Response
    {
        if (isset($_COOKIE['cart'])) {
            $user = null;
            if ($email = $request->request->get('email')) {
                $user = $this->userRepository->findOneBy(['email' => $email]);
            }

            $managerEmail = $this->settingRepository->findOneBy(['slug' => 'email']);
            $totalCart = $this->getTotalCart($_COOKIE['cart']);

            $order = $this->orderRepository->create([
                'orderNumber' => $this->generateOrderNumber(),
                'name'        => $request->request->get('name'),
                'surname'     => $request->request->get('surname'),
                'address'     => $this->getAddress($request->request),
                'phone'       => $request->request->get('phone'),
                'email'       => $request->request->get('email') ?? '',
                'paytype'     => $request->request->get('paytype'),
                'deltype'     => $request->request->get('deltype'),
                'comment'     => $request->request->get('comment') ?? '',
                'total'       => $totalCart['totalPrice'],
                'products'    => $totalCart['products'],
                'user'        => $user
            ]);

            unset($_COOKIE['cart']);
            unset($_COOKIE['totalCount']);
            setcookie('cart', null, -1, '/');
            setcookie('totalCount', null, -1, '/');

            $mainUrl = sprintf('%s%s/', 'https://', $request->getHost());

            if ($order->getEmail()) {
                $message = (new Email())
                    ->subject(sprintf('Нове замовлення %s', $order->getOrderNumber()))
                    ->from('x-media@x-media.com.ua')
                    ->to($order->getEmail())
                    ->html(
                        $this->renderView(
                            'emails/client-orders.html.twig',
                            [
                                'order' => $order,
                                'mainUrl' => $mainUrl,
                                'phoneNumber' => $this->settingRepository->findOneBy(['slug' => 'phone_number']),
                                'email' => $managerEmail
                            ]
                        )
                    );

                $mailer->send($message);
            }

            $managerMessage = (new Email())
                ->subject(sprintf('Нове замовлення %s', $order->getOrderNumber()))
                ->from('x-media@x-media.com.ua')
                ->to($managerEmail->getValue())
                ->html(
                    $this->renderView(
                        'emails/manager-order.html.twig',
                        [
                            'mainUrl' => $mainUrl,
                            'order' => $order
                        ]
                    )
                )
            ;

            $mailer->send($managerMessage);

            return $this->renderTemplate($request, 'thank_page/index.html.twig', [
                'order' => $order
            ]);
        } else {
            return $this->redirectToRoute('index');
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

    private function getAddress($data): ?string
    {
        $address = $data->get('address');
        $city = $this->novaPoshtaCityRepository->findOneBy(['ref' => $data->get('city')]);
        $office = $this->novaPoshtaOfficeRepository->findOneBy(['ref' => $data->get('office')]);
        $pickUpPoint = $this->settingRepository->findOneBy(['slug' => 'pick_up_point_address']);

        return $city ? $city . ', ' . $office : (!empty($address) ? $address : $pickUpPoint->getValue() ?? null);
    }

    private function generateOrderNumber(): string
    {
        $lastId = 1;
        $today = date('Ymd');
        $lastOrder = $this->orderRepository->findOneBy([], ['id' => 'desc']);

        if ($lastOrder) {
            $lastId = $lastOrder->getId() + 1;
        }

        return sprintf('%s%s', $today, $lastId);
    }
}
