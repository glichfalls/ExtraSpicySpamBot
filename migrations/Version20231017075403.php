<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231017075403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE draw CHANGE gambling_losses gambling_losses BIGINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE honor CHANGE amount amount BIGINT NOT NULL');
        $this->addSql('ALTER TABLE stock_transaction CHANGE amount amount BIGINT NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE amount amount BIGINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE draw CHANGE gambling_losses gambling_losses INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE honor CHANGE amount amount INT NOT NULL');
        $this->addSql('ALTER TABLE stock_transaction CHANGE amount amount INT NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE amount amount INT NOT NULL');
    }
}
