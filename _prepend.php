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

if (!defined('DC_RC_PATH')) {
    return null;
}

/* clearbricks ns */
use Clearbricks;

class Prepend
{
    public static function init()
    {
        Clearbricks::lib()->autoload([
            'plugins\\pacKman\\Core'    => __DIR__ . '/inc/class.core.php',
            'plugins\\pacKman\\Utils'   => __DIR__ . '/inc/class.utils.php',
            'plugins\\pacKman\\FileZip' => __DIR__ . '/inc/class.filezip.php',
        ]);
    }
}

Prepend::init();
