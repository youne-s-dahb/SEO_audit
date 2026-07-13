<?php

namespace App\Entity;

use App\Repository\AuditPageImageRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditPageImageRepository::class)]
#[ORM\Table(name: 'audit_page_images')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class AuditPageImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(nullable: true)]
    private ?bool $hasAlt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $altText = null;

    #[ORM\Column(nullable: true)]
    private ?float $fileSizeKb = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $imageType = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function hasAlt(): ?bool
    {
        return $this->hasAlt;
    }

    public function setHasAlt(?bool $hasAlt): static
    {
        $this->hasAlt = $hasAlt;

        return $this;
    }

    public function getAltText(): ?string
    {
        return $this->altText;
    }

    public function setAltText(?string $altText): static
    {
        $this->altText = $altText;

        return $this;
    }

    public function getFileSizeKb(): ?float
    {
        return $this->fileSizeKb;
    }

    public function setFileSizeKb(?float $fileSizeKb): static
    {
        $this->fileSizeKb = $fileSizeKb;

        return $this;
    }

    public function getImageType(): ?string
    {
        return $this->imageType;
    }

    public function setImageType(?string $imageType): static
    {
        $this->imageType = $imageType;

        return $this;
    }
}
