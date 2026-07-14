<?php

namespace App\Repository;

use App\Entity\OrderFulfillmentLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderFulfillmentLink>
 */
class OrderFulfillmentLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderFulfillmentLink::class);
    }

    /**
     * @return OrderFulfillmentLink[]
     */
    public function findByVendorOrderId(int $vendorOrderId): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.vendorOrder = :vendorOrderId')
            ->setParameter('vendorOrderId', $vendorOrderId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return OrderFulfillmentLink[]
     */
    public function findAllLinks(): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.vendorOrder', 'vendorOrder')->addSelect('vendorOrder')
            ->leftJoin('l.order', 'o')->addSelect('o')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, string> map key => linkGroup color hex
     */
    public function buildLinkColorMap(): array
    {
        $links = $this->findAllLinks();
        $map = [];
        $palette = ['#0d9488', '#2563eb', '#d97706', '#7c3aed', '#db2777', '#059669', '#dc2626', '#0891b2'];
        $paletteIndex = 0;
        $groupColors = [];

        foreach ($links as $link) {
            $group = $link->getLinkGroup();
            if (! isset($groupColors[$group])) {
                $groupColors[$group] = $palette[$paletteIndex % count($palette)];
                ++$paletteIndex;
            }

            $color = $groupColors[$group];
            $map['vendor:' . $link->getVendorOrder()->getId()] = $color;

            if ($link->getOrder() !== null) {
                $map['order:' . $link->getOrder()->getId()] = $color;
            }

            if ($link->getRozetkaOrderId() !== null) {
                $map['rozetka:' . $link->getRozetkaOrderId()] = $color;
            }
        }

        return $map;
    }

    /**
     * @return array<string, string[]> card key => all peer keys in the same link group
     */
    public function buildLinkPeerMap(): array
    {
        $groupMembers = [];

        foreach ($this->findAllLinks() as $link) {
            $group = $link->getLinkGroup();
            $groupMembers[$group][] = 'vendor:' . $link->getVendorOrder()->getId();

            if ($link->getOrder() !== null) {
                $groupMembers[$group][] = 'order:' . $link->getOrder()->getId();
            }

            if ($link->getRozetkaOrderId() !== null) {
                $groupMembers[$group][] = 'rozetka:' . $link->getRozetkaOrderId();
            }
        }

        $peerMap = [];
        foreach ($groupMembers as $members) {
            $members = array_values(array_unique($members));
            if (count($members) < 2) {
                continue;
            }

            foreach ($members as $key) {
                $peerMap[$key] = $members;
            }
        }

        return $peerMap;
    }
}
