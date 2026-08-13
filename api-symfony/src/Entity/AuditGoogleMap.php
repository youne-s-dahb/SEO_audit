<?php

namespace App\Entity;

use App\Repository\AuditGoogleMapRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditGoogleMapRepository::class)]
#[ORM\Table(name: 'audit_google_maps')]
#[ApiResource]
class AuditGoogleMap
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $isPresent = null;

    #[ORM\Column(nullable: true)]
    private ?float $rating = null;

    #[ORM\Column(nullable: true)]
    private ?int $reviewsCount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $placeId = null;

    #[ORM\OneToOne(
        inversedBy: 'googleMap',
        cascade: ['persist', 'remove']
    )]
    #[ORM\JoinColumn(nullable: false)]
    private ?Audit $audit = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $businessName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;


    // =====================================================
    // ID
    // =====================================================

    public function getId(): ?int
    {
        return $this->id;
    }


    // =====================================================
    // IS PRESENT
    // =====================================================

    public function isPresent(): ?bool
    {
        return $this->isPresent;
    }

    public function setIsPresent(bool $isPresent): static
    {
        $this->isPresent = $isPresent;

        return $this;
    }


    // =====================================================
    // RATING
    // =====================================================

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(?float $rating): static
    {
        $this->rating = $rating;

        return $this;
    }


    // =====================================================
    // REVIEWS
    // =====================================================

    public function getReviewsCount(): ?int
    {
        return $this->reviewsCount;
    }

    public function setReviewsCount(?int $reviewsCount): static
    {
        $this->reviewsCount = $reviewsCount;

        return $this;
    }


    // =====================================================
    // PLACE ID
    // =====================================================

    public function getPlaceId(): ?string
    {
        return $this->placeId;
    }

    public function setPlaceId(?string $placeId): static
    {
        $this->placeId = $placeId;

        return $this;
    }


    // =====================================================
    // AUDIT
    // =====================================================

    public function getAudit(): ?Audit
    {
        return $this->audit;
    }

    public function setAudit(Audit $audit): static
    {
        $this->audit = $audit;

        return $this;
    }


    // =====================================================
    // BUSINESS NAME
    // =====================================================

    public function getBusinessName(): ?string
    {
        return $this->businessName;
    }

    public function setBusinessName(?string $businessName): static
    {
        $this->businessName = $businessName;

        return $this;
    }


    // =====================================================
    // TITLE
    // =====================================================

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }


    // =====================================================
    // ADDRESS
    // =====================================================

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }
}