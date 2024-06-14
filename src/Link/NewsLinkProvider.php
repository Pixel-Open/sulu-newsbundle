<?php

declare(strict_types=1);

namespace Pixel\NewsBundle\Link;

use Pixel\NewsBundle\Entity\News;
use Pixel\NewsBundle\Repository\NewsRepository;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NewsLinkProvider implements LinkProviderInterface
{
    private NewsRepository $newsRepository;

    private TranslatorInterface $translator;

    public function __construct(NewsRepository $newsRepository, TranslatorInterface $translator)
    {
        $this->newsRepository = $newsRepository;
        $this->translator = $translator;
    }

    public function getConfiguration()
    {
        return LinkConfigurationBuilder::create()
            ->setTitle($this->translator->trans('news'))
            ->setResourceKey(News::RESOURCE_KEY) // the resourceKey of the entity that should be loaded
            ->setListAdapter('table')
            ->setDisplayProperties(['title'])
            ->setOverlayTitle($this->translator->trans('news'))
            ->setEmptyText($this->translator->trans('news.emptyNews'))
            ->setIcon('su-newspaper')
            ->getLinkConfiguration();
    }

    public function preload(array $hrefs, $locale, $published = true): array
    {
        $result = [];
        if (0 === count($hrefs)) {
            return $result;
        }

        $items = $this->newsRepository->findBy([
            'id' => $hrefs,
        ]); // load items by id
        foreach ($items as $item) {
            $result[] = new LinkItem($item->getId(), $item->getTitle(), $item->getRoutePath(), $item->isPublished()); // create link-item foreach item
        }

        return $result;
    }
}
