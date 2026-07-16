<?php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;

class AuditResultProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // $data هنا هو الـ Object د الـ AuditResult اللي API Platform عمرات ليه الـ resultData ديالو
        
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data; // كترجع الـ data باش الـ API ترد على Python بـ 201 Created
    }
}