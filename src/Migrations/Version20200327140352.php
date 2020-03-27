<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200327140352 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE lead (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, fields LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', dt DATETIME NOT NULL, uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_289161CBD17F50A6 (uuid), INDEX IDX_289161CB32C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE organization (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_C1EE637CD17F50A6 (uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE organization_api (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, name VARCHAR(255) NOT NULL, api_key VARCHAR(23) NOT NULL, INDEX IDX_A78887EC32C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE organization_contact (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, name VARCHAR(255) DEFAULT NULL, email VARCHAR(255) NOT NULL, job_title VARCHAR(255) DEFAULT NULL, is_primary TINYINT(1) NOT NULL, mobile_phone VARCHAR(255) DEFAULT NULL, work_phone VARCHAR(255) DEFAULT NULL, home_phone VARCHAR(255) DEFAULT NULL, notify_via_email TINYINT(1) NOT NULL, notify_via_mobile TINYINT(1) NOT NULL, uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_81B06E7BD17F50A6 (uuid), INDEX IDX_81B06E7B32C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE lead ADD CONSTRAINT FK_289161CB32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE organization_api ADD CONSTRAINT FK_A78887EC32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE organization_contact ADD CONSTRAINT FK_81B06E7B32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE lead DROP FOREIGN KEY FK_289161CB32C8A3DE');
        $this->addSql('ALTER TABLE organization_api DROP FOREIGN KEY FK_A78887EC32C8A3DE');
        $this->addSql('ALTER TABLE organization_contact DROP FOREIGN KEY FK_81B06E7B32C8A3DE');
        $this->addSql('DROP TABLE lead');
        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP TABLE organization_api');
        $this->addSql('DROP TABLE organization_contact');
    }
}
