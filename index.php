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

dcPage::checkSuper();

# Queries
$p_url = 'plugin.php?p=pacKman';
$action = isset($_POST['action']) ? $_POST['action'] : '';
$type = isset($_POST['type']) && in_array($_POST['type'],
    array('plugins', 'themes', 'repository')) ? $_POST['type'] : '';

# Settings
$core->blog->settings->addNamespace('pacKman');
$s = $core->blog->settings->pacKman;

# Modules
if (!isset($core->themes)) {
    $core->themes = new dcThemes($core);
    $core->themes->loadModules($core->blog->themes_path, null);
}
$themes = $core->themes;
$plugins = $core->plugins;

# Paths
$ppexp = explode(PATH_SEPARATOR, DC_PLUGINS_ROOT);
$pppop = array_pop($ppexp);
$plugins_path = path::real($pppop);
$themes_path = $core->blog->themes_path;
$repo_path = $s->packman_pack_repository;

# Rights
$is_writable = libPackman::is_writable(
    $s->packman_pack_repository,
    $s->packman_pack_filename
);
$is_editable =
    !empty($type)
    && !empty($_POST['modules']) 
    && is_array($_POST['modules']);

$is_configured = libPackman::is_configured(
    $core,
    $s->packman_pack_repository,
    $s->packman_pack_filename,
    $s->packman_secondpack_filename
);

# Actions
try
{
    # Download
    if (isset($_REQUEST['package']) && empty($type)) {

        $modules = array();
        if ($type == 'plugins') {
            $modules = dcPackman::getPackages($core, $plugins_path);
        }
        elseif ($type == 'themes') {
            $modules = dcPackman::getPackages($core, $themes_path);
        }
        else {
            $modules = array_merge(
                dcPackman::getPackages($core, dirname($repo_path . '/' . $s->packman_pack_filename)),
                dcPackman::getPackages($core, dirname($repo_path . '/' . $s->packman_secondpack_filename))
            );
        }

        foreach($modules as $f) {

            if (preg_match('/' . preg_quote($_REQUEST['package']) . '$/', $f['root'])
             && is_file($f['root']) && is_readable($f['root'])
            ) {

                # --BEHAVIOR-- packmanBeforeDownloadPackage
                $core->callBehavior('packmanBeforeDownloadPackage', $f, $type);

                header('Content-Type: application/zip');
                header('Content-Length: ' . filesize($f['root']));
                header('Content-Disposition: attachment; filename="' . basename($f['root']) . '"');
                readfile($f['root']);

                # --BEHAVIOR-- packmanAfterDownloadPackage
                $core->callBehavior('packmanAfterDownloadPackage', $f, $type);

                exit;
            }
        }

        # Not found
        header('Content-Type: text/plain');
        http::head(404, 'Not Found');
        exit;
    }
    elseif (!empty($action) && !$is_editable) {
        throw new Exception('No selected modules');
    }

    # Pack
    elseif ($action == 'packup') {

        foreach ($_POST['modules'] as $root => $id) {

            if (!${$type}->moduleExists($id)) {
                throw new Exception('No such module');
            }

            $module = ${$type}->getModules($id);
            $module['id'] = $id;
            $module['type'] = $type == 'themes' ? 'theme' : 'plugin';

            $root = $s->packman_pack_repository;
            $files = array(
                $s->packman_pack_filename,
                $s->packman_secondpack_filename
            );
            $nocomment = $s->packman_pack_nocomment;
            $overwrite = $s->packman_pack_overwrite;
            $exclude = explode(',', $s->packman_pack_excludefiles);

            # --BEHAVIOR-- packmanBeforeCreatePackage
            $core->callBehavior('packmanBeforeCreatePackage', $core, $module);

            dcPackman::pack($module, $root, $files, $overwrite, $exclude, $nocomment);

            # --BEHAVIOR-- packmanAfterCreatePackage
            $core->callBehavior('packmanAfterCreatePackage', $core, $module);

        }

        dcPage::addSuccessNotice(
            __('Package successfully created.')
        );
        http::redirect(empty($_POST['redir']) ? 
            $p_url . '#packman-' . $type : $_POST['redir']
        );
    }

    # Delete
    elseif ($action == 'delete') {

        foreach ($_POST['modules'] as $root => $id) {
            if (!file_exists($root) || !files::isDeletable($root))  {
                throw new Exception('Undeletable file: ' . $root);
            }

            unlink($root);
        }

        dcPage::addSuccessNotice(
            __('Package successfully deleted.')
        );
        http::redirect(
            $p_url . '#packman-repository-' . $type
        );
    }

    # Install
    elseif ($action == 'install') {

        foreach ($_POST['modules'] as $root => $id) {

            # --BEHAVIOR-- packmanBeforeInstallPackage
            $core->callBehavior('packmanBeforeInstallPackage', $type, $id, $root);

            if ($type == 'plugins') {
                $plugins->installPackage($root, $plugins);
            }
            if ($type == 'themes') {
                $themes->installPackage($root, $themes);
            }

            # --BEHAVIOR-- packmanAfterInstallPackage
            $core->callBehavior('packmanAfterInstallPackage', $type, $id, $root);

        }

        dcPage::addSuccessNotice(
            __('Package successfully installed.')
        );
        http::redirect(
            $p_url . '#packman-repository-' . $type
        );
    }

    # Copy
    elseif (strpos($action, 'copy_to_') !== false) {

        if ($action == 'copy_to_plugins') {
            $dest = $plugins_path;
        }
        elseif ($action == 'copy_to_themes') {
            $dest = $themes_path;
        }
        elseif ($action == 'copy_to_repository') {
            $dest = $repo_path;
        }

        foreach ($_POST['modules'] as $root => $id) {
            file_put_contents(
                $dest . '/' . basename($root),
                file_get_contents($root)
            );
        }

        dcPage::addSuccessNotice(
            __('Package successfully copied.')
        );
        http::redirect(
            $p_url . '#packman-repository-' . $type
        );
    }

    # Move
    elseif (strpos($action, 'move_to_') !== false) {

        if ($action == 'move_to_plugins') {
            $dest = $plugins_path;
        }
        elseif ($action == 'move_to_themes') {
            $dest = $themes_path;
        }
        elseif ($action == 'move_to_repository') {
            $dest = $repo_path;
        }

        foreach ($_POST['modules'] as $root => $id) {
            file_put_contents(
                $dest . '/' . basename($root),
                file_get_contents($root)
            );
            unlink($root);
        }

        dcPage::addSuccessNotice(
            __('Package successfully moved.')
        );
        http::redirect(
            $p_url . '#packman-repository-' . $type
        );
    }
}
catch(Exception $e) {
    $core->error->add($e->getMessage());
}

