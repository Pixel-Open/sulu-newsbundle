<?php

namespace Pixel\NewsBundle\Twig;

use Pixel\NewsBundle\Entity\News;
use Pixel\NewsBundle\Repository\NewsRepository;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NewsExtension extends AbstractExtension
{
    private NewsRepository $newsRepository;

    private Environment $environment;

    public function __construct(NewsRepository $newsRepository, Environment $environment)
    {
        $this->newsRepository = $newsRepository;
        $this->environment = $environment;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction("get_latest_news_html", [$this, "getLatestNewsHtml"], [
                "is_safe" => ["html"],
            ]),
            new TwigFunction("get_latest_news", [$this, "getLatestNews"]),
        ];
    }

    public function getLatestNewsHtml(int $limit = 3, string $locale = 'fr'): string
    {
        $news = $this->newsRepository->findByFilters([], 0, $limit, $limit, $locale);
        ;
        return $this->environment->render("@News/twig/news.html.twig", [
            "news" => $news,
        ]);
    }

    /**
     * @return array<News>
     */
    public function getLatestNews(int $limit = 3, string $locale = 'fr'): array
    {
        return $this->newsRepository->findByFilters([], 0, $limit, $limit, $locale);
    }
}
