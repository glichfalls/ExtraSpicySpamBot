<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231123202239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE draw CHANGE previous_jackpot previous_jackpot NUMERIC(63, 2) DEFAULT NULL, CHANGE gambling_losses gambling_losses NUMERIC(63, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE honor CHANGE amount amount NUMERIC(63, 2) NOT NULL');
        $this->addSql('ALTER TABLE stock_price CHANGE price price NUMERIC(10, 0) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE draw CHANGE previous_jackpot previous_jackpot BIGINT NOT NULL, CHANGE gambling_losses gambling_losses BIGINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE honor CHANGE amount amount BIGINT NOT NULL');
        $this->addSql('ALTER TABLE stock_price CHANGE price price DOUBLE PRECISION NOT NULL');
    }
}
