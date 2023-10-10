<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231010191306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE effect_collectable (effect_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', collectable_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_DCA0EE07F5E9B83B (effect_id), INDEX IDX_DCA0EE07A4EF7C48 (collectable_id), PRIMARY KEY(effect_id, collectable_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE effect_collectable ADD CONSTRAINT FK_DCA0EE07F5E9B83B FOREIGN KEY (effect_id) REFERENCES effect (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE effect_collectable ADD CONSTRAINT FK_DCA0EE07A4EF7C48 FOREIGN KEY (collectable_id) REFERENCES collectable (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE collectable DROP FOREIGN KEY FK_D8ABBF81F5E9B83B');
        $this->addSql('DROP INDEX IDX_D8ABBF81F5E9B83B ON collectable');
        $this->addSql('ALTER TABLE collectable DROP effect_id');
        $this->addSql('ALTER TABLE collectable_item_instance CHANGE price price INT NOT NULL');
        $this->addSql('ALTER TABLE effect ADD type VARCHAR(255) NOT NULL, ADD magnitude DOUBLE PRECISION NOT NULL, ADD operator VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE effect_collectable DROP FOREIGN KEY FK_DCA0EE07F5E9B83B');
        $this->addSql('ALTER TABLE effect_collectable DROP FOREIGN KEY FK_DCA0EE07A4EF7C48');
        $this->addSql('DROP TABLE effect_collectable');
        $this->addSql('ALTER TABLE collectable ADD effect_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE collectable ADD CONSTRAINT FK_D8ABBF81F5E9B83B FOREIGN KEY (effect_id) REFERENCES effect (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_D8ABBF81F5E9B83B ON collectable (effect_id)');
        $this->addSql('ALTER TABLE collectable_item_instance CHANGE price price INT DEFAULT 10000000 NOT NULL');
        $this->addSql('ALTER TABLE effect DROP type, DROP magnitude, DROP operator');
    }
}
