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
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

# -- Module specs --

$mod_conf = [
    [
        'packman_menu_plugins',
        'Add link to pacKman in plugins page',
        false,
        'boolean',
    ],
    [
        'packman_pack_nocomment',
        'Remove comments from files',
        false,
        'boolean',
    ],
    [
        'packman_pack_overwrite',
        'Overwrite existing package',
        false,
        'boolean',
    ],
    [
        'packman_pack_filename',
        'Name of package',
        '%type%-%id%',
        'string',
    ],
    [
        'packman_secondpack_filename',
        'Name of second package',
        '%type%-%id%-%version%',
        'string',
    ],
    [
        'packman_pack_repository',
        'Path to package repository',
        '',
        'string',
    ],
    [
        'packman_pack_excludefiles',
        'Extra files to exclude from package',
        '*.zip,*.tar,*.tar.gz,.directory,.hg',
        'string',
    ],
];

# -- Nothing to change below --

try {
    # Grab info
    $mod_id = basename(__DIR__);
    $dc_min = dcCore::app()->plugins->moduleInfo($mod_id, 'requires')[0][1];

    # Check module version
    if (version_compare(
        dcCore::app()->getVersion($mod_id),
        dcCore::app()->plugins->moduleInfo($mod_id, 'version'),
        '>='
    )) {
        return null;
    }

    # Check Dotclear version
    if (!method_exists('dcUtils', 'versionsCompare')
     || dcUtils::versionsCompare(DC_VERSION, $dc_min, '<', false)) {
        throw new Exception(sprintf(
            '%s requires Dotclear %s',
            $mod_id,
            $dc_min
        ));
    }

    # Set module settings
    dcCore::app()->blog->settings->addNamespace($mod_id);
    foreach ($mod_conf as $v) {
        dcCore::app()->blog->settings->{$mod_id}->put(
            $v[0],
            $v[2],
            $v[3],
            $v[1],
            false,
            true
        );
    }

    # Set module version
    dcCore::app()->setVersion(
        $mod_id,
        dcCore::app()->plugins->moduleInfo($mod_id, 'version')
    );

    return true;
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());

    return false;
}
