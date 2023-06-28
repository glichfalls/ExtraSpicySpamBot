<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230628195103 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_4B3656605E237E06 ON stock');
        $this->addSql('ALTER TABLE stock ADD display_symbol VARCHAR(255) NOT NULL, ADD type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE stock_price ADD price DOUBLE PRECISION NOT NULL, ADD `change` DOUBLE PRECISION DEFAULT NULL, ADD change_percent DOUBLE PRECISION DEFAULT NULL, DROP open, DROP close, DROP low, DROP high, DROP pre_market, DROP after_hours, DROP volume, CHANGE date date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE stock_transaction DROP FOREIGN KEY FK_ADF9A3E5DCD6110');
        $this->addSql('DROP INDEX IDX_ADF9A3E5DCD6110 ON stock_transaction');
        $this->addSql('ALTER TABLE stock_transaction ADD price_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', DROP stock_id, DROP price');
        $this->addSql('ALTER TABLE stock_transaction ADD CONSTRAINT FK_ADF9A3E5D614C7E7 FOREIGN KEY (price_id) REFERENCES stock_price (id)');
        $this->addSql('CREATE INDEX IDX_ADF9A3E5D614C7E7 ON stock_transaction (price_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stock DROP display_symbol, DROP type');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B3656605E237E06 ON stock (name)');
        $this->addSql('ALTER TABLE stock_price ADD open INT UNSIGNED NOT NULL, ADD close INT UNSIGNED NOT NULL, ADD low INT UNSIGNED NOT NULL, ADD high INT UNSIGNED NOT NULL, ADD pre_market INT UNSIGNED NOT NULL, ADD after_hours INT UNSIGNED NOT NULL, ADD volume INT UNSIGNED NOT NULL, DROP price, DROP `change`, DROP change_percent, CHANGE date date DATE NOT NULL');
        $this->addSql('ALTER TABLE stock_transaction DROP FOREIGN KEY FK_ADF9A3E5D614C7E7');
        $this->addSql('DROP INDEX IDX_ADF9A3E5D614C7E7 ON stock_transaction');
        $this->addSql('ALTER TABLE stock_transaction ADD stock_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', ADD price INT UNSIGNED NOT NULL, DROP price_id');
        $this->addSql('ALTER TABLE stock_transaction ADD CONSTRAINT FK_ADF9A3E5DCD6110 FOREIGN KEY (stock_id) REFERENCES stock (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_ADF9A3E5DCD6110 ON stock_transaction (stock_id)');
    }
}
