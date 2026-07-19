<?php

namespace App\Entity;

use App\Repository\AuditRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\AuditResultProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\State\AuditProcessor;

#[ORM\Entity(repositoryClass: AuditRepository::class)]
#[ORM\Table(name: 'audits')] // Smiya dial l-table f DB dyalk   

#[ApiResource]
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

    #[ORM\OneToOne(mappedBy: 'audit', cascade: ['persist', 'remove'])]
    private ?AuditGoogleMap $googleMap = null;


    /**
     * @var Collection<int, AuditReport>
     */
    #[ORM\OneToMany(targetEntity: AuditReport::class, mappedBy: 'audit')]
    private Collection $reports;

    /**
     * @var Collection<int, KeywordRanking>
     */
    #[ORM\OneToMany(targetEntity: KeywordRanking::class, mappedBy: 'audit')]
    private Collection $keywordRankings;

    /**
     * @var Collection<int, CompetitorComparison>
     */
    #[ORM\OneToMany(targetEntity: CompetitorComparison::class, mappedBy: 'audit')]
    private Collection $competitorComparisons;

    /**
     * @var Collection<int, Recommendation>
     */
    #[ORM\OneToMany(targetEntity: Recommendation::class, mappedBy: 'audit')]
    private Collection $recommendations;

    #[ORM\Column(nullable: true)]
    private ?int $accessibilityScore = null;

    #[ORM\Column(nullable: true)]
    private ?int $bestPracticesScore = null;

    #[ORM\Column(nullable: true)]
    private ?int $seoScore = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metrics = null;

    public function __construct()
    {
        $this->pages = new ArrayCollection();
        $this->criteriaScore = new ArrayCollection();
        $this->reports = new ArrayCollection();
        $this->keywordRankings = new ArrayCollection();
        $this->competitorComparisons = new ArrayCollection();
        $this->recommendations = new ArrayCollection();
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

    public function getGoogleMap(): ?AuditGoogleMap
    {
        return $this->googleMap;
    }

    public function setGoogleMap(AuditGoogleMap $googleMap): static
    {
        // set the owning side of the relation if necessary
        if ($googleMap->getAudit() !== $this) {
            $googleMap->setAudit($this);
        }

        $this->googleMap = $googleMap;

        return $this;
    }

  
    /**
     * @return Collection<int, AuditReport>
     */
    public function getReports(): Collection
    {
        return $this->reports;
    }

    public function addReport(AuditReport $report): static
    {
        if (!$this->reports->contains($report)) {
            $this->reports->add($report);
            $report->setAudit($this);
        }

        return $this;
    }

    public function removeReport(AuditReport $report): static
    {
        if ($this->reports->removeElement($report)) {
            // set the owning side to null (unless already changed)
            if ($report->getAudit() === $this) {
                $report->setAudit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, KeywordRanking>
     */
    public function getKeywordRankings(): Collection
    {
        return $this->keywordRankings;
    }

    public function addKeywordRanking(KeywordRanking $keywordRanking): static
    {
        if (!$this->keywordRankings->contains($keywordRanking)) {
            $this->keywordRankings->add($keywordRanking);
            $keywordRanking->setAudit($this);
        }

        return $this;
    }

    public function removeKeywordRanking(KeywordRanking $keywordRanking): static
    {
        if ($this->keywordRankings->removeElement($keywordRanking)) {
            // set the owning side to null (unless already changed)
            if ($keywordRanking->getAudit() === $this) {
                $keywordRanking->setAudit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CompetitorComparison>
     */
    public function getCompetitorComparisons(): Collection
    {
        return $this->competitorComparisons;
    }

    public function addCompetitorComparison(CompetitorComparison $competitorComparison): static
    {
        if (!$this->competitorComparisons->contains($competitorComparison)) {
            $this->competitorComparisons->add($competitorComparison);
            $competitorComparison->setAudit($this);
        }

        return $this;
    }

    public function removeCompetitorComparison(CompetitorComparison $competitorComparison): static
    {
        if ($this->competitorComparisons->removeElement($competitorComparison)) {
            // set the owning side to null (unless already changed)
            if ($competitorComparison->getAudit() === $this) {
                $competitorComparison->setAudit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Recommendation>
     */
    public function getRecommendations(): Collection
    {
        return $this->recommendations;
    }

    public function addRecommendation(Recommendation $recommendation): static
    {
        if (!$this->recommendations->contains($recommendation)) {
            $this->recommendations->add($recommendation);
            $recommendation->setAudit($this);
        }

        return $this;
    }

    public function removeRecommendation(Recommendation $recommendation): static
    {
        if ($this->recommendations->removeElement($recommendation)) {
            // set the owning side to null (unless already changed)
            if ($recommendation->getAudit() === $this) {
                $recommendation->setAudit(null);
            }
        }

        return $this;
    }

    public function getAccessibilityScore(): ?int
    {
        return $this->accessibilityScore;
    }

    public function setAccessibilityScore(?int $accessibilityScore): static
    {
        $this->accessibilityScore = $accessibilityScore;
        return $this;
    }

    public function getBestPracticesScore(): ?int
    {
        return $this->bestPracticesScore;
    }

    public function setBestPracticesScore(?int $bestPracticesScore): static
    {
        $this->bestPracticesScore = $bestPracticesScore;
        return $this;
    }

    public function getSeoScore(): ?int
    {
        return $this->seoScore;
    }

    public function setSeoScore(?int $seoScore): static
    {
        $this->seoScore = $seoScore;
        return $this;
    }

    public function getMetrics(): ?array
    {
        return $this->metrics;
    }

    public function setMetrics(?array $metrics): static
    {
        $this->metrics = $metrics;
        return $this;
    }
}
