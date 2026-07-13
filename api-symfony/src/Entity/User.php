<?php

namespace App\Entity;

use App\Repository\UserRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')] //smya f database dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $passwordHash = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $fullName = null;

    #[ORM\Column(length: 30)]
    private ?string $role = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, ApiQuota>
     */
    #[ORM\OneToMany(targetEntity: ApiQuota::class, mappedBy: 'account')]
    private Collection $apiQuotas;

    /**
     * @var Collection<int, Site>
     */
    #[ORM\OneToMany(targetEntity: Site::class, mappedBy: 'account')]
    private Collection $sites;

    /**
     * @var Collection<int, Audit>
     */
    #[ORM\OneToMany(targetEntity: Audit::class, mappedBy: 'requestedBy')]
    private Collection $requestedAudits;

    /**
     * @var Collection<int, Alert>
     */
    #[ORM\OneToMany(targetEntity: Alert::class, mappedBy: 'account')]
    private Collection $alerts;

    public function __construct()
    {
        $this->apiQuotas = new ArrayCollection();
        $this->sites = new ArrayCollection();
        $this->requestedAudits = new ArrayCollection();
        $this->alerts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): static
    {
        $this->passwordHash = $passwordHash;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, ApiQuota>
     */
    public function getApiQuotas(): Collection
    {
        return $this->apiQuotas;
    }

    public function addApiQuota(ApiQuota $apiQuota): static
    {
        if (!$this->apiQuotas->contains($apiQuota)) {
            $this->apiQuotas->add($apiQuota);
            $apiQuota->setAccount($this);
        }

        return $this;
    }

    public function removeApiQuota(ApiQuota $apiQuota): static
    {
        if ($this->apiQuotas->removeElement($apiQuota)) {
            // set the owning side to null (unless already changed)
            if ($apiQuota->getAccount() === $this) {
                $apiQuota->setAccount(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Site>
     */
    public function getSites(): Collection
    {
        return $this->sites;
    }

    public function addSite(Site $site): static
    {
        if (!$this->sites->contains($site)) {
            $this->sites->add($site);
            $site->setAccount($this);
        }

        return $this;
    }

    public function removeSite(Site $site): static
    {
        if ($this->sites->removeElement($site)) {
            // set the owning side to null (unless already changed)
            if ($site->getAccount() === $this) {
                $site->setAccount(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Audit>
     */
    public function getRequestedAudits(): Collection
    {
        return $this->requestedAudits;
    }

    public function addRequestedAudit(Audit $requestedAudit): static
    {
        if (!$this->requestedAudits->contains($requestedAudit)) {
            $this->requestedAudits->add($requestedAudit);
            $requestedAudit->setRequestedBy($this);
        }

        return $this;
    }

    public function removeRequestedAudit(Audit $requestedAudit): static
    {
        if ($this->requestedAudits->removeElement($requestedAudit)) {
            // set the owning side to null (unless already changed)
            if ($requestedAudit->getRequestedBy() === $this) {
                $requestedAudit->setRequestedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Alert>
     */
    public function getAlerts(): Collection
    {
        return $this->alerts;
    }

    public function addAlert(Alert $alert): static
    {
        if (!$this->alerts->contains($alert)) {
            $this->alerts->add($alert);
            $alert->setAccount($this);
        }

        return $this;
    }

    public function removeAlert(Alert $alert): static
    {
        if ($this->alerts->removeElement($alert)) {
            // set the owning side to null (unless already changed)
            if ($alert->getAccount() === $this) {
                $alert->setAccount(null);
            }
        }

        return $this;
    }
}
