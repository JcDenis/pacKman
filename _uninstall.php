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

$mod_id = 'pacKman';

$this->addUserAction(
    /* type */  'settings',
    /* action */    'delete_all',
    /* ns */        $mod_id,
    /* desc */  __('delete all settings')
);

$this->addUserAction(
    /* type */  'plugins',
    /* action */    'delete',
    /* ns */        $mod_id,
    /* desc */  __('delete plugin files')
);

$this->addUserAction(
    /* type */  'versions',
    /* action */    'delete',
    /* ns */        $mod_id,
    /* desc */  __('delete the version number')
);


$this->addDirectAction(
    /* type */  'settings',
    /* action */    'delete_all',
    /* ns */        $mod_id,
    /* desc */  sprintf(__('delete all %s settings'), $mod_id)
);

$this->addDirectAction(
    /* type */  'plugins',
    /* action */    'delete',
    /* ns */        $mod_id,
    /* desc */  sprintf(__('delete %s plugin files'), $mod_id)
);

$this->addDirectAction(
    /* type */  'versions',
    /* action */    'delete',
    /* ns */        $mod_id,
    /* desc */  sprintf(__('delete %s version number'), $mod_id)
);