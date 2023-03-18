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

use dcCore;
use dcNamespace;
use dcNsProcess;

class Install extends dcNsProcess
{
    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            self::$init = version_compare(phpversion(), My::PHP_MIN, '>=')
                && dcCore::app()->newVersion(My::id(), dcCore::app()->plugins->moduleInfo(My::id(), 'version'));
        }

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        try {
            // Upgrade
            self::growUp();

            return true;
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());

            return false;
        }
    }

    public static function growUp(): void
    {
        $current = dcCore::app()->getVersion(My::id());

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
                    $cur->setting_ns = My::id();
                    $cur->update(
                        "WHERE setting_id = '" . $record->setting_id . "' and setting_ns = 'pacKman' " .
                        'AND blog_id ' . (null === $record->blog_id ? 'IS NULL ' : ("= '" . dcCore::app()->con->escape($record->blog_id) . "' "))
                    );
                }
            }
        }
    }
}