# Display
echo 
'<html><head><title>' . __('pacKman') . '</title>' .
dcPage::jsPageTabs() .
dcPage::jsLoad('index.php?pf=pacKman/js/packman.js');

# --BEHAVIOR-- packmanAdminHeader
$core->callBehavior('packmanAdminHeader', $core);

echo 
'</head><body>' .

dcPage::breadcrumb(
    array(
        __('Plugins') => '',
        __('pacKman') => ''
    )
).
dcPage::notices();

if ($core->error->flag()) {
    echo
    '<p class="warning">' . __('pacKman is not well configured.') . ' ' .
    '<a href="plugins.php?module=pacKman&amp;conf=1&amp;redir=' .
    urlencode('plugin.php?p=pacKman') . '">' . __('Configuration') . '</a>' .
    '</p>';
}
else {

    $repo_path_modules = array_merge(
        dcPackman::getPackages(
            $core,
            dirname($repo_path . '/' . $s->packman_pack_filename)
        ),
        dcPackman::getPackages(
            $core,
            dirname($repo_path . '/' . $s->packman_secondpack_filename)
        )
    );
    $plugins_path_modules = dcPackman::getPackages(
        $core,
        $plugins_path
    );
    $themes_path_modules = dcPackman::getPackages(
        $core,
        $themes_path
    );

    libPackman::modules(
        $core,
        $plugins->getModules(),
        'plugins',
        __('Installed plugins')
    );

    libPackman::modules(
        $core,
        $themes->getModules(),
        'themes',
        __('Installed themes')
    );

    libPackman::repository(
        $core,
        $plugins_path_modules,
        'plugins',
        __('Plugins root')
    );

    libPackman::repository(
        $core,
        $themes_path_modules,
        'themes',
        __('Themes root')
    );

    libPackman::repository(
        $core,
        $repo_path_modules,
        'repository',
        __('Packages repository')
    );
}

# --BEHAVIOR-- packmanAdminTabs
$core->callBehavior('packmanAdminTabs', $core);

dcPage::helpBlock('pacKman');

echo 
'</body></html>';