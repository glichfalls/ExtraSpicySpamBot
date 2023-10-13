<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    BoShurik\TelegramBotBundle\BoShurikTelegramBotBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class => ['dev' => true],
    Rollbar\Symfony\RollbarBundle\RollbarBundle::class => ['all' => true],
    Cron\CronBundle\CronCronBundle::class => ['all' => true],
    Sentry\SentryBundle\SentryBundle::class => ['prod' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Nelmio\CorsBundle\NelmioCorsBundle::class => ['all' => true],
    ApiPlatform\Symfony\Bundle\ApiPlatformBundle::class => ['all' => true],
    Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle::class => ['all' => true],
    League\FlysystemBundle\FlysystemBundle::class => ['all' => true],
];
