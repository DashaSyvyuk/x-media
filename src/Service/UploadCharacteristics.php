<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\RozetkaCharacteristicsValue;
use App\Repository\RozetkaCharacteristicsRepository;
use App\Repository\RozetkaCharacteristicsValueRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
            if ($characteristics = $this->rozetkaCharacteristicsRepository->findOneBy(['rozetkaId' => (int) $row[0]])) {
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

                    if (!in_array($row[2], ['TextArea', 'TextInput'], true)) {
                        if ($row[6] !== '') {
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

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string}>
     */
    private function readFile(UploadedFile $file): array
    {
        $path = $file->getPathname();
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        $worksheet = $spreadsheet->getActiveSheet();

        $result = [];
        foreach ($worksheet->getRowIterator() as $key => $row) {
            if ($key <= 2) {
                continue;
            }

            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $data = [];
            foreach ($cellIterator as $cell) {
                $data[] = $this->normalizeCell($cell->getValue());
            }

            // Skip completely empty trailing rows
            if ($data[0] === '' && $data[1] === '') {
                continue;
            }

            $result[] = $data;
        }

        return $result;
    }

    private function normalizeCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            // Avoid scientific notation for Excel numeric IDs
            return is_float($value) && floor($value) === $value
                ? (string) (int) $value
                : (string) $value;
        }

        return trim((string) $value);
    }
}
