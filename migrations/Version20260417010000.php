<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add rejection comment for admin quiz moderation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz ADD rejection_comment LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz DROP rejection_comment');
    }
}
