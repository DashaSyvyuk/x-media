<?php

namespace App\Controller\Admin2;

use App\Service\Admin2\Admin2StatisticsProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class StatisticsController extends AbstractController
{
    public function __construct(
        private readonly Admin2StatisticsProvider $statisticsProvider,
    ) {
    }

    #[Route('/admin/statistics', name: 'admin2_statistics', methods: ['GET'])]
    public function orders(Request $request): Response
    {
        $customRange = $this->statisticsProvider->parseCustomRange(
            $request->query->get('from'),
            $request->query->get('to'),
        );

        if ($customRange !== null) {
            [$from, $to] = $customRange;
            $stats = $this->statisticsProvider->buildOrders(
                Admin2StatisticsProvider::PERIOD_CUSTOM,
                $from,
                $to,
            );
        } else {
            $period = $this->statisticsProvider->normalizePeriod(
                (string) $request->query->get('period', Admin2StatisticsProvider::PERIOD_30),
            );
            $stats = $this->statisticsProvider->buildOrders($period);
        }

        return $this->render('admin2/statistics/orders.html.twig', [
            'stats'   => $stats,
            'periods' => Admin2StatisticsProvider::PERIODS,
        ]);
    }

    #[Route('/admin/statistics/finance', name: 'admin2_statistics_finance', methods: ['GET'])]
    public function finance(): Response
    {
        return $this->render('admin2/statistics/finance.html.twig', [
            'stats' => $this->statisticsProvider->buildFinance(
                $this->isGranted('ROLE_SUPER_ADMIN'),
            ),
        ]);
    }
}
