<?php

namespace App\Entity;

use App\Repository\AlertRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlertRepository::class)]
#[ORM\Table(name: 'alerts')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class Alert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $alertType = null;

    #[ORM\Column(nullable: true)]
    private ?int $previousPosition = null;

    #[ORM\Column(nullable: true)]
    private ?int $newPosition = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isRead = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAlertType(): ?string
    {
        return $this->alertType;
    }

    public function setAlertType(?string $alertType): static
    {
        $this->alertType = $alertType;

        return $this;
    }

    public function getPreviousPosition(): ?int
    {
        return $this->previousPosition;
    }

    public function setPreviousPosition(?int $previousPosition): static
    {
        $this->previousPosition = $previousPosition;

        return $this;
    }

    public function getNewPosition(): ?int
    {
        return $this->newPosition;
    }

    public function setNewPosition(?int $newPosition): static
    {
        $this->newPosition = $newPosition;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function isRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(?bool $isRead): static
    {
        $this->isRead = $isRead;

        return $this;
    }
}
