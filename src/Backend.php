<?php
/**
 * @brief pacKman, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pacKman;

use dcAdmin;
use dcCore;
use dcFavorites;
use dcPage;
use dcNsProcess;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init == defined('DC_CONTEXT_ADMIN')
            && dcCore::app()->auth?->isSuperAdmin()
            && My::phpCompliant();

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->addBehavior('adminDashboardFavoritesV2', function (dcFavorites $favs): void {
            $favs->register(My::id(), [
                'title'      => My::name(),
                'url'        => dcCore::app()->adminurl?->get('admin.plugin.' . My::id(), [], '#packman-repository-repository'),
                'small-icon' => [dcPage::getPF(My::id() . '/icon.svg'), dcPage::getPF(My::id() . '/icon-dark.svg')],
                'large-icon' => [dcPage::getPF(My::id() . '/icon.svg'), dcPage::getPF(My::id() . '/icon-dark.svg')],
                //'permissions' => dcCore::app()->auth->isSuperAdmin(),
            ]);
        });

        dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
            My::name(),
            dcCore::app()->adminurl?->get('admin.plugin.' . My::id()) . '#packman-repository-repository',
            [dcPage::getPF(My::id() . '/icon.svg'), dcPage::getPF(My::id() . '/icon-dark.svg')],
            preg_match('/' . preg_quote((string) dcCore::app()->adminurl?->get('admin.plugin.' . My::id())) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth?->isSuperAdmin()
        );

        return true;
    }
}
