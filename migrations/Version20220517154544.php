<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220517154544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commandes_burgers ADD commande_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commandes_burgers ADD CONSTRAINT FK_A3FDD8BC82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('CREATE INDEX IDX_A3FDD8BC82EA2E54 ON commandes_burgers (commande_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commandes_burgers DROP FOREIGN KEY FK_A3FDD8BC82EA2E54');
        $this->addSql('DROP INDEX IDX_A3FDD8BC82EA2E54 ON commandes_burgers');
        $this->addSql('ALTER TABLE commandes_burgers DROP commande_id');
    }
}
