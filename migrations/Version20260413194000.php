<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413194000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add category field to questions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE question ADD category VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE question DROP category');
    }
}
