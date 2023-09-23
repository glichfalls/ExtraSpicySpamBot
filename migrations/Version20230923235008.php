<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230923235008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE collectable_auction (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', instance_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', seller_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', highest_bidder_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', highest_bid INT NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_295E7B563A51721D (instance_id), INDEX IDX_295E7B568DE820D9 (seller_id), INDEX IDX_295E7B56C03ECC6F (highest_bidder_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE collectable_auction ADD CONSTRAINT FK_295E7B563A51721D FOREIGN KEY (instance_id) REFERENCES collectable_item_instance (id)');
        $this->addSql('ALTER TABLE collectable_auction ADD CONSTRAINT FK_295E7B568DE820D9 FOREIGN KEY (seller_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE collectable_auction ADD CONSTRAINT FK_295E7B56C03ECC6F FOREIGN KEY (highest_bidder_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE collectable_auction DROP FOREIGN KEY FK_295E7B563A51721D');
        $this->addSql('ALTER TABLE collectable_auction DROP FOREIGN KEY FK_295E7B568DE820D9');
        $this->addSql('ALTER TABLE collectable_auction DROP FOREIGN KEY FK_295E7B56C03ECC6F');
        $this->addSql('DROP TABLE collectable_auction');
    }
}
