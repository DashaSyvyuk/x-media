<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716122000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add note field to fop profiles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fop_profiles ADD note LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fop_profiles DROP note');
    }
}
