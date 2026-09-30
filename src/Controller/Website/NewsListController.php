<?php

declare(strict_types=1);

namespace Pixel\NewsBundle\Controller\Website;

use Pixel\NewsBundle\Repository\NewsRepository;
use Sulu\Bundle\WebsiteBundle\Resolver\TemplateAttributeResolverInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NewsListController extends AbstractController
{
    private const PAGE_SIZE = 12;

    public function __construct(
        private NewsRepository $newsRepository,
        private TemplateAttributeResolverInterface $templateAttributeResolver,
    ) {
    }

    public function indexAction(Request $request): Response
    {
        $locale = $request->getLocale();
        $page = max(1, (int) $request->query->get('page', 1));
        $categoryIds = $this->parseCategoryIds($request);

        $filters = [];
        if (!empty($categoryIds)) {
            $filters['categories'] = $categoryIds;
            $filters['categoryOperator'] = 'or';
        }

        $options = ['page' => $page - 1];

        $news = $this->newsRepository->findByFilters($filters, 1, self::PAGE_SIZE, self::PAGE_SIZE, $locale, $options);
        $total = $this->newsRepository->countPublished($locale, $categoryIds);
        $totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));

        $parameters = $this->templateAttributeResolver->resolve([
            'news' => $news,
            'pagination' => [
                'page' => $page,
                'totalPages' => $totalPages,
                'total' => $total,
                'limit' => self::PAGE_SIZE,
                'hasNextPage' => $page < $totalPages,
                'hasPreviousPage' => $page > 1,
            ],
            'currentCategories' => $categoryIds,
        ]);

        return new Response($this->renderView('@News/news_list.html.twig', $parameters));
    }

    /**
     * @return int[]
     */
    private function parseCategoryIds(Request $request): array
    {
        $param = $request->query->get('category', '');
        if (!$param) {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', (string) $param))));
    }
}
