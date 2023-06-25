<?php

namespace App\Command\Honor;

use App\Entity\Honor\HonorFactory;
use App\Repository\ChatRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('telegram:honor:passive')]
class PassiveHonorCommand extends Command
{
    private const CHAT_ID = -1001285586333;
    private const PASSIVE_HONOR_PER_HOUR = 10;

    public function __construct(
        private EntityManagerInterface $manager,
        private ChatRepository $chatRepository,
        private UserRepository $userRepository,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $chat = $this->chatRepository->findOneBy(['chatId' => self::CHAT_ID]);
        $users = $this->userRepository->findBy(['chatId' => self::CHAT_ID]);
        foreach ($users as $user) {
            $this->manager->persist(HonorFactory::create($chat, null, $user, self::PASSIVE_HONOR_PER_HOUR));
        }
        $this->manager->flush();
        return Command::SUCCESS;
    }

}