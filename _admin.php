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
            'title'       => __('Packages repository'),
            'url'         => 'plugin.php?p=pacKman#packman-repository-repository',
            'small-icon'  => 'index.php?pf=pacKman/icon.png',
            'large-icon'  => 'index.php?pf=pacKman/icon-big.png',
            'permissions' => $core->auth->isSuperAdmin(),
            'active_cb'   => [
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