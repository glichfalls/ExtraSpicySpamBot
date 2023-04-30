<?php

namespace App\Command;

use App\Entity\WasteDisposal\WasteDisposalDate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand('app:waste-disposal:fetch')]
class WasteDisposableCalendarImportCommand extends Command
{

    private const URL = 'https://www.wallisellen.ch/abfalldaten';

    private HttpBrowser $browser;

    public function __construct(
        private EntityManagerInterface $manager,
        HttpClientInterface $client
    )
    {
        parent::__construct();
        $this->browser = new HttpBrowser($client);
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('loading first calendar page');
        $crawler = $this->load();
        $rowCount = $this->fetchList($crawler);
        $output->writeln(sprintf('found %s rows', $rowCount));
        $this->manager->flush();
        return self::SUCCESS;
    }

    public function load(): Crawler
    {
        return $this->browser->request('GET', self::URL);
    }

    public function fetchList(Crawler $crawler): int
    {
        $rows = 0;
        $entities = $crawler->filter('#icmsTable-abfallsammlung')->attr('data-entities');
        $entities = json_decode($entities, true);
        foreach ($entities['data'] as $entity) {
            if (!array_key_exists('_anlassDate-sort', $entity)) {
                continue;
            }
            $date = new WasteDisposalDate();
            $date->setCreatedAt(new \DateTime());
            $date->setUpdatedAt(new \DateTime());
            if (array_key_exists('abfallkreisNameList', $entity)) {
                $date->setZone($entity['abfallkreisNameList']);
            } else {
                $date->setZone('');
            }
            if (preg_match('/(?<date>\d{4}-\d{2}-\d{2}).*/', $entity['_anlassDate-sort'], $matches)) {
                $date->setDate(new \DateTime($matches['date']));
            }
            if (preg_match('/<a.*>(?<name>.+)<\/a>/', $entity['name'], $matches)) {
                $date->setDescription($matches['name']);
            }
            $this->manager->persist($date);
            $rows++;
        }
        return $rows;
    }

}