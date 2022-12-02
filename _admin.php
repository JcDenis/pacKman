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

dcCore::app()->blog->settings->addNamespace('pacKman');

dcCore::app()->addBehavior('adminDashboardFavoritesV2', function (dcFavorites $favs): void {
    $favs->register('pacKman', [
        'title'       => __('Packages repository'),
        'url'         => dcCore::app()->adminurl->get('admin.plugin.pacKman') . '#packman-repository-repository',
        'small-icon'  => [dcPage::getPF('pacKman/icon.svg'), dcPage::getPF('pacKman/icon-dark.svg')],
        'large-icon'  => [dcPage::getPF('pacKman/icon.svg'), dcPage::getPF('pacKman/icon-dark.svg')],
        //'permissions' => dcCore::app()->auth->isSuperAdmin(),
    ]);
});

dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
    __('Packages repository'),
    dcCore::app()->adminurl->get('admin.plugin.pacKman') . '#packman-repository-repository',
    [dcPage::getPF('pacKman/icon.svg'), dcPage::getPF('pacKman/icon-dark.svg')],
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.pacKman')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->isSuperAdmin()
);
