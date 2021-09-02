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

$d = dirname(__FILE__) . '/inc/';

$__autoload['dcPackman'] = $d . 'class.dc.packman.php';
$__autoload['libPackman'] = $d . 'lib.packman.php';
$__autoload['packmanFileZip'] = $d . 'lib.packman.filezip.php';