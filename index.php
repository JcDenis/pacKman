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
declare(strict_types=1);

namespace plugins\pacKman;

if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

/* dotclear ns */
use dcCore;
use dcPage;
use dcThemes;

/* clearbricks ns */
use files;
use http;
use path;

/* php ns */
use Exception;

class index
{
    private static $plugins_path = '';
    private static $themes_path  = '';

    public static function init()
    {
        dcPage::checkSuper();
        dcCore::app()->blog->settings->addNamespace(basename(__DIR__));

        # Paths
        $e                  = explode(PATH_SEPARATOR, DC_PLUGINS_ROOT);
        $p                  = array_pop($e);
        self::$plugins_path = (string) path::real($p);
        self::$themes_path  = dcCore::app()->blog->themes_path;
    }

    public static function process()
    {
        # Queries
        $action = $_POST['action'] ?? '';
        $type   = isset($_POST['type']) && in_array($_POST['type'], ['plugins', 'themes', 'repository']) ? $_POST['type'] : '';

        # Settings
        $s = dcCore::app()->blog->settings->__get(basename(__DIR__));

        # Modules
        if (!(dcCore::app()->themes instanceof dcThemes)) {
            dcCore::app()->themes = new dcThemes();
            dcCore::app()->themes->loadModules(dcCore::app()->blog->themes_path, null);
        }
        $themes  = dcCore::app()->themes;
        $plugins = dcCore::app()->plugins;

        # Rights
        $is_writable = Utils::is_writable(
            $s->packman_pack_repository,
            $s->packman_pack_filename
        );
        $is_editable = !empty($type)
            && !empty($_POST['modules'])
            && is_array($_POST['modules']);

        # Actions
        try {
            # Download
            if (isset($_REQUEST['package']) && empty($type)) {
                $modules = [];
                if ($type == 'plugins') {
                    $modules = Core::getPackages(self::$plugins_path);
                } elseif ($type == 'themes') {
                    $modules = Core::getPackages(self::$themes_path);
                } else {
                    $modules = array_merge(
                        Core::getPackages(dirname($s->packman_pack_repository . '/' . $s->packman_pack_filename)),
                        Core::getPackages(dirname($s->packman_pack_repository . '/' . $s->packman_secondpack_filename))
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
                    if (!Utils::moduleExists($type, $id)) {
                        throw new Exception('No such module');
                    }

                    $module         = Utils::getModules($type, $id);
                    $module['id']   = $id;
                    $module['type'] = $type == 'themes' ? 'theme' : 'plugin';

                    $root  = (string) $s->packman_pack_repository;
                    $files = [
                        (string) $s->packman_pack_filename,
                        (string) $s->packman_secondpack_filename,
                    ];
                    $nocomment  = (bool) $s->packman_pack_nocomment;
                    $fixnewline = (bool) $s->packman_pack_fixnewline;
                    $overwrite  = (bool) $s->packman_pack_overwrite;
                    $exclude    = explode(',', (string) $s->packman_pack_excludefiles);

                    # --BEHAVIOR-- packmanBeforeCreatePackage
                    dcCore::app()->callBehavior('packmanBeforeCreatePackage', $module);

                    Core::pack($module, $root, $files, $overwrite, $exclude, $nocomment, $fixnewline);

                    # --BEHAVIOR-- packmanAfterCreatePackage
                    dcCore::app()->callBehavior('packmanAfterCreatePackage', $module);
                }

                dcPage::addSuccessNotice(
                    __('Package successfully created.')
                );

                if (!empty($_POST['redir'])) {
                    http::redirect($_POST['redir']);
                } else {
                    dcCore::app()->adminurl->redirect('admin.plugin.' . basename(__DIR__), [], '#packman-' . $type);
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
                    dcCore::app()->adminurl->redirect('admin.plugin.' . basename(__DIR__), [], '#packman-repository-' . $type);
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
                    dcCore::app()->adminurl->redirect('admin.plugin.' . basename(__DIR__), [], '#packman-repository-' . $type);
                }

            # Copy
            } elseif (strpos($action, 'copy_to_') !== false) {
                $dest = $s->packman_pack_repository;
                if ($action == 'copy_to_plugins') {
                    $dest = self::$plugins_path;
                } elseif ($action == 'copy_to_themes') {
                    $dest = self::$themes_path;
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
                    dcCore::app()->adminurl->redirect('admin.plugin.' . basename(__DIR__), [], '#packman-repository-' . $type);
                }

            # Move
            } elseif (strpos($action, 'move_to_') !== false) {
                $dest = $s->packman_pack_repository;
                if ($action == 'move_to_plugins') {
                    $dest = self::$plugins_path;
                } elseif ($action == 'move_to_themes') {
                    $dest = self::$themes_path;
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
                    dcCore::app()->adminurl->redirect('admin.plugin.' . basename(__DIR__), [], '#packman-repository-' . $type);
                }
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    public static function render()
    {
        # Settings
        $s = dcCore::app()->blog->settings->__get(basename(__DIR__));

        $is_configured = Utils::is_configured(
            $s->packman_pack_repository,
            $s->packman_pack_filename,
            $s->packman_secondpack_filename
        );

        # Display
        echo
        '<html><head><title>' . __('pacKman') . '</title>' .
        dcPage::jsPageTabs() .
        dcPage::jsLoad(dcPage::getPF(basename(__DIR__) . '/js/packman.js'));

        # --BEHAVIOR-- packmanAdminHeader
        dcCore::app()->callBehavior('packmanAdminHeader');

        echo
        '</head><body>' .

        dcPage::breadcrumb([
            __('Plugins') => '',
            __('pacKman') => '',
        ]) .
        dcPage::notices();

        if (dcCore::app()->error->flag() || !$is_configured) {
            echo
            '<div class="warning">' . __('pacKman is not well configured.') . ' ' .
            '<a href="' . dcCore::app()->adminurl->get('admin.plugins', ['module' => basename(__DIR__), 'conf' => '1', 'redir' => dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__))]) . '">' . __('Configuration') . '</a>' .
            '</div>';
        } else {
            $repo_path_modules = array_merge(
                Core::getPackages(dirname($s->packman_pack_repository . '/' . $s->packman_pack_filename)),
                Core::getPackages(dirname($s->packman_pack_repository . '/' . $s->packman_secondpack_filename))
            );
            $plugins_path_modules = Core::getPackages(self::$plugins_path);
            $themes_path_modules  = Core::getPackages(self::$themes_path);

            Utils::modules(
                Utils::getModules('plugins'),
                'plugins',
                __('Installed plugins')
            );

            Utils::modules(
                Utils::getModules('themes'),
                'themes',
                __('Installed themes')
            );

            Utils::repository(
                $plugins_path_modules,
                'plugins',
                __('Plugins root')
            );

            Utils::repository(
                $themes_path_modules,
                'themes',
                __('Themes root')
            );

            Utils::repository(
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
    }
}

index::init();
index::process();
index::render();
