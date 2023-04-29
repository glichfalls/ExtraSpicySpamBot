<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230429012545 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE honor ADD sender_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', ADD recipient_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', ADD chat_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', DROP sender, DROP recipient, DROP chat');
        $this->addSql('ALTER TABLE honor ADD CONSTRAINT FK_1202AD96F624B39D FOREIGN KEY (sender_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE honor ADD CONSTRAINT FK_1202AD96E92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE honor ADD CONSTRAINT FK_1202AD961A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id)');
        $this->addSql('CREATE INDEX IDX_1202AD96F624B39D ON honor (sender_id)');
        $this->addSql('CREATE INDEX IDX_1202AD96E92F8F78 ON honor (recipient_id)');
        $this->addSql('CREATE INDEX IDX_1202AD961A9A7125 ON honor (chat_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE honor DROP FOREIGN KEY FK_1202AD96F624B39D');
        $this->addSql('ALTER TABLE honor DROP FOREIGN KEY FK_1202AD96E92F8F78');
        $this->addSql('ALTER TABLE honor DROP FOREIGN KEY FK_1202AD961A9A7125');
        $this->addSql('DROP INDEX IDX_1202AD96F624B39D ON honor');
        $this->addSql('DROP INDEX IDX_1202AD96E92F8F78 ON honor');
        $this->addSql('DROP INDEX IDX_1202AD961A9A7125 ON honor');
        $this->addSql('ALTER TABLE honor ADD sender VARCHAR(255) NOT NULL, ADD recipient VARCHAR(255) NOT NULL, ADD chat VARCHAR(255) NOT NULL, DROP sender_id, DROP recipient_id, DROP chat_id');
    }
}
