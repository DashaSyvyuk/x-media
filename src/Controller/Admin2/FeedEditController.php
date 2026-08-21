<?php

namespace App\Controller\Admin2;

use App\Entity\Feed;
use App\Form\Admin2\FeedType;
use App\Repository\FeedRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class FeedEditController extends AbstractController
{
    public function __construct(
        private readonly FeedRepository $feedRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/feeds/new', name: 'admin2_feeds_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $feed = new Feed();
        $feed->setType(Feed::FEED_ROZETKA);

        return $this->handleForm($request, $feed, true);
    }

    #[Route('/admin/feeds/{id}/edit', name: 'admin2_feeds_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $feed = $this->feedRepository->find($id);
        if (! $feed instanceof Feed) {
            throw $this->createNotFoundException('Feed не знайдено.');
        }

        return $this->handleForm($request, $feed, false);
    }

    private function handleForm(Request $request, Feed $feed, bool $isNew): Response
    {
        $form = $this->createForm(FeedType::class, $feed);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isNew) {
                $this->entityManager->persist($feed);
            }

            $this->entityManager->flush();
            $this->addFlash('success', $isNew ? 'Feed створено.' : 'Feed збережено.');

            return $this->redirectToRoute('admin2_feeds');
        }

        return $this->render('admin2/feeds/edit.html.twig', [
            'feed'  => $feed,
            'form'  => $form,
            'isNew' => $isNew,
        ]);
    }
}
