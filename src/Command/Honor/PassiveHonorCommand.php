<?php

namespace App\Command\Honor;

use App\Entity\Honor\HonorFactory;
use App\Repository\ChatRepository;
use App\Repository\UserRepository;
use App\Service\Collectable\CollectableService;
use App\Service\Collectable\EffectType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('telegram:honor:passive')]
class PassiveHonorCommand extends Command
{

    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly ChatRepository $chatRepository,
        private readonly UserRepository $userRepository,
        private readonly CollectableService $collectableService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $chats = $this->chatRepository->getAllWithPassiveHonorEnabled();
        foreach ($chats as $chat) {
            $users = $this->userRepository->getUsersByChat($chat);
            foreach ($users as $user) {
                $collectables = $this->collectableService->getEffectsByUserAndType($user, $chat, [EffectType::PASSIVE_HONOR]);
                $baseAmount = $chat->getConfig()->getPassiveHonorAmount();
                $finalAmount = $collectables->apply($baseAmount);
                $this->manager->persist(HonorFactory::create($chat, null, $user, $finalAmount));
            }
        }
        $this->manager->flush();
        return Command::SUCCESS;
    }

}
