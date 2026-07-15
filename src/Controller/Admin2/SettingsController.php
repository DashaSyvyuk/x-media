<?php

namespace App\Controller\Admin2;

use App\Entity\AdminUser;
use App\Entity\Currency;
use App\Entity\DeliveryType;
use App\Entity\FopProfile;
use App\Entity\PaymentType;
use App\Entity\Setting;
use App\Repository\AdminUserRepository;
use App\Repository\DeliveryTypeRepository;
use App\Repository\PaymentTypeRepository;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class SettingsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SettingRepository $settingRepository,
        private readonly PaymentTypeRepository $paymentTypeRepository,
        private readonly DeliveryTypeRepository $deliveryTypeRepository,
        private readonly AdminUserRepository $adminUserRepository,
    ) {
    }

    #[Route('/admin2/settings', name: 'admin2_settings', methods: ['GET'])]
    public function index(Request $request): Response
    {
        /** @var AdminUser $user */
        $user = $this->getUser();

        $tabs      = $this->buildTabs();
        $activeTab = $this->resolveActiveTab($tabs, (string) $request->query->get('tab', 'profile'));

        return $this->render('admin2/settings/index.html.twig', [
            'user'          => $user,
            'initials'      => $this->getInitials($user),
            'roleLabel'     => $this->getRoleLabel($user),
            'tabs'          => $tabs,
            'activeTab'     => $activeTab,
            'currencies'    => $activeTab === 'currencies'
                ? $this->entityManager->getRepository(Currency::class)->findBy([], ['id' => 'DESC'])
                : [],
            'fops'          => $activeTab === 'fops'
                ? $this->entityManager->getRepository(FopProfile::class)->findBy([], ['id' => 'DESC'])
                : [],
            'shopSettings'  => $activeTab === 'shop'
                ? $this->settingRepository->findBy([], ['id' => 'DESC'])
                : [],
            'paymentTypes'  => $activeTab === 'payment'
                ? $this->paymentTypeRepository->findBy([], ['id' => 'DESC'])
                : [],
            'deliveryTypes' => $activeTab === 'delivery'
                ? $this->deliveryTypeRepository->findBy([], ['priority' => 'ASC', 'id' => 'DESC'])
                : [],
            'adminUsers'    => $activeTab === 'admins'
                ? $this->adminUserRepository->findBy([], ['id' => 'DESC'])
                : [],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function buildTabs(): array
    {
        $tabs = ['profile' => 'Профіль'];

        if ($this->isGranted('ROLE_ADMIN')) {
            $tabs['currencies'] = 'Валюти';
            $tabs['fops']       = 'ФОП';
            $tabs['delivery']   = 'Доставка';
            $tabs['payment']    = 'Оплата';
            $tabs['admins']     = 'Адміни';
        }

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $tabs['shop'] = 'Параметри';
        }

        return $tabs;
    }

    /**
     * @param array<string, string> $tabs
     */
    private function resolveActiveTab(array $tabs, string $requested): string
    {
        if (isset($tabs[$requested])) {
            return $requested;
        }

        return array_key_first($tabs) ?: 'profile';
    }

    private function getInitials(AdminUser $user): string
    {
        return mb_strtoupper(mb_substr($user->getName(), 0, 1) . mb_substr($user->getSurname(), 0, 1));
    }

    private function getRoleLabel(AdminUser $user): string
    {
        $roles = $user->getRoles();

        if (in_array(AdminUser::ROLE_SUPER_ADMIN, $roles, true)) {
            return 'Бог';
        }

        if (in_array(AdminUser::ROLE_ADMIN, $roles, true)) {
            return 'Кріпак';
        }

        return 'Холоп';
    }
}
