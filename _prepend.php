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
if (!defined('DC_RC_PATH')) {
    return null;
}

Clearbricks::lib()->autoload([
    'dcPackman'      => __DIR__ . '/inc/class.dc.packman.php',
    'libPackman'     => __DIR__ . '/inc/lib.packman.php',
    'packmanFileZip' => __DIR__ . '/inc/lib.packman.filezip.php',
]);
