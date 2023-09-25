<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230925194403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE effect (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE collectable_transaction DROP FOREIGN KEY FK_E5FC0CE23A51721D');
        $this->addSql('ALTER TABLE collectable_transaction DROP FOREIGN KEY FK_E5FC0CE26C755722');
        $this->addSql('ALTER TABLE collectable_transaction DROP FOREIGN KEY FK_E5FC0CE28DE820D9');
        $this->addSql('ALTER TABLE collectable_transaction DROP FOREIGN KEY FK_E5FC0CE2AA23F6C8');
        $this->addSql('DROP TABLE collectable_transaction');
        $this->addSql('ALTER TABLE collectable ADD effect_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE collectable ADD CONSTRAINT FK_D8ABBF81F5E9B83B FOREIGN KEY (effect_id) REFERENCES effect (id)');
        $this->addSql('CREATE INDEX IDX_D8ABBF81F5E9B83B ON collectable (effect_id)');
        $this->addSql('ALTER TABLE collectable_item_instance ADD owner_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE collectable_item_instance ADD CONSTRAINT FK_A38A334A7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_A38A334A7E3C61F9 ON collectable_item_instance (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE collectable DROP FOREIGN KEY FK_D8ABBF81F5E9B83B');
        $this->addSql('CREATE TABLE collectable_transaction (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', instance_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', seller_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', buyer_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', next_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', price INT NOT NULL, is_completed TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_E5FC0CE23A51721D (instance_id), INDEX IDX_E5FC0CE26C755722 (buyer_id), INDEX IDX_E5FC0CE28DE820D9 (seller_id), UNIQUE INDEX UNIQ_E5FC0CE2AA23F6C8 (next_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE collectable_transaction ADD CONSTRAINT FK_E5FC0CE23A51721D FOREIGN KEY (instance_id) REFERENCES collectable_item_instance (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE collectable_transaction ADD CONSTRAINT FK_E5FC0CE26C755722 FOREIGN KEY (buyer_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE collectable_transaction ADD CONSTRAINT FK_E5FC0CE28DE820D9 FOREIGN KEY (seller_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE collectable_transaction ADD CONSTRAINT FK_E5FC0CE2AA23F6C8 FOREIGN KEY (next_id) REFERENCES collectable_transaction (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE effect');
        $this->addSql('DROP INDEX IDX_D8ABBF81F5E9B83B ON collectable');
        $this->addSql('ALTER TABLE collectable DROP effect_id');
        $this->addSql('ALTER TABLE collectable_item_instance DROP FOREIGN KEY FK_A38A334A7E3C61F9');
        $this->addSql('DROP INDEX IDX_A38A334A7E3C61F9 ON collectable_item_instance');
        $this->addSql('ALTER TABLE collectable_item_instance DROP owner_id');
    }
}
