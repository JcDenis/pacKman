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
    [dcPage::getPF('pacKman/icon.svg'), dcPage::getPF('pacKman/icon-dark.svg')],
    preg_match(
        '/plugin.php\?p=pacKman(&.*)?$/',
        $_SERVER['REQUEST_URI']
    ),
    $core->auth->isSuperAdmin()
);

class packmanBehaviors
{
    public static function adminDashboardFavorites(dcCore $core, dcFavorites $favs): void
    {
        $favs->register('pacKman', [
            'title'       => __('Packages repository'),
            'url'         => 'plugin.php?p=pacKman#packman-repository-repository',
            'small-icon'  => [dcPage::getPF('pacKman/icon.svg'), dcPage::getPF('pacKman/icon-dark.svg')],
            'large-icon'  => [dcPage::getPF('pacKman/icon.svg'), dcPage::getPF('pacKman/icon-dark.svg')],
            'permissions' => $core->auth->isSuperAdmin(),
            'active_cb'   => [
                'packmanBehaviors',
                'adminDashboardFavoritesActive'
            ]
        ]);
    }

    public static function adminDashboardFavoritesActive(string $request, array $params): bool
    {
        return $request == 'plugin.php'
            && isset($params['p'])
            && $params['p'] == 'pacKman';
    }
}
