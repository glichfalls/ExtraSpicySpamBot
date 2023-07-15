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
        $chats = $this->chatRepository->getAllWithPassiveHonorEnabled();
        foreach ($chats as $chat) {
            $users = $this->userRepository->getUsersByChat($chat);
            foreach ($users as $user) {
                $this->manager->persist(HonorFactory::create($chat, null, $user, $chat->getConfig()->getPassiveHonorAmount()));
            }
        }
        $this->manager->flush();
        return Command::SUCCESS;
    }

}