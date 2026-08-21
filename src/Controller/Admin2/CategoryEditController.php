<?php

namespace App\Controller\Admin2;

use App\Entity\Category;
use App\Form\Admin2\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\Admin2\EntityImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class CategoryEditController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityImageUploader $entityImageUploader,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/categories/new', name: 'admin2_categories_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $category = new Category();
        $category->setTitle('');
        $category->setSlug('');
        $category->setStatus(Category::ACTIVE);
        $category->setPosition(0);
        $category->setMetaKeyword('');
        $category->setMetaDescription('');

        return $this->handleForm($request, $category, true);
    }

    #[Route('/admin/categories/{id}/edit', name: 'admin2_categories_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $category = $this->categoryRepository->find($id);
        if (! $category instanceof Category) {
            throw $this->createNotFoundException('Категорію не знайдено.');
        }

        return $this->handleForm($request, $category, false);
    }

    private function handleForm(Request $request, Category $category, bool $isNew): Response
    {
        $form = $this->createForm(CategoryType::class, $category, [
            'exclude_category_id' => $isNew ? null : $category->getId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applyUploadedImage($form->get('imageFile')->getData(), $category);

            if ($isNew) {
                $this->entityManager->persist($category);
            }

            $this->entityManager->flush();

            $this->addFlash(
                'success',
                $isNew
                    ? sprintf('Категорію «%s» створено.', $category->getTitle())
                    : sprintf('Категорію «%s» збережено.', $category->getTitle()),
            );

            return $this->redirectToRoute('admin2_categories');
        }

        return $this->render('admin2/categories/edit.html.twig', [
            'category' => $category,
            'form'     => $form,
            'isNew'    => $isNew,
        ]);
    }

    private function applyUploadedImage(mixed $file, Category $category): void
    {
        if (! $file instanceof UploadedFile) {
            return;
        }

        $category->setImage($this->entityImageUploader->upload($file, Category::class));
    }
}
