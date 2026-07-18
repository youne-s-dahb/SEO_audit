<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260718141319 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recommendations ADD recommendation TEXT NOT NULL');
        $this->addSql('ALTER TABLE recommendations DROP title');
        $this->addSql('ALTER TABLE recommendations DROP description');
        $this->addSql('ALTER TABLE recommendations DROP priority');
        $this->addSql('ALTER TABLE recommendations DROP impact_level');
        $this->addSql('ALTER TABLE recommendations DROP is_done');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recommendations ADD title VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE recommendations ADD description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE recommendations ADD priority VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE recommendations ADD impact_level VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE recommendations ADD is_done BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE recommendations DROP recommendation');
    }
}
