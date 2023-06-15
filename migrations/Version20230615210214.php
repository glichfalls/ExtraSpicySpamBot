<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230615210214 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE raid (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', chat_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', target_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', leader_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', is_active TINYINT(1) NOT NULL, is_successful TINYINT(1) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_578763B31A9A7125 (chat_id), INDEX IDX_578763B3158E0B66 (target_id), INDEX IDX_578763B373154ED4 (leader_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE raid_supporters (raid_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_C2295ACE9C55ABC9 (raid_id), INDEX IDX_C2295ACEA76ED395 (user_id), PRIMARY KEY(raid_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE raid_defenders (raid_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_35B261129C55ABC9 (raid_id), INDEX IDX_35B26112A76ED395 (user_id), PRIMARY KEY(raid_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE raid ADD CONSTRAINT FK_578763B31A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id)');
        $this->addSql('ALTER TABLE raid ADD CONSTRAINT FK_578763B3158E0B66 FOREIGN KEY (target_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE raid ADD CONSTRAINT FK_578763B373154ED4 FOREIGN KEY (leader_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE raid_supporters ADD CONSTRAINT FK_C2295ACE9C55ABC9 FOREIGN KEY (raid_id) REFERENCES raid (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE raid_supporters ADD CONSTRAINT FK_C2295ACEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE raid_defenders ADD CONSTRAINT FK_35B261129C55ABC9 FOREIGN KEY (raid_id) REFERENCES raid (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE raid_defenders ADD CONSTRAINT FK_35B26112A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE raid DROP FOREIGN KEY FK_578763B31A9A7125');
        $this->addSql('ALTER TABLE raid DROP FOREIGN KEY FK_578763B3158E0B66');
        $this->addSql('ALTER TABLE raid DROP FOREIGN KEY FK_578763B373154ED4');
        $this->addSql('ALTER TABLE raid_supporters DROP FOREIGN KEY FK_C2295ACE9C55ABC9');
        $this->addSql('ALTER TABLE raid_supporters DROP FOREIGN KEY FK_C2295ACEA76ED395');
        $this->addSql('ALTER TABLE raid_defenders DROP FOREIGN KEY FK_35B261129C55ABC9');
        $this->addSql('ALTER TABLE raid_defenders DROP FOREIGN KEY FK_35B26112A76ED395');
        $this->addSql('DROP TABLE raid');
        $this->addSql('DROP TABLE raid_supporters');
        $this->addSql('DROP TABLE raid_defenders');
    }
}
