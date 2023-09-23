<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230923210636 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE collectable (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, tradeable TINYINT(1) NOT NULL, `unique` TINYINT(1) NOT NULL, image_public_path LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE collectable_item_instance (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', chat_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', collectable_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_A38A334A1A9A7125 (chat_id), INDEX IDX_A38A334AA4EF7C48 (collectable_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE collectable_transaction (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', instance_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', seller_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', buyer_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', next_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', price INT NOT NULL, is_completed TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_E5FC0CE23A51721D (instance_id), INDEX IDX_E5FC0CE28DE820D9 (seller_id), INDEX IDX_E5FC0CE26C755722 (buyer_id), UNIQUE INDEX UNIQ_E5FC0CE2AA23F6C8 (next_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE collectable_item_instance ADD CONSTRAINT FK_A38A334A1A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id)');
        $this->addSql('ALTER TABLE collectable_item_instance ADD CONSTRAINT FK_A38A334AA4EF7C48 FOREIGN KEY (collectable_id) REFERENCES collectable (id)');
        $this->addSql('ALTER TABLE collectable_transaction ADD CONSTRAINT FK_E5FC0CE23A51721D FOREIGN KEY (instance_id) REFERENCES collectable_item_instance (id)');
        $this->addSql('ALTER TABLE collectable_transaction ADD CONSTRAINT FK_E5FC0CE28DE820D9 FOREIGN KEY (seller_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE collectable_transaction ADD CONSTRAINT FK_E5FC0CE26C755722 FOREIGN KEY (buyer_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE collectable_transaction ADD CONSTRAINT FK_E5FC0CE2AA23F6C8 FOREIGN KEY (next_id) REFERENCES collectable_transaction (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE collectable_item_instance DROP FOREIGN KEY FK_A38A334A1A9A7125');
        $this->addSql('ALTER TABLE collectable_item_instance DROP FOREIGN KEY FK_A38A334AA4EF7C48');
        $this->addSql('ALTER TABLE collectable_transaction DROP FOREIGN KEY FK_E5FC0CE23A51721D');
        $this->addSql('ALTER TABLE collectable_transaction DROP FOREIGN KEY FK_E5FC0CE28DE820D9');
        $this->addSql('ALTER TABLE collectable_transaction DROP FOREIGN KEY FK_E5FC0CE26C755722');
        $this->addSql('ALTER TABLE collectable_transaction DROP FOREIGN KEY FK_E5FC0CE2AA23F6C8');
        $this->addSql('DROP TABLE collectable');
        $this->addSql('DROP TABLE collectable_item_instance');
        $this->addSql('DROP TABLE collectable_transaction');
    }
}
