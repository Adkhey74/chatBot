<?php

namespace App\Command;

use App\Entity\Dealership;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'import:dealerships')]
class ImportDealershipsCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = fopen('data/concessions.csv', 'r');

        // skip header
        fgetcsv($file);

        while (($row = fgetcsv($file, 1000, ',')) !== false) {
            $dealership = new Dealership();
            $dealership->setName($row[0]);
            $dealership->setCity($row[1]);
            $dealership->setAddress($row[2]);
            $dealership->setZipcode($row[3]);
            $dealership->setLatitude((float) $row[4]);
            $dealership->setLongitude((float) $row[5]);

            $this->em->persist($dealership);
        }

        fclose($file);
        $this->em->flush();

        $output->writeln('✅ Import terminé !');
        return Command::SUCCESS;
    }
}
