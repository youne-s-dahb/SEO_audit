<?php

namespace App\Controller;

use App\Entity\Audit;
use App\Entity\AuditGoogleMap;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Attribute\Route;

final class GoogleMapsAuditController extends AbstractController
{
    #[Route('/api/audits/{id}/google-maps', name: 'audit_google_maps', methods: ['POST'])]
    public function __invoke(
        int $id,
        EntityManagerInterface $em
    ): JsonResponse {

        // Chercher l'audit
        $audit = $em->getRepository(Audit::class)->find($id);

        if (!$audit) {
            return $this->json([
                'message' => 'Audit not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // URL du site
        $url = $audit->getSite()->getUrl();

        // Appel du service Python
        $client = HttpClient::create();

        $response = $client->request(
            'GET',
            'http://analyzer:8000/maps/presence',
            [
                'query' => [
                    'url' => $url
                ]
            ]
        );

        $data = $response->toArray();

        // Vérifier s'il existe déjà un audit Google Maps
        $googleMap = $em->getRepository(AuditGoogleMap::class)
            ->findOneBy(['audit' => $audit]);

        if (!$googleMap) {
            $googleMap = new AuditGoogleMap();
            $googleMap->setAudit($audit);
        }

        // Remplir les données
        $googleMap->setIsPresent($data['is_present']);

        $googleMap->setBusinessName($data['business_name'] ?? null);
        $googleMap->setTitle($data['title'] ?? null);
        $googleMap->setAddress($data['address'] ?? null);

        $googleMap->setRating($data['rating'] ?? null);
        $googleMap->setReviewsCount($data['reviews_count'] ?? null);
        $googleMap->setPlaceId($data['place_id'] ?? null);

        // Sauvegarde
        $em->persist($googleMap);
        $em->flush();

        return $this->json([
            'message' => 'Google Maps audit completed successfully.',
            'google_maps' => $googleMap
        ]);
    }
}