<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\RozetkaCharacteristicsValue;
use App\Repository\RozetkaCharacteristicsRepository;
use App\Repository\RozetkaCharacteristicsValueRepository;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadCharacteristics
{
    public function __construct(
        private readonly RozetkaCharacteristicsRepository $rozetkaCharacteristicsRepository,
        private readonly RozetkaCharacteristicsValueRepository $valueRepository
    ) {
    }

    public function upload(UploadedFile $file, Category $category): void
    {
        $rows = $this->readFile($file);

        foreach ($rows as $row) {
            if ($characteristics = $this->rozetkaCharacteristicsRepository->findOneBy(['rozetkaId' => $row[0]])) {
                $characteristicsValue = $this->valueRepository->findOneBy(['rozetkaId' => $row[5]]);

                if (
                    $characteristics->getTitle() !== $row[1] ||
                    $characteristics->getType() !== $row[2] ||
                    $characteristics->getFilterType() !== !('disable' === $row[3]) ||
                    $characteristics->getUnit() !== $row[4] ||
                    $characteristics->getEndToEndParameter() !== ($row[7] === 'Так') ||
                    !$characteristicsValue ||
                    $characteristicsValue->getTitle() !== $row[6]
                ) {
                    $characteristics->setTitle($row[1]);
                    $characteristics->setType($row[2]);
                    $characteristics->setFilterType(!('disable' === $row[3]));
                    $characteristics->setUnit($row[4]);
                    $characteristics->setEndToEndParameter($row[7] === 'Так');
                    $characteristics->addCategory($category);

                    if (!in_array($row[2], ['TextArea', 'TextInput'])) {
                        if ($row[6]) {
                            if (!$characteristicsValue) {
                                $characteristicsValue = new RozetkaCharacteristicsValue();
                                $characteristicsValue->setRozetkaId($row[5]);
                                $characteristicsValue->setTitle($row[6]);
                                $characteristicsValue->setActive(true);
                            } else {
                                $characteristicsValue->setTitle($row[6]);
                            }

                            $characteristics->addValue($characteristicsValue);
                        }
                    }

                    $this->rozetkaCharacteristicsRepository->update($characteristics);
                }
            } else {
                $characteristics = $this->rozetkaCharacteristicsRepository->fill([
                    'rozetkaId' => $row[0],
                    'title' => $row[1],
                    'type' => $row[2],
                    'filterType' => !('disable' === $row[3]),
                    'unit' => $row[4],
                    'endToEndParameter' => $row[7] === 'Так',
                    'active' => true,
                    'values' => [
                        [
                            'rozetkaId' => $row[5],
                            'title' => $row[6],
                            'active' => true,
                        ]
                    ],
                    'category' => $category,
                ]);

                $this->rozetkaCharacteristicsRepository->create($characteristics);
            }
        }
    }

    private function readFile(UploadedFile $file): iterable
    {
        $xlsxReader = new Xlsx();
        $xlsxReader->setReadDataOnly(true);
        $spreadsheet = $xlsxReader->load($file);

        $worksheet = $spreadsheet->getActiveSheet();

        $result = [];
        foreach ($worksheet->getRowIterator() as $key => $row) {
            if ($key > 2) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $data = [];
                foreach ($cellIterator as $cell) {
                    $data[] = $cell->getValue();
                }

                $result[] = $data;
            }
        }

        return $result;
    }
}
