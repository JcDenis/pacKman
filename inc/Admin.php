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

/* dotclear ns */
use dcAdmin;
use dcCore;
use dcFavorites;
use dcPage;

class Admin
{
    private static $init = false;

    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            dcCore::app()->blog->settings->addNamespace(Core::id());
            self::$init = true;
        }

        return self::$init;
    }

    public static function process()
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->addBehavior('adminDashboardFavoritesV2', function (dcFavorites $favs): void {
            $favs->register(Core::id(), [
                'title'      => __('Packages repository'),
                'url'        => dcCore::app()->adminurl->get('admin.plugin.' . Core::id(), [], '#packman-repository-repository'),
                'small-icon' => [dcPage::getPF(Core::id() . '/icon.svg'), dcPage::getPF(Core::id() . '/icon-dark.svg')],
                'large-icon' => [dcPage::getPF(Core::id() . '/icon.svg'), dcPage::getPF(Core::id() . '/icon-dark.svg')],
                //'permissions' => dcCore::app()->auth->isSuperAdmin(),
            ]);
        });

        dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
            __('Packages repository'),
            dcCore::app()->adminurl->get('admin.plugin.' . Core::id()) . '#packman-repository-repository',
            [dcPage::getPF(Core::id() . '/icon.svg'), dcPage::getPF(Core::id() . '/icon-dark.svg')],
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.' . Core::id())) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth->isSuperAdmin()
        );
    }
}
