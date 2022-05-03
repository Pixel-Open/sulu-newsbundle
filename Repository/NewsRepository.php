<?php

namespace Pixel\NewsBundle\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Pixel\NewsBundle\Entity\News;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryTrait;

class NewsRepository extends EntityRepository implements DataProviderRepositoryInterface
{
    use DataProviderRepositoryTrait;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, new ClassMetadata(News::class));
    }

    public function create(string $locale): News
    {
        $news = new News();
        $news->setLocale($locale);
        return $news;
    }

    public function save(News $news): void
    {
        $this->getEntityManager()->persist($news);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id, string $locale): ?News
    {
        $news = $this->find($id);
        if (!$news) {
            return null;
        }
        $news->setLocale($locale);
        return $news;
    }

    public function findAllForSitemap(int $page, int $limit): array
    {
        $query = $this->createQueryBuilder('n')
            ->leftJoin('n.translations', 't')
            ->where('t.isPublished = 1');
        return $query->getQuery()->getResult();
    }

    public function countForSitemap()
    {
        $query = $this->createQueryBuilder('n')
            ->select('count(n)');
        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function appendJoins(QueryBuilder $queryBuilder, $alias, $locale)
    {
        $queryBuilder->addSelect('category')->leftJoin($alias . '.category', 'category');
    }

    public function appendCategoriesRelation(QueryBuilder $queryBuilder, $alias)
    {
        return $alias . '.category';
    }

    public function findByFilters($filters, $page, $pageSize, $limit, $locale, $options = []): array
    {
        $entities = $this->getPublishedNews($filters, $locale);

        return \array_map(
            function (News $entity) use ($locale) {
                return $entity->setLocale($locale);
            },
            $entities
        );
    }

    public function getPublishedNews(array $filters, string $locale): array
    {
        $query = $this->createQueryBuilder('n')
            ->leftJoin('n.translations', 'translation')
            ->where('translation.isPublished = 1');
        if (isset($filters['sortBy'])) $query->orderBy($filters['sortBy'], $filters['sortMethod']);
        $news = $query->getQuery()->getResult();
        if (!$news) {
            return [];
        }
        return $news;
    }

    protected function appendSortByJoins(QueryBuilder $queryBuilder, string $alias, string $locale): void
    {
        $queryBuilder->innerJoin($alias . '.translations', 'translation', Join::WITH, 'translation.locale = :locale');
        $queryBuilder->setParameter('locale', $locale);
    }
}