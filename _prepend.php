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

$d = dirname(__FILE__) . '/inc/';

$__autoload['dcPackman'] = $d . 'class.dc.packman.php';
$__autoload['libPackman'] = $d . 'lib.packman.php';
$__autoload['packmanFileZip'] = $d . 'lib.packman.filezip.php';