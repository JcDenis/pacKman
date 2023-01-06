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
    protected static $init = false;

    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            self::$init = true;
        }

        return self::$init;
    }

    public static function process(): ?bool
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->addBehavior('adminDashboardFavoritesV2', function (dcFavorites $favs): void {
            $favs->register(basename(__NAMESPACE__), [
                'title'      => __('Packages repository'),
                'url'        => dcCore::app()->adminurl->get('admin.plugin.' . basename(__NAMESPACE__), [], '#packman-repository-repository'),
                'small-icon' => [dcPage::getPF(basename(__NAMESPACE__) . '/icon.svg'), dcPage::getPF(basename(__NAMESPACE__) . '/icon-dark.svg')],
                'large-icon' => [dcPage::getPF(basename(__NAMESPACE__) . '/icon.svg'), dcPage::getPF(basename(__NAMESPACE__) . '/icon-dark.svg')],
                //'permissions' => dcCore::app()->auth->isSuperAdmin(),
            ]);
        });

        dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
            __('Packages repository'),
            dcCore::app()->adminurl->get('admin.plugin.' . basename(__NAMESPACE__)) . '#packman-repository-repository',
            [dcPage::getPF(basename(__NAMESPACE__) . '/icon.svg'), dcPage::getPF(basename(__NAMESPACE__) . '/icon-dark.svg')],
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.' . basename(__NAMESPACE__))) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth->isSuperAdmin()
        );

        return true;
    }
}
