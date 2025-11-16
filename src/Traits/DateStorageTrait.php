<?php

namespace App\Traits;

use App\Entity\CirculationPayment;
use App\Entity\DebtorPayment;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trait DateStorageTrait
 */
trait DateStorageTrait
{
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        //using Doctrine DateTime here
        $this->createdAt = new \DateTime('now');
        //using Doctrine DateTime here
        $this->updatedAt = new \DateTime('now');

        if (isset($this->title)) {
            $this->metaKeyword = $this->title;
            $this->metaDescription = $this->title;
        }

        if ($this instanceof DebtorPayment) {
            $debtor = $this->getDebtor();
            $total = $debtor->getTotal() - $this->getSum();
            $this->description = sprintf(
                '%s %s %s = %s %s',
                number_format($total, 0, '.', ' '),
                $this->getSum() > 0 ? '+' : '-',
                number_format(abs($this->getSum()), 0, '.', ' '),
                number_format((int) $total + $this->getSum(), 0, '.', ' '),
                $debtor->getCurrency()?->getShortTitle()
            );
        }

        if ($this instanceof CirculationPayment) {
            $circulation = $this->getCirculation();
            $total = $circulation->getTotal() - $this->getSum();
            $this->description = sprintf(
                '%s %s %s = %s %s',
                number_format($total, 0, '.', ' '),
                $this->getSum() > 0 ? '+' : '-',
                number_format(abs($this->getSum()), 0, '.', ' '),
                number_format((int) $total + $this->getSum(), 0, '.', ' '),
                $circulation->getCurrency()?->getShortTitle()
            );
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        //using Doctrine DateTime here
        $this->updatedAt = new \DateTime('now');

        if (isset($this->title)) {
            $this->metaKeyword = $this->title;
            $this->metaDescription = $this->title;
        }

        if ($this instanceof DebtorPayment) {
            $debtor = $this->getDebtor();
            $total = $debtor->getTotal() - $this->getSum();
            $this->description = sprintf(
                '%s %s %s = %s %s',
                number_format($total, 0, '.', ' '),
                $this->getSum() > 0 ? '+' : '-',
                number_format(abs($this->getSum()), 0, '.', ' '),
                number_format((int) $total + $this->getSum(), 0, '.', ' '),
                $debtor->getCurrency()?->getShortTitle()
            );
        }

        if ($this instanceof CirculationPayment) {
            $circulation = $this->getCirculation();
            $total = $circulation->getTotal() - $this->getSum();
            $this->description = sprintf(
                '%s %s %s = %s %s',
                number_format($total, 0, '.', ' '),
                $this->getSum() > 0 ? '+' : '-',
                number_format(abs($this->getSum()), 0, '.', ' '),
                number_format((int) $total + $this->getSum(), 0, '.', ' '),
                $circulation->getCurrency()?->getShortTitle()
            );
        }
    }
}
