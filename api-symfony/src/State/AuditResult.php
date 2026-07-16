<?php
namespace App\State;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\AuditResultProcessor;
use Doctrine\ORM\Mapping as ORM;


class AuditResult {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'json')]
    private array $resultData = [];

    public function getResultData(): array { return $this->resultData; }
    public function setResultData(array $data): self { 
        $this->resultData = $data; 
        return $this; 
    }
}