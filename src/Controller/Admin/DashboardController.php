<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Currency;
use App\Entity\DeliveryType;
use App\Entity\Feedback;
use App\Entity\Filter;
use App\Entity\Order;
use App\Entity\PaymentType;
use App\Entity\Product;
use App\Entity\Promotion;
use App\Entity\Setting;
use App\Entity\Slider;
use App\Entity\Supplier;
use App\Entity\SupplierOrder;
use App\Entity\User;
use App\Entity\Warehouse;
use App\Repository\OrderRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
    )
    {
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(ProductCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            // the name visible to end users
            ->setTitle('X-media')

            // by default EasyAdmin displays a black square as its default favicon;
            // use this method to display a custom favicon: the given path is passed
            // "as is" to the Twig asset() function:
            // <link rel="shortcut icon" href="{{ asset('...') }}">
            ->setFaviconPath('favicon.ico')

            // the domain used by default is 'messages'
            // ->setTranslationDomain('my-custom-domain')

            // there's no need to define the "text direction" explicitly because
            // its default value is inferred dynamically from the user locale
            ->setTextDirection('ltr')

            // set this option if you prefer the page content to span the entire
            // browser width, instead of the default design which sets a max width

            // by default, all backend URLs are generated as absolute URLs. If you
            // need to generate relative URLs instead, call this method
            //->generateRelativeUrls()
            ;
    }

    public function configureMenuItems(): iterable
    {
        // yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        $ordersCount = $this->orderRepository->count(['status' => 'new']);

        yield MenuItem::subMenu('Контент', 'fas fa-box-open')->setSubItems([
            MenuItem::linkToCrud('Товари', 'fas fa-box-open', Product::class),
            MenuItem::linkToCrud('Фільтри', 'fas fa-filter', Filter::class),
            MenuItem::linkToCrud('Категорії', 'fas fa-comment', Category::class),
            MenuItem::linkToCrud('Слайдер', 'fas fa-image', Slider::class),
            MenuItem::linkToCrud('Акції', 'fa fa-percent', Promotion::class),
            MenuItem::linkToCrud('Коментарі', 'fas fa-comment', Comment::class),
            MenuItem::linkToCrud('Відгуки', 'fas fa-comment', Feedback::class),
        ]);

        yield MenuItem::subMenu(sprintf('Замовлення (%d)', $ordersCount), 'fas fa-list')->setSubItems([
            MenuItem::linkToCrud(sprintf('Замовлення (%d)', $ordersCount), 'fas fa-list', Order::class),
            MenuItem::linkToCrud('Користувачі', 'fa-solid fa-user-secret', User::class),
        ]);

        yield MenuItem::subMenu('Постачальники', 'fa fa-truck')->setSubItems([
            MenuItem::linkToCrud('Постачальники', 'fas fa-truck', Supplier::class),
            MenuItem::linkToCrud('Замовлення', 'fa fa-shopping-cart', SupplierOrder::class),
            MenuItem::linkToCrud('Склади', 'fa fa-archive', Warehouse::class)
        ]);

        yield MenuItem::subMenu('Налаштування', 'fa fa-cog')->setSubItems([
            MenuItem::linkToCrud('Валюти', 'fas fa-comment', Currency::class),
            MenuItem::linkToCrud('Налаштування', 'fa fa-cog', Setting::class),
            MenuItem::linkToCrud('Способи доставки', 'fa fa-bus', DeliveryType::class),
            MenuItem::linkToCrud('Способи оплати', 'fa fa-dollar', PaymentType::class),
        ]);
    }
}
