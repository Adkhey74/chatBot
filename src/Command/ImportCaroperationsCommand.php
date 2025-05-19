<?php

namespace App\Command;

use App\Entity\CarOperation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'import:caroperations',
    description: 'Importe les opérations de voiture depuis un fichier CSV.',
)]
class ImportCaroperationsCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = 'data/carOperation.csv';

        if (!file_exists($filePath)) {
            $io->error("Le fichier $filePath est introuvable.");
            return Command::FAILURE;
        }

        $file = fopen($filePath, 'r');
        fgetcsv($file, 1000, ';'); // Sauter l'en-tête

        $count = 0;

        while (($row = fgetcsv($file, 1000, ';')) !== false) {
            $operation = new CarOperation();
            $operation->setName($row[0]);
            $operation->setCategory($row[1]);
            $operation->setAdditionnalHelp($row[2] ?? null);
            $operation->setAdditionnalComment($row[3] ?? null);
            $operation->setTimeUnit((int) $row[4]);
            $operation->setPrice((float) $row[5]);

            $this->em->persist($operation);
            $count++;
        }

        fclose($file);
        $this->em->flush();

        $io->success("✅ $count opérations importées avec succès.");
        return Command::SUCCESS;
    }
}
