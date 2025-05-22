<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250522082324 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE appointment_car_operation (appointment_id INT NOT NULL, car_operation_id INT NOT NULL, INDEX IDX_602BEAEBE5B533F9 (appointment_id), INDEX IDX_602BEAEB1D758A6B (car_operation_id), PRIMARY KEY(appointment_id, car_operation_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment_car_operation ADD CONSTRAINT FK_602BEAEBE5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment_car_operation ADD CONSTRAINT FK_602BEAEB1D758A6B FOREIGN KEY (car_operation_id) REFERENCES car_operation (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F8441D758A6B
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_FE38F8441D758A6B ON appointment
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment ADD supplementary_infos LONGTEXT DEFAULT NULL, CHANGE appointment_date appointment_date DATETIME DEFAULT NULL, CHANGE car_operation_id driver_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844C3423909 FOREIGN KEY (driver_id) REFERENCES driver (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FE38F844C3423909 ON appointment (driver_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment_car_operation DROP FOREIGN KEY FK_602BEAEBE5B533F9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment_car_operation DROP FOREIGN KEY FK_602BEAEB1D758A6B
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE appointment_car_operation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844C3423909
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_FE38F844C3423909 ON appointment
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment DROP supplementary_infos, CHANGE appointment_date appointment_date DATETIME NOT NULL, CHANGE driver_id car_operation_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8441D758A6B FOREIGN KEY (car_operation_id) REFERENCES car_operation (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FE38F8441D758A6B ON appointment (car_operation_id)
        SQL);
    }
}
