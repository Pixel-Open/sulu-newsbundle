<?php

declare(strict_types=1);

namespace Pixel\NewsBundle\Trash;

use Doctrine\ORM\EntityManagerInterface;
use Pixel\NewsBundle\Admin\NewsAdmin;
use Pixel\NewsBundle\Domain\Event\NewsRestoredEvent;
use Pixel\NewsBundle\Entity\News;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\TrashBundle\Application\DoctrineRestoreHelper\DoctrineRestoreHelperInterface;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfiguration;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfigurationProviderInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;

class NewsTrashItemHandler implements StoreTrashItemHandlerInterface, RestoreTrashItemHandlerInterface, RestoreConfigurationProviderInterface
{
    private TrashItemRepositoryInterface $trashItemRepository;

    private EntityManagerInterface $entityManager;

    private DoctrineRestoreHelperInterface $doctrineRestoreHelper;

    private DomainEventCollectorInterface $domainEventCollector;

    public function __construct(
        TrashItemRepositoryInterface $trashItemRepository,
        EntityManagerInterface $entityManager,
        DoctrineRestoreHelperInterface $doctrineRestoreHelper,
        DomainEventCollectorInterface $domainEventCollector
    ) {
        $this->trashItemRepository = $trashItemRepository;
        $this->entityManager = $entityManager;
        $this->doctrineRestoreHelper = $doctrineRestoreHelper;
        $this->domainEventCollector = $domainEventCollector;
    }

    public static function getResourceKey(): string
    {
        return News::RESOURCE_KEY;
    }

    public function store(object $resource, array $options = []): TrashItemInterface
    {
        $cover = $resource->getCover();
        $category = $resource->getCategory();

        $data = [
            "title" => $resource->getTitle(),
            "slug" => $resource->getRoutePath(),
            "isPublished" => $resource->isPublished(),
            "seo" => $resource->getSeo(),
            "content" => $resource->getContent(),
            "coverId" => $cover ? $cover->getId() : null,
            "categoryId" => $category ? $category->getId() : null,
            "publishedAt" => $resource->getPublishedAt(),
        ];
        return $this->trashItemRepository->create(
            News::RESOURCE_KEY,
            (string) $resource->getId(),
            $resource->getTitle(),
            $data,
            null,
            $options,
            News::SECURITY_CONTEXT,
            null,
            null
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $data = $trashItem->getRestoreData();
        $actuId = (int) $trashItem->getResourceId();
        $actu = new News();
        $actu->setTitle($data['title']);
        $actu->setRoutePath($data['slug']);
        $actu->setIsPublished($data['isPublished']);
        $actu->setSeo($data['seo']);
        $actu->setContent($data['content']);
        if ($data['coverId']) {
            $actu->setCover($this->entityManager->find(MediaInterface::class, $data['coverId']));
        }
        $actu->setCategory($this->entityManager->find(CategoryInterface::class, $data['categoryId']));
        if (isset($data['publishedAt'])) {
            $actu->setPublishedAt(new \DateTimeImmutable($data['publishedAt']['date']));
        }
        $this->domainEventCollector->collect(
            new NewsRestoredEvent($actu, $data)
        );

        $this->doctrineRestoreHelper->persistAndFlushWithId($actu, $actuId);
        $this->createRoute($this->entityManager, $actuId, $actu->getRoutePath(), News::class);
        $this->entityManager->flush();
        return $actu;
    }

    private function createRoute(EntityManagerInterface $manager, int $id, string $slug, string $class): void
    {
        $route = new Route();
        $route->setPath($slug);
        $route->setLocale('fr');
        $route->setEntityClass($class);
        $route->setEntityId((string) $id);
        $route->setHistory(false);
        $route->setCreated(new \DateTime());
        $route->setChanged(new \DateTime());
        $manager->persist($route);
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration(
            null,
            NewsAdmin::EDIT_FORM_VIEW,
            [
                'id' => 'id',
            ]
        );
    }
}
