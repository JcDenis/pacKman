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

namespace plugins\pacKman;

if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

/* dotclear ns */
use dcAdmin;
use dcCore;
use dcFavorites;
use dcPage;

class admin
{
    public static function init()
    {
        dcCore::app()->blog->settings->addNamespace(basename(__DIR__));
    }

    public static function process()
    {
        dcCore::app()->addBehavior('adminDashboardFavoritesV2', function (dcFavorites $favs): void {
            $favs->register(basename(__DIR__), [
                'title'      => __('Packages repository'),
                'url'        => dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__), [], '#packman-repository-repository'),
                'small-icon' => [dcPage::getPF(basename(__DIR__) . '/icon.svg'), dcPage::getPF(basename(__DIR__) . '/icon-dark.svg')],
                'large-icon' => [dcPage::getPF(basename(__DIR__) . '/icon.svg'), dcPage::getPF(basename(__DIR__) . '/icon-dark.svg')],
                //'permissions' => dcCore::app()->auth->isSuperAdmin(),
            ]);
        });

        dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
            __('Packages repository'),
            dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__)) . '#packman-repository-repository',
            [dcPage::getPF(basename(__DIR__) . '/icon.svg'), dcPage::getPF(basename(__DIR__) . '/icon-dark.svg')],
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__))) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth->isSuperAdmin()
        );
    }
}

admin::init();
admin::process();
