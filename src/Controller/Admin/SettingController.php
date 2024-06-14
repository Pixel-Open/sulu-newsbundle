<?php

declare(strict_types=1);

namespace Pixel\NewsBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Pixel\NewsBundle\Entity\Setting;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @RouteResource("news-settings")
 */
class SettingController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    private EntityManagerInterface $entityManager;

    private MediaManagerInterface $mediaManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        MediaManagerInterface $mediaManager,
        ViewHandlerInterface $viewHandler,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        $this->entityManager = $entityManager;
        $this->mediaManager = $mediaManager;
        parent::__construct($viewHandler, $tokenStorage);
    }

    public function getAction(): Response
    {
        $applicationSetting = $this->entityManager->getRepository(Setting::class)->findOneBy([]);
        return $this->handleView($this->view($applicationSetting ?: new Setting()));
    }

    public function putAction(Request $request): Response
    {
        $applicationSetting = $this->entityManager->getRepository(Setting::class)->findOneBy([]);
        if (!$applicationSetting) {
            $applicationSetting = new Setting();
            $this->entityManager->persist($applicationSetting);
        }
        $this->mapDataToEntity($request->request->all(), $applicationSetting);
        $this->entityManager->flush();
        return $this->handleView($this->view($applicationSetting));
    }

    /**
     * @param array<mixed> $data
     */
    public function mapDataToEntity(array $data, Setting $entity): void
    {
        $defaultImageId = $data['defaultImage']['id'] ?? null;

        $entity->setDefaultImage($defaultImageId ? $this->mediaManager->getEntityById($defaultImageId) : null);
    }

    public function getSecurityContext()
    {
        return Setting::SECURITY_CONTEXT;
    }
}
