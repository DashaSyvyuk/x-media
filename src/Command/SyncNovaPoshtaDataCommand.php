<?php

namespace App\Command;

use App\Service\SyncNovaPoshtaService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncNovaPoshtaDataCommand extends Command
{
    protected static $defaultName = 'app:sync-nova-poshta';
    private SyncNovaPoshtaService $syncNovaPoshtaService;

    public function __construct(
        SyncNovaPoshtaService $syncNovaPoshtaService,
    ) {
        $this->syncNovaPoshtaService = $syncNovaPoshtaService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->syncNovaPoshtaService->execute();

        return 0;
    }
}
