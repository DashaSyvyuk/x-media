<?php

namespace App\Controller\Admin2;

use App\Entity\Slider;
use App\Form\Admin2\SliderType;
use App\Repository\SliderRepository;
use App\Service\Admin2\EntityImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class SliderEditController extends AbstractController
{
    public function __construct(
        private readonly SliderRepository $sliderRepository,
        private readonly EntityImageUploader $entityImageUploader,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/sliders/new', name: 'admin2_sliders_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $slider = new Slider();
        $slider->setTitle('');
        $slider->setUrl('');
        $slider->setImageUrl('');
        $slider->setPriority(0);
        $slider->setActive(true);

        return $this->handleForm($request, $slider, true);
    }

    #[Route('/admin/sliders/{id}/edit', name: 'admin2_sliders_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $slider = $this->sliderRepository->find($id);
        if (! $slider instanceof Slider) {
            throw $this->createNotFoundException('Слайд не знайдено.');
        }

        return $this->handleForm($request, $slider, false);
    }

    private function handleForm(Request $request, Slider $slider, bool $isNew): Response
    {
        $form = $this->createForm(SliderType::class, $slider);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applyUploadedImage($form->get('imageFile')->getData(), $slider);

            if ($isNew) {
                $this->entityManager->persist($slider);
            }

            $this->entityManager->flush();

            $this->addFlash(
                'success',
                $isNew
                    ? sprintf('Слайд «%s» створено.', $slider->getTitle() ?: '#' . $slider->getId())
                    : sprintf('Слайд «%s» збережено.', $slider->getTitle() ?: '#' . $slider->getId()),
            );

            return $this->redirectToRoute('admin2_sliders');
        }

        return $this->render('admin2/sliders/edit.html.twig', [
            'slider' => $slider,
            'form'   => $form,
            'isNew'  => $isNew,
        ]);
    }

    private function applyUploadedImage(mixed $file, Slider $slider): void
    {
        if (! $file instanceof UploadedFile) {
            return;
        }

        $slider->setImageUrl($this->entityImageUploader->upload($file, Slider::class));
    }
}
