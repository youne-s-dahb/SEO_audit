<?php

namespace App\Entity;

use App\Repository\SiteRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteRepository::class)]
#[ORM\Table(name: 'sites')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class Site
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}