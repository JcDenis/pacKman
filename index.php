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

dcPage::checkSuper();

# Queries
$action = $_POST['action'] ?? '';
$type   = isset($_POST['type']) && in_array($_POST['type'], ['plugins', 'themes', 'repository']) ? $_POST['type'] : '';

# Settings
dcCore::app()->blog->settings->addNamespace('pacKman');
$s = dcCore::app()->blog->settings->pacKman;

# Modules
if (!isset(dcCore::app()->themes)) {
    dcCore::app()->themes = new dcThemes(dcCore::app());
    dcCore::app()->themes->loadModules(dcCore::app()->blog->themes_path, null);
}
$themes  = dcCore::app()->themes;
$plugins = dcCore::app()->plugins;

# Paths
$ppexp        = explode(PATH_SEPARATOR, DC_PLUGINS_ROOT);
$pppop        = array_pop($ppexp);
$plugins_path = (string) path::real($pppop);
$themes_path  = dcCore::app()->blog->themes_path;
$repo_path    = $s->packman_pack_repository;

# Rights
$is_writable = libPackman::is_writable(
    $s->packman_pack_repository,
    $s->packman_pack_filename
);
$is_editable = !empty($type)
    && !empty($_POST['modules'])
    && is_array($_POST['modules']);

$is_configured = libPackman::is_configured(
    $s->packman_pack_repository,
    $s->packman_pack_filename,
    $s->packman_secondpack_filename
);

