<?php

namespace App\Entity;

use App\Repository\AuditKeywordDensityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditKeywordDensityRepository::class)]
class AuditKeywordDensity
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
