<?php

namespace App\Controller\API;

use App\Repository\RozetkaCharacteristicsRepository;
use App\Repository\RozetkaCharacteristicsValueRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RozetkaCharacteristicsController extends AbstractController
{
    public const FORM_FIELDS = [
        'List'                => 'form/multi-select.html.twig',
        'ListValues'          => 'form/multi-select.html.twig',
        'ComboBox'            => 'form/select.html.twig',
        'CheckBoxGroupValues' => 'form/multi-select.html.twig',
        'Integer'             => 'form/integer.html.twig',
        'Decimal'             => 'form/decimal.html.twig',
        'TextInput'           => 'form/text-input.html.twig',
        'TextArea'            => 'form/textarea.html.twig',
    ];

    public function __construct(
        private readonly RozetkaCharacteristicsRepository $rozetkaCharacteristicsRepository,
        private readonly RozetkaCharacteristicsValueRepository $rozetkaCharacteristicsValueRepository
    ) {
    }

    public function getValue(string $id, int $valueId): Response
    {
        $characteristics = $this->rozetkaCharacteristicsRepository->findOneBy(['rozetkaId' => $id]);

        if (!$characteristics) {
            return new Response('Not found', 404);
        }

        $values = [];
        foreach ($this->rozetkaCharacteristicsValueRepository->findBy(['characteristic' => $characteristics, 'active' => true], ['title' => 'ASC']) as $value) {
            $values[] = [
                'id' => $value->getId(),
                'value' => $value->getTitle()
            ];
        }

        return $this->render(self::FORM_FIELDS[$characteristics->getType()], [
            'values' => $values,
            'valueId' => $valueId
        ]);
    }
}
