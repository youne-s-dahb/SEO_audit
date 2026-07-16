<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

class PythonAnalyzerService
{
    private HttpClientInterface $client;
    private LoggerInterface $logger;

    // URL du conteneur Python dans Docker (injectée via env, plus hardcodée)
    private string $pythonApiUrl;

    public function __construct(
        HttpClientInterface $client,
        LoggerInterface $logger,
        string $pythonApiUrl = 'http://analyzer:8000'
    ) {
        $this->client = $client;
        
        $this->logger = $logger;
        $this->pythonApiUrl = $pythonApiUrl;
    }

    /**
     * Lance un audit SEO via FastAPI.
     *
     * @param string $url URL du site à analyser.
     *
     * @return array Résultat JSON converti en tableau PHP.
     */
    public function analyze(string $url): array
    {
        // -----------------------------
        // Validation basique de l'URL (defense in depth,
        // Python reste responsable de la validation/SSRF réelle)
        // -----------------------------
        if (trim($url) === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'error' => 'URL invalide.'
            ];
        }

        try {
            $response = $this->client->request(
                'GET',
                $this->pythonApiUrl . '/audit',
                [
                    'query' => [
                        'url' => $url
                    ],
                    'timeout' => 60
                ]
            );

            // 1. Njibo status code gbel ay parse dyal response body
            $statusCode = $response->getStatusCode();

            // 2. Checki l-erreur dyal l-moteur direct (FastAPI crash wla error code)
            if ($statusCode >= 400) {
                $this->logger->error('Erreur HTTP retournée par le moteur Python', [
                    'status_code' => $statusCode,
                    'url' => $url,
                ]);

                return [
                    'success' => false,
                    'error' => 'Le moteur Python a retourné une erreur.'
                ];
            }

            // 3. Db secure 100% n-parsiw JSON hit l-status 200 OK
            $data = $response->toArray();

            return $data;

        } catch (TransportExceptionInterface $e) {
            // Erreur réseau (connexion refusée, timeout, DNS interne, etc.)
            $this->logger->error('Erreur de transport vers le moteur Python', [
                'exception' => $e->getMessage(),
                'url' => $url,
            ]);

            return [
                'success' => false,
                'error' => 'Impossible de communiquer avec le moteur Python.'
            ];

        } catch (HttpExceptionInterface $e) {
            // Réponse HTTP en erreur (si jamais levée malgré la sécurité)
            $this->logger->error('Erreur HTTP du moteur Python', [
                'exception' => $e->getMessage(),
                'url' => $url,
            ]);

            return [
                'success' => false,
                'error' => 'Le moteur Python a retourné une erreur.'
            ];

        } catch (\Throwable $e) {
            // Toute autre erreur inattendue (bug interne, etc.)
            $this->logger->error('Erreur inattendue lors de la communication avec le moteur Python', [
                'exception' => $e->getMessage(),
                'url' => $url,
            ]);

            return [
                'success' => false,
                'error' => 'Impossible de communiquer avec le moteur Python.'
            ];
        }
    }
}