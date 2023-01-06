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

class Uninstall
{
    protected static $init = false;

    public static function init(): bool
    {
        self::$init = defined('DC_RC_PATH');

        return self::$init;
    }

    public static function process($uninstaller): ?bool
    {
        if (!self::$init) {
            return false;
        }

        $uninstaller->addUserAction(
            /* type */
            'settings',
            /* action */
            'delete_all',
            /* ns */
            basename(__NAMESPACE__),
            /* desc */
            __('delete all settings')
        );

        $uninstaller->addUserAction(
            /* type */
            'plugins',
            /* action */
            'delete',
            /* ns */
            basename(__NAMESPACE__),
            /* desc */
            __('delete plugin files')
        );

        $uninstaller->addUserAction(
            /* type */
            'versions',
            /* action */
            'delete',
            /* ns */
            basename(__NAMESPACE__),
            /* desc */
            __('delete the version number')
        );

        $uninstaller->addDirectAction(
            /* type */
            'settings',
            /* action */
            'delete_all',
            /* ns */
            basename(__NAMESPACE__),
            /* desc */
            sprintf(__('delete all %s settings'), basename(__NAMESPACE__))
        );

        $uninstaller->addDirectAction(
            /* type */
            'plugins',
            /* action */
            'delete',
            /* ns */
            basename(__NAMESPACE__),
            /* desc */
            sprintf(__('delete %s plugin files'), basename(__NAMESPACE__))
        );

        $uninstaller->addDirectAction(
            /* type */
            'versions',
            /* action */
            'delete',
            /* ns */
            basename(__NAMESPACE__),
            /* desc */
            sprintf(__('delete %s version number'), basename(__NAMESPACE__))
        );

        return true;
    }
}
