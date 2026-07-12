<?php

namespace App\Entity;

use App\Repository\AuditGoogleMapRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditGoogleMapRepository::class)]
#[ORM\Table(name: 'audit_google_maps')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class AuditGoogleMap
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $audit_id = null;

    #[ORM\Column]
    private ?bool $is_present = null;

    #[ORM\Column(nullable: true)]
    private ?float $rating = null;

    #[ORM\Column(nullable: true)]
    private ?int $reviews_count = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $place_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuditId(): ?int
    {
        return $this->audit_id;
    }

    public function setAuditId(int $audit_id): static
    {
        $this->audit_id = $audit_id;

        return $this;
    }

    public function isPresent(): ?bool
    {
        return $this->is_present;
    }

    public function setIsPresent(bool $is_present): static
    {
        $this->is_present = $is_present;

        return $this;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(?float $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getReviewsCount(): ?int
    {
        return $this->reviews_count;
    }

    public function setReviewsCount(?int $reviews_count): static
    {
        $this->reviews_count = $reviews_count;

        return $this;
    }

    public function getPlaceId(): ?string
    {
        return $this->place_id;
    }

    public function setPlaceId(?string $place_id): static
    {
        $this->place_id = $place_id;

        return $this;
    }
}
