<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250713190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add x-kom product URL field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD xkom_url VARCHAR(2048) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP xkom_url');
    }
}
