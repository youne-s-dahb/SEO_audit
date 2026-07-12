<?php

namespace App\Entity;

use App\Repository\AuditPageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;

#[ORM\Entity(repositoryClass: AuditPageRepository::class)]
#[ORM\Table(name: 'audit_pages')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna ğŸ”¥
class AuditPage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $audit_id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $url = null;

    #[ORM\Column]
    private ?int $status_code = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(nullable: true)]
    private ?int $title_length = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $meta_description = null;

    #[ORM\Column(nullable: true)]
    private ?int $meta_length = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $canonical_url = null;

    #[ORM\Column(length: 50)]
    private ?string $meta_robots = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $langÃ_attribute = null;

    #[ORM\Column(nullable: true)]
    private ?int $h1_count = null;

    #[ORM\Column(nullable: true)]
    private ?bool $h1_is_unique = null;

    #[ORM\Column(nullable: true)]
    private ?int $word_count = null;

    #[ORM\Column(nullable: true)]
    private ?int $internal_links_count = null;

    #[ORM\Column(nullable: true)]
    private ?int $external_links_count = null;

    #[ORM\Column(nullable: true)]
    private ?int $broken_links_count = null;

    #[ORM\Column(nullable: true)]
    private ?int $image_count = null;

    #[ORM\Column(nullable: true)]
    private ?int $images_without_alt_count = null;

    #[ORM\Column(nullable: true)]
    private ?bool $has_structured_data = null;

    #[ORM\Column(nullable: true)]
    private ?bool $viewport_meta = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_https = null;

    #[ORM\Column(nullable: true)]
    private ?int $response_time_ms = null;

    #[ORM\Column(nullable: true)]
    private ?int $load_time_ms = null;

    #[ORM\Column(nullable: true)]
    private ?int $crawl_depth = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $created_at = null;

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
        return $this->status_code;
    }

    public function setStatusCode(int $status_code): static
    {
        $this->status_code = $status_code;

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
        return $this->title_length;
    }

    public function setTitleLength(?int $title_length): static
    {
        $this->title_length = $title_length;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->meta_description;
    }

    public function setMetaDescription(?string $meta_description): static
    {
        $this->meta_description = $meta_description;

        return $this;
    }

    public function getMetaLength(): ?int
    {
        return $this->meta_length;
    }

    public function setMetaLength(?int $meta_length): static
    {
        $this->meta_length = $meta_length;

        return $this;
    }

    public function getCanonicalUrl(): ?string
    {
        return $this->canonical_url;
    }

    public function setCanonicalUrl(?string $canonical_url): static
    {
        $this->canonical_url = $canonical_url;

        return $this;
    }

    public function getMetaRobots(): ?string
    {
        return $this->meta_robots;
    }

    public function setMetaRobots(string $meta_robots): static
    {
        $this->meta_robots = $meta_robots;

        return $this;
    }

    public function getLangÃAttribute(): ?string
    {
        return $this->langÃ_attribute;
    }

    public function setLangÃAttribute(?string $langÃ_attribute): static
    {
        $this->langÃ_attribute = $langÃ_attribute;

        return $this;
    }

    public function getH1Count(): ?int
    {
        return $this->h1_count;
    }

    public function setH1Count(?int $h1_count): static
    {
        $this->h1_count = $h1_count;

        return $this;
    }

    public function isH1IsUnique(): ?bool
    {
        return $this->h1_is_unique;
    }

    public function setH1IsUnique(?bool $h1_is_unique): static
    {
        $this->h1_is_unique = $h1_is_unique;

        return $this;
    }

    public function getWordCount(): ?int
    {
        return $this->word_count;
    }

    public function setWordCount(?int $word_count): static
    {
        $this->word_count = $word_count;

        return $this;
    }

    public function getInternalLinksCount(): ?int
    {
        return $this->internal_links_count;
    }

    public function setInternalLinksCount(?int $internal_links_count): static
    {
        $this->internal_links_count = $internal_links_count;

        return $this;
    }

    public function getExternalLinksCount(): ?int
    {
        return $this->external_links_count;
    }

    public function setExternalLinksCount(?int $external_links_count): static
    {
        $this->external_links_count = $external_links_count;

        return $this;
    }

    public function getBrokenLinksCount(): ?int
    {
        return $this->broken_links_count;
    }

    public function setBrokenLinksCount(?int $broken_links_count): static
    {
        $this->broken_links_count = $broken_links_count;

        return $this;
    }

    public function getImageCount(): ?int
    {
        return $this->image_count;
    }

    public function setImageCount(?int $image_count): static
    {
        $this->image_count = $image_count;

        return $this;
    }

    public function getImagesWithoutAltCount(): ?int
    {
        return $this->images_without_alt_count;
    }

    public function setImagesWithoutAltCount(?int $images_without_alt_count): static
    {
        $this->images_without_alt_count = $images_without_alt_count;

        return $this;
    }

    public function hasStructuredData(): ?bool
    {
        return $this->has_structured_data;
    }

    public function setHasStructuredData(?bool $has_structured_data): static
    {
        $this->has_structured_data = $has_structured_data;

        return $this;
    }

    public function isViewportMeta(): ?bool
    {
        return $this->viewport_meta;
    }

    public function setViewportMeta(?bool $viewport_meta): static
    {
        $this->viewport_meta = $viewport_meta;

        return $this;
    }

    public function isHttps(): ?bool
    {
        return $this->is_https;
    }

    public function setIsHttps(?bool $is_https): static
    {
        $this->is_https = $is_https;

        return $this;
    }

    public function getResponseTimeMs(): ?int
    {
        return $this->response_time_ms;
    }

    public function setResponseTimeMs(?int $response_time_ms): static
    {
        $this->response_time_ms = $response_time_ms;

        return $this;
    }

    public function getLoadTimeMs(): ?int
    {
        return $this->load_time_ms;
    }

    public function setLoadTimeMs(?int $load_time_ms): static
    {
        $this->load_time_ms = $load_time_ms;

        return $this;
    }

    public function getCrawlDepth(): ?int
    {
        return $this->crawl_depth;
    }

    public function setCrawlDepth(?int $crawl_depth): static
    {
        $this->crawl_depth = $crawl_depth;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
}
