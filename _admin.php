<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of pacKman, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2013 Jean-Christian Denis and contributors
# contact@jcdenis.fr http://jcd.lv
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

$core->blog->settings->addNamespace('pacKman');

$core->addBehavior('adminDashboardFavorites', ['packmanBehaviors', 'adminDashboardFavorites']);

$_menu['Plugins']->addItem(
    __('Packages repository'),
    'plugin.php?p=pacKman#packman-repository-repository',
    'index.php?pf=pacKman/icon.png',
    preg_match(
        '/plugin.php\?p=pacKman(&.*)?$/',
        $_SERVER['REQUEST_URI']
    ),
    $core->auth->isSuperAdmin()
);

class packmanBehaviors
{
    public static function adminDashboardFavorites($core, $favs)
    {
        $favs->register('pacKman', [
            'title' => __('Packages repository'),
            'url' => 'plugin.php?p=pacKman#packman-repository-repository',
            'small-icon' => 'index.php?pf=pacKman/icon.png',
            'large-icon' => 'index.php?pf=pacKman/icon-big.png',
            'permissions' => $core->auth->isSuperAdmin(),
            'active_cb' => [
                'packmanBehaviors', 
                'adminDashboardFavoritesActive'
            ]
        ]);
    }

    public static function adminDashboardFavoritesActive($request, $params)
    {
        return $request == 'plugin.php' 
            && isset($params['p']) 
            && $params['p'] == 'pacKman';
    }
}