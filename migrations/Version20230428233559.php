<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230428233559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE message ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE user ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE message DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE user DROP created_at, DROP updated_at');
    }
}
