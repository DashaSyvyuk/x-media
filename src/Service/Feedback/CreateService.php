<?php

namespace App\Service\Feedback;

use App\Entity\Feedback;
use App\Repository\FeedbackRepository;

class CreateService
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
    ) {
    }

    /**
     * @param array<string, string> $data
     */
    public function run(array $data): Feedback
    {
        return $this->feedbackRepository->create([
            'author'  => $data['author'],
            'email'   => $data['email'],
            'comment' => $data['comment'],
            'status'  => $data['status'],
        ]);
    }
}
