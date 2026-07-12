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

    #[ORM\Column]
    private ?int $audit_page_id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $image_url = null;

    #[ORM\Column(nullable: true)]
    private ?bool $has_alt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $alt_text = null;

    #[ORM\Column(nullable: true)]
    private ?float $file_size_kb = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $image_type = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuditPageId(): ?int
    {
        return $this->audit_page_id;
    }

    public function setAuditPageId(int $audit_page_id): static
    {
        $this->audit_page_id = $audit_page_id;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->image_url;
    }

    public function setImageUrl(?string $image_url): static
    {
        $this->image_url = $image_url;

        return $this;
    }

    public function hasAlt(): ?bool
    {
        return $this->has_alt;
    }

    public function setHasAlt(?bool $has_alt): static
    {
        $this->has_alt = $has_alt;

        return $this;
    }

    public function getAltText(): ?string
    {
        return $this->alt_text;
    }

    public function setAltText(?string $alt_text): static
    {
        $this->alt_text = $alt_text;

        return $this;
    }

    public function getFileSizeKb(): ?float
    {
        return $this->file_size_kb;
    }

    public function setFileSizeKb(?float $file_size_kb): static
    {
        $this->file_size_kb = $file_size_kb;

        return $this;
    }

    public function getImageType(): ?string
    {
        return $this->image_type;
    }

    public function setImageType(?string $image_type): static
    {
        $this->image_type = $image_type;

        return $this;
    }
}
