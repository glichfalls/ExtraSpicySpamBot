<?php

namespace App\Command;

use App\Entity\Chat\Chat;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use TelegramBot\Api\BotApi;

#[AsCommand('telegram:send')]
class TestMessageCommand extends Command
{

    /** @var EntityRepository<Chat> */
    private EntityRepository $chatRepository;

    public function __construct(private BotApi $bot, private EntityManagerInterface $manager)
    {
        parent::__construct();
        // insecure but it doesn't work on localhost otherwise
        $this->bot->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->chatRepository = $this->manager->getRepository(Chat::class);
    }

    protected function configure(): void
    {
        $this->addArgument('message', InputArgument::REQUIRED)
            ->addOption('chat', 'c', InputArgument::OPTIONAL, 'Chat id');
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        try {
            if ($input->getOption('chat')) {
                $this->bot->sendMessage($input->getOption('chat'), $input->getArgument('message'));
                return self::SUCCESS;
            }
            $chatId = $this->getChatId($input, $output);
            $this->bot->sendMessage($chatId, $input->getArgument('message'));
            return self::SUCCESS;
        } catch (\Exception $exception) {
            $output->writeln(sprintf('Error: %s', $exception->getMessage()));
            return self::FAILURE;
        }
    }

    private function getChatId(InputInterface $input, OutputInterface $output): string
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $chats = $this->chatRepository->findAll();
        $question = new ChoiceQuestion('chat: ', array_map(fn(Chat $chat) => $chat->getName(), $chats));
        $question->setValidator(fn($answer) => $answer);
        $selectedChat = $helper->ask($input, $output, $question);
        return $chats[$selectedChat]->getChatId();
    }

}