<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231107214309 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // create item table and copy data from collectable table
        $this->addSql('CREATE TABLE item (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, rarity VARCHAR(255) NOT NULL, permanent TINYINT(1) NOT NULL, attributes JSON NOT NULL, price BIGINT DEFAULT NULL, image_public_path LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO item (id, name, description, rarity, permanent, attributes, price, image_public_path) SELECT id, name, description, 'Common', 1, '[]', null, image_public_path FROM collectable");
        // create effect_item table and copy data from effect_collectable table
        $this->addSql('CREATE TABLE effect_item (effect_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', item_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_8F7E12E7F5E9B83B (effect_id), INDEX IDX_8F7E12E7126F525E (item_id), PRIMARY KEY(effect_id, item_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO effect_item (effect_id, item_id) SELECT effect_id, collectable_id FROM effect_collectable");
        // create this tables without data
        $this->addSql('CREATE TABLE item_auction (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', instance_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', seller_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', highest_bidder_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', highest_bid INT NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_D5B9D3C73A51721D (instance_id), INDEX IDX_D5B9D3C78DE820D9 (seller_id), INDEX IDX_D5B9D3C7C03ECC6F (highest_bidder_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE item_challenge (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', instance_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', success TINYINT(1) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, payload JSON NOT NULL, INDEX IDX_DBD39FA13A51721D (instance_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        // create item_instance table and copy data from collectable_item_instance table
        $this->addSql('CREATE TABLE item_instance (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', item_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', chat_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', owner_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', tradeable TINYINT(1) NOT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_2E3D7915126F525E (item_id), INDEX IDX_2E3D79151A9A7125 (chat_id), INDEX IDX_2E3D79157E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO item_instance (id, item_id, chat_id, owner_id, tradeable, expires_at, created_at, updated_at) SELECT id, collectable_id, chat_id, owner_id, 1, null, created_at, updated_at FROM collectable_item_instance");
        // add foreign keys
        $this->addSql('ALTER TABLE effect_item ADD CONSTRAINT FK_8F7E12E7F5E9B83B FOREIGN KEY (effect_id) REFERENCES effect (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE effect_item ADD CONSTRAINT FK_8F7E12E7126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_auction ADD CONSTRAINT FK_D5B9D3C73A51721D FOREIGN KEY (instance_id) REFERENCES item_instance (id)');
        $this->addSql('ALTER TABLE item_auction ADD CONSTRAINT FK_D5B9D3C78DE820D9 FOREIGN KEY (seller_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE item_auction ADD CONSTRAINT FK_D5B9D3C7C03ECC6F FOREIGN KEY (highest_bidder_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE item_challenge ADD CONSTRAINT FK_DBD39FA13A51721D FOREIGN KEY (instance_id) REFERENCES item_instance (id)');
        $this->addSql('ALTER TABLE item_instance ADD CONSTRAINT FK_2E3D7915126F525E FOREIGN KEY (item_id) REFERENCES item (id)');
        $this->addSql('ALTER TABLE item_instance ADD CONSTRAINT FK_2E3D79151A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id)');
        $this->addSql('ALTER TABLE item_instance ADD CONSTRAINT FK_2E3D79157E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        // drop old tables
        $this->addSql('ALTER TABLE collectable_auction DROP FOREIGN KEY FK_295E7B563A51721D');
        $this->addSql('ALTER TABLE collectable_auction DROP FOREIGN KEY FK_295E7B568DE820D9');
        $this->addSql('ALTER TABLE collectable_auction DROP FOREIGN KEY FK_295E7B56C03ECC6F');
        $this->addSql('ALTER TABLE collectable_item_instance DROP FOREIGN KEY FK_A38A334A1A9A7125');
        $this->addSql('ALTER TABLE collectable_item_instance DROP FOREIGN KEY FK_A38A334A7E3C61F9');
        $this->addSql('ALTER TABLE collectable_item_instance DROP FOREIGN KEY FK_A38A334AA4EF7C48');
        $this->addSql('ALTER TABLE effect_collectable DROP FOREIGN KEY FK_DCA0EE07A4EF7C48');
        $this->addSql('ALTER TABLE effect_collectable DROP FOREIGN KEY FK_DCA0EE07F5E9B83B');
        $this->addSql('DROP TABLE collectable');
        $this->addSql('DROP TABLE collectable_auction');
        $this->addSql('DROP TABLE collectable_item_instance');
        $this->addSql('DROP TABLE effect_collectable');
    }

    public function down(Schema $schema): void
    {
        // TODO: implement method with data migration
        $this->addSql('CREATE TABLE collectable (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, tradeable TINYINT(1) NOT NULL, is_unique TINYINT(1) NOT NULL, image_public_path LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE collectable_auction (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', instance_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', seller_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', highest_bidder_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', highest_bid INT NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_295E7B563A51721D (instance_id), INDEX IDX_295E7B568DE820D9 (seller_id), INDEX IDX_295E7B56C03ECC6F (highest_bidder_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE collectable_item_instance (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', chat_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', collectable_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', owner_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, price INT NOT NULL, INDEX IDX_A38A334A1A9A7125 (chat_id), INDEX IDX_A38A334AA4EF7C48 (collectable_id), INDEX IDX_A38A334A7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE effect_collectable (effect_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', collectable_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', INDEX IDX_DCA0EE07F5E9B83B (effect_id), INDEX IDX_DCA0EE07A4EF7C48 (collectable_id), PRIMARY KEY(effect_id, collectable_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE collectable_auction ADD CONSTRAINT FK_295E7B563A51721D FOREIGN KEY (instance_id) REFERENCES collectable_item_instance (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE collectable_auction ADD CONSTRAINT FK_295E7B568DE820D9 FOREIGN KEY (seller_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE collectable_auction ADD CONSTRAINT FK_295E7B56C03ECC6F FOREIGN KEY (highest_bidder_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE collectable_item_instance ADD CONSTRAINT FK_A38A334A1A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE collectable_item_instance ADD CONSTRAINT FK_A38A334A7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE collectable_item_instance ADD CONSTRAINT FK_A38A334AA4EF7C48 FOREIGN KEY (collectable_id) REFERENCES collectable (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE effect_collectable ADD CONSTRAINT FK_DCA0EE07A4EF7C48 FOREIGN KEY (collectable_id) REFERENCES collectable (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE effect_collectable ADD CONSTRAINT FK_DCA0EE07F5E9B83B FOREIGN KEY (effect_id) REFERENCES effect (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE effect_item DROP FOREIGN KEY FK_8F7E12E7F5E9B83B');
        $this->addSql('ALTER TABLE effect_item DROP FOREIGN KEY FK_8F7E12E7126F525E');
        $this->addSql('ALTER TABLE item_auction DROP FOREIGN KEY FK_D5B9D3C73A51721D');
        $this->addSql('ALTER TABLE item_auction DROP FOREIGN KEY FK_D5B9D3C78DE820D9');
        $this->addSql('ALTER TABLE item_auction DROP FOREIGN KEY FK_D5B9D3C7C03ECC6F');
        $this->addSql('ALTER TABLE item_challenge DROP FOREIGN KEY FK_DBD39FA13A51721D');
        $this->addSql('ALTER TABLE item_instance DROP FOREIGN KEY FK_2E3D7915126F525E');
        $this->addSql('ALTER TABLE item_instance DROP FOREIGN KEY FK_2E3D79151A9A7125');
        $this->addSql('ALTER TABLE item_instance DROP FOREIGN KEY FK_2E3D79157E3C61F9');
        $this->addSql('DROP TABLE effect_item');
        $this->addSql('DROP TABLE item');
        $this->addSql('DROP TABLE item_auction');
        $this->addSql('DROP TABLE item_challenge');
        $this->addSql('DROP TABLE item_instance');
    }
}
