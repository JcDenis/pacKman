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
use dcCore;

/* php ns */
use Exception;

class Install
{
    # -- Module specs --
    private static $mod_conf = [
        [
            'packman_menu_plugins',
            'Add link to pacKman in plugins page',
            false,
            'boolean',
        ],
        [
            'packman_pack_nocomment',
            'Remove comments from files',
            false,
            'boolean',
        ],
        [
            'packman_pack_overwrite',
            'Overwrite existing package',
            false,
            'boolean',
        ],
        [
            'packman_pack_filename',
            'Name of package',
            '%type%-%id%',
            'string',
        ],
        [
            'packman_secondpack_filename',
            'Name of second package',
            '%type%-%id%-%version%',
            'string',
        ],
        [
            'packman_pack_repository',
            'Path to package repository',
            '',
            'string',
        ],
        [
            'packman_pack_excludefiles',
            'Extra files to exclude from package',
            '*.zip,*.tar,*.tar.gz,.directory,.hg',
            'string',
        ],
    ];

    # -- Nothing to change below --
    private static $init = false;

    public static function init(): bool
    {
        self::$init = defined('DC_CONTEXT_ADMIN') && dcCore::app()->newVersion(Core::id(), dcCore::app()->plugins->moduleInfo(Core::id(), 'version'));

        return self::$init;
    }

    public static function process()
    {
        if (!self::$init) {
            return false;
        }

        try {
            # Set module settings
            dcCore::app()->blog->settings->addNamespace(Core::id());
            foreach (self::$mod_conf as $v) {
                dcCore::app()->blog->settings->__get(Core::id())->put(
                    $v[0],
                    $v[2],
                    $v[3],
                    $v[1],
                    false,
                    true
                );
            }

            return true;
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());

            return false;
        }
    }
}
