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

use Dotclear\App;
use Dotclear\Core\Process;
use Exception;

class Install extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            // Upgrade
            self::growUp();

            return true;
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return false;
        }
    }

    public static function growUp(): void
    {
        $current = App::version()->getVersion(My::id());

        // Update settings id, ns
        if ($current && version_compare($current, '2022.12.19.1', '<=')) {
            $record = App::con()->select(
                'SELECT * FROM ' . App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME . ' ' .
                "WHERE setting_ns = 'pacKman' "
            );

            while ($record->fetch()) {
                if (preg_match('/^packman_(.*?)$/', $record->f('setting_id'), $match)) {
                    $cur = App::blogWorspace()->openBlogWorkspaceCursor();
                    $cur->setField('setting_id', $match[1]);
                    $cur->setField('setting_ns', My::id());
                    $cur->update(
                        "WHERE setting_id = '" . $record->f('setting_id') . "' and setting_ns = 'pacKman' " .
                        'AND blog_id ' . (null === $record->f('blog_id') ? 'IS NULL ' : ("= '" . App::con()->escapeStr($record->f('blog_id')) . "' "))
                    );
                }
            }
        }
    }
}
