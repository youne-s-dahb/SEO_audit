<?php

namespace App\Entity;

use App\Repository\AuditRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditRepository::class)]
#[ORM\Table(name: 'audits')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class Audit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?float $globalScore = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $scoreColor = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isHttps = null;

    #[ORM\Column(nullable: true)]
    private ?bool $hasRobotsTxt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $hasSitemapXml = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isMobileFriendly = null;

    #[ORM\Column(nullable: true)]
    private ?int $pageLoadTimeMs = null;

    #[ORM\Column(nullable: true)]
    private ?int $pagespeedDesktopScore = null;

    #[ORM\Column(nullable: true)]
    private ?int $pagespeedMobileScore = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'audits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $site = null;

    #[ORM\ManyToOne(inversedBy: 'requestedAudits')]
    private ?User $requestedBy = null;


    /**
     * @var Collection<int, AuditPage>
     */
    #[ORM\OneToMany(targetEntity: AuditPage::class, mappedBy: 'audit')]
    private Collection $pages;

    public function __construct()
    {
        $this->pages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getGlobalScore(): ?float
    {
        return $this->globalScore;
    }

    public function setGlobalScore(?float $globalScore): static
    {
        $this->globalScore = $globalScore;

        return $this;
    }

    public function getScoreColor(): ?string
    {
        return $this->scoreColor;
    }

    public function setScoreColor(?string $scoreColor): static
    {
        $this->scoreColor = $scoreColor;

        return $this;
    }

    public function isHttps(): ?bool
    {
        return $this->isHttps;
    }

    public function setIsHttps(?bool $isHttps): static
    {
        $this->isHttps = $isHttps;

        return $this;
    }

    public function hasRobotsTxt(): ?bool
    {
        return $this->hasRobotsTxt;
    }

    public function setHasRobotsTxt(?bool $hasRobotsTxt): static
    {
        $this->hasRobotsTxt = $hasRobotsTxt;

        return $this;
    }

    public function hasSitemapXml(): ?bool
    {
        return $this->hasSitemapXml;
    }

    public function setHasSitemapXml(?bool $hasSitemapXml): static
    {
        $this->hasSitemapXml = $hasSitemapXml;

        return $this;
    }

    public function isMobileFriendly(): ?bool
    {
        return $this->isMobileFriendly;
    }

    public function setIsMobileFriendly(?bool $isMobileFriendly): static
    {
        $this->isMobileFriendly = $isMobileFriendly;

        return $this;
    }

    public function getPageLoadTimeMs(): ?int
    {
        return $this->pageLoadTimeMs;
    }

    public function setPageLoadTimeMs(?int $pageLoadTimeMs): static
    {
        $this->pageLoadTimeMs = $pageLoadTimeMs;

        return $this;
    }

    public function getPagespeedDesktopScore(): ?int
    {
        return $this->pagespeedDesktopScore;
    }

    public function setPagespeedDesktopScore(?int $pagespeedDesktopScore): static
    {
        $this->pagespeedDesktopScore = $pagespeedDesktopScore;

        return $this;
    }

    public function getPagespeedMobileScore(): ?int
    {
        return $this->pagespeedMobileScore;
    }

    public function setPagespeedMobileScore(?int $pagespeedMobileScore): static
    {
        $this->pagespeedMobileScore = $pagespeedMobileScore;

        return $this;
    }


    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;

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

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): static
    {
        $this->site = $site;

        return $this;
    }

    public function getRequestedBy(): ?User
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?User $requestedBy): static
    {
        $this->requestedBy = $requestedBy;

        return $this;
    }


    /**
     * @return Collection<int, AuditPage>
     */
    public function getPages(): Collection
    {
        return $this->pages;
    }

    public function addPage(AuditPage $page): static
    {
        if (!$this->pages->contains($page)) {
            $this->pages->add($page);
            $page->setAudit($this);
        }

        return $this;
    }

    public function removePage(AuditPage $page): static
    {
        if ($this->pages->removeElement($page)) {
            // set the owning side to null (unless already changed)
            if ($page->getAudit() === $this) {
                $page->setAudit(null);
            }
        }

        return $this;
    }
}
