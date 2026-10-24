<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261024184155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the title table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE title (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(10) NOT NULL, year INTEGER NOT NULL, watched BOOLEAN NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE title');
    }
}
