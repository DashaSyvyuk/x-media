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
    public function onPrePersist()
    {
        //using Doctrine DateTime here
        $this->createdAt = new \DateTime('now');
        //using Doctrine DateTime here
        $this->updatedAt = new \DateTime('now');

        if ($this->title) {
            $this->metaKeyword = $this->title;
            $this->metaDescription = $this->title;
        }
    }

    /**
     * @ORM\PreUpdate()
     */
    public function onPreUpdate()
    {
        //using Doctrine DateTime here
        $this->updatedAt = new \DateTime('now');

        if ($this->title) {
            $this->metaKeyword = $this->title;
            $this->metaDescription = $this->title;
        }
    }
}
