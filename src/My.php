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
use Dotclear\Module\MyPlugin;

/**
 * This module definitions.
 */
class My extends MyPlugin
{
    /** @var    array   Excluded files */
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
        return in_array($context, [My::BACKEND, My::MANAGE, My::MENU]) ? dcCore::app()->auth->isSuperAdmin() : null;
    }
}
