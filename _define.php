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
    '2025.09.09',
    [
        'requires'    => [['core', '2.36']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-09-09T16:35:02+00:00',
    ]
);
