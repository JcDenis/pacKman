<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of pacKman, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2021 Jean-Christian Denis and contributors
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

# -- Module specs --

$dc_min = '2.18';
$mod_id = 'pacKman';
$mod_conf = [
    [
        'packman_menu_plugins',
        'Add link to pacKman in plugins page',
        false,
        'boolean'
    ],
    [
        'packman_pack_nocomment',
        'Remove comments from files',
        false,
        'boolean'
    ],
    [
        'packman_pack_overwrite',
        'Overwrite existing package',
        false,
        'boolean'
    ],
    [
        'packman_pack_filename',
        'Name of package',
        '%type%-%id%',
        'string'
    ],
    [
        'packman_secondpack_filename',
        'Name of second package',
        '%type%-%id%-%version%',
        'string'
    ],
    [
        'packman_pack_repository',
        'Path to package repository',
        '',
        'string'
    ],
    [
        'packman_pack_excludefiles',
        'Extra files to exclude from package',
        '*.zip,*.tar,*.tar.gz,.directory,.hg',
        'string'
    ]
];

# -- Nothing to change below --

try {

    # Check module version
    if (version_compare(
        $core->getVersion($mod_id),
        $core->plugins->moduleInfo($mod_id, 'version'),
        '>=')) {
        return null;
    }

    # Check Dotclear version
    if (!method_exists('dcUtils', 'versionsCompare') 
     || dcUtils::versionsCompare(DC_VERSION, $dc_min, '<', false)) {
        throw new Exception(sprintf(
            '%s requires Dotclear %s', $mod_id, $dc_min
        ));
    }

    # Set module settings
    $core->blog->settings->addNamespace($mod_id);
    foreach($mod_conf as $v) {
        $core->blog->settings->{$mod_id}->put(
            $v[0], $v[2], $v[3], $v[1], false, true
        );
    }

    # Set module version
    $core->setVersion(
        $mod_id,
        $core->plugins->moduleInfo($mod_id, 'version')
    );

    return true;
}
catch (Exception $e) {
    $core->error->add($e->getMessage());

    return false;
}