<?php declare(strict_types=1);

namespace App\Service\Browser;

use HeadlessChromium\Browser\ProcessAwareBrowser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Clip;
use Psr\Log\LoggerInterface;
use Twig\Environment;

readonly class ChartRenderService
{

    public function __construct(
        private LoggerInterface $logger,
        private Environment $twig,
    ) {

    }

    private function getChromeBinaryPath(): string
    {
        return '/usr/bin/google-chrome-stable';
    }

    private function createBrowser(): ProcessAwareBrowser
    {
        $browserFactory = new BrowserFactory();
        return $browserFactory->createBrowser([
            'debugLogger' => $this->logger,
        ]);
    }

    public function render(array $context): ?string
    {
        try {
            $browser = $this->createBrowser();
            $page = $browser->createPage();
            $page->setHtml($this->twig->render('chart/chart.html.twig', $context));
            return $page->screenshot([
                'format' => 'jpeg',
                'quality' => 100,
                'clip' => new Clip(0, 0, 420, 420),
            ])->getBase64();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return null;
        }
    }

}
