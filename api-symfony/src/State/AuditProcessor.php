<?php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Audit;
use App\Service\PythonAnalyzerService;

use Psr\Log\LoggerInterface;

/**reçoit la requête d'API Platform, appelle le moteur Python pour lancer l'audit, 
remplit l'entité Audit, puis la sauvegarde dans la base de données.*/ 


final class AuditProcessor implements ProcessorInterface
{
    /**
     * EntityManager dyal Doctrine.
     * Howa li kaydir persist f Doctrine.
     */
   private EntityManagerInterface $entityManager;
    private PythonAnalyzerService $pythonAnalyzer;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        PythonAnalyzerService $pythonAnalyzer,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->pythonAnalyzer = $pythonAnalyzer;
        $this->logger = $logger;
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): mixed {
        // ----------------------------------------
        // Kan2akdou belli data hiya Audit
        // ----------------------------------------
        if (!$data instanceof Audit) {
            return $data;
        }
        //audit demarre
        $data->setStatus('running');
        $data->setStartedAt(new \DateTimeImmutable());

        //Sauvegarder l-statut 'running' f BD bach l-UI t-choufo
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        try {
            // Kanjib URL mn Site
            $site = $data->getSite();

            if (!$site) {
                throw new \Exception("Site introuvable.");
            }

            $url = $site->getUrl();

            if (!$url) {
                throw new \Exception("URL vide.");
            }

            // Appel du moteur Python
            $result = $this->pythonAnalyzer->analyze($url);

            // Vérification du résultat
            if (!$result || !is_array($result)) {
                throw new \Exception("Réponse invalide du moteur Python.");
            }


            // Résultat SEO
            $seo = $result["seo"] ?? [];

            // HTTPS
            $data->setIsHttps(
                str_starts_with($seo["url"] ?? "", "https://")
            );

            // robots.txt & sitemap.xml (temporaire)
            // BUG FIX #2 : on ne peut pas affirmer "true" sans vérification réelle,
            // on met "false" en attendant l'implémentation réelle (évite une fausse info)
            $data->setHasRobotsTxt(false);
            $data->setHasSitemapXml(false);

            // Résultat PageSpeed
            $pagespeed = $result["pagespeed"] ?? [];

            // BUG FIX #7 : validation du type avant cast (évite un cast silencieux faux)
            $rawPerformance = $pagespeed["scores_detailles"]["performance"] ?? 0;
            $performance = is_numeric($rawPerformance) ? (float) $rawPerformance : 0;

            // BUG FIX #6 : gestion de l'incertitude d'échelle (0-1 vs 0-100)
            // Ila l-valeur jat entre 0 et 1 (échelle décimale), kanrédouha *100
            if ($performance > 0 && $performance <= 1) {
                $performance = $performance * 100;
            }
            $performance = (int) round($performance);

            // BUG FIX #1 : précédence d'opérateur corrigée (le cast était appliqué
            // avant le ??, ce qui rendait le "?? 0" inutile et générait un warning)
            // -> déjà résolu ci-dessus via $rawPerformance / is_numeric()

            $data->setPagespeedDesktopScore($performance);

            // BUG FIX #3 : le score mobile est une estimation temporaire basée sur
            // le score desktop (pas de vraie donnée mobile pour l'instant) — clarifié
            // explicitement pour éviter toute confusion future, sans changer la logique
            $data->setPagespeedMobileScore($performance); // TODO: remplacer par le vrai score Mobile PageSpeed

            // Temps de chargement (LCP)
            $pageLoad = $pagespeed["resume_technique"]["lcp"] ?? null;

            if (!empty($pageLoad)) {
                // BUG FIX #4 : gestion des deux formats possibles ("6.4 s" ou "800 ms")
                $pageLoadStr = trim((string) $pageLoad);

                if (str_ends_with($pageLoadStr, "ms")) {
                    // Déjà en millisecondes, ex: "800 ms" -> 800
                    $pageLoadMs = (float) str_replace(
                        [" ms", ",", " "],
                        ["", ".", ""],
                        $pageLoadStr
                    );
                } else {
                    // En secondes, ex: "6.4 s" -> 6.4 -> 6400 ms
                    $pageLoadSec = (float) str_replace(
                        [" s", ",", " "],
                        ["", ".", ""],
                        $pageLoadStr
                    );
                    $pageLoadMs = $pageLoadSec * 1000;
                }

                // round() kat-hni d-développeur men l-machakil d precision dyal float
                $data->setPageLoadTimeMs(
                    (int) round($pageLoadMs)
                );
            }

            // Score global & Couleur
            // BUG FIX #5 : le nom "GlobalScore" est trompeur car il ne reflète que
            // le score de performance PageSpeed pour l'instant — clarifié par TODO,
            // sans changer la logique/valeur actuelle
            $data->setGlobalScore($performance); // TODO: agréger SEO + HTTPS + mobile-friendly, pas seulement performance

            if ($performance >= 90) {
                $data->setScoreColor("green");
            } elseif ($performance >= 50) {
                $data->setScoreColor("orange");
            } else {
                $data->setScoreColor("red");
            }

            // Friendly Mobile (temporaire)
            $data->setIsMobileFriendly($performance >= 50);

            
            // Audit terminé et Sauvegarde
            $data->setStatus("completed");
            $data->setFinishedAt(new \DateTimeImmutable());

            // Sauvegarde finale après succès de l'audit
            $this->entityManager->persist($data);
            $this->entityManager->flush();
            
        } catch (\Throwable $e) {
            // Log de l'erreur
            $this->logger->error($e->getMessage());

            // Mise à jour de l'entité f l-cas dyal l'échec
            $data->setStatus('failed');
            $data->setErrorMessage($e->getMessage());
            $data->setFinishedAt(new \DateTimeImmutable());

            // Sauvegarde même en cas d'erreur
            $this->entityManager->persist($data);
            $this->entityManager->flush();
        }
        return $data;
    }
}