<?php

declare(strict_types=1);

namespace Pixel\NewsBundle\Admin;

use Pixel\NewsBundle\Entity\Setting;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class SettingAdmin extends Admin
{
    public const TAB_VIEW = 'news.settings';
    public const FORM_VIEW = 'news.settings.form';

    public function __construct(
        private readonly ViewBuilderFactoryInterface $viewBuilderFactory,
        private readonly SecurityCheckerInterface $securityChecker
    ) {
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if (!$this->hasEditPermission()) {
            return;
        }

        $navigationItem = new NavigationItem('news.settings');
        $navigationItem->setPosition(4);
        $navigationItem->setView(self::TAB_VIEW);

        $navigationItemCollection->get(Admin::SETTINGS_NAVIGATION_ITEM)->addChild($navigationItem);
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        if (!$this->hasEditPermission()) {
            return;
        }

        $this->addTabView($viewCollection);
        $this->addFormView($viewCollection);
    }

    public function getSecurityContexts(): array
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'Setting' => [
                    Setting::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::EDIT,
                    ],
                ],
            ],
        ];
    }

    private function hasEditPermission(): bool
    {
        return $this->securityChecker->hasPermission(
            Setting::SECURITY_CONTEXT,
            PermissionTypes::EDIT
        );
    }

    private function addTabView(ViewCollection $viewCollection): void
    {
        $viewCollection->add(
            $this->viewBuilderFactory
                ->createResourceTabViewBuilder(self::TAB_VIEW, '/news-settings/:id')
                ->setResourceKey(Setting::RESOURCE_KEY)
                ->setAttributeDefault('id', '-')
        );
    }

    private function addFormView(ViewCollection $viewCollection): void
    {
        $viewCollection->add(
            $this->viewBuilderFactory
                ->createFormViewBuilder(self::FORM_VIEW, '/details')
                ->setResourceKey(Setting::RESOURCE_KEY)
                ->setFormKey(Setting::FORM_KEY)
                ->setTabTitle('sulu_admin.details')
                ->addToolbarActions([new ToolbarAction('sulu_admin.save')])
                ->setParent(self::TAB_VIEW)
        );
    }
}