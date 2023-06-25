<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230625104136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE draw (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', chat_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', previous_draw_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', previous_jackpot INT NOT NULL, date DATE NOT NULL, winning_number INT DEFAULT NULL, INDEX IDX_70F2BD0F1A9A7125 (chat_id), UNIQUE INDEX UNIQ_70F2BD0F5E5EAB6B (previous_draw_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ticket (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', user_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', draw_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', number INT NOT NULL, INDEX IDX_97A0ADA3A76ED395 (user_id), INDEX IDX_97A0ADA36FC5C1B8 (draw_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE draw ADD CONSTRAINT FK_70F2BD0F1A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id)');
        $this->addSql('ALTER TABLE draw ADD CONSTRAINT FK_70F2BD0F5E5EAB6B FOREIGN KEY (previous_draw_id) REFERENCES draw (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA36FC5C1B8 FOREIGN KEY (draw_id) REFERENCES draw (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE draw DROP FOREIGN KEY FK_70F2BD0F1A9A7125');
        $this->addSql('ALTER TABLE draw DROP FOREIGN KEY FK_70F2BD0F5E5EAB6B');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA3A76ED395');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA36FC5C1B8');
        $this->addSql('DROP TABLE draw');
        $this->addSql('DROP TABLE ticket');
    }
}
