<?php

namespace App\EventListener;

// Ce listener Doctrine connecte automatiquement les deux pipelines
// d'audit sans MODIFIER AuditCallbackController ni AuditPipelineController.
//
// Des qu'un Audit est flush avec status === 'completed' (peu importe
// quel controleur a fait ce flush), il declenche l'appel a
// /audit-onpage et importe AuditPage + AuditPageHeading +
// AuditPageImage + AuditKeywordDensity pour cet audit.
//
// Fonctionnement synchrone : le flush() appelant (dans
// AuditCallbackController ou AuditPipelineOrchestrator::run()) ne
// rend la main qu'une fois l'import termine.

use App\Entity\Audit;
use App\Service\AuditPipelineOrchestrator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
class AuditPageImportListener
{
    /** @var array<int, Audit> indexe par spl_object_id pour eviter les doublons */
    private array $pendingAudits = [];

    // Empeche la re-entrance : le flush() qu'on declenche nous-memes
    // dans postFlush() (pour persister les nouvelles AuditPage) va
    // re-emettre onFlush/postFlush — on ignore ce second passage.
    private bool $isImporting = false;

    public function __construct(
        private AuditPipelineOrchestrator $orchestrator,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        if ($this->isImporting) {
            return;
        }

        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $candidates = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates()
        );

        foreach ($candidates as $entity) {
            if (!$entity instanceof Audit) {
                continue;
            }

            $changeSet = $uow->getEntityChangeSet($entity);

            if (!isset($changeSet['status'])) {
                continue;
            }

            // Le changeset Doctrine peut etre [old, new] ou juste new
            // selon insertion/update — on gere les deux cas.
            $newStatus = is_array($changeSet['status'])
                ? $changeSet['status'][1]
                : $changeSet['status'];

            if ($newStatus === 'completed') {
                $this->pendingAudits[spl_object_id($entity)] = $entity;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->isImporting || empty($this->pendingAudits)) {
            return;
        }

        $this->isImporting = true;

        $em = $args->getObjectManager();
        $auditsToProcess = $this->pendingAudits;
        $this->pendingAudits = [];

        try {
            foreach ($auditsToProcess as $audit) {
                $url = $audit->getSite()?->getUrl();

                if (!$url) {
                    $this->logger?->warning('AuditPageImportListener: audit sans URL, import ignore.', [
                        'audit_id' => $audit->getId(),
                    ]);
                    continue;
                }

                try {
                    // Protection anti-doublon : si cet Audit a deja des
                    // AuditPage (import precedent reussi), on ne relance
                    // pas l'import. Verification applicative uniquement,
                    // aucun changement de structure de base necessaire.
                    if ($audit->getPages()->count() > 0) {
                        $this->logger?->info('AuditPageImportListener: audit deja importe, ignore.', [
                            'audit_id' => $audit->getId(),
                        ]);
                        continue;
                    }

                    $this->orchestrator->importOnPageData($audit, $url);
                } catch (\Throwable $e) {
                    // Le crawl detaille echoue : on ne casse PAS la reponse
                    // principale (le score global est deja sauvegarde).
                    // Le frontend affichera juste "aucune page disponible".
                    $this->logger?->error('AuditPageImportListener: echec import on-page.', [
                        'audit_id' => $audit->getId(),
                        'url' => $url,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Flush separe pour persister les entites importees ci-dessus
            // (AuditPage, AuditPageHeading, AuditPageImage,
            // AuditKeywordDensity) — safe ici car on est APRES la fin
            // du flush principal (postFlush), pas pendant onFlush.
            $em->flush();

        } finally {
            $this->isImporting = false;
        }
    }
}