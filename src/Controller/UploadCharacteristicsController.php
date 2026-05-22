<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Service\UploadCharacteristics;

class UploadCharacteristicsController extends AbstractController
{
    /**
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    public function index(): Response
    {
        $categories = $this->categoryRepository->getUploadCharacteristicCategories();

        return $this->render('upload_characteristics/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    public function upload(Request $request, UploadCharacteristics $service): Response
    {
        $categoryId = (int) $request->request->get('category', 0);
        $file = $request->files->get('file');

        if ($categoryId <= 0 || $file === null) {
            return $this->redirectToRoute('upload_characteristics');
        }

        $category = $this->categoryRepository->find($categoryId);
        if ($category === null) {
            return $this->redirectToRoute('upload_characteristics');
        }

        $service->upload($file, $category);

        return $this->redirectToRoute('upload_characteristics');
    }
}
