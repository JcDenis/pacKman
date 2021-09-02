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

if (!defined('DC_RC_PATH')) {
    return null;
}

$this->registerModule(
    'pacKman',
    'Manage your Dotclear packages',
    'Jean-Christian Denis',
    '2021.08.22.1',
    [
        'requires' => [['core', '2.19']],
        'permissions'   => null,
        'type'          => 'plugin',
        'support'       => 'https://github.com/JcDenis/pacKman',
        'details'       => 'https://plugins.dotaddict.org/dc2/details/pacKman',
        'repository' => 'https://raw.githubusercontent.com/JcDenis/pacKman/master/dcstore.xml'
    ]
);