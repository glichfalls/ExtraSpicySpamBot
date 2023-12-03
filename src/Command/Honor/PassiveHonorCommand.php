<?php declare(strict_types=1);

namespace App\Command\Honor;

use App\Entity\Honor\HonorFactory;
use App\Entity\Item\Effect\EffectType;
use App\Repository\ChatRepository;
use App\Repository\UserRepository;
use App\Service\Honor\HonorService;
use App\Service\Items\ItemEffectService;
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
        private readonly ItemEffectService $itemEffectService,
        private readonly HonorService $honorService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $chats = $this->chatRepository->getAllWithPassiveHonorEnabled();
        foreach ($chats as $chat) {
            $users = $this->userRepository->getUsersByChat($chat);
            foreach ($users as $user) {
                $effects = $this->itemEffectService->getEffectsByUserAndType($user, $chat, EffectType::PASSIVE_HONOR);
                $baseAmount = $chat->getConfig()->getPassiveHonorAmount();
                $finalAmount = $effects->apply($baseAmount);
                $this->honorService->addHonor($chat, $user, $finalAmount);
                $this->manager->persist(HonorFactory::create($chat, null, $user, $finalAmount));
            }
        }
        $this->manager->flush();
        return Command::SUCCESS;
    }

}