# Actions
try {
    # Download
    if (isset($_REQUEST['package']) && empty($type)) {
        $modules = [];
        if ($type == 'plugins') {
            $modules = dcPackman::getPackages($plugins_path);
        } elseif ($type == 'themes') {
            $modules = dcPackman::getPackages($themes_path);
        } else {
            $modules = array_merge(
                dcPackman::getPackages(dirname($repo_path . '/' . $s->packman_pack_filename)),
                dcPackman::getPackages(dirname($repo_path . '/' . $s->packman_secondpack_filename))
            );
        }

        foreach ($modules as $f) {
            if (preg_match('/' . preg_quote($_REQUEST['package']) . '$/', $f['root'])
                && is_file($f['root']) && is_readable($f['root'])
            ) {

                # --BEHAVIOR-- packmanBeforeDownloadPackage
                dcCore::app()->callBehavior('packmanBeforeDownloadPackage', $f, $type);

                header('Content-Type: application/zip');
                header('Content-Length: ' . filesize($f['root']));
                header('Content-Disposition: attachment; filename="' . basename($f['root']) . '"');
                readfile($f['root']);

                # --BEHAVIOR-- packmanAfterDownloadPackage
                dcCore::app()->callBehavior('packmanAfterDownloadPackage', $f, $type);

                exit;
            }
        }

        # Not found
        header('Content-Type: text/plain');
        http::head(404, 'Not Found');
        exit;
    } elseif (!empty($action) && !$is_editable) {
        throw new Exception('No selected modules');

    # Pack
    } elseif ($action == 'packup') {
        foreach ($_POST['modules'] as $root => $id) {
            if (!${$type}->moduleExists($id)) {
                throw new Exception('No such module');
            }

            $module         = ${$type}->getModules($id);
            $module['id']   = $id;
            $module['type'] = $type == 'themes' ? 'theme' : 'plugin';

            $root  = $s->packman_pack_repository;
            $files = [
                $s->packman_pack_filename,
                $s->packman_secondpack_filename
            ];
            $nocomment  = $s->packman_pack_nocomment;
            $fixnewline = $s->packman_pack_fixnewline;
            $overwrite  = $s->packman_pack_overwrite;
            $exclude    = explode(',', $s->packman_pack_excludefiles);

            # --BEHAVIOR-- packmanBeforeCreatePackage
            dcCore::app()->callBehavior('packmanBeforeCreatePackage', dcCore::app(), $module);

            dcPackman::pack($module, $root, $files, $overwrite, $exclude, $nocomment, $fixnewline);

            # --BEHAVIOR-- packmanAfterCreatePackage
            dcCore::app()->callBehavior('packmanAfterCreatePackage', dcCore::app(), $module);
        }

        dcPage::addSuccessNotice(
            __('Package successfully created.')
        );

        if (!empty($_POST['redir'])) {
            http::redirect($_POST['redir']);
        } else {
            dcCore::app()->adminurl->redirect('admin.plugin.pacKman', [], '#packman-' . $type);
        }

        # Delete
    } elseif ($action == 'delete') {
        foreach ($_POST['modules'] as $root => $id) {
            if (!file_exists($root) || !files::isDeletable($root)) {
                throw new Exception('Undeletable file: ' . $root);
            }

            unlink($root);
        }

        dcPage::addSuccessNotice(
            __('Package successfully deleted.')
        );

        if (!empty($_POST['redir'])) {
            http::redirect($_POST['redir']);
        } else {
            dcCore::app()->adminurl->redirect('admin.plugin.pacKman', [], '#packman-repository-' . $type);
        }

        # Install
    } elseif ($action == 'install') {
        foreach ($_POST['modules'] as $root => $id) {

            # --BEHAVIOR-- packmanBeforeInstallPackage
            dcCore::app()->callBehavior('packmanBeforeInstallPackage', $type, $id, $root);

            if ($type == 'plugins') {
                $plugins->installPackage($root, $plugins);
            }
            if ($type == 'themes') {
                $themes->installPackage($root, $themes);
            }

            # --BEHAVIOR-- packmanAfterInstallPackage
            dcCore::app()->callBehavior('packmanAfterInstallPackage', $type, $id, $root);
        }

        dcPage::addSuccessNotice(
            __('Package successfully installed.')
        );

        if (!empty($_POST['redir'])) {
            http::redirect($_POST['redir']);
        } else {
            dcCore::app()->adminurl->redirect('admin.plugin.pacKman', [], '#packman-repository-' . $type);
        }

        # Copy
    } elseif (strpos($action, 'copy_to_') !== false) {
        $dest = $repo_path;
        if ($action == 'copy_to_plugins') {
            $dest = $plugins_path;
        } elseif ($action == 'copy_to_themes') {
            $dest = $themes_path;
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

        if (!empty($_POST['redir'])) {
            http::redirect($_POST['redir']);
        } else {
            dcCore::app()->adminurl->redirect('admin.plugin.pacKman', [], '#packman-repository-' . $type);
        }

        # Move
    } elseif (strpos($action, 'move_to_') !== false) {
        $dest = $repo_path;
        if ($action == 'move_to_plugins') {
            $dest = $plugins_path;
        } elseif ($action == 'move_to_themes') {
            $dest = $themes_path;
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

        if (!empty($_POST['redir'])) {
            http::redirect($_POST['redir']);
        } else {
            dcCore::app()->adminurl->redirect('admin.plugin.pacKman', [], '#packman-repository-' . $type);
        }
    }
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

# Display
echo
'<html><head><title>' . __('pacKman') . '</title>' .
dcPage::jsPageTabs() .
dcPage::jsLoad(dcPage::getPF('pacKman/js/packman.js'));

# --BEHAVIOR-- packmanAdminHeader
dcCore::app()->callBehavior('packmanAdminHeader', dcCore::app());

echo
'</head><body>' .

dcPage::breadcrumb([
    __('Plugins') => '',
    __('pacKman') => ''
]) .
dcPage::notices();

if (dcCore::app()->error->flag()) {
    echo
    '<p class="warning">' . __('pacKman is not well configured.') . ' ' .
    '<a href="plugins.php?module=pacKman&amp;conf=1&amp;redir=' .
    urlencode('plugin.php?p=pacKman') . '">' . __('Configuration') . '</a>' .
    '</p>';
} else {
    $repo_path_modules = array_merge(
        dcPackman::getPackages(dirname($repo_path . '/' . $s->packman_pack_filename)),
        dcPackman::getPackages(dirname($repo_path . '/' . $s->packman_secondpack_filename))
    );
    $plugins_path_modules = dcPackman::getPackages($plugins_path);
    $themes_path_modules = dcPackman::getPackages($themes_path);

    libPackman::modules(
        $plugins->getModules(),
        'plugins',
        __('Installed plugins')
    );

    libPackman::modules(
        $themes->getModules(),
        'themes',
        __('Installed themes')
    );

    libPackman::repository(
        $plugins_path_modules,
        'plugins',
        __('Plugins root')
    );

    libPackman::repository(
        $themes_path_modules,
        'themes',
        __('Themes root')
    );

    libPackman::repository(
        $repo_path_modules,
        'repository',
        __('Packages repository')
    );
}

# --BEHAVIOR-- packmanAdminTabs
dcCore::app()->callBehavior('packmanAdminTabs');

dcPage::helpBlock('pacKman');

echo
'</body></html>';
