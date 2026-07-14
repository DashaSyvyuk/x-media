<?php

namespace App\Command;

use App\Service\GenerateEkatalogXmlService;
use App\Service\GenerateHotlineXmlService;
use App\Service\GeneratePromXmlService;
use App\Service\GenerateRozetkaXmlService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:generate-feed',
    description: 'Generate marketplace XML feed (hotline, prom, ekatalog, rozetka-a, rozetka-p)',
)]
class GenerateFeedCommand extends Command
{
    public function __construct(
        private readonly GenerateHotlineXmlService $generateHotlineXmlService,
        private readonly GeneratePromXmlService $generatePromXmlService,
        private readonly GenerateEkatalogXmlService $generateEkatalogXmlService,
        private readonly GenerateRozetkaXmlService $generateRozetkaXmlService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('feed', InputArgument::REQUIRED, 'hotline|prom|ekatalog|rozetka-a|rozetka-p');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $feed = (string) $input->getArgument('feed');

        $url = match ($feed) {
            'hotline'   => $this->generateHotlineXmlService->execute(),
            'prom'      => $this->generatePromXmlService->execute(),
            'ekatalog'  => $this->generateEkatalogXmlService->execute(),
            'rozetka-a' => $this->generateRozetkaXmlService->execute('active_for_a'),
            'rozetka-p' => $this->generateRozetkaXmlService->execute('active_for_p'),
            default     => null,
        };

        if ($url === null) {
            $output->writeln('<error>Feed generation failed.</error>');

            return Command::FAILURE;
        }

        $output->writeln('<info>' . $url . '</info>');

        return Command::SUCCESS;
    }
}
