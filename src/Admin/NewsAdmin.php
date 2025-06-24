<?php

declare(strict_types=1);

namespace Pixel\NewsBundle\Admin;

use Pixel\NewsBundle\Entity\News;
use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\View\ActivityViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\TogglerToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AutomationBundle\Admin\View\AutomationViewBuilderFactoryInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\View\ReferenceViewBuilderFactoryInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class NewsAdmin extends Admin
{
    public const LIST_VIEW = 'news.news.list';

    public const ADD_FORM_VIEW = 'news.news.add_form';

    public const ADD_FORM_DETAILS_VIEW = 'news.news.add_form.details';

    public const EDIT_FORM_VIEW = 'news.news.edit_form';

    public const EDIT_FORM_SEO_VIEW = 'news.news.seo.edit_form';

    public const EDIT_FORM_EXCERPT_VIEW = 'news.news.excerpt.edit_form';

    public const EDIT_FORM_DETAILS_VIEW = 'news.news.edit_form.details';

    private ViewBuilderFactoryInterface $viewBuilderFactory;

    private SecurityCheckerInterface $securityChecker;

    private WebspaceManagerInterface $webspaceManager;

    private ActivityViewBuilderFactoryInterface $activityViewBuilderFactory;

    private AutomationViewBuilderFactoryInterface $automationViewBuilderFactory;

    private ReferenceViewBuilderFactoryInterface $referenceViewBuilderFactory;

    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        WebspaceManagerInterface $webspaceManager,
        ActivityViewBuilderFactoryInterface $activityViewBuilderFactory,
        AutomationViewBuilderFactoryInterface $automationViewBuilderFactory,
        ReferenceViewBuilderFactoryInterface $referenceViewBuilderFactory
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->webspaceManager = $webspaceManager;
        $this->activityViewBuilderFactory = $activityViewBuilderFactory;
        $this->automationViewBuilderFactory = $automationViewBuilderFactory;
        $this->referenceViewBuilderFactory = $referenceViewBuilderFactory;
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(News::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $rootNavigationItem = new NavigationItem('news');
            $rootNavigationItem->setIcon('su-newspaper');
            $rootNavigationItem->setPosition(10);
            $rootNavigationItem->setView(static::LIST_VIEW);
            $navigationItemCollection->add($rootNavigationItem);
        }
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $locales = $this->webspaceManager->getAllLocales();
        $formToolbarActions = [];
        $listToolbarActions = [];
        if ($this->securityChecker->hasPermission(News::SECURITY_CONTEXT, PermissionTypes::ADD)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.add');
        }

        if ($this->securityChecker->hasPermission(News::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $formToolbarActions[] = new ToolbarAction('sulu_admin.save');
            $formToolbarActions[] = new TogglerToolbarAction(
                'news.isPublished',
                'isPublished',
                'enable',
                'disable'
            );
        }

        if ($this->securityChecker->hasPermission(News::SECURITY_CONTEXT, PermissionTypes::DELETE)) {
            $formToolbarActions[] = new ToolbarAction('sulu_admin.delete');
            $listToolbarActions[] = new ToolbarAction('sulu_admin.delete');
        }

        if ($this->securityChecker->hasPermission(News::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.export');
        }

        if ($this->securityChecker->hasPermission(News::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $viewCollection->add(
                $this->viewBuilderFactory->createListViewBuilder(static::LIST_VIEW, '/news/:locale')
                    ->setResourceKey(News::RESOURCE_KEY)
                    ->setListKey(News::LIST_KEY)
                    ->setTitle('news')
                    ->addListAdapters(['table'])
                    ->addLocales($locales)
                    ->setDefaultLocale($locales[0])
                    ->setAddView(static::ADD_FORM_VIEW)
                    ->setEditView(static::EDIT_FORM_VIEW)
                    ->addToolbarActions($listToolbarActions)
            );

            $viewCollection->add(
                $this->viewBuilderFactory->createResourceTabViewBuilder(static::ADD_FORM_VIEW, '/news/:locale/add')
                    ->setResourceKey(News::RESOURCE_KEY)
                    ->addLocales($locales)
                    ->setBackView(static::LIST_VIEW)
            );

            $viewCollection->add(
                $this->viewBuilderFactory->createFormViewBuilder(static::ADD_FORM_DETAILS_VIEW, '/details')
                    ->setResourceKey(News::RESOURCE_KEY)
                    ->setFormKey(News::FORM_KEY)
                    ->setTabTitle('sulu_admin.details')
                    ->setEditView(static::EDIT_FORM_VIEW)
                    ->addToolbarActions($formToolbarActions)
                    ->setParent(static::ADD_FORM_VIEW)
            );

            $viewCollection->add(
                $this->viewBuilderFactory->createResourceTabViewBuilder(static::EDIT_FORM_VIEW, '/news/:locale/:id')
                    ->setResourceKey(News::RESOURCE_KEY)
                    ->addLocales($locales)
                    ->setBackView(static::LIST_VIEW)
                    ->setTitleProperty('title')
            );

            $viewCollection->add(
                $this->viewBuilderFactory->createPreviewFormViewBuilder(static::EDIT_FORM_DETAILS_VIEW, '/details')
                    ->setResourceKey(News::RESOURCE_KEY)
                    ->setFormKey(News::FORM_KEY)
                    ->setTabTitle('sulu_admin.details')
                    ->addToolbarActions($formToolbarActions)
                    ->setParent(static::EDIT_FORM_VIEW)
            );

            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder(static::EDIT_FORM_SEO_VIEW, '/seo')
                    ->setResourceKey(News::RESOURCE_KEY)
                    ->setFormKey('seo')
                    ->setTabTitle('sulu_page.seo')
                    ->addToolbarActions($formToolbarActions)
                    ->setTitleVisible(true)
                    ->setTabOrder(2048)
                    ->setParent(static::EDIT_FORM_VIEW)
            );

            /* $viewCollection->add(
                 $this->viewBuilderFactory
                     ->createFormViewBuilder(static::EDIT_FORM_EXCERPT_VIEW, '/excerpt')
                     ->setResourceKey(News::RESOURCE_KEY)
                     ->setFormKey('excerpt')
                     ->setTabTitle('sulu_page.excerpt')
                     ->addToolbarActions($formToolbarActions)
                     ->setTitleVisible(true)
                     ->setTabOrder(3072)
                     ->setParent(static::EDIT_FORM_VIEW)
             );*/

            if ($this->activityViewBuilderFactory->hasActivityListPermission()) {
                $viewCollection->add(
                    $this->activityViewBuilderFactory->createActivityListViewBuilder(static::EDIT_FORM_VIEW . '.activity', '/activity', News::RESOURCE_KEY)
                        ->setParent(static::EDIT_FORM_VIEW)
                );
            }

            $viewCollection->add(
                $this->automationViewBuilderFactory->createTaskListViewBuilder(self::EDIT_FORM_VIEW . "automation", "/automation/:locale", News::class)
                    ->addLocales($locales)
                    ->setDefaultLocale($locales[0])
                    ->setParent(self::EDIT_FORM_VIEW)
            );

            if ($this->referenceViewBuilderFactory->hasReferenceListPermission()) {
                $viewCollection->add(
                    $this->referenceViewBuilderFactory->createReferenceListViewBuilder(static::EDIT_FORM_VIEW . ".insights.references", "/references", News::RESOURCE_KEY)
                        ->setParent(static::EDIT_FORM_VIEW)
                );
            }
        }
    }

    /**
     * @return mixed[]
     */
    public function getSecurityContexts(): array
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'News' => [
                    News::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                ],
            ],
        ];
    }
}
