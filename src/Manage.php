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
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Form\{
    Div,
    Text
};
use Dotclear\Helper\Network\Http;
use Exception;

class Manage extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN')
            && dcCore::app()->auth?->isSuperAdmin();

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        # Settings
        $s = new Settings();

        # Queries
        $action = $_POST['action'] ?? '';
        $type   = isset($_POST['type']) && in_array($_POST['type'], ['plugins', 'themes', 'repository', 'repository-themes', 'repository-plugins']) ? $_POST['type'] : '';
        $repo   = $s->pack_typedrepo ? (empty($_REQUEST['repo']) ? $type : (str_contains($_REQUEST['repo'], 'themes') ? 'themes' : 'plugins')) : null;
        $dir    = Utils::getRepositoryDir($s->pack_repository, $repo);

        # Modules
        if (!(dcCore::app()->themes instanceof dcThemes)) {
            dcCore::app()->themes = new dcThemes();
            dcCore::app()->themes->loadModules((string) dcCore::app()->blog?->themes_path, null);
        }
        $themes  = dcCore::app()->themes;
        $plugins = dcCore::app()->plugins;

        # Rights
        $is_writable = Utils::isWritable($dir, $s->pack_filename);
        $is_editable = !empty($type) && !empty($_POST['modules']) && is_array($_POST['modules']);

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
                        Core::getPackages(dirname($dir . DIRECTORY_SEPARATOR . $s->pack_filename)),
                        Core::getPackages(dirname($dir . DIRECTORY_SEPARATOR . $s->secondpack_filename))
                    );
                }

                foreach ($modules as $module) {
                    if (preg_match('/' . preg_quote($_REQUEST['package']) . '$/', $module->get('root'))
                        && is_file($module->get('root')) && is_readable($module->get('root'))
                    ) {
                        # --BEHAVIOR-- packmanBeforeDownloadPackage
                        dcCore::app()->callBehavior('packmanBeforeDownloadPackage', $module->dump(), $type);

                        header('Content-Type: application/zip');
                        header('Content-Length: ' . filesize($module->get('root')));
                        header('Content-Disposition: attachment; filename="' . basename($module->get('root')) . '"');
                        readfile($module->get('root'));

                        # --BEHAVIOR-- packmanAfterDownloadPackage
                        dcCore::app()->callBehavior('packmanAfterDownloadPackage', $module->dump(), $type);

                        exit;
                    }
                }

                # Not found
                header('Content-Type: text/plain');
                Http::head(404, 'Not Found');
                exit;
            } elseif (!empty($action) && !$is_editable) {
                dcPage::addErrorNotice(
                    __('No modules selected.')
                );

                if (!empty($_POST['redir'])) {
                    Http::redirect($_POST['redir']);
                } else {
                    dcCore::app()->adminurl?->redirect('admin.plugin.' . My::id(), [], '#packman-' . $type);
                }

            # Pack
            } elseif ($action == 'packup') {
                foreach ($_POST['modules'] as $root => $id) {
                    if (!dcCore::app()->{$type}->getDefine($id)->isDefined()) {
                        throw new Exception('No such module');
                    }

                    $module = dcCore::app()->{$type}->getDefine($id);

                    # --BEHAVIOR-- packmanBeforeCreatePackage
                    dcCore::app()->callBehavior('packmanBeforeCreatePackage', $module->dump());

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
                    dcCore::app()->callBehavior('packmanAfterCreatePackage', $module->dump());
                }

                dcPage::addSuccessNotice(
                    __('Package successfully created.')
                );

                if (!empty($_POST['redir'])) {
                    Http::redirect($_POST['redir']);
                } else {
                    dcCore::app()->adminurl?->redirect('admin.plugin.' . My::id(), [], '#packman-' . $type);
                }

            # Delete
            } elseif ($action == 'delete') {
                $del_success = false;
                foreach ($_POST['modules'] as $root => $id) {
                    if (!file_exists($root) || !Files::isDeletable($root)) {
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
                    Http::redirect($_POST['redir']);
                } else {
                    dcCore::app()->adminurl?->redirect('admin.plugin.' . My::id(), [], '#packman-repository-' . $type);
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
                    Http::redirect($_POST['redir']);
                } else {
                    dcCore::app()->adminurl?->redirect('admin.plugin.' . My::id(), [], '#packman-repository-' . $type);
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
                        $dest . DIRECTORY_SEPARATOR . basename($root),
                        file_get_contents($root)
                    );
                }

                dcPage::addSuccessNotice(
                    __('Package successfully copied.')
                );

                if (!empty($_POST['redir'])) {
                    Http::redirect($_POST['redir']);
                } else {
                    dcCore::app()->adminurl?->redirect('admin.plugin.' . My::id(), [], '#packman-repository-' . $type);
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
                    Http::redirect($_POST['redir']);
                } else {
                    dcCore::app()->adminurl?->redirect('admin.plugin.' . My::id(), [], '#packman-repository-' . $type);
                }
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!static::$init) {
            return;
        }

        # Settings
        $s             = new Settings();
        $is_configured = $is_plugins_configured = $is_themes_configured = true;

        if ($s->pack_typedrepo) {
            $dir_plugins           = Utils::getRepositoryDir($s->pack_repository, 'plugins');
            $is_plugins_configured = Utils::isConfigured(
                $dir_plugins,
                $s->pack_filename,
                $s->secondpack_filename
            );
            $dir_themes           = Utils::getRepositoryDir($s->pack_repository, 'themes');
            $is_themes_configured = Utils::isConfigured(
                $dir_themes,
                $s->pack_filename,
                $s->secondpack_filename
            );
        } else {
            $dir           = Utils::getRepositoryDir($s->pack_repository);
            $is_configured = Utils::isConfigured(
                $dir,
                $s->pack_filename,
                $s->secondpack_filename
            );
        }

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

        if (dcCore::app()->error->flag() || !$is_configured || !$is_plugins_configured || !$is_themes_configured) {
            echo
            (new Div())
                ->separator(' ')
                ->class('warning')
                ->items([
                    (new Text(null, sprintf(__('Module "%s" is not well configured.'), My::name()))),
                ])
                ->render();
        } else {
            Utils::modules(
                dcCore::app()->plugins->getDefines((new Settings())->hide_distrib ? ['distributed' => false] : []),
                'plugins',
                __('Installed plugins')
            );

            Utils::modules(
                dcCore::app()->themes->getDefines((new Settings())->hide_distrib ? ['distributed' => false] : []),
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

            if ($s->pack_typedrepo) {
                Utils::repository(
                    array_merge(
                        Core::getPackages(dirname($dir_themes . DIRECTORY_SEPARATOR . $s->pack_filename)),
                        Core::getPackages(dirname($dir_themes . DIRECTORY_SEPARATOR . $s->secondpack_filename))
                    ),
                    'repository-themes',
                    __('Themes packages repository')
                );
                Utils::repository(
                    array_merge(
                        Core::getPackages(dirname($dir_plugins . DIRECTORY_SEPARATOR . $s->pack_filename)),
                        Core::getPackages(dirname($dir_plugins . DIRECTORY_SEPARATOR . $s->secondpack_filename))
                    ),
                    'repository-plugins',
                    __('Plugins packages repository')
                );
            } else {
                Utils::repository(
                    array_merge(
                        Core::getPackages(dirname($dir . DIRECTORY_SEPARATOR . $s->pack_filename)),
                        Core::getPackages(dirname($dir . DIRECTORY_SEPARATOR . $s->secondpack_filename))
                    ),
                    'repository',
                    __('Packages repository')
                );
            }
        }

        # --BEHAVIOR-- packmanAdminTabs
        dcCore::app()->callBehavior('packmanAdminTabs');

        dcPage::helpBlock('pacKman');
        dcPage::closeModule();
    }
}
