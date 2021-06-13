<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Table("filter_parameter", indexes={
 *     @Index(columns={"title"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\FilterParameterRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class FilterParameter
{
    use DateStorageTrait;

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $title = "";

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\FilterParameterValue",
     *     mappedBy="filterParameter", cascade={"all"}, orphanRemoval=true, fetch="EAGER")
     */
    private $values;

    /**
     * @ORM\Column(type="datetime")
     */
    public $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    public $updatedAt;

    public function __construct()
    {
        $this->values = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param FilterParameterValue $value
     */
    public function addValue(FilterParameterValue $value)
    {
        $value->setFilterParameter($this);
        if (!$this->values->contains($value)) {
            $this->values[] = $value;
        }
    }

    /**
     * @param FilterParameterValue $value
     * @return bool
     */
    public function removeValue(FilterParameterValue $value): bool
    {
        return $this->values->removeElement($value);
    }

    public function getValues()
    {
        return $this->values;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function __toString():string
    {
        return '' . $this->title;
    }
}
