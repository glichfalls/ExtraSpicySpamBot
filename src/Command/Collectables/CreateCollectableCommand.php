<?php

namespace App\Command\Collectables;

use App\Entity\Collectable\Collectable;
use App\Repository\ChatRepository;
use App\Service\Telegram\Collectables\CollectableService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:collectables:create')]
class CreateCollectableCommand extends Command
{

    public function __construct(
        private EntityManagerInterface $manager,
        private ChatRepository $chatRepository,
        private CollectableService $collectableService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Name');
        $this->addArgument('description', InputArgument::REQUIRED, 'Description');
        $this->addArgument('image', InputArgument::REQUIRED, 'Image');
        $this->addArgument('chat', InputArgument::REQUIRED, 'Chat ID');
        $this->addArgument('instances', InputArgument::OPTIONAL, 'Number of instances', default: 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $collectable = new Collectable();
        $collectable->setName($input->getArgument('name'));
        $collectable->setDescription($input->getArgument('description'));
        $collectable->setImagePublicPath($input->getArgument('image'));
        $collectable->setTradeable(true);
        $collectable->setUnique(false);

        $chat = $this->chatRepository->getChatByTelegramId($input->getArgument('chat'));
        for ($i = 0; $i < $input->getArgument('instances'); $i++) {
            $this->collectableService->createCollectableInstance($collectable, $input->getArgument('chat'));
        }
        $this->manager->persist($collectable);
        $this->manager->flush();
        return self::SUCCESS;
    }

}