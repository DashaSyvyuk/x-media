<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\DeliveryTypeRepository;
use App\Repository\NovaPoshtaCityRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use App\Service\Order\CreateService;
use App\Utils\OrderNumber;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderPageController extends BaseController
{
    /**
     * @param CategoryRepository $categoryRepository
     * @param OrderRepository $orderRepository
     * @param ProductRepository $productRepository
     * @param SettingRepository $settingRepository
     * @param DeliveryTypeRepository $deliveryTypeRepository
     * @param NovaPoshtaCityRepository $novaPoshtaCityRepository
     * @param UserRepository $userRepository
     * @param OrderNumber $orderNumber
     * @param CreateService $createService
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
        private readonly SettingRepository $settingRepository,
        private readonly DeliveryTypeRepository $deliveryTypeRepository,
        private readonly NovaPoshtaCityRepository $novaPoshtaCityRepository,
        private readonly UserRepository $userRepository,
        private readonly OrderNumber $orderNumber,
        private readonly CreateService $createService,
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
                'deliveryTypes' => $this->deliveryTypeRepository->findBy(['enabled' => true], ['priority' => 'ASC']),
                'cities' => $this->novaPoshtaCityRepository->getCitiesWithOffices(),
                'offices' => $city ? $city->getOffices() : null,
                'user' => $user ?? null
            ]);
        } else {
            return $this->redirectToRoute('index');
        }
    }

    public function post(Request $request, ValidatorInterface $validator): Response|JsonResponse
    {
        if (isset($_COOKIE['cart'])) {
            $totalCart = $this->getTotalCart();

            $user = null;
            if ($email = trim($request->request->get('email'))) {
                $user = $this->userRepository->findOneBy(['email' => $email]);
            }

            $order = $this->orderRepository->fill([
                'orderNumber'      => $this->orderNumber->generateOrderNumber(),
                'email'            => trim($request->request->get('email')) ?? '',
                'name'             => trim($request->request->get('name')) ?? '',
                'surname'          => trim($request->request->get('surname')) ?? '',
                'address'          => trim($request->request->get('address')) ?? '',
                'city'             => trim($request->request->get('city')) ?? '',
                'office'           => trim($request->request->get('office')) ?? '',
                'phone'            => trim($request->request->get('phone')) ?? '',
                'paytype'          => trim($request->request->get('paytype')) ?? '',
                'deltype'          => trim($request->request->get('deltype')) ?? '',
                'comment'          => trim($request->request->get('comment')) ?? '',
                'total'            => $totalCart['totalPrice'],
                'products'         => $totalCart['products'],
                'user'             => $user,
                'sendNotification' => true,
            ]);

            $errors = $validator->validate($order);

            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $violation) {
                    $messages[$violation->getPropertyPath()][] = $violation->getMessage();
                }
                return new JsonResponse($messages, 422);
            }

            $order = $this->orderRepository->create($order);

            $this->createService->run($order, $request->getHost());

            unset($_COOKIE['cart']);
            unset($_COOKIE['totalCount']);
            setcookie('cart', null, -1, '/');
            setcookie('totalCount', null, -1, '/');
            setcookie('orderId', $order->getId());

            return new JsonResponse(null, 200);
        } else {
            return new JsonResponse([], 422);
        }
    }
}
