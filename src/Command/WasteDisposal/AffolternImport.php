<?php

namespace App\Command\WasteDisposal;

use App\Entity\WasteDisposal\WasteDisposalDate;
use Doctrine\ORM\EntityManagerInterface;
use IntlDateFormatter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:waste-disposal:affoltern:import')]
class AffolternImport extends Command
{

    private const DATA = <<<DATA
    5. Mai 2023
    19. Mai 2023
    2. Juni 2023
    16. Juni 2023
    30. Juni 2023
    14. Juli 2023
    28. Juli 2023
    11. August 2023
    25. August 2023
    8. September 2023
    22. September 2023
    6. Oktober 2023
    20. Oktober 2023
    3. November 2023
    17. November 2023
    1. Dezember 2023
    15. Dezember 2023
    29. Dezember 2023
    DATA;

    private array $months = [
        'Januar' => 'January',
        'Februar' => 'February',
        'März' => 'March',
        'April' => 'April',
        'Mai' => 'May',
        'Juni' => 'June',
        'Juli' => 'July',
        'August' => 'August',
        'September' => 'September',
        'Oktober' => 'October',
        'November' => 'November',
        'Dezember' => 'December',
    ];

    public function __construct(private EntityManagerInterface $manager)
    {
        parent::__construct();
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $dates = explode("\n", self::DATA);
        foreach ($dates as $date) {
            // replace month name
            $line = explode(' ', $date);
            $line[1] = $this->months[$line[1]];
            $date = implode(' ', $line);
            $output->writeln($date);
            $wasteDisposalDate = new WasteDisposalDate();
            $wasteDisposalDate->setZipCode('8046');
            $wasteDisposalDate->setDate(new \DateTime($date));
            $wasteDisposalDate->setDescription('Kartonsammlung');
            $wasteDisposalDate->setCreatedAt(new \DateTime());
            $wasteDisposalDate->setUpdatedAt(new \DateTime());
            $this->manager->persist($wasteDisposalDate);
        }
        $this->manager->flush();
        return self::SUCCESS;
    }

}