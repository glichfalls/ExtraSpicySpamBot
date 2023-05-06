<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Cron\CronBundle\Entity\CronJob;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230506150545 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function getDescription(): string
    {
        return 'Create Cron Jobs';
    }

    public function up(Schema $schema): void
    {

    }

    public function postUp(Schema $schema): void
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $job = new CronJob();
        $job->setName('Waste Disposal reminder');
        $job->setCommand('app:waste-disposal:reminder');
        $job->setSchedule('0 8,20 * * *');
        $job->setDescription('Reminds to take out the waste disposal');
        $job->setEnabled(true);
        $em->persist($job);
        $job = new CronJob();
        $job->setName('Weekday Sailor Memes');
        $job->setCommand('telegram:memes:weekday-sailor');
        $job->setSchedule('0 8 * * 1-4');
        $job->setDescription('sailor memes on weekdays');
        $job->setEnabled(true);
        $em->persist($job);
        $job = new CronJob();
        $job->setName('Friday Sailor Meme');
        $job->setCommand('telegram:memes:weekday-sailor');
        $job->setSchedule('0 16 * * 5');
        $job->setDescription('sailor meme on friday');
        $job->setEnabled(true);
        $em->persist($job);
        $job = new CronJob();
        $job->setName('Weekend Sailor Memes');
        $job->setCommand('telegram:memes:weekend-sailor');
        $job->setSchedule('0 10 * * 6,7');
        $job->setDescription('sailor memes on weekends');
        $job->setEnabled(true);
        $em->persist($job);
        $em->flush();
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
