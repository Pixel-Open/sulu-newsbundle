<?php

declare(strict_types=1);

namespace Pixel\NewsBundle\Sitemap;

use Pixel\NewsBundle\Repository\NewsRepository;
use Sulu\Bundle\WebsiteBundle\Sitemap\Sitemap;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class NewsSitemapProvider implements SitemapProviderInterface
{
    private NewsRepository $newsRepository;

    private WebspaceManagerInterface $webspaceManager;

    /**
     * @var array<string>
     */
    private array $locales = [];

    public function __construct(NewsRepository $newsRepository, WebspaceManagerInterface $webspaceManager)
    {
        $this->newsRepository = $newsRepository;
        $this->webspaceManager = $webspaceManager;
    }

    public function build($page, $scheme, $host): array
    {
        $locale = $this->getLocaleByHost($host);
        $result = [];
        foreach ($this->newsRepository->findAllForSitemap((int) $page, (int) self::PAGE_SIZE) as $news) {
            //$news->setLocale($locale);
            $result[] = new SitemapUrl(
                $scheme . '://' . $host . $news->getRoutePath(),
                $news->getLocale(),
                $news->getLocale(),
                new \DateTime()
            );
        }

        return $result;
    }

    private function getLocaleByHost(string $host): ?string
    {
        if (!\array_key_exists($host, $this->locales)) {
            $portalInformation = $this->webspaceManager->getPortalInformations();
            foreach ($portalInformation as $hostName => $portal) {
                if ($hostName === $host) {
                    $this->locales[$host] = $portal->getLocale();
                }
            }
        }
        if (isset($this->locales[$host])) {
            return $this->locales[$host];
        }
        return null;
    }

    public function createSitemap($scheme, $host): Sitemap
    {
        return new Sitemap($this->getAlias(), $this->getMaxPage($scheme, $host));
    }

    public function getAlias(): string
    {
        return 'news';
    }

    public function getMaxPage($scheme, $host)
    {
        return (int) ceil($this->newsRepository->countForSitemap() / self::PAGE_SIZE);
    }
}
