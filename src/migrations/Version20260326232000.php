<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326232000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stage 4 baseline: ensure required indexes for orders analytics.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_orders_create_date ON orders (create_date)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_orders_create_date_status ON orders (create_date, status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_orders_create_date_status');
        $this->addSql('DROP INDEX IF EXISTS idx_orders_create_date');
    }
}
