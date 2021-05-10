<?php

namespace App\Controller;

use App\Repository\SliderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomePageController extends AbstractController
{
    private $sliderRepository;

    /**
     * @param SliderRepository $sliderRepository
     */
    public function __construct(SliderRepository $sliderRepository)
    {
        $this->sliderRepository = $sliderRepository;
    }

    /**
     * @Route("/", name="home_page")
     */
    public function index(): Response
    {
        $sliders = $this->sliderRepository->findAll();

        return $this->render('home_page/index.html.twig', [
            'sliders' => $sliders
        ]);
    }
}
