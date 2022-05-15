<?php

namespace App\Command;

use App\Entity\NovaPoshtaCity;
use App\Entity\NovaPoshtaOffice;
use App\Entity\Setting;
use App\Repository\NovaPoshtaCityRepository;
use App\Repository\NovaPoshtaOfficeRepository;
use App\Repository\SettingRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncNovaPoshtaDataCommand extends Command
{
    protected static $defaultName = 'app:sync-nova-poshta';

    private Setting $key;

    private \GuzzleHttp\Client $client;

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
        $this->key = $this->settingRepository->findOneBy(['slug' => 'nova_poshta_api_key']);
        $this->client = new \GuzzleHttp\Client(['base_uri' => 'https://api.novaposhta.ua']);

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->syncCities($output);

        $this->syncOffices($output);

        return 0;
    }

    private function syncCities($output) {
        $output->writeln('Start to sync cities');

        $page = 1;
        $limit = 20;
        $count = 20;

        while ($count == $limit) {
            $response = $this->client->request('POST', '/v2.0/json/', [
                'json' => [
                    'apiKey' => $this->key->getValue(),
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
                if ($city = $this->novaPoshtaCityRepository->findOneBy(['ref' => $item['Ref']])) {
                    $city->setTitle($item['Description']);
                    $this->novaPoshtaCityRepository->update($city);
                } else {
                    $city = new NovaPoshtaCity();
                    $city->setRef($item['Ref']);
                    $city->setTitle($item['Description']);
                    $this->novaPoshtaCityRepository->create($city);
                }

                $output->writeln('Sync city ' . $item['Description']);
            }

            $count = count($data['data']);
            $page++;
        }
    }

    private function syncOffices($output) {
        $page = 1;
        $limit = 20;
        $count = 20;

        while ($count == $limit) {
            $response = $this->client->request('POST', '/v2.0/json/', [
                'json' => [
                    'apiKey' => $this->key->getValue(),
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
                $city = $this->novaPoshtaCityRepository->findOneBy(['ref' => $item['CityRef']]);

                if ($city) {
                    $output->writeln('Sync office:' . $city->getTitle());

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

            $count = count($data['data']);
            $page++;
        }
    }
}