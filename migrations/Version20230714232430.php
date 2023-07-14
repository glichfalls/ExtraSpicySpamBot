<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230714232430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE one_to_how_much_round (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', challenger_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', opponent_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', winner_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', accepted TINYINT(1) NOT NULL, `range` INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_BE02F6A82D521FDF (challenger_id), INDEX IDX_BE02F6A87F656CDC (opponent_id), INDEX IDX_BE02F6A85DFCD4B8 (winner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE one_to_how_much_round ADD CONSTRAINT FK_BE02F6A82D521FDF FOREIGN KEY (challenger_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE one_to_how_much_round ADD CONSTRAINT FK_BE02F6A87F656CDC FOREIGN KEY (opponent_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE one_to_how_much_round ADD CONSTRAINT FK_BE02F6A85DFCD4B8 FOREIGN KEY (winner_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE one_to_how_much_round DROP FOREIGN KEY FK_BE02F6A82D521FDF');
        $this->addSql('ALTER TABLE one_to_how_much_round DROP FOREIGN KEY FK_BE02F6A87F656CDC');
        $this->addSql('ALTER TABLE one_to_how_much_round DROP FOREIGN KEY FK_BE02F6A85DFCD4B8');
        $this->addSql('DROP TABLE one_to_how_much_round');
    }
}
