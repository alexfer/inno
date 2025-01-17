<?php

declare(strict_types=1);

namespace AppMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250116170243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attach ADD path VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX invoice_number_idx ON store_invoice (number)');
        $this->addSql('CREATE INDEX order_number_idx ON store_orders (number)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX order_number_idx');
        $this->addSql('ALTER TABLE attach DROP path');
        $this->addSql('DROP INDEX invoice_number_idx');
    }
}
