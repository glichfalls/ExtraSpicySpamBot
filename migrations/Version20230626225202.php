<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230626225202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bank_account (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', chat_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', user_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_53A23E0A1A9A7125 (chat_id), INDEX IDX_53A23E0AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transaction (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', bank_account_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', amount INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_723705D112CB990C (bank_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bank_account ADD CONSTRAINT FK_53A23E0A1A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id)');
        $this->addSql('ALTER TABLE bank_account ADD CONSTRAINT FK_53A23E0AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D112CB990C FOREIGN KEY (bank_account_id) REFERENCES bank_account (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bank_account DROP FOREIGN KEY FK_53A23E0A1A9A7125');
        $this->addSql('ALTER TABLE bank_account DROP FOREIGN KEY FK_53A23E0AA76ED395');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D112CB990C');
        $this->addSql('DROP TABLE bank_account');
        $this->addSql('DROP TABLE transaction');
    }
}
