<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260810202730 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
            $this->addSql('ALTER TABLE users ALTER is_verified DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
            $this->addSql('ALTER TABLE users ALTER is_verified SET DEFAULT false');
    }
}
