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
    public static function process()
    {
        try {
            # Check module version
            if (!dcCore::app()->newVersion(
                basename(__DIR__),
                dcCore::app()->plugins->moduleInfo(basename(__DIR__), 'version')
            )) {
                return null;
            }

            # Set module settings
            dcCore::app()->blog->settings->addNamespace(basename(__DIR__));
            foreach (self::$mod_conf as $v) {
                dcCore::app()->blog->settings->__get(basename(__DIR__))->put(
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

return Install::process();
