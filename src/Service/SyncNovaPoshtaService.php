<?php

namespace App\Service;

use App\Entity\NovaPoshtaCity;
use App\Entity\NovaPoshtaOffice;
use App\Repository\NovaPoshtaCityRepository;
use App\Repository\NovaPoshtaOfficeRepository;
use App\Repository\SettingRepository;
use GuzzleHttp\Client;

class SyncNovaPoshtaService
{
    private string $key;

    private Client $client;

    private SettingRepository $settingRepository;

    private NovaPoshtaCityRepository $novaPoshtaCityRepository;

    private NovaPoshtaOfficeRepository $novaPoshtaOfficeRepository;

    public function __construct(
        SettingRepository $settingRepository,
        NovaPoshtaCityRepository $novaPoshtaCityRepository,
        NovaPoshtaOfficeRepository $novaPoshtaOfficeRepository
    ) {
        $this->settingRepository = $settingRepository;
        $this->novaPoshtaCityRepository = $novaPoshtaCityRepository;
        $this->novaPoshtaOfficeRepository = $novaPoshtaOfficeRepository;
        $this->key = $this->settingRepository->findOneBy(['slug' => 'nova_poshta_api_key'])->getValue();
        $this->client = new Client(['base_uri' => 'https://api.novaposhta.ua']);
    }

    public function execute(): void
    {
        /*foreach ($this->syncCities() as $item) {
            $this->saveCity($item);
        }*/

        foreach ($this->syncOffices() as $item) {
            $this->saveOffice($item);
        }
    }

    private function syncCities(): \Generator
    {
        $page = 1;
        $limit = 20;
        $count = 20;

        while ($count == $limit) {
            $response = $this->client->request('POST', '/v2.0/json/', [
                'json' => [
                    'apiKey' => $this->key,
                    'modelName' => 'Address',
                    'calledMethod' => 'getCities',
                    'methodProperties' => [
                        'Page' => $page,
                        'Warehouse' => '1',
                        'Limit' => $limit
                    ]
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ]
            ]);

            $data = json_decode($response->getBody()->__toString(), true);

            foreach ($data['data'] as $item) {
                yield $item;
            }

            $count = count($data['data']);
            $page++;
        }
    }

    private function syncOffices(): \Generator
    {
        $page = 200;
        $limit = 20;
        $count = 20;

        while ($count == $limit) {
            $response = $this->client->request('POST', '/v2.0/json/', [
                'json' => [
                    'apiKey' => $this->key,
                    'modelName' => 'Address',
                    'calledMethod' => 'getWarehouses',
                    'methodProperties' => [
                        'Page' => $page,
                        'Limit' => $limit
                    ]
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ]
            ]);

            $data = json_decode($response->getBody()->__toString(), true);

            foreach ($data['data'] as $item) {
                if (strpos($item['Description'], 'Поштомат') === false) {
                    yield $item;
                }
            }

            $count = count($data['data']);
            $page++;
        }
    }

    private function saveCity(array $item): void
    {
        if ($city = $this->novaPoshtaCityRepository->findOneBy(['ref' => $item['Ref']])) {
            $city->setTitle($item['Description']);
            $this->novaPoshtaCityRepository->update($city);
        } else {
            $city = new NovaPoshtaCity();
            $city->setRef($item['Ref']);
            $city->setTitle($item['Description']);
            $this->novaPoshtaCityRepository->create($city);
        }
    }

    private function saveOffice(array $item): void
    {
        $city = $this->novaPoshtaCityRepository->findOneBy(['ref' => $item['CityRef']]);

        if ($city) {
            if ($office = $this->novaPoshtaOfficeRepository->findOneBy(['ref' => $item['Ref']])) {
                $office->setTitle($item['Description']);
                $this->novaPoshtaOfficeRepository->update($office);
            } else {
                $office = new NovaPoshtaOffice();
                $office->setRef($item['Ref']);
                $office->setTitle($item['Description']);
                $office->setCity($city);
                $this->novaPoshtaOfficeRepository->create($office);
            }
        }
    }
}
