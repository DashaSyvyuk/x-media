<?php

namespace App\Form\Admin2;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductPickerType extends AbstractType
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            fn(?Product $product): ?int => $product?->getId(),
            function (?int $id): ?Product {
                if ($id === null || $id <= 0) {
                    return null;
                }

                $product = $this->productRepository->find($id);
                if ($product === null) {
                    throw new TransformationFailedException(sprintf('Товар #%d не знайдено.', $id));
                }

                return $product;
            },
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $product = $form->getData();
        if (! $product instanceof Product) {
            $viewId = $form->getViewData();
            if (is_numeric($viewId) && (int) $viewId > 0) {
                $product = $this->productRepository->find((int) $viewId);
            }
        }

        $view->vars['selected_product'] = $product instanceof Product
            ? $this->productRepository->findAdminPickerItem($product->getId())
            : null;
        $view->vars['search_url'] = $this->urlGenerator->generate('admin2_api_products_search');
        $view->vars['show_url_template'] = $this->urlGenerator->generate('admin2_api_products_show', ['id' => 0]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label'           => 'Товар',
            'invalid_message' => 'Товар з таким ID не існує.',
        ]);
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'product_picker';
    }
}
