<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231203000157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chat_config CHANGE passive_honor_amount passive_honor_amount NUMERIC(63, 2) NOT NULL');
        $this->addSql('ALTER TABLE item_auction CHANGE highest_bid highest_bid NUMERIC(63, 2) NOT NULL');
        $this->addSql('ALTER TABLE raid CHANGE amount amount NUMERIC(63, 2) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE slot_machine_jackpot CHANGE amount amount NUMERIC(63, 2) NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE amount amount NUMERIC(63, 2) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chat_config CHANGE passive_honor_amount passive_honor_amount INT NOT NULL');
        $this->addSql('ALTER TABLE item_auction CHANGE highest_bid highest_bid INT NOT NULL');
        $this->addSql('ALTER TABLE raid CHANGE amount amount BIGINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE slot_machine_jackpot CHANGE amount amount BIGINT NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE amount amount BIGINT NOT NULL');
    }
}
