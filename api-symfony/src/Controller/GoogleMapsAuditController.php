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
        $audit = $em->getRepository(Audit::class)->find($id);

        if (!$audit) {
            return $this->json([
                'message' => 'Audit not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $site = $audit->getSite();
        $siteUrl = $site?->getUrl();

        if (!$site || !$siteUrl) {
            return $this->json([
                'message' => 'Audit site URL not found'
            ], Response::HTTP_BAD_REQUEST);
        }

        $client = HttpClient::create([
            'timeout' => 20,
            'max_duration' => 30,
        ]);

        try {
            $response = $client->request(
                'GET',
                'http://analyzer:8000/maps/presence',
                ['query' => ['url' => $siteUrl]]
            );

            if ($response->getStatusCode() >= 400) {
                throw new \RuntimeException('Google Maps analyzer returned ' . $response->getStatusCode());
            }

            $payload = $response->toArray();
            if (!is_array($payload)) {
                $payload = [];
            }
        } catch (\Throwable $e) {
            return $this->json([
                'message' => 'Google Maps audit unavailable.',
                'status' => 'error',
                'isPresent' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        }

        $googleMap = $em->getRepository(AuditGoogleMap::class)
            ->findOneBy(['audit' => $audit]);

        if (!$googleMap) {
            $googleMap = new AuditGoogleMap();
            $googleMap->setAudit($audit);
        }

        $isPresent = (bool) ($payload['is_present'] ?? $payload['isPresent'] ?? false);
        $businessName = $payload['business_name'] ?? $payload['businessName'] ?? null;
        $title = $payload['title'] ?? null;
        $address = $payload['address'] ?? null;
        $rating = $payload['rating'] ?? null;
        $reviewsCount = $payload['reviews_count'] ?? $payload['reviewsCount'] ?? null;
        $placeId = $payload['place_id'] ?? $payload['placeId'] ?? null;

        $googleMap->setIsPresent($isPresent);
        $googleMap->setBusinessName(is_scalar($businessName) ? (string) $businessName : null);
        $googleMap->setTitle(is_scalar($title) ? (string) $title : null);
        $googleMap->setAddress(is_scalar($address) ? (string) $address : null);
        $googleMap->setRating(is_numeric($rating) ? (float) $rating : null);
        $googleMap->setReviewsCount(is_numeric($reviewsCount) ? (int) $reviewsCount : null);
        $googleMap->setPlaceId(is_scalar($placeId) ? (string) $placeId : null);

        $em->persist($googleMap);
        $em->flush();

        $responseData = [
            'id' => $googleMap->getId(),
            'auditId' => $audit->getId(),
            'audit_id' => $audit->getId(),
            'site' => $siteUrl,
            'url' => $siteUrl,
            'status' => $isPresent ? 'present' : 'not_found',
            'isPresent' => $googleMap->isPresent(),
            'is_present' => $googleMap->isPresent(),
            'businessName' => $googleMap->getBusinessName(),
            'business_name' => $googleMap->getBusinessName(),
            'title' => $googleMap->getTitle(),
            'address' => $googleMap->getAddress(),
            'rating' => $googleMap->getRating(),
            'reviewsCount' => $googleMap->getReviewsCount(),
            'reviews_count' => $googleMap->getReviewsCount(),
            'placeId' => $googleMap->getPlaceId(),
            'place_id' => $googleMap->getPlaceId(),
        ];

        return $this->json($responseData);
    }
}