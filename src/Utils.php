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

/* clearbricks ns */
use dt;
use files;
use form;
use html;
use path;

class Utils
{
    public static function getPluginsPath(): string
    {
        $e = explode(PATH_SEPARATOR, DC_PLUGINS_ROOT);
        $p = array_pop($e);

        return (string) path::real($p);
    }

    public static function getThemesPath(): string
    {
        return dcCore::app()->blog->themes_path;
    }

    public static function is_configured(string $repo, string $file_a, string $file_b): bool
    {
        if (!is_dir(DC_TPL_CACHE) || !is_writable(DC_TPL_CACHE)) {
            dcCore::app()->error->add(
                __('Cache directory is not writable.')
            );
        }
        if (!is_writable($repo)) {
            dcCore::app()->error->add(
                __('Path to repository is not writable.')
            );
        }

        if (empty($file_a)) {
            dcCore::app()->error->add(
                __('You must specify the name of package to export.')
            );
        }

        if (!is_writable(dirname($repo . '/' . $file_a))) {
            dcCore::app()->error->add(
                __('Path to first export package is not writable.')
            );
        }

        if (!empty($file_b) && !is_writable(dirname($repo . '/' . $file_b))) {
            dcCore::app()->error->add(
                __('Path to second export package is not writable.')
            );
        }

        return !dcCore::app()->error->flag();
    }

    public static function is_writable(string $path, string $file): bool
    {
        return !(empty($path) || empty($file) || !is_writable(dirname($path . '/' . $file)));
    }

    public static function getRepositoryDir(?string $dir): string
    {
        if (empty($dir)) {
            try {
                $dir = DC_VAR . DIRECTORY_SEPARATOR . 'packman';
                @files::makeDir($dir, true);
            } catch (Exception $e) {
                $dir = '';
            }
        }

        return $dir;
    }

    public static function getModules(string $type, ?string $id = null): ?array
    {
        $type = $type == 'themes' ? 'themes' : 'plugins';

        $modules = array_merge(dcCore::app()->{$type}->getDisabledModules(), dcCore::app()->{$type}->getModules());

        if ((new Settings())->hide_distrib) {
            $modules = array_diff_key($modules, array_flip(array_values(array_merge(explode(',', DC_DISTRIB_PLUGINS), explode(',', DC_DISTRIB_THEMES)))));
        }

        if (empty($id)) {
            return $modules;
        } elseif (array_key_exists($id, $modules)) {
            return $modules[$id];
        }

        return null;
    }

    public static function moduleExists(string $type, ?string $id): bool
    {
        $type = $type == 'themes' ? 'themes' : 'plugins';

        return array_key_exists($id, array_merge(dcCore::app()->{$type}->getDisabledModules(), dcCore::app()->{$type}->getModules()));
    }

    public static function modules(array $modules, string $type, string $title): ?bool
    {
        if (empty($modules) || !is_array($modules)) {
            return null;
        }

        $type = $type == 'themes' ? 'themes' : 'plugins';

        echo
        '<div class="multi-part" ' .
        'id="packman-' . $type . '" title="' . $title . '">' .
        '<h3>' . $title . '</h3>' .
        '<form action="plugin.php" method="post">' .
        '<table class="clear"><tr>' .
        '<th class="nowrap">' . __('Id') . '</th>' .
        '<th class="nowrap">' . __('Version') . '</th>' .
        '<th class="nowrap maximal">' . __('Name') . '</th>' .
        '<th class="nowrap">' . __('Root') . '</th>' .
        '</tr>';

        foreach (self::sort($modules) as $id => $module) {
            echo
            '<tr class="line">' .
            '<td class="nowrap"><label class="classic">' .
                form::checkbox(['modules[' . html::escapeHTML($module['root']) . ']'], html::escapeHTML($id)) .
                html::escapeHTML($id) .
            '</label></td>' .
            '<td class="nowrap count">' .
                html::escapeHTML($module['version']) .
            '</td>' .
            '<td class="nowrap maximal">' .
                __(html::escapeHTML($module['name'])) .
            '</td>' .
            '<td class="nowrap">' .
                dirname((string) path::real($module['root'], false)) .
            '</td>' .
            '</tr>';
        }

        echo
        '</table>' .
        '<p class="checkboxes-helpers"></p>' .
        '<p>' .
        (
            !empty($_REQUEST['redir']) ?
            form::hidden(
                ['redir'],
                html::escapeHTML($_REQUEST['redir'])
            ) : ''
        ) .
        form::hidden(['p'], My::id()) .
        form::hidden(['type'], $type) .
        form::hidden(['action'], 'packup') .
        '<input type="submit" name="packup" value="' .
         __('Pack up selected modules') . '" />' .
        dcCore::app()->formNonce() . '</p>' .
        '</form>' .

        '</div>';

        return true;
    }

