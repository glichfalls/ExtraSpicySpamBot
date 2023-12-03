<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ramsey\Uuid\Uuid;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231203183438 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->createFirstSeason();
        $this->addSql('ALTER TABLE effect CHANGE magnitude magnitude VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE raid CHANGE amount amount NUMERIC(63, 2) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE upgrade_type CHANGE price price NUMERIC(63, 2) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bank_account DROP FOREIGN KEY FK_53A23E0A4EC001D1');
        $this->addSql('DROP INDEX IDX_53A23E0A4EC001D1 ON bank_account');
        $this->addSql('ALTER TABLE bank_account DROP season_id');
        $this->addSql('ALTER TABLE portfolio DROP FOREIGN KEY FK_A9ED10624EC001D1');
        $this->addSql('DROP INDEX IDX_A9ED10624EC001D1 ON portfolio');
        $this->addSql('ALTER TABLE portfolio DROP season_id');
        $this->addSql('ALTER TABLE stock_transaction DROP FOREIGN KEY FK_ADF9A3E54EC001D1');
        $this->addSql('DROP INDEX IDX_ADF9A3E54EC001D1 ON stock_transaction');
        $this->addSql('ALTER TABLE stock_transaction DROP season_id');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D14EC001D1');
        $this->addSql('DROP INDEX IDX_723705D14EC001D1 ON transaction');
        $this->addSql('ALTER TABLE transaction DROP season_id');
        $this->addSql('ALTER TABLE honor DROP FOREIGN KEY FK_1202AD964EC001D1');
        $this->addSql('ALTER TABLE item_instance DROP FOREIGN KEY FK_2E3D79154EC001D1');
        $this->addSql('DROP TABLE season');
        $this->addSql('ALTER TABLE effect CHANGE magnitude magnitude DOUBLE PRECISION NOT NULL');
        $this->addSql('DROP INDEX IDX_1202AD964EC001D1 ON honor');
        $this->addSql('ALTER TABLE honor DROP season_id');
        $this->addSql('DROP INDEX IDX_2E3D79154EC001D1 ON item_instance');
        $this->addSql('ALTER TABLE item_instance DROP season_id');
        $this->addSql('ALTER TABLE raid CHANGE amount amount NUMERIC(63, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE upgrade_type CHANGE price price INT NOT NULL');
    }

    private function createFirstSeason(): void
    {
        $id = Uuid::uuid4()->toString();
        $this->addSql('CREATE TABLE season (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', start DATE NOT NULL, end DATE DEFAULT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql(sprintf('INSERT INTO season (id, start, end, name) VALUES (\'%s\', NOW(), NULL, \'Season 1\')', $id));
        $this->addSql('ALTER TABLE honor ADD season_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE honor ADD CONSTRAINT FK_1202AD964EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)');
        $this->addSql('CREATE INDEX IDX_1202AD964EC001D1 ON honor (season_id)');
        $this->addSql(sprintf('UPDATE honor SET season_id = \'%s\'', $id));
        $this->addSql('ALTER TABLE item_instance ADD season_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE item_instance ADD CONSTRAINT FK_2E3D79154EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)');
        $this->addSql('CREATE INDEX IDX_2E3D79154EC001D1 ON item_instance (season_id)');
        $this->addSql(sprintf('UPDATE item_instance SET season_id = \'%s\'', $id));
        $this->addSql('ALTER TABLE bank_account ADD season_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE bank_account ADD CONSTRAINT FK_53A23E0A4EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)');
        $this->addSql('CREATE INDEX IDX_53A23E0A4EC001D1 ON bank_account (season_id)');
        $this->addSql(sprintf('UPDATE bank_account SET season_id = \'%s\'', $id));
        $this->addSql('ALTER TABLE portfolio ADD season_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED10624EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)');
        $this->addSql('CREATE INDEX IDX_A9ED10624EC001D1 ON portfolio (season_id)');
        $this->addSql(sprintf('UPDATE portfolio SET season_id = \'%s\'', $id));
        $this->addSql('ALTER TABLE stock_transaction ADD season_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE stock_transaction ADD CONSTRAINT FK_ADF9A3E54EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)');
        $this->addSql('CREATE INDEX IDX_ADF9A3E54EC001D1 ON stock_transaction (season_id)');
        $this->addSql(sprintf('UPDATE stock_transaction SET season_id = \'%s\'', $id));
        $this->addSql('ALTER TABLE transaction ADD season_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D14EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)');
        $this->addSql('CREATE INDEX IDX_723705D14EC001D1 ON transaction (season_id)');
        $this->addSql(sprintf('UPDATE transaction SET season_id = \'%s\'', $id));
    }
}
