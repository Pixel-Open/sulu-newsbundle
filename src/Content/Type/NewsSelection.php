<?php

declare(strict_types=1);

namespace Pixel\NewsBundle\Content\Type;

use Doctrine\ORM\EntityManagerInterface;
use Pixel\NewsBundle\Entity\News;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;

class NewsSelection extends SimpleContentType
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct('news_selection', []);
    }

    /**
     * @return News[]
     */
    public function getContentData(PropertyInterface $property): array
    {
        $ids = $property->getValue();

        if (empty($ids)) {
            return [];
        }

        $news = $this->entityManager->getRepository(News::class)->findBy([
            'id' => $ids,
        ]);

        $idPositions = array_flip($ids);
        usort($news, function (News $a, News $b) use ($idPositions) {
            return $idPositions[$a->getId()] - $idPositions[$b->getId()];
        });

        return $news;
    }

    /**
     * @return array<string, array<int>|null>
     */
    public function getViewData(PropertyInterface $property): array
    {
        return [
            'ids' => $property->getValue(),
        ];
    }
}
