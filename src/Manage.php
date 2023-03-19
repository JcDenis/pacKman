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
use dcNsProcess;

/* clearbricks ns */
use files;
use http;

class Manage extends dcNsProcess
{
    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            self::$init = dcCore::app()->auth->isSuperAdmin() && version_compare(phpversion(), My::PHP_MIN, '>=');
        }

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        # Queries
        $action = $_POST['action'] ?? '';
        $type   = isset($_POST['type']) && in_array($_POST['type'], ['plugins', 'themes', 'repository']) ? $_POST['type'] : '';

        # Settings
        $s   = new Settings();
        $dir = Utils::getRepositoryDir($s->pack_repository);

        # Modules
        if (!(dcCore::app()->themes instanceof dcThemes)) {
            dcCore::app()->themes = new dcThemes();
            dcCore::app()->themes->loadModules(dcCore::app()->blog->themes_path, null);
        }
        $themes  = dcCore::app()->themes;
        $plugins = dcCore::app()->plugins;

        # Rights
        $is_writable = Utils::is_writable(
            $dir,
            $s->pack_filename
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
                    $modules = Core::getPackages(Utils::getPluginsPath());
                } elseif ($type == 'themes') {
                    $modules = Core::getPackages(Utils::getThemesPath());
                } else {
                    $modules = array_merge(
                        Core::getPackages(dirname($dir . '/' . $s->pack_filename)),
                        Core::getPackages(dirname($dir . '/' . $s->secondpack_filename))
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
                dcPage::addErrorNotice(
                    __('No modules selected.')
                );

                if (!empty($_POST['redir'])) {
                    http::redirect($_POST['redir']);
                } else {
                    dcCore::app()->adminurl->redirect('admin.plugin.' . My::id(), [], '#packman-' . $type);
                }

            # Pack
            } elseif ($action == 'packup') {
                foreach ($_POST['modules'] as $root => $id) {
                    if (!Utils::moduleExists($type, $id)) {
                        throw new Exception('No such module');
                    }

                    $module         = Utils::getModules($type, $id);
                    $module['id']   = $id;
                    $module['type'] = $type == 'themes' ? 'theme' : 'plugin';

                    # --BEHAVIOR-- packmanBeforeCreatePackage
                    dcCore::app()->callBehavior('packmanBeforeCreatePackage', $module);

                    Core::pack(
                        $module,
                        $dir,
                        [$s->pack_filename, $s->secondpack_filename],
                        $s->pack_overwrite,
                        explode(',', $s->pack_excludefiles),
                        $s->pack_nocomment,
                        $s->pack_fixnewline
                    );

                    # --BEHAVIOR-- packmanAfterCreatePackage
                    dcCore::app()->callBehavior('packmanAfterCreatePackage', $module);
                }

                dcPage::addSuccessNotice(
                    __('Package successfully created.')
                );

                if (!empty($_POST['redir'])) {
                    http::redirect($_POST['redir']);
                } else {
                    dcCore::app()->adminurl->redirect('admin.plugin.' . My::id(), [], '#packman-' . $type);
                }

            # Delete
            } elseif ($action == 'delete') {
                $del_success = false;
                foreach ($_POST['modules'] as $root => $id) {
                    if (!file_exists($root) || !files::isDeletable($root)) {
                        dcPage::addWarningNotice(sprintf(__('Undeletable file "%s"', $root)));
                    } else {
                        $del_success = true;
                    }

                    unlink($root);
                }

                if ($del_success) {
                    dcPage::addSuccessNotice(
                        __('Package successfully deleted.')
                    );
                }

                if (!empty($_POST['redir'])) {
                    http::redirect($_POST['redir']);
                } else {
                    dcCore::app()->adminurl->redirect('admin.plugin.' . My::id(), [], '#packman-repository-' . $type);
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
                    dcCore::app()->adminurl->redirect('admin.plugin.' . My::id(), [], '#packman-repository-' . $type);
                }

            # Copy
            } elseif (strpos($action, 'copy_to_') !== false) {
                $dest = (string) $dir;
                if ($action == 'copy_to_plugins') {
                    $dest = Utils::getPluginsPath();
                } elseif ($action == 'copy_to_themes') {
                    $dest = Utils::getThemesPath();
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
                    dcCore::app()->adminurl->redirect('admin.plugin.' . My::id(), [], '#packman-repository-' . $type);
                }

            # Move
            } elseif (strpos($action, 'move_to_') !== false) {
                $dest = (string) $dir;
                if ($action == 'move_to_plugins') {
                    $dest = Utils::getPluginsPath();
                } elseif ($action == 'move_to_themes') {
                    $dest = Utils::getThemesPath();
                }

                foreach ($_POST['modules'] as $root => $id) {
                    file_put_contents(
                        $dest . DIRECTORY_SEPARATOR . basename($root),
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
                    dcCore::app()->adminurl->redirect('admin.plugin.' . My::id(), [], '#packman-repository-' . $type);
                }
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::$init) {
            return;
        }

        # Settings
        $s   = new Settings();
        $dir = Utils::getRepositoryDir($s->pack_repository);

        $is_configured = Utils::is_configured(
            $dir,
            $s->pack_filename,
            $s->secondpack_filename
        );

        # Display
        dcPage::openModule(
            My::name(),
            dcPage::jsPageTabs() .
            dcPage::jsModuleLoad(My::id() . '/js/backend.js') .

            # --BEHAVIOR-- packmanAdminHeader
            dcCore::app()->callBehavior('packmanAdminHeader')
        );

        echo
        dcPage::breadcrumb([
            __('Plugins') => '',
            My::name()    => '',
        ]) .
        dcPage::notices();

        if (dcCore::app()->error->flag() || !$is_configured) {
            echo
            '<div class="warning">' . __('pacKman is not well configured.') . ' ' .
            '<a href="' . dcCore::app()->adminurl->get('admin.plugins', ['module' => My::id(), 'conf' => '1', 'redir' => dcCore::app()->adminurl->get('admin.plugin.' . My::id())]) . '">' . __('Configuration') . '</a>' .
            '</div>';
        } else {
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
                Core::getPackages(Utils::getPluginsPath()),
                'plugins',
                __('Plugins root')
            );

            Utils::repository(
                Core::getPackages(Utils::getThemesPath()),
                'themes',
                __('Themes root')
            );

            Utils::repository(
                array_merge(
                    Core::getPackages(dirname($dir . '/' . $s->pack_filename)),
                    Core::getPackages(dirname($dir . '/' . $s->secondpack_filename))
                ),
                'repository',
                __('Packages repository')
            );
        }

        # --BEHAVIOR-- packmanAdminTabs
        dcCore::app()->callBehavior('packmanAdminTabs');

        dcPage::helpBlock('pacKman');
        dcPage::closeModule();
    }
}
