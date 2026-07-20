<?php

namespace App\Command;

use App\Service\Admin2\AdminWebPushNotifier;
use App\Service\Admin2\RozetkaOrderPresenter;
use App\Service\Admin2\RozetkaSellerApiClient;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'admin2:notify-rozetka-new-orders',
    description: 'Poll Rozetka for new orders and send admin web-push notifications',
)]
class NotifyRozetkaNewOrdersCommand extends Command
{
    private const CACHE_KEY = 'admin2_rozetka_push_seen_ids';
    private const CACHE_TTL = 86400 * 14;

    public function __construct(
        private readonly RozetkaSellerApiClient $rozetkaApiClient,
        private readonly RozetkaOrderPresenter $rozetkaOrderPresenter,
        private readonly AdminWebPushNotifier $notifier,
        private readonly CacheItemPoolInterface $cache,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'seed',
            null,
            InputOption::VALUE_NONE,
            'Remember current new orders without sending notifications',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (! $this->rozetkaApiClient->isConfigured()) {
            $io->warning('Rozetka API is not configured.');

            return Command::SUCCESS;
        }

        if (! $this->notifier->isConfigured()) {
            $io->warning('VAPID keys are not configured; skipping push.');

            return Command::SUCCESS;
        }

        $orders = $this->rozetkaApiClient->fetchActiveOrders(2, 50, [4]);
        $currentIds = [];
        $byId = [];

        foreach ($orders as $order) {
            $id = (int) ($order['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $currentIds[] = $id;
            $byId[$id] = $order;
        }

        $cacheItem = $this->cache->getItem(self::CACHE_KEY);
        $seen = $cacheItem->isHit() && is_array($cacheItem->get())
            ? array_map('intval', $cacheItem->get())
            : null;

        $seedOnly = (bool) $input->getOption('seed');
        $isFirstRun = $seen === null;

        if ($isFirstRun || $seedOnly) {
            $this->storeSeen($cacheItem, $currentIds);
            $io->success(sprintf(
                'Seeded %d Rozetka new-order id(s)%s.',
                count($currentIds),
                $seedOnly ? '' : ' (first run, no notifications)',
            ));

            return Command::SUCCESS;
        }

        $seenMap = array_fill_keys($seen, true);
        $newIds = array_values(array_filter(
            $currentIds,
            static fn (int $id): bool => ! isset($seenMap[$id]),
        ));

        $sent = 0;
        foreach ($newIds as $id) {
            $presented = $this->rozetkaOrderPresenter->presentListItem($byId[$id]);
            $this->notifier->notifyNewRozetkaOrder($presented);
            ++$sent;
        }

        $merged = array_values(array_unique(array_merge($seen, $currentIds)));
        rsort($merged);
        $merged = array_slice($merged, 0, 500);
        $this->storeSeen($cacheItem, $merged);

        $io->success(sprintf('Checked %d order(s); sent %d notification(s).', count($currentIds), $sent));

        return Command::SUCCESS;
    }

    /**
     * @param list<int> $ids
     */
    private function storeSeen(\Psr\Cache\CacheItemInterface $cacheItem, array $ids): void
    {
        $cacheItem->set(array_values(array_unique(array_map('intval', $ids))));
        $cacheItem->expiresAfter(self::CACHE_TTL);
        $this->cache->save($cacheItem);
    }
}
