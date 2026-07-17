<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260717221619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audits ADD accessibility_score INT DEFAULT NULL');
        $this->addSql('ALTER TABLE audits ADD best_practices_score INT DEFAULT NULL');
        $this->addSql('ALTER TABLE audits ADD seo_score INT DEFAULT NULL');
        $this->addSql('ALTER TABLE audits ADD metrics JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE audits ADD requested_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE audits ADD CONSTRAINT FK_32451E6C4DA1E751 FOREIGN KEY (requested_by_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_32451E6C4DA1E751 ON audits (requested_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audits DROP CONSTRAINT FK_32451E6C4DA1E751');
        $this->addSql('DROP INDEX IDX_32451E6C4DA1E751');
        $this->addSql('ALTER TABLE audits DROP accessibility_score');
        $this->addSql('ALTER TABLE audits DROP best_practices_score');
        $this->addSql('ALTER TABLE audits DROP seo_score');
        $this->addSql('ALTER TABLE audits DROP metrics');
        $this->addSql('ALTER TABLE audits DROP requested_by_id');
    }
}
