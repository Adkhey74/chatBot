<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250519134630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE driver_vehicle (driver_id INT NOT NULL, vehicle_id INT NOT NULL, INDEX IDX_DE7F80E6C3423909 (driver_id), INDEX IDX_DE7F80E6545317D1 (vehicle_id), PRIMARY KEY(driver_id, vehicle_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE driver_vehicle ADD CONSTRAINT FK_DE7F80E6C3423909 FOREIGN KEY (driver_id) REFERENCES driver (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE driver_vehicle ADD CONSTRAINT FK_DE7F80E6545317D1 FOREIGN KEY (vehicle_id) REFERENCES vehicle (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE driver_vehicle DROP FOREIGN KEY FK_DE7F80E6C3423909
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE driver_vehicle DROP FOREIGN KEY FK_DE7F80E6545317D1
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE driver_vehicle
        SQL);
    }
}
