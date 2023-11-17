<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231117192655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE item_effect (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', effect_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', item_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_3099E43DF5E9B83B (effect_id), INDEX IDX_3099E43D126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE unit (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, attack INT NOT NULL, defense INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE item_effect ADD CONSTRAINT FK_3099E43DF5E9B83B FOREIGN KEY (effect_id) REFERENCES effect (id)');
        $this->addSql('ALTER TABLE item_effect ADD CONSTRAINT FK_3099E43D126F525E FOREIGN KEY (item_id) REFERENCES item (id)');
        $this->addSql('ALTER TABLE effect_item DROP FOREIGN KEY FK_8F7E12E7126F525E');
        $this->addSql('ALTER TABLE effect_item DROP FOREIGN KEY FK_8F7E12E7F5E9B83B');
        $this->addSql('INSERT INTO item_effect (id, effect_id, item_id) SELECT UUID(), effect_id, item_id FROM effect_item');
        $this->addSql('DROP TABLE effect_item');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE effect_item (effect_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', item_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', INDEX IDX_8F7E12E7126F525E (item_id), INDEX IDX_8F7E12E7F5E9B83B (effect_id), PRIMARY KEY(effect_id, item_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE effect_item ADD CONSTRAINT FK_8F7E12E7126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE effect_item ADD CONSTRAINT FK_8F7E12E7F5E9B83B FOREIGN KEY (effect_id) REFERENCES effect (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_effect DROP FOREIGN KEY FK_3099E43DF5E9B83B');
        $this->addSql('ALTER TABLE item_effect DROP FOREIGN KEY FK_3099E43D126F525E');
        $this->addSql('DROP TABLE item_effect');
    }
}
