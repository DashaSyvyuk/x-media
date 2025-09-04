<?php

namespace App\Controller\Admin;

use App\Entity\AdminUser;
use App\Entity\Category;
use App\Entity\Circulation;
use App\Entity\Comment;
use App\Entity\Currency;
use App\Entity\Debtor;
use App\Entity\DeliveryType;
use App\Entity\Feed;
use App\Entity\Feedback;
use App\Entity\Filter;
use App\Entity\Order;
use App\Entity\PaymentType;
use App\Entity\Product;
use App\Entity\Promotion;
use App\Entity\ReturnProduct;
use App\Entity\RozetkaProduct;
use App\Entity\Setting;
use App\Entity\Slider;
use App\Entity\Supplier;
use App\Entity\SupplierOrder;
use App\Entity\User;
use App\Entity\Warehouse;
use App\Entity\Warranty;
use App\Repository\CommentRepository;
use App\Repository\FeedbackRepository;
use App\Repository\OrderRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')")]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly CommentRepository $commentRepository,
        private readonly FeedbackRepository $feedbackRepository,
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
        $ordersCount = $this->orderRepository->count(['status' => Order::NEW]);
        $commentsCount = $this->commentRepository->count(['status' => Comment::STATUS_NEW]);
        $feedbacksCount = $this->feedbackRepository->count(['status' => Feedback::STATUS_NEW]);

        yield MenuItem::subMenu('Контент', 'fas fa-box-open')->setSubItems([
            MenuItem::linkToCrud('Товари', 'fas fa-box-open', Product::class),
            MenuItem::linkToCrud('Фільтри', 'fas fa-filter', Filter::class),
            MenuItem::linkToCrud('Категорії', 'fas fa-comment', Category::class),
            MenuItem::linkToCrud('Слайдер', 'fas fa-image', Slider::class)->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('Акції', 'fa fa-percent', Promotion::class)->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud(sprintf('Коментарі (%s)', $commentsCount), 'fas fa-comment', Comment::class)->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud(sprintf('Відгуки (%s)', $feedbacksCount), 'fas fa-comment', Feedback::class)->setPermission('ROLE_ADMIN'),
        ]);

        yield MenuItem::subMenu('Фінанси', 'fa fa-coins')->setSubItems([
            MenuItem::linkToCrud('Борги', 'fa fa-coins', Debtor::class)->setPermission('ROLE_SUPER_ADMIN')
                ->setQueryParameter('filters[active][value]', 1),
            MenuItem::linkToCrud('Обіг', 'fa fa-dollar-sign', Circulation::class)
                ->setQueryParameter('filters[active][value]', 1),
        ])->setPermission('ROLE_ADMIN');

        yield MenuItem::subMenu(sprintf('Замовлення (%d)', $ordersCount), 'fas fa-list')->setSubItems([
            MenuItem::linkToCrud(sprintf('Замовлення (%d)', $ordersCount), 'fas fa-list', Order::class),
            MenuItem::linkToCrud('Користувачі', 'fa-solid fa-user-secret', User::class),
            MenuItem::linkToCrud('Повернення', 'fa fa-undo', ReturnProduct::class),
            MenuItem::linkToCrud('Гарантія', 'fa fa-certificate', Warranty::class),
        ])->setPermission('ROLE_SUPER_ADMIN');

        yield MenuItem::subMenu('Постачальники', 'fa fa-truck')->setSubItems([
            MenuItem::linkToCrud('Постачальники', 'fas fa-truck', Supplier::class),
            MenuItem::linkToCrud('Замовлення', 'fa fa-shopping-cart', SupplierOrder::class),
            MenuItem::linkToCrud('Склади', 'fa fa-archive', Warehouse::class)
        ])->setPermission('ROLE_ADMIN');

        yield MenuItem::subMenu('Налаштування', 'fa fa-cog')->setSubItems([
            MenuItem::linkToCrud('Валюти', 'fas fa-comment', Currency::class),
            MenuItem::linkToCrud('Налаштування', 'fa fa-cog', Setting::class)->setPermission('ROLE_SUPER_ADMIN'),
            MenuItem::linkToCrud('Способи доставки', 'fa fa-bus', DeliveryType::class),
            MenuItem::linkToCrud('Способи оплати', 'fa fa-dollar', PaymentType::class),
            MenuItem::linkToCrud('Адміни', 'fa-solid fa-user-secret', AdminUser::class),
        ])->setPermission('ROLE_ADMIN');

        yield MenuItem::subMenu('Feeds', 'fa fa-file')->setSubItems([
            MenuItem::linkToCrud('Feeds', 'fa fa-file', Feed::class)->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('Rozetka', 'fa fa-face-smile', RozetkaProduct::class),
        ]);
    }
}
