<?php

declare(strict_types=1);

namespace Pixel\NewsBundle\Reference;

use Pixel\NewsBundle\Entity\News;
use Pixel\NewsBundle\Repository\NewsRepository;
use Sulu\Bundle\ReferenceBundle\Application\Refresh\ReferenceRefresherInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class NewsReferenceRefresher implements ReferenceRefresherInterface
{
    public function __construct(
        private NewsReferenceProvider $newsReferenceProvider,
        private NewsRepository $newsRepository,
        private WebspaceManagerInterface $webspaceManager,
        private string $suluContext,
    ) {
    }

    public static function getResourceKey(): string
    {
        return News::RESOURCE_KEY;
    }

    public function refresh(): \Generator
    {
        $locales = $this->webspaceManager->getAllLocales();

        foreach ($this->newsRepository->findAll() as $news) {
            foreach ($locales as $locale) {
                $news->setLocale($locale);
                $this->newsReferenceProvider->updateReferences($news, $locale, $this->suluContext);
            }
            yield $news;
        }
    }
}
