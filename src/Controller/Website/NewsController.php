<?php

declare(strict_types=1);

namespace Pixel\NewsBundle\Controller\Website;

use Pixel\NewsBundle\Entity\News;
use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\WebsiteBundle\Resolver\TemplateAttributeResolverInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class NewsController extends AbstractController
{
    private TemplateAttributeResolverInterface $templateAttributeResolver;

    private RouteRepositoryInterface $routeRepository;

    private WebspaceManagerInterface $webspaceManager;

    private Environment $twig;

    public function __construct(TemplateAttributeResolverInterface $templateAttributeResolver, RouteRepositoryInterface $routeRepository, WebspaceManagerInterface $webspaceManager, Environment $twig)
    {
        $this->templateAttributeResolver = $templateAttributeResolver;
        $this->routeRepository = $routeRepository;
        $this->webspaceManager = $webspaceManager;
        $this->twig = $twig;
    }

    /**
     * @param array<mixed> $attributes
     * @throws \Exception
     */
    public function indexAction(News $news, array $attributes = [], bool $preview = false, bool $partial = false): Response
    {
        $seo = $news->getSeo() ?: [];

        if (empty($seo['title'])) {
            $seo['title'] = $news->getTitle();
            $news->setSeo($seo);
        }

        $parameters = $this->templateAttributeResolver->resolve([
            'news' => $news,
            'localizations' => $this->getLocalizationsArrayForEntity($news),
        ]);

        if ($partial) {
            return $this->renderBlock(
                '@News/news.html.twig',
                'content',
                $parameters
            );
        } elseif ($preview) {
            $content = $this->renderPreview(
                '@News/news.html.twig',
                $parameters
            );
        } else {
            if (!$news->isPublished()) {
                throw $this->createNotFoundException();
            }
            $content = $this->renderView(
                '@News/news.html.twig',
                $parameters
            );
        }

        return new Response($content);
    }

    /**
     * @return array<string, array<mixed>>
     */
    protected function getLocalizationsArrayForEntity(News $entity): array
    {
        $routes = $this->routeRepository->findAllByEntity(News::class, (string) $entity->getId());

        $localizations = [];
        foreach ($routes as $route) {
            $url = $this->webspaceManager->findUrlByResourceLocator(
                $route->getPath(),
                null,
                $route->getLocale()
            );

            $localizations[$route->getLocale()] = [
                'locale' => $route->getLocale(),
                'url' => $url,
            ];
        }

        return $localizations;
    }

    /**
     * @param array<mixed> $parameters
     */
    protected function renderPreview(string $view, array $parameters = []): string
    {
        $parameters['previewParentTemplate'] = $view;
        $parameters['previewContentReplacer'] = Preview::CONTENT_REPLACER;

        return $this->renderView('@SuluWebsite/Preview/preview.html.twig', $parameters);
    }
}
