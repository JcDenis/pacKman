<?php

declare(strict_types=1);

namespace Dotclear\Plugin\pacKman;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

/**
 * @brief   pacKman My plugin helper.
 * @ingroup pacKman
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class My extends MyPlugin
{
    /**
     * Excluded files.
     *
     * @var    array<int,string>    EXCLUDED_FILES
     */
    public const EXCLUDED_FILES = [
        '.',
        '..',
        '__MACOSX',
        '.svn',
        '.hg*',
        '.git*',
        'CVS',
        '.DS_Store',
        'Thumbs.db',
        '_disabled',
    ];

    public static function checkCustomContext(int $context): ?bool
    {
        // Only backend and super admin
        return $context === self::INSTALL ? null : App::task()->checkContext('BACKEND') && App::auth()->isSuperAdmin();
    }
}
