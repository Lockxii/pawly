<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261022110915 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Associer les demandes aux utilisateurs connectes';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE adoption ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE adoption ADD CONSTRAINT FK_EDDEB6A9A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_EDDEB6A9A76ED395 ON adoption (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE adoption DROP FOREIGN KEY FK_EDDEB6A9A76ED395');
        $this->addSql('DROP INDEX IDX_EDDEB6A9A76ED395 ON adoption');
        $this->addSql('ALTER TABLE adoption DROP user_id');
    }
}
