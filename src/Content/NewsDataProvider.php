<?php

declare(strict_types=1);

namespace Pixel\NewsBundle\Content;

use Pixel\NewsBundle\Repository\NewsRepository;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Serializer\ArraySerializerInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\ItemInterface;
use Sulu\Component\SmartContent\Orm\BaseDataProvider;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class NewsDataProvider extends BaseDataProvider
{
    private RequestStack $requestStack;

    private NewsRepository $newsRepository;

    public function __construct(DataProviderRepositoryInterface $repository, ArraySerializerInterface $serializer, RequestStack $requestStack, NewsRepository $newsRepository)
    {
        parent::__construct($repository, $serializer);
        $this->newsRepository = $newsRepository;
        $this->requestStack = $requestStack;
    }

    public function getConfiguration()
    {
        if (!$this->configuration) {
            $this->configuration = self::createConfigurationBuilder()
                ->enableLimit()
                ->enablePagination()
                ->enablePresentAs()
                ->enableCategories()
                ->enableSorting([
                    [
                        'column' => 'translation.title',
                        'title' => 'news.title',
                    ],
                    [
                        'column' => 'translation.publishedAt',
                        'title' => 'news.publishedAt',
                    ],
                ])
                ->getConfiguration();
        }

        return $this->configuration;
    }

    /**
     * @param array<mixed> $filters
     * @param array<mixed> $propertyParameter
     * @param array<mixed> $options
     * @param int $limit
     * @param int $page
     * @param int $pageSize
     * @return DataProviderResult
     */
    public function resolveResourceItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        $locale = $options['locale'];
        $request = $this->requestStack->getCurrentRequest();
        $options['page'] = $request->get('p');
        $news = $this->newsRepository->findByFilters($filters, $page, $pageSize, $limit, $locale, $options);
        return new DataProviderResult($news, $this->newsRepository->hasNextPage($filters, $page, $pageSize, $limit, $locale, $options));
    }

    /**
     * Decorates result as data item.
     * @param array<mixed> $data
     *
     * @return ItemInterface[]
     */
    protected function decorateDataItems(array $data)
    {
        return array_map(
            function ($item) {
                return new NewsDataItem($item);
            },
            $data
        );
    }

    /**
     * Returns additional options for query creation.
     *
     * @param PropertyParameter[] $propertyParameter
     * @param array<mixed> $options
     *
     * @return array<mixed>
     */
    protected function getOptions(array $propertyParameter, array $options = [])
    {
        $request = $this->requestStack->getCurrentRequest();
        $result = [
            'page' => $request->get('p'),
            'type' => $request->get('type'),
        ];

        return array_filter($result);
    }
}
