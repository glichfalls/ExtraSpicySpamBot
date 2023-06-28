<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230628171141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE portfolio (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', chat_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', user_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_A9ED10621A9A7125 (chat_id), INDEX IDX_A9ED1062A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stock (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, symbol VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_4B3656605E237E06 (name), UNIQUE INDEX UNIQ_4B365660ECC836F9 (symbol), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stock_price (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', stock_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', open INT UNSIGNED NOT NULL, close INT UNSIGNED NOT NULL, low INT UNSIGNED NOT NULL, high INT UNSIGNED NOT NULL, pre_market INT UNSIGNED NOT NULL, after_hours INT UNSIGNED NOT NULL, volume INT UNSIGNED NOT NULL, date DATE NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_56D83E28DCD6110 (stock_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stock_transaction (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', portfolio_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', stock_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', amount INT NOT NULL, price INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_ADF9A3E5B96B5643 (portfolio_id), INDEX IDX_ADF9A3E5DCD6110 (stock_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED10621A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id)');
        $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED1062A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE stock_price ADD CONSTRAINT FK_56D83E28DCD6110 FOREIGN KEY (stock_id) REFERENCES stock (id)');
        $this->addSql('ALTER TABLE stock_transaction ADD CONSTRAINT FK_ADF9A3E5B96B5643 FOREIGN KEY (portfolio_id) REFERENCES portfolio (id)');
        $this->addSql('ALTER TABLE stock_transaction ADD CONSTRAINT FK_ADF9A3E5DCD6110 FOREIGN KEY (stock_id) REFERENCES stock (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE portfolio DROP FOREIGN KEY FK_A9ED10621A9A7125');
        $this->addSql('ALTER TABLE portfolio DROP FOREIGN KEY FK_A9ED1062A76ED395');
        $this->addSql('ALTER TABLE stock_price DROP FOREIGN KEY FK_56D83E28DCD6110');
        $this->addSql('ALTER TABLE stock_transaction DROP FOREIGN KEY FK_ADF9A3E5B96B5643');
        $this->addSql('ALTER TABLE stock_transaction DROP FOREIGN KEY FK_ADF9A3E5DCD6110');
        $this->addSql('DROP TABLE portfolio');
        $this->addSql('DROP TABLE stock');
        $this->addSql('DROP TABLE stock_price');
        $this->addSql('DROP TABLE stock_transaction');
    }
}
