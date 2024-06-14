<?php

namespace Pixel\NewsBundle\Routing;

use Pixel\NewsBundle\Controller\Website\NewsController;
use Pixel\NewsBundle\Entity\News;
use Pixel\NewsBundle\Repository\NewsRepository;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;

class NewsRouteDefaultsProvider implements RouteDefaultsProviderInterface
{
    private NewsRepository $newsRepository;

    public function __construct(NewsRepository $newsRepository)
    {
        $this->newsRepository = $newsRepository;
    }

    /**
     * @return mixed[]
     */
    public function getByEntity($entityClass, $id, $locale, $object = null)
    {
        return [
            '_controller' => NewsController::class . '::indexAction',
            'news' => $object ?: $this->newsRepository->findById((int) $id, $locale),
        ];
    }

    public function isPublished($entityClass, $id, $locale)
    {
        $actu = $this->newsRepository->findById((int) $id, $locale);
        if (!$this->supports($entityClass) || !$actu instanceof News) {
            return false;
        }
        return $actu->isPublished();
    }

    public function supports($entityClass)
    {
        return News::class === $entityClass;
    }
}
