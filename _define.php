<?php
/**
 * @file
 * @brief       The plugin pacKman definition
 * @ingroup     pacKman
 *
 * @defgroup    pacKman Plugin pacKman.
 *
 * Manage your Dotclear packages.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

$this->registerModule(
    'Packages repository',
    'Manage your Dotclear packages',
    'Jean-Christian Denis',
    '2023.11.04',
    [
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . basename(__DIR__) . '/issues',
        'details'     => 'https://github.com/JcDenis/' . basename(__DIR__) . '/src/branch/master/README.md',
        'repository'  => 'https://github.com/JcDenis/' . basename(__DIR__) . '/raw/branch/master/dcstore.xml',
    ]
);
