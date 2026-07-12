<?php

declare(strict_types=1);

namespace Dotclear\Plugin\pacKman;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief       pacKman install class.
 * @ingroup     pacKman
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Install
{
    use TraitProcess;

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
            $record = App::db()->con()->select(
                'SELECT * FROM ' . App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME . ' ' .
                "WHERE setting_ns = 'pacKman' "
            );

            while ($record->fetch()) {
                $sid  = is_string($record->f('setting_id')) ? $record->f('setting_id') : '';
                $blog = is_string($record->f('blog_id')) ? $record->f('blog_id') : null;
                if (preg_match('/^packman_(.*?)$/', $sid, $match)) {
                    $cur = App::blogWorkspace()->openBlogWorkspaceCursor();
                    $cur->setField('setting_id', $match[1]);
                    $cur->setField('setting_ns', My::id());
                    $cur->update(
                        "WHERE setting_id = '" . $sid . "' and setting_ns = 'pacKman' " .
                        'AND blog_id ' . (null === $blog ? 'IS NULL ' : ("= '" . App::db()->con()->escapeStr($blog) . "' "))
                    );
                }
            }
        }
    }
}
