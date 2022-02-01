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

$this->registerModule(
    'pacKman',
    'Manage your Dotclear packages',
    'Jean-Christian Denis',
    '2022.02.01',
    [
        'requires'    => [['core', '2.21']],
        'permissions' => null,
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/pacKman',
        'details'     => 'https://plugins.dotaddict.org/dc2/details/pacKman',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/pacKman/master/dcstore.xml'
    ]
);
