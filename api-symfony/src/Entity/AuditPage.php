<?php

namespace App\Entity;

use App\Repository\AuditPageRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditPageRepository::class)]
#[ORM\Table(name: 'audit_pages')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class AuditPage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $url = null;

    #[ORM\Column(nullable: true)]
    private ?int $statusCode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(nullable: true)]
    private ?int $titleLength = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\Column(nullable: true)]
    private ?int $metaLength = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $canonicalUrl = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $metaRobots = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $langAttribute = null;

    #[ORM\Column(nullable: true)]
    private ?int $h1Count = null;

    #[ORM\Column(nullable: true)]
    private ?bool $h1IsUnique = null;

    #[ORM\Column(nullable: true)]
    private ?int $wordCount = null;

    #[ORM\Column(nullable: true)]
    private ?int $internalLinksCount = null;

    #[ORM\Column(nullable: true)]
    private ?int $externalLinksCount = null;

    #[ORM\Column(nullable: true)]
    private ?int $brokenLinksCount = null;

    #[ORM\Column]
    private ?int $imagesCount = null;

    #[ORM\Column]
    private ?int $imagesWithoutAltCount = null;

    #[ORM\Column]
    private ?bool $hasStructuredData = null;

    #[ORM\Column(nullable: true)]
    private ?bool $viewportMeta = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isHttps = null;

    #[ORM\Column(nullable: true)]
    private ?int $responseTimeMs = null;

    #[ORM\Column(nullable: true)]
    private ?int $loadTimeMs = null;

    #[ORM\Column]
    private ?int $crawlDepth = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'pages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Audit $audit = null;

    /**
     * @var Collection<int, AuditPageHeading>
     */
    #[ORM\OneToMany(targetEntity: AuditPageHeading::class, mappedBy: 'auditPage')]
    private Collection $heading;

    /**
     * @var Collection<int, AuditPageImage>
     */
    #[ORM\OneToMany(targetEntity: AuditPageImage::class, mappedBy: 'auditPage')]
    private Collection $image;

    /**
     * @var Collection<int, AuditKeywordDensity>
     */
    #[ORM\OneToMany(targetEntity: AuditKeywordDensity::class, mappedBy: 'auditPage')]
    private Collection $keywordDensities;

    public function __construct()
    {
        $this->heading = new ArrayCollection();
        $this->image = new ArrayCollection();
        $this->keywordDensities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function setStatusCode(?int $statusCode): static
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitleLength(): ?int
    {
        return $this->titleLength;
    }

    public function setTitleLength(?int $titleLength): static
    {
        $this->titleLength = $titleLength;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getMetaLength(): ?int
    {
        return $this->metaLength;
    }

    public function setMetaLength(?int $metaLength): static
    {
        $this->metaLength = $metaLength;

        return $this;
    }

    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }

    public function setCanonicalUrl(?string $canonicalUrl): static
    {
        $this->canonicalUrl = $canonicalUrl;

        return $this;
    }

    public function getMetaRobots(): ?string
    {
        return $this->metaRobots;
    }

    public function setMetaRobots(?string $metaRobots): static
    {
        $this->metaRobots = $metaRobots;

        return $this;
    }

    public function getLangAttribute(): ?string
    {
        return $this->langAttribute;
    }

    public function setLangAttribute(?string $langAttribute): static
    {
        $this->langAttribute = $langAttribute;

        return $this;
    }

    public function getH1Count(): ?int
    {
        return $this->h1Count;
    }

    public function setH1Count(?int $h1Count): static
    {
        $this->h1Count = $h1Count;

        return $this;
    }

    public function isH1IsUnique(): ?bool
    {
        return $this->h1IsUnique;
    }

    public function setH1IsUnique(?bool $h1IsUnique): static
    {
        $this->h1IsUnique = $h1IsUnique;

        return $this;
    }

    public function getWordCount(): ?int
    {
        return $this->wordCount;
    }

    public function setWordCount(?int $wordCount): static
    {
        $this->wordCount = $wordCount;

        return $this;
    }

    public function getInternalLinksCount(): ?int
    {
        return $this->internalLinksCount;
    }

    public function setInternalLinksCount(?int $internalLinksCount): static
    {
        $this->internalLinksCount = $internalLinksCount;

        return $this;
    }

    public function getExternalLinksCount(): ?int
    {
        return $this->externalLinksCount;
    }

    public function setExternalLinksCount(?int $externalLinksCount): static
    {
        $this->externalLinksCount = $externalLinksCount;

        return $this;
    }

    public function getBrokenLinksCount(): ?int
    {
        return $this->brokenLinksCount;
    }

    public function setBrokenLinksCount(?int $brokenLinksCount): static
    {
        $this->brokenLinksCount = $brokenLinksCount;

        return $this;
    }

    public function getImagesCount(): ?int
    {
        return $this->imagesCount;
    }

    public function setImagesCount(int $imagesCount): static
    {
        $this->imagesCount = $imagesCount;

        return $this;
    }

    public function getImagesWithoutAltCount(): ?int
    {
        return $this->imagesWithoutAltCount;
    }

    public function setImagesWithoutAltCount(int $imagesWithoutAltCount): static
    {
        $this->imagesWithoutAltCount = $imagesWithoutAltCount;

        return $this;
    }

    public function hasStructuredData(): ?bool
    {
        return $this->hasStructuredData;
    }

    public function setHasStructuredData(bool $hasStructuredData): static
    {
        $this->hasStructuredData = $hasStructuredData;

        return $this;
    }

    public function isViewportMeta(): ?bool
    {
        return $this->viewportMeta;
    }

    public function setViewportMeta(?bool $viewportMeta): static
    {
        $this->viewportMeta = $viewportMeta;

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

    public function getResponseTimeMs(): ?int
    {
        return $this->responseTimeMs;
    }

    public function setResponseTimeMs(?int $responseTimeMs): static
    {
        $this->responseTimeMs = $responseTimeMs;

        return $this;
    }

    public function getLoadTimeMs(): ?int
    {
        return $this->loadTimeMs;
    }

    public function setLoadTimeMs(?int $loadTimeMs): static
    {
        $this->loadTimeMs = $loadTimeMs;

        return $this;
    }

    public function getCrawlDepth(): ?int
    {
        return $this->crawlDepth;
    }

    public function setCrawlDepth(int $crawlDepth): static
    {
        $this->crawlDepth = $crawlDepth;

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

    public function getAudit(): ?Audit
    {
        return $this->audit;
    }

    public function setAudit(?Audit $audit): static
    {
        $this->audit = $audit;

        return $this;
    }

    /**
     * @return Collection<int, AuditPageHeading>
     */
    public function getHeading(): Collection
    {
        return $this->heading;
    }

    public function addHeading(AuditPageHeading $heading): static
    {
        if (!$this->heading->contains($heading)) {
            $this->heading->add($heading);
            $heading->setAuditPage($this);
        }

        return $this;
    }

    public function removeHeading(AuditPageHeading $heading): static
    {
        if ($this->heading->removeElement($heading)) {
            // set the owning side to null (unless already changed)
            if ($heading->getAuditPage() === $this) {
                $heading->setAuditPage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AuditPageImage>
     */
    public function getImage(): Collection
    {
        return $this->image;
    }

    public function addImage(AuditPageImage $image): static
    {
        if (!$this->image->contains($image)) {
            $this->image->add($image);
            $image->setAuditPage($this);
        }

        return $this;
    }

    public function removeImage(AuditPageImage $image): static
    {
        if ($this->image->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getAuditPage() === $this) {
                $image->setAuditPage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AuditKeywordDensity>
     */
    public function getKeywordDensities(): Collection
    {
        return $this->keywordDensities;
    }

    public function addKeywordDensity(AuditKeywordDensity $keywordDensity): static
    {
        if (!$this->keywordDensities->contains($keywordDensity)) {
            $this->keywordDensities->add($keywordDensity);
            $keywordDensity->setAuditPage($this);
        }

        return $this;
    }

    public function removeKeywordDensity(AuditKeywordDensity $keywordDensity): static
    {
        if ($this->keywordDensities->removeElement($keywordDensity)) {
            // set the owning side to null (unless already changed)
            if ($keywordDensity->getAuditPage() === $this) {
                $keywordDensity->setAuditPage(null);
            }
        }

        return $this;
    }
}
