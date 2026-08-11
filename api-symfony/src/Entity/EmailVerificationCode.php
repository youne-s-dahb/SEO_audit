<?php

namespace App\Entity;

use App\Repository\EmailVerificationCodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailVerificationCodeRepository::class)]
#[ORM\Table(name: 'email_verification_codes')]
class EmailVerificationCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 6)]
    private ?string $code = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column]
    private bool $verified = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getCode(): ?string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }

    public function getExpiresAt(): ?\DateTimeImmutable { return $this->expiresAt; }
    public function setExpiresAt(\DateTimeImmutable $expiresAt): static { $this->expiresAt = $expiresAt; return $this; }

    public function isVerified(): bool { return $this->verified; }
    public function setVerified(bool $verified): static { $this->verified = $verified; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
}