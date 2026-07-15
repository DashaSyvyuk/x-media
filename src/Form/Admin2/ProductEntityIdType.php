<?php

namespace App\Form\Admin2;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Maps Product entity ↔ numeric ID without loading the full catalog into a select.
 */
class ProductEntityIdType extends AbstractType
{
    public function __construct(
        private readonly ProductRepository $productRepository,
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label'           => 'ID товару',
            'invalid_message' => 'Товар з таким ID не існує.',
        ]);
    }

    public function getParent(): string
    {
        return IntegerType::class;
    }
}
