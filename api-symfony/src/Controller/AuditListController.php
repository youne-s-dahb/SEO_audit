<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AuditListController extends AbstractController
{
    #[Route('/api/audits/mine', name: 'audit_list', methods: ['GET'])]
    public function __invoke(
        EntityManagerInterface $em,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['message' => 'Machi connecté.'], 401);
        }

        $audits = $em->getRepository(Audit::class)->findBy(
            ['requestedBy' => $user],
            ['createdAt' => 'DESC']
        );

        $data = array_map(function (Audit $audit) {
            return [
                'id' => $audit->getId(),
                'url' => $audit->getSite()?->getUrl(),
                'status' => $audit->getStatus(),
                'score' => $audit->getGlobalScore(),
                'score_color' => $audit->getScoreColor(),
                'created_at' => $audit->getCreatedAt()?->format(DATE_ATOM),
            ];
        }, $audits);

        return $this->json($data);
    }
}