<?php

namespace App\Entity;

use \DateTime;
use App\Traits\DateStorageTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Table("product_image", indexes={
 *     @Index(columns={"product_id", "position"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\ProductImageRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ProductImage
{
    use DateStorageTrait;

    const SERVER_PATH_TO_IMAGE_FOLDER = '../public/images/products';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @ORM\Column(type="text")
     */
    private string $imageUrl = "";

    private $file;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $position = 0;

    /**
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @ORM\ManyToOne(targetEntity="App\Entity\Product", inversedBy="images")
     */
    private Product $product;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @param Product $product
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return string
     */
    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    /**
     * @param string $imageUrl
     */
    public function setImageUrl(string $imageUrl)
    {
        $this->imageUrl = $imageUrl;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function upload()
    {
        if ($this->getFile() == null) {
            return;
        }

        $safeFilename = md5(date('Y-m-d H:i:s:u')) . rand(200, 999);
        $fileName = $safeFilename . '.' . $this->getFile()->guessExtension();

        $this->getFile()->move(
            self::SERVER_PATH_TO_IMAGE_FOLDER,
            $fileName
        );

        $this->imageUrl = 'images/products/' . $fileName;

        $this->setFile(null);
    }

    /**
     * @ORM\PrePersist()
     */
    public function onPrePersist()
    {
        $this->upload();
        $this->createdAt = new DateTime('now');
        $this->updatedAt = new DateTime('now');
    }

    /**
     * @ORM\PreUpdate()
     */
    public function onPreUpdate()
    {
        $this->upload();
        $this->updatedAt = new DateTime('now');
    }

    public function refreshUpdated(): void
    {
        $this->setUpdatedAt(new DateTime());
    }
}
