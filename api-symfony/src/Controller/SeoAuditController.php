<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Predis\Client as RedisClient; // <--- هنا درنا Alias (اسم مستعار)

use ApiPlatform\Symfony\Bundle\Test\Client as ApiClient;
class SeoAuditController extends AbstractController
{
    #[Route('/api/audit/check', name: 'check_redis_data', methods: ['GET'])]
    public function checkRedisData(Request $request): JsonResponse
    {
        // دابا كنعيطو بالاسم الجديد RedisClient
        $client = new RedisClient(['host' => 'redis', 'port' => 6379]);
        
        $url = $request->query->get('url');
        if (!$url) {
            return $this->json(['message' => 'URL parameter is missing'], 400);
        }

        $data = $client->get("audit:" . $url);
        
        if (!$data) {
            return $this->json(['message' => 'Audit not found or still in progress'], 404);
        }
        
        return $this->json(json_decode($data, true));
    }
}