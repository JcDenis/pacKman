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

namespace Dotclear\Plugin\pacKman;

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

class Manage
{
    private static $plugins_path = '';
    private static $themes_path  = '';
    private static $init         = false;

    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            dcPage::checkSuper();
            dcCore::app()->blog->settings->addNamespace(Core::id());

            # Paths
            $e                  = explode(PATH_SEPARATOR, DC_PLUGINS_ROOT);
            $p                  = array_pop($e);
            self::$plugins_path = (string) path::real($p);
            self::$themes_path  = dcCore::app()->blog->themes_path;
            self::$init         = true;
        }

        return self::$init;
    }

    public static function process(): void
    {
        if (!self::$init) {
            return;
        }

        # Queries
        $action = $_POST['action'] ?? '';
        $type   = isset($_POST['type']) && in_array($_POST['type'], ['plugins', 'themes', 'repository']) ? $_POST['type'] : '';

        # Settings
        $s = dcCore::app()->blog->settings->get(Core::id());

        # Modules
        if (!(dcCore::app()->themes instanceof dcThemes)) {
            dcCore::app()->themes = new dcThemes();
            dcCore::app()->themes->loadModules(dcCore::app()->blog->themes_path, null);
        }
        $themes  = dcCore::app()->themes;
        $plugins = dcCore::app()->plugins;

        # Rights
        $is_writable = Utils::is_writable(
            $s->get('packman_pack_repository'),
            $s->get('packman_pack_filename')
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
                        Core::getPackages(dirname($s->get('packman_pack_repository') . '/' . $s->get('packman_pack_filename'))),
                        Core::getPackages(dirname($s->get('packman_pack_repository') . '/' . $s->get('packman_secondpack_filename')))
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

                    $root  = (string) $s->get('packman_pack_repository');
                    $files = [
                        (string) $s->get('packman_pack_filename'),
                        (string) $s->get('packman_secondpack_filename'),
                    ];
                    $nocomment  = (bool) $s->get('packman_pack_nocomment');
                    $fixnewline = (bool) $s->get('packman_pack_fixnewline');
                    $overwrite  = (bool) $s->get('packman_pack_overwrite');
                    $exclude    = explode(',', (string) $s->get('packman_pack_excludefiles'));

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
                    dcCore::app()->adminurl->redirect('admin.plugin.' . Core::id(), [], '#packman-' . $type);
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
                    dcCore::app()->adminurl->redirect('admin.plugin.' . Core::id(), [], '#packman-repository-' . $type);
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
                    dcCore::app()->adminurl->redirect('admin.plugin.' . Core::id(), [], '#packman-repository-' . $type);
                }

            # Copy
            } elseif (strpos($action, 'copy_to_') !== false) {
                $dest = (string) $s->get('packman_pack_repository');
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
                    dcCore::app()->adminurl->redirect('admin.plugin.' . Core::id(), [], '#packman-repository-' . $type);
                }

            # Move
            } elseif (strpos($action, 'move_to_') !== false) {
                $dest = (string) $s->get('packman_pack_repository');
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
                    dcCore::app()->adminurl->redirect('admin.plugin.' . Core::id(), [], '#packman-repository-' . $type);
                }
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    public static function render()
    {
        if (!self::$init) {
            return false;
        }

        # Settings
        $s = dcCore::app()->blog->settings->get(Core::id());

        $is_configured = Utils::is_configured(
            $s->get('packman_pack_repository'),
            $s->get('packman_pack_filename'),
            $s->get('packman_secondpack_filename')
        );

        # Display
        echo
        '<html><head><title>' . __('pacKman') . '</title>' .
        dcPage::jsPageTabs() .
        dcPage::jsLoad(dcPage::getPF(Core::id() . '/js/packman.js'));

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
            '<a href="' . dcCore::app()->adminurl->get('admin.plugins', ['module' => Core::id(), 'conf' => '1', 'redir' => dcCore::app()->adminurl->get('admin.plugin.' . Core::id())]) . '">' . __('Configuration') . '</a>' .
            '</div>';
        } else {
            $repo_path_modules = array_merge(
                Core::getPackages(dirname($s->get('packman_pack_repository') . '/' . $s->get('packman_pack_filename'))),
                Core::getPackages(dirname($s->get('packman_pack_repository') . '/' . $s->get('packman_secondpack_filename')))
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
