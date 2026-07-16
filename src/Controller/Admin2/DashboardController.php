<?php

namespace App\Controller\Admin2;

use App\Entity\AdminUser;
use App\Service\Admin2\Admin2DashboardProvider;
use App\Service\Admin2\OrderFulfillmentCustomerBoardProvider;
use App\Service\Admin2\OrderFulfillmentStatusHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly Admin2DashboardProvider $dashboardProvider,
        private readonly OrderFulfillmentCustomerBoardProvider $customerBoardProvider,
        private readonly OrderFulfillmentStatusHelper $fulfillmentStatusHelper,
    ) {
    }

    #[Route('/admin', name: 'admin2_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        /** @var AdminUser $user */
        $user = $this->getUser();
        $isStaff = $this->isGranted('ROLE_ADMIN');

        $packingOrders = [];
        if (! $isStaff) {
            foreach ($this->customerBoardProvider->getCustomerOrders(false) as $order) {
                if ($this->fulfillmentStatusHelper->isPackingTone((string) ($order['statusTone'] ?? ''))) {
                    $packingOrders[] = $order;
                }
            }
        }

        return $this->render('admin2/dashboard/index.html.twig', [
            'user'          => $user,
            'dash'          => $this->dashboardProvider->build(
                $this->isGranted('ROLE_SUPER_ADMIN'),
            ),
            'packingOrders' => $packingOrders,
        ]);
    }
}
