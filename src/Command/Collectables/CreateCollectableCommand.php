<?php

namespace App\Command\Collectables;

use App\Entity\Collectable\Collectable;
use App\Repository\ChatRepository;
use App\Service\Collectable\CollectableService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:collectables:create')]
class CreateCollectableCommand extends Command
{
    private const CHAT_ID = -1001285586333;

    public function __construct(
        private EntityManagerInterface $manager,
        private ChatRepository $chatRepository,
        private CollectableService $collectableService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Name');
        $this->addArgument('description', InputArgument::REQUIRED, 'Description');
        $this->addArgument('image', InputArgument::REQUIRED, 'Image');
        $this->addArgument('chat', InputArgument::OPTIONAL, 'Chat ID', default: self::CHAT_ID);
        $this->addArgument('instances', InputArgument::OPTIONAL, 'Number of instances', default: 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $collectable = new Collectable();
        $collectable->setName($input->getArgument('name'));
        $collectable->setDescription($input->getArgument('description'));
        $collectable->setImagePublicPath(sprintf('collectable/%s', $input->getArgument('image')));
        $collectable->setTradeable(true);
        $collectable->setUnique(false);
        if (!file_exists(sprintf('public/%s', $collectable->getImagePublicPath()))) {
            throw new \RuntimeException('Image does not exist.');
        }
        $chat = $this->chatRepository->getChatByTelegramId($input->getArgument('chat'));
        for ($i = 0; $i < $input->getArgument('instances'); $i++) {
            $this->collectableService->createCollectableInstance(
                $collectable,
                $chat,
                null,
            );
        }
        $this->manager->persist($collectable);
        $this->manager->flush();
        return self::SUCCESS;
    }

}
