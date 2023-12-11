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
use App\Utils\OrderNumber;
use App\Utils\TurboSms;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderPageController extends BaseController
{
    /**
     * @param CategoryRepository $categoryRepository
     * @param OrderRepository $orderRepository
     * @param ProductRepository $productRepository
     * @param SettingRepository $settingRepository
     * @param DeliveryTypeRepository $deliveryTypeRepository
     * @param NovaPoshtaCityRepository $novaPoshtaCityRepository
     * @param NovaPoshtaOfficeRepository $novaPoshtaOfficeRepository
     * @param UserRepository $userRepository
     * @param OrderNumber $orderNumber
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
        private readonly SettingRepository $settingRepository,
        private readonly DeliveryTypeRepository $deliveryTypeRepository,
        private readonly NovaPoshtaCityRepository $novaPoshtaCityRepository,
        private readonly NovaPoshtaOfficeRepository $novaPoshtaOfficeRepository,
        private readonly UserRepository $userRepository,
        private readonly OrderNumber $orderNumber
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository, $this->productRepository);
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

            $totalCart = $this->getTotalCart();

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
            $totalCart = $this->getTotalCart();

            $order = $this->orderRepository->create([
                'orderNumber' => $this->orderNumber->generateOrderNumber(),
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
                'user'        => $user,
                'sendNotification' => true,
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

            if ($phone = $order->getPhone()) {
                TurboSms::send($phone, sprintf('Дякуємо за замовлення у нашому магазині! Номер замовлення %s :)', $order->getOrderNumber()));
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

    private function getAddress($data): ?string
    {
        $address = $data->get('address');
        $city = $this->novaPoshtaCityRepository->findOneBy(['ref' => $data->get('city')]);
        $office = $this->novaPoshtaOfficeRepository->findOneBy(['ref' => $data->get('office')]);
        $pickUpPoint = $this->settingRepository->findOneBy(['slug' => 'pick_up_point_address']);

        return $city ? $city . ', ' . $office : (!empty($address) ? $address : $pickUpPoint->getValue() ?? null);
    }
}
