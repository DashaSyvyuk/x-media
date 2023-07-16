<?php

namespace App\Controller\API;

use App\Repository\FilterAttributeRepository;
use App\Repository\FilterRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class FilterAttributeController
{
    public function __construct(
        private readonly FilterRepository $filterRepository,
        private readonly FilterAttributeRepository $filterAttributeRepository
    ) {
    }

    public function get(int $id): Response
    {
        $filter = $this->filterRepository->findOneBy(['id' => $id]);

        if (!$filter) {
            return new Response('Not found', 404);
        }
        $filterAttributes = [];

        foreach ($this->filterAttributeRepository->findBy(['filter' => $filter]) as $filterAttribute) {
            $filterAttributes[] = [
                'id' => $filterAttribute->getId(),
                'value' => $filterAttribute->getValue()
            ];
        }

        return new JsonResponse($filterAttributes);
    }
}
