<?php

declare(strict_types=1);

namespace Pixel\NewsBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Pixel\NewsBundle\Common\DoctrineListRepresentationFactory;
use Pixel\NewsBundle\Domain\Event\NewsCreatedEvent;
use Pixel\NewsBundle\Domain\Event\NewsModifiedEvent;
use Pixel\NewsBundle\Domain\Event\NewsRemovedEvent;
use Pixel\NewsBundle\Entity\News;
use Pixel\NewsBundle\Repository\NewsRepository;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\SecuredControllerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

//use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
//use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;

/**
 * @RouteResource("news")
 */
class NewsController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    use RequestParametersTrait;

    private DoctrineListRepresentationFactory $doctrineListRepresentationFactory;

    private EntityManagerInterface $entityManager;

    private MediaManagerInterface $mediaManager;

    private CategoryManagerInterface $categoryManager;

    private WebspaceManagerInterface $webspaceManager;

    private RouteManagerInterface $routeManager;

    private RouteRepositoryInterface $routeRepository;

    private TrashManagerInterface $trashManager;

    private DomainEventCollectorInterface $domainEventCollector;

    private NewsRepository $repository;

    public function __construct(
        DoctrineListRepresentationFactory $doctrineListRepresentationFactory,
        EntityManagerInterface $entityManager,
        MediaManagerInterface $mediaManager,
        ViewHandlerInterface $viewHandler,
        CategoryManagerInterface $categoryManager,
        WebspaceManagerInterface $webspaceManager,
        RouteManagerInterface $routeManager,
        RouteRepositoryInterface $routeRepository,
        TrashManagerInterface $trashManager,
        DomainEventCollectorInterface $domainEventCollector,
        NewsRepository $repository,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        $this->doctrineListRepresentationFactory = $doctrineListRepresentationFactory;
        $this->entityManager = $entityManager;
        $this->mediaManager = $mediaManager;
        $this->categoryManager = $categoryManager;
        $this->webspaceManager = $webspaceManager;
        $this->routeManager = $routeManager;
        $this->routeRepository = $routeRepository;
        $this->trashManager = $trashManager;
        $this->domainEventCollector = $domainEventCollector;
        $this->repository = $repository;

        parent::__construct($viewHandler, $tokenStorage);
    }

    public function cgetAction(Request $request): Response
    {
        $locale = $request->query->get('locale');
        $listRepresentation = $this->doctrineListRepresentationFactory->createDoctrineListRepresentation(
            News::RESOURCE_KEY,
            [],
            [
                'locale' => $locale,
            ]
        );

        return $this->handleView($this->view($listRepresentation));
    }

    public function getAction(int $id, Request $request): Response
    {
        $item = $this->load($id, $request);
        if (!$item) {
            throw new NotFoundHttpException();
        }

        if ($item->getTitle() === null and $item->getDefaultLocale()) {
            $request->setMethod($item->getDefaultLocale());
            $item = $this->load($id, $request, $item->getDefaultLocale());
        }

        return $this->handleView($this->view($item));
    }

    protected function load(int $id, Request $request, string $defaultLocal = null): ?News
    {
        return $this->repository->findById($id, ($defaultLocal) ? $defaultLocal : (string) $this->getLocale($request));
    }

    public function putAction(Request $request, int $id): Response
    {
        $item = $this->load($id, $request);
        if (!$item) {
            throw new NotFoundHttpException();
        }

        $data = $request->request->all();

        $this->mapDataToEntity($data, $item);
        $this->updateRoutesForEntity($item, $request->query->get('locale'));
        $this->domainEventCollector->collect(
            new NewsModifiedEvent($item, $data)
        );
        $this->entityManager->flush();
        $this->save($item);

        return $this->handleView($this->view($item));
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function mapDataToEntity(array $data, News $entity): void
    {
        $coverId = $data['cover']['id'] ?? null;
        $categoryId = (isset($data['category']['id'])) ? $data['category']['id'] : $data['category'];
        $isPublished = $data['isPublished'] ?? false;
        $seo = (isset($data['ext']['seo'])) ? $data['ext']['seo'] : null;
        $publishedAt = $data['publishedAt'] ?? null;

        $entity->setTitle($data['title']);
        $entity->setRoutePath($data['routePath']);
        $entity->setIsPublished($isPublished);
        $entity->setCover($coverId ? $this->mediaManager->getEntityById($coverId) : null);
        $entity->setCategory($this->categoryManager->findById($categoryId));
        $entity->setContent($data['content']);
        $entity->setSeo($seo);
        $entity->setPublishedAt($publishedAt ? new \DateTimeImmutable($publishedAt) : new \DateTimeImmutable());
    }

    protected function updateRoutesForEntity(News $entity, string $locale): void
    {
        $this->routeManager->createOrUpdateByAttributes(
            News::class,
            (string) $entity->getId(),
            $locale,
            $entity->getRoutePath(),
        );
    }

    protected function save(News $news): void
    {
        $this->repository->save($news);
    }

    public function postAction(Request $request): Response
    {
        $item = $this->create($request);
        $data = $request->request->all();
        $this->mapDataToEntity($data, $item);
        $this->save($item);
        $this->updateRoutesForEntity($item, $request->query->get('locale'));
        $this->domainEventCollector->collect(
            new NewsCreatedEvent($item, $data)
        );
        $this->entityManager->flush();
        return $this->handleView($this->view($item, 201));
    }

    protected function create(Request $request): News
    {
        return $this->repository->create((string) $this->getLocale($request));
    }

    public function deleteAction(int $id): Response
    {
        /** @var News $actu */
        $actu = $this->entityManager->getRepository(News::class)->find($id);
        $actuTitle = $actu->getTitle();
        if ($actu) {
            $this->trashManager->store(News::RESOURCE_KEY, $actu);
            $this->entityManager->remove($actu);
            $this->removeRoutesForEntity($actu);
            $this->domainEventCollector->collect(
                new NewsRemovedEvent($id, $actuTitle)
            );
            $this->entityManager->flush();
        }

        return $this->handleView($this->view(null, 204));
    }

    protected function removeRoutesForEntity(News $entity): void
    {
        // remove route for all locales of the application because event entity is not localized
        foreach ($this->webspaceManager->getAllLocales() as $locale) {
            $routes = $this->routeRepository->findAllByEntity(
                News::class,
                (string) $entity->getId(),
                $locale
            );

            foreach ($routes as $route) {
                $this->routeRepository->remove($route);
            }
        }
    }

    public function getSecurityContext(): string
    {
        return News::SECURITY_CONTEXT;
    }

    /**
     * @Rest\Post("/news/{id}")
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws EntityNotFoundException
     */
    public function postTriggerAction(int $id, Request $request): Response
    {
        $action = $this->getRequestParameter($request, 'action', true);
        $locale = $this->getRequestParameter($request, 'locale', true);

        try {
            switch ($action) {
                case 'enable':
                    /** @var News $item */
                    $item = $this->entityManager->getRepository(News::class)->find($id);
                    $item->setLocale($locale);
                    $item->setIsPublished(true);
                    $this->entityManager->persist($item);
                    $this->entityManager->flush();
                    break;
                case 'disable':
                    /** @var News $item */
                    $item = $this->entityManager->getRepository(News::class)->find($id);
                    $item->setLocale($locale);
                    $item->setIsPublished(false);
                    $this->entityManager->persist($item);
                    $this->entityManager->flush();
                    break;
                default:
                    throw new BadRequestHttpException(sprintf('Unknown action "%s".', $action));
            }
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
            return $this->handleView($view);
        }

        return $this->handleView($this->view($item));
    }
}
