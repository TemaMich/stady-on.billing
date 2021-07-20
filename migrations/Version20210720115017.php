<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210720115017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT fk_723705d1cc405842');
        $this->addSql('DROP INDEX idx_723705d1cc405842');
        $this->addSql('ALTER TABLE transaction ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE transaction ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE transaction RENAME COLUMN billing_user_id TO username_id');
        $this->addSql('COMMENT ON COLUMN transaction.created_at IS NULL');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1ED766068 FOREIGN KEY (username_id) REFERENCES "billing_user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_723705D1ED766068 ON transaction (username_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D1ED766068');
        $this->addSql('DROP INDEX IDX_723705D1ED766068');
        $this->addSql('ALTER TABLE transaction ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE transaction ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE transaction RENAME COLUMN username_id TO billing_user_id');
        $this->addSql('COMMENT ON COLUMN transaction.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT fk_723705d1cc405842 FOREIGN KEY (billing_user_id) REFERENCES billing_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_723705d1cc405842 ON transaction (billing_user_id)');
    }
}
