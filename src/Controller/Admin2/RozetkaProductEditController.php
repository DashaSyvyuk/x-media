<?php

namespace App\Controller\Admin2;

use App\Entity\RozetkaProduct;
use App\Form\Admin2\RozetkaProductType;
use App\Repository\RozetkaProductRepository;
use App\Service\Admin2\RozetkaProductCopyService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')")]
class RozetkaProductEditController extends AbstractController
{
    public function __construct(
        private readonly RozetkaProductRepository $rozetkaProductRepository,
        private readonly RozetkaProductCopyService $rozetkaProductCopyService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/rozetka/{id}/edit', name: 'admin2_rozetka_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $rozetkaProduct = $this->rozetkaProductRepository->find($id);
        if (! $rozetkaProduct instanceof RozetkaProduct) {
            throw $this->createNotFoundException('Rozetka товар не знайдено.');
        }

        $copyCharacteristics = $request->request->getBoolean('copy_characteristics');
        $disableFeedFlags    = ! $rozetkaProduct->getReady();

        $form = $this->createForm(RozetkaProductType::class, $rozetkaProduct, [
            'disable_feed_flags' => $disableFeedFlags,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($copyCharacteristics && ! $rozetkaProduct->getReady()) {
                $sourceId = $form->has('rozetkaProduct')
                    ? $form->get('rozetkaProduct')->getData()?->getId()
                    : null;
                $source = $this->rozetkaProductCopyService->findSource($sourceId);

                if ($source === null) {
                    $this->addFlash('error', 'Оберіть товар-джерело для копіювання характеристик.');
                } else {
                    $this->rozetkaProductCopyService->copyCharacteristics($rozetkaProduct, $source);
                }
            }

            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Rozetka товар «%s» збережено.', $rozetkaProduct->getTitle()));

            return $this->redirectToRoute('admin2_rozetka_edit', ['id' => $rozetkaProduct->getId()]);
        }

        return $this->render('admin2/rozetka/edit.html.twig', [
            'rozetkaProduct' => $rozetkaProduct,
            'form'           => $form,
            'product'        => $rozetkaProduct->getProduct(),
        ]);
    }
}
