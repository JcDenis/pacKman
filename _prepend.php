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

if (!class_exists('Dotclear\Plugin\pacKman\Prepend')) {
    require __DIR__ . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'prepend.php';

    if (Dotclear\Plugin\pacKman\Prepend::init()) {
        Dotclear\Plugin\pacKman\Prepend::process();
    }
}
