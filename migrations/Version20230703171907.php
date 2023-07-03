<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230703171907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket ADD numbers JSON NOT NULL');
        $this->addSql('UPDATE ticket SET numbers = CONCAT("[", number, "]")');
        $this->addSql('ALTER TABLE ticket DROP `number`');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket ADD number INT NOT NULL');
        $this->addSql('UPDATE ticket SET number = JSON_EXTRACT(numbers, "$[0]")');
        $this->addSql('ALTER TABLE ticket DROP `numbers`');
    }
}
