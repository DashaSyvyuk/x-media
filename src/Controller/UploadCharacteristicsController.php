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
        $categories = $this->categoryRepository->findAll();

        return $this->render('upload_characteristics/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    public function upload(Request $request, UploadCharacteristics $service): Response
    {
        ini_set('memory_limit', '512M');
        $categoryId = $request->request->get('category');

        if (!$category = $this->categoryRepository->findOneBy(['id' => $categoryId])) {
            return $this->redirectToRoute('upload_characteristics');
        }

        $file = $request->files->get('file');
        $service->upload($file, $category);

        return $this->redirectToRoute('upload_characteristics');
    }
}