    public static function repository(array $modules, string $type, string $title): ?bool
    {
        if (empty($modules) || !is_array($modules)) {
            return null;
        }
        if (!in_array($type, ['plugins', 'themes', 'repository'])) {
            return null;
        }

        echo
        '<div class="multi-part" ' .
        'id="packman-repository-' . $type . '" title="' . $title . '">' .
        '<h3>' . $title . '</h3>';

        $combo_action = [__('delete') => 'delete'];

        if ($type == 'plugins' || $type == 'themes') {
            $combo_action[__('install')] = 'install';
        }
        if ($type != 'plugins') {
            $combo_action[sprintf(__('copy to %s directory'), __('plugins'))] = 'copy_to_plugins';
            $combo_action[sprintf(__('move to %s directory'), __('plugins'))] = 'move_to_plugins';
        }
        if ($type != 'themes') {
            $combo_action[sprintf(__('copy to %s directory'), __('themes'))] = 'copy_to_themes';
            $combo_action[sprintf(__('move to %s directory'), __('themes'))] = 'move_to_themes';
        }
        if ($type != 'repository') {
            $combo_action[sprintf(__('copy to %s directory'), __('repository'))] = 'copy_to_repository';
            $combo_action[sprintf(__('move to %s directory'), __('repository'))] = 'move_to_repository';
        }

        echo
        '<form action="plugin.php" method="post">' .
        '<table class="clear"><tr>' .
        '<th class="nowrap">' . __('Id') . '</th>' .
        '<th class="nowrap">' . __('Version') . '</th>' .
        '<th class="nowrap">' . __('Name') . '</th>' .
        '<th class="nowrap">' . __('File') . '</th>' .
        '<th class="nowrap">' . __('Date') . '</th>' .
        '</tr>';

        $dup = [];
        foreach (self::sort($modules) as $module) {
            if (isset($dup[$module['root']])) {
                continue;
            }

            $dup[$module['root']] = 1;

            echo
            '<tr class="line">' .
            '<td class="nowrap"><label class="classic" title="' .
                html::escapeHTML($module['root']) . '">' .
                form::checkbox(['modules[' . html::escapeHTML($module['root']) . ']'], $module['id']) .
                html::escapeHTML($module['id']) .
            '</label></td>' .
            '<td class="nowrap count">' .
                html::escapeHTML($module['version']) .
            '</td>' .
            '<td class="nowrap maximal">' .
                __(html::escapeHTML($module['name'])) .
            '</td>' .
            '<td class="nowrap">' .
                '<a class="packman-download" href="' .
                dcCore::app()->adminurl->get('admin.plugin.' . My::id(), [
                    'package' => basename($module['root']),
                    'repo'    => $type,
                ]) . '" title="' . __('Download') . '">' .
                html::escapeHTML(basename($module['root'])) . '</a>' .
            '</td>' .
            '<td class="nowrap">' .
                html::escapeHTML(dt::str(__('%Y-%m-%d %H:%M'), (int) @filemtime($module['root']))) .
            '</td>' .
            '</tr>';
        }

        echo
        '</table>' .
        '<div class="two-cols">' .
        '<p class="col checkboxes-helpers"></p>' .
        '<p class="col right">' . __('Selected modules action:') . ' ' .
        form::combo(['action'], $combo_action) .
        '<input type="submit" name="packup" value="' . __('ok') . '" />' .
        form::hidden(['p'], My::id()) .
        form::hidden(['tab'], 'repository') .
        form::hidden(['type'], $type) .
        dcCore::app()->formNonce() .
        '</p>' .
        '</div>' .
        '</form>' .
        '</div>';

        return true;
    }

    protected static function sort(array $modules): array
    {
        $key = $ver = [];
        foreach ($modules as $i => $module) {
            $key[$i] = $module['id'] ?? $i;
            $ver[$i] = $module['version'];
        }
        array_multisort($key, SORT_ASC, $ver, SORT_ASC, $modules);

        return $modules;
    }
}
