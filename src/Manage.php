<?php

declare(strict_types=1);

namespace Dotclear\Plugin\pacKman;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Core\Backend\{
    Notices,
    Page
};
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Form\{
    Div,
    Text
};
use Dotclear\Helper\Network\Http;
use Exception;

/**
 * @brief   pacKman manage page class.
 * @ingroup pacKman
 *
 * @author      Jean-Christian Denis
 * @copyright   Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Manage extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
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
        if (App::themes()->isEmpty()) {
            App::themes()->loadModules(App::blog()->themesPath(), null);
        }

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
                        App::behavior()->callBehavior('packmanBeforeDownloadPackage', $module->dump(), $type);

                        header('Content-Type: application/zip');
                        header('Content-Length: ' . filesize($module->get('root')));
                        header('Content-Disposition: attachment; filename="' . basename($module->get('root')) . '"');
                        readfile($module->get('root'));

                        # --BEHAVIOR-- packmanAfterDownloadPackage
                        App::behavior()->callBehavior('packmanAfterDownloadPackage', $module->dump(), $type);

                        exit;
                    }
                }

                # Not found
                header('Content-Type: text/plain');
                Http::head(404, 'Not Found');
                exit;
            } elseif (!empty($action) && !$is_editable) {
                Notices::addErrorNotice(
                    __('No modules selected.')
                );

                if (!empty($_POST['redir'])) {
                    Http::redirect($_POST['redir']);
                } else {
                    My::redirect([], '#packman-' . $type);
                }

                # Pack
            } elseif ($action == 'packup') {
                foreach ($_POST['modules'] as $root => $id) {
                    if ($type == 'themes') {
                        if (!App::themes()->getDefine($id)->isDefined()) {
                            throw new Exception('No such module');
                        }

                        $module = App::themes()->getDefine($id);
                    } else {
                        if (!App::plugins()->getDefine($id)->isDefined()) {
                            throw new Exception('No such module');
                        }

                        $module = App::plugins()->getDefine($id);
                    }

                    # --BEHAVIOR-- packmanBeforeCreatePackage
                    App::behavior()->callBehavior('packmanBeforeCreatePackage', $module->dump());

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
                    App::behavior()->callBehavior('packmanAfterCreatePackage', $module->dump());
                }

                Notices::addSuccessNotice(
                    __('Package successfully created.')
                );

                if (!empty($_POST['redir'])) {
                    Http::redirect($_POST['redir']);
                } else {
                    My::redirect([], '#packman-' . $type);
                }

                # Delete
            } elseif ($action == 'delete') {
                $del_success = false;
                foreach ($_POST['modules'] as $root => $id) {
                    if (!file_exists($root) || !Files::isDeletable($root)) {
                        Notices::addWarningNotice(sprintf(__('Undeletable file "%s"', $root)));
                    } else {
                        $del_success = true;
                    }

                    unlink($root);
                }

                if ($del_success) {
                    Notices::addSuccessNotice(
                        __('Package successfully deleted.')
                    );
                }

                if (!empty($_POST['redir'])) {
                    Http::redirect($_POST['redir']);
                } else {
                    My::redirect([], '#packman-repository-' . $type);
                }

                # Install
            } elseif ($action == 'install') {
                foreach ($_POST['modules'] as $root => $id) {
                    # --BEHAVIOR-- packmanBeforeInstallPackage
                    App::behavior()->callBehavior('packmanBeforeInstallPackage', $type, $id, $root);

                    $mods = $type == 'themes' ? App::themes() : App::plugins();
                    $mods->installPackage($root, $mods);

                    # --BEHAVIOR-- packmanAfterInstallPackage
                    App::behavior()->callBehavior('packmanAfterInstallPackage', $type, $id, $root);
                }

                Notices::addSuccessNotice(
                    __('Package successfully installed.')
                );

                if (!empty($_POST['redir'])) {
                    Http::redirect($_POST['redir']);
                } else {
                    My::redirect([], '#packman-repository-' . $type);
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

                Notices::addSuccessNotice(
                    __('Package successfully copied.')
                );

                if (!empty($_POST['redir'])) {
                    Http::redirect($_POST['redir']);
                } else {
                    My::redirect([], '#packman-repository-' . $type);
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

                Notices::addSuccessNotice(
                    __('Package successfully moved.')
                );

                if (!empty($_POST['redir'])) {
                    Http::redirect($_POST['redir']);
                } else {
                    My::redirect([], '#packman-repository-' . $type);
                }
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
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
        Page::openModule(
            My::name(),
            Page::jsPageTabs() .
            My::jsLoad('backend') .

            # --BEHAVIOR-- packmanAdminHeader
            App::behavior()->callBehavior('packmanAdminHeader')
        );

        echo
        Page::breadcrumb([
            __('Plugins') => '',
            My::name()    => '',
        ]) .
        Notices::GetNotices();

        if (App::error()->flag() || !$is_configured || !$is_plugins_configured || !$is_themes_configured) {
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
                App::plugins()->getDefines((new Settings())->hide_distrib ? ['distributed' => false] : []),
                'plugins',
                __('Installed plugins')
            );

            Utils::modules(
                App::themes()->getDefines((new Settings())->hide_distrib ? ['distributed' => false] : []),
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
        App::behavior()->callBehavior('packmanAdminTabs');

        Page::helpBlock('pacKman');
        Page::closeModule();
    }
}
