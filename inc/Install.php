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
use dcNamespace;

/* php ns */
use Exception;

class Install
{
    // Module specs
    private static $mod_conf = [
        [
            'menu_plugins',
            'Add link to pacKman in plugins page',
            false,
            'boolean',
        ],
        [
            'pack_nocomment',
            'Remove comments from files',
            false,
            'boolean',
        ],
        [
            'pack_overwrite',
            'Overwrite existing package',
            false,
            'boolean',
        ],
        [
            'pack_filename',
            'Name of package',
            '%type%-%id%',
            'string',
        ],
        [
            'secondpack_filename',
            'Name of second package',
            '%type%-%id%-%version%',
            'string',
        ],
        [
            'pack_repository',
            'Path to package repository',
            '',
            'string',
        ],
        [
            'pack_excludefiles',
            'Extra files to exclude from package',
            '*.zip,*.tar,*.tar.gz,.directory,.hg',
            'string',
        ],
    ];

    // Nothing to change below
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
            // Upgrade
            self::growUp();

            // Set module settings
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

    public static function growUp()
    {
        $current = dcCore::app()->getVersion(Core::id());

        // Update settings id, ns
        if ($current && version_compare($current, '2022.12.19.1', '<=')) {
            $record = dcCore::app()->con->select(
                'SELECT * FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
                "WHERE setting_ns = 'pacKman' "
            );

            while ($record->fetch()) {
                if (preg_match('/^packman_(.*?)$/', $record->setting_id, $match)) {
                    $cur             = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME);
                    $cur->setting_id = $match[1];
                    $cur->setting_ns = Core::id();
                    $cur->update(
                        "WHERE setting_id = '" . $record->setting_id . "' and setting_ns = 'pacKman' " .
                        'AND blog_id ' . (null === $record->blog_id ? 'IS NULL ' : ("= '" . dcCore::app()->con->escape($record->blog_id) . "' "))
                    );
                }
            }
        }
    }
}
