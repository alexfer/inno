<?php

declare(strict_types=1);

namespace AppMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250117043647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entry_attachment DROP CONSTRAINT fk_e1f59089bb1a0722');
        $this->addSql('DROP INDEX idx_e1f59089bb1a0722');
        $this->addSql('ALTER TABLE entry_attachment RENAME COLUMN details_id TO entry_id');
        $this->addSql('ALTER TABLE entry_attachment ADD CONSTRAINT FK_E1F59089BA364942 FOREIGN KEY (entry_id) REFERENCES entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_E1F59089BA364942 ON entry_attachment (entry_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entry_attachment DROP CONSTRAINT FK_E1F59089BA364942');
        $this->addSql('DROP INDEX IDX_E1F59089BA364942');
        $this->addSql('ALTER TABLE entry_attachment RENAME COLUMN entry_id TO details_id');
        $this->addSql('ALTER TABLE entry_attachment ADD CONSTRAINT fk_e1f59089bb1a0722 FOREIGN KEY (details_id) REFERENCES entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_e1f59089bb1a0722 ON entry_attachment (details_id)');
    }
}
