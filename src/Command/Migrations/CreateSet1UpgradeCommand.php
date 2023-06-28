<?php

namespace App\Command\Migrations;

use App\Entity\Honor\Upgrade\UpgradeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('honor:upgrades:create-set-1')]
class CreateSet1UpgradeCommand extends Command
{
    public const BANK_UPGRADE_1 = 'bank 1';
    public const BANK_UPGRADE_1_PRICE = 10_000;
    public const BANK_UPGRADE_1_MAX_BALANCE = 500_000;
    public const BANK_UPGRADE_2 = 'bank 2';
    public const BANK_UPGRADE_2_PRICE = 25_000;
    public const BANK_UPGRADE_2_MAX_BALANCE = 2_000_000;

    public function __construct(private EntityManagerInterface $manager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $upgrade = new UpgradeType();
        $upgrade->setName('Bank Upgrade 1');
        $upgrade->setCode(self::BANK_UPGRADE_1);
        $upgrade->setPrice(self::BANK_UPGRADE_1_PRICE);
        $this->manager->persist($upgrade);
        $upgrade = new UpgradeType();
        $upgrade->setName('Bank Upgrade 2');
        $upgrade->setCode(self::BANK_UPGRADE_2);
        $upgrade->setPrice(self::BANK_UPGRADE_2_PRICE);
        $this->manager->persist($upgrade);
        $this->manager->flush();
        return Command::SUCCESS;
    }

}