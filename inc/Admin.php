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
    private static $pid    = '';
    protected static $init = false;

    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            self::$pid  = basename(dirname(__DIR__));
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
            $favs->register(self::$pid, [
                'title'      => __('Packages repository'),
                'url'        => dcCore::app()->adminurl->get('admin.plugin.' . self::$pid, [], '#packman-repository-repository'),
                'small-icon' => [dcPage::getPF(self::$pid . '/icon.svg'), dcPage::getPF(self::$pid . '/icon-dark.svg')],
                'large-icon' => [dcPage::getPF(self::$pid . '/icon.svg'), dcPage::getPF(self::$pid . '/icon-dark.svg')],
                //'permissions' => dcCore::app()->auth->isSuperAdmin(),
            ]);
        });

        dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
            __('Packages repository'),
            dcCore::app()->adminurl->get('admin.plugin.' . self::$pid) . '#packman-repository-repository',
            [dcPage::getPF(self::$pid . '/icon.svg'), dcPage::getPF(self::$pid . '/icon-dark.svg')],
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.' . self::$pid)) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth->isSuperAdmin()
        );

        return true;
    }
}
