<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait DateStorageTrait
 */
trait DateStorageTrait
{
    /**
     * @ORM\PrePersist()
     */
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
    }

    /**
     * @ORM\PreUpdate()
     */
    public function onPreUpdate(): void
    {
        //using Doctrine DateTime here
        $this->updatedAt = new \DateTime('now');

        if (isset($this->title)) {
            $this->metaKeyword = $this->title;
            $this->metaDescription = $this->title;
        }
    }
}
