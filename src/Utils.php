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

use dcCore;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Helper\File\Zip\Zip;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Hidden,
    Label,
    Para,
    Select,
    Submit,
    Text
};
use Dotclear\Helper\Html\Html;

use dt;

class Utils
{
    public static function getPluginsPath(): string
    {
        $e = explode(PATH_SEPARATOR, DC_PLUGINS_ROOT);
        $p = array_pop($e);

        return (string) Path::real($p);
    }

    public static function getThemesPath(): string
    {
        return (string) dcCore::app()->blog?->themes_path;
    }

    public static function isConfigured(string $repo, string $file_a, string $file_b): bool
    {
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

        if (!is_writable(dirname($repo . DIRECTORY_SEPARATOR . $file_a))) {
            dcCore::app()->error->add(
                __('Path to first export package is not writable.')
            );
        }

        if (!empty($file_b) && !is_writable(dirname($repo . DIRECTORY_SEPARATOR . $file_b))) {
            dcCore::app()->error->add(
                __('Path to second export package is not writable.')
            );
        }

        return !dcCore::app()->error->flag();
    }

    public static function isWritable(string $path, string $file): bool
    {
        return !(empty($path) || empty($file) || !is_writable(dirname($path . DIRECTORY_SEPARATOR . $file)));
    }

    public static function getUnzipCapability(): string
    {
        switch (Unzip::USE_DEFAULT) {
            case Unzip::USE_PHARDATA:
                if (class_exists('PharData')) {
                    return 'PharData';
                }
                if (class_exists('ZipArchive')) {
                    return 'ZipArchive';
                }

                break;
            case Unzip::USE_ZIPARCHIVE:
                if (class_exists('ZipArchive')) {
                    return 'ZipArchive';
                }
                if (class_exists('PharData')) {
                    return 'PharData';
                }

                break;
            case Unzip::USE_LEGACY:

                break;
        }

        return 'Legacy';
    }

    public static function getZipCapability(): string
    {
        switch (Zip::USE_DEFAULT) {
            case Zip::USE_PHARDATA:
                if (class_exists('PharData')) {
                    return 'PharData';
                }
                if (class_exists('ZipArchive')) {
                    return 'ZipArchive';
                }

                break;
            case Zip::USE_ZIPARCHIVE:
                if (class_exists('ZipArchive')) {
                    return 'ZipArchive';
                }
                if (class_exists('PharData')) {
                    return 'PharData';
                }

                break;
            case Unzip::USE_LEGACY:

                break;
        }

        return 'Legacy';
    }

    public static function getRepositoryDir(?string $dir): string
    {
        if (empty($dir)) {
            try {
                $dir = DC_VAR . DIRECTORY_SEPARATOR . 'packman';
                @Files::makeDir($dir, true);
            } catch (Exception $e) {
                $dir = '';
            }
        }

        return $dir;
    }

    public static function modules(array $modules, string $type, string $title): ?bool
    {
        if (empty($modules)) {
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

        $i = 1;
        self::sort($modules);
        foreach ($modules as $module) {
            echo
            '<tr class="line">' .
            (new Para(null, 'td'))->class('nowrap')->items([
                (new Checkbox(['modules[' . Html::escapeHTML($module->get('root')) . ']', 'modules_' . $type . $i], false))->value(Html::escapeHTML($module->getId())),
                (new Label(Html::escapeHTML($module->getId()), Label::OUTSIDE_LABEL_AFTER))->for('modules_' . $type . $i)->class('classic'),

            ])->render() .
            '<td class="nowrap count">' .
                Html::escapeHTML($module->get('version')) .
            '</td>' .
            '<td class="nowrap maximal">' .
                __(Html::escapeHTML($module->get('name'))) .
            '</td>' .
            '<td class="nowrap">' .
                dirname((string) Path::real($module->get('root'), false)) .
            '</td>' .
            '</tr>';

            $i++;
        }

        echo
        '</table>' .
        '<p class="checkboxes-helpers"></p>' .
        (new Para())->items([
            (new Hidden(['redir'], Html::escapeHTML($_REQUEST['redir'] ?? ''))),
            (new Hidden(['p'], My::id())),
            (new Hidden(['type'], $type)),
            (new Hidden(['action'], 'packup')),
            (new Submit(['packup']))->value(__('Pack up selected modules')),
            dcCore::app()->formNonce(false),
        ])->render() .
        '</form>' .

        '</div>';

        return true;
    }

    public static function repository(array $modules, string $type, string $title): ?bool
    {
        if (empty($modules)) {
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
        $i   = 1;
        self::sort($modules);
        foreach ($modules as $module) {
            if (isset($dup[$module->get('root')])) {
                continue;
            }

            $dup[$module->get('root')] = 1;

            echo
            '<tr class="line">' .
            (new Para(null, 'td'))->class('nowrap')->items([
                (new Checkbox(['modules[' . Html::escapeHTML($module->get('root')) . ']', 'r_modules_' . $type . $i], false))->value(Html::escapeHTML($module->getId())),
                (new Label(Html::escapeHTML($module->getId()), Label::OUTSIDE_LABEL_AFTER))->for('r_modules_' . $type . $i)->class('classic')->title(Html::escapeHTML($module->get('root'))),

            ])->render() .
            '<td class="nowrap count">' .
                Html::escapeHTML($module->get('version')) .
            '</td>' .
            '<td class="nowrap maximal">' .
                __(Html::escapeHTML($module->get('name'))) .
            '</td>' .
            '<td class="nowrap">' .
                '<a class="packman-download" href="' .
                dcCore::app()->adminurl?->get('admin.plugin.' . My::id(), [
                    'package' => basename($module->get('root')),
                    'repo'    => $type,
                ]) . '" title="' . __('Download') . '">' .
                Html::escapeHTML(basename($module->get('root'))) . '</a>' .
            '</td>' .
            '<td class="nowrap">' .
                Html::escapeHTML(dt::str(__('%Y-%m-%d %H:%M'), (int) @filemtime($module->get('root')))) .
            '</td>' .
            '</tr>';

            $i++;
        }

        echo
        '</table>' .
        '<div class="two-cols">' .
        '<p class="col checkboxes-helpers"></p>' .
        (new Para())->class('col right')->items([
            (new Text('', __('Selected modules action:') . ' ')),
            (new Select(['action']))->items($combo_action),
            (new Submit(['packup']))->value(__('ok')),
            (new Hidden(['p'], My::id())),
            (new Hidden(['tab'], 'repository')),
            (new Hidden(['type'], $type)),
            dcCore::app()->formNonce(false),
        ])->render() .
        '</div>' .
        '</form>' .
        '</div>';

        return true;
    }

    protected static function sort(array &$modules): void
    {
        uasort($modules, fn ($a, $b) => $a->get('version') <=> $b->get('version'));
        uasort($modules, fn ($a, $b) => strtolower($a->get('id')) <=> strtolower($b->get('id')));
    }
}
