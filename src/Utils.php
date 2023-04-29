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
use Dotclear\Helper\Date;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Form,
    Hidden,
    Label,
    Para,
    Select,
    Submit,
    Text
};
use Dotclear\Helper\Html\Html;
use Exception;

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

        $i     = 1;
        $tbody = [];
        self::sort($modules);
        foreach ($modules as $module) {
            $tbody[] = (new Para(null, 'tr'))
                ->class('line')
                ->items([
                    (new Para(null, 'td'))
                        ->class('nowrap')
                        ->items([
                            (new Checkbox(['modules[' . Html::escapeHTML($module->get('root')) . ']', 'modules_' . $type . $i], false))
                                ->value(Html::escapeHTML($module->getId())),
                            (new Label(Html::escapeHTML($module->getId()), Label::OUTSIDE_LABEL_AFTER))
                                ->class('classic')
                                ->for('modules_' . $type . $i),
                        ]),
                    (new Text('td', Html::escapeHTML($module->get('version'))))
                        ->class('nowrap count'),
                    (new Text('td', Html::escapeHTML($module->get('name'))))
                        ->class('nowrap'),
                    (new Text('td', dirname((string) Path::real($module->get('root'), false))))
                        ->class('nowrap maximal'),
                ]);

            $i++;
        }

        echo
        (new Div('packman-' . $type))
            ->class('multi-part')
            ->title($title)
            ->items([
                (new Text('h3', $title)),
                (new Form('packman-form-' . $type))
                    ->method('post')
                    ->action('plugin.php')
                    ->fields([
                        (new Para(null, 'table'))
                            ->class('clear')
                            ->items([
                                (new Para(null, 'tr'))
                                    ->items([
                                        (new Text('th', Html::escapeHTML(__('Id'))))
                                            ->class('nowrap'),
                                        (new Text('th', Html::escapeHTML(__('Version'))))
                                            ->class('nowrap'),
                                        (new Text('th', Html::escapeHTML(__('Name'))))
                                            ->class('nowrap'),
                                        (new Text('th', Html::escapeHTML(__('Root'))))
                                            ->class('nowrap'),
                                    ]),
                                (new Para(null, 'tbody'))
                                    ->items($tbody),
                            ]),
                        (new Para())
                            ->class('checkboxes-helpers'),
                        (new Para())
                            ->items([
                                (new Hidden(['redir'], Html::escapeHTML($_REQUEST['redir'] ?? ''))),
                                (new Hidden(['p'], My::id())),
                                (new Hidden(['type'], $type)),
                                (new Hidden(['action'], 'packup')),
                                (new Submit(['packup']))
                                    ->value(__('Pack up selected modules')),
                                dcCore::app()->formNonce(false),
                            ]),
                    ]),
            ])
            ->render();

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

        $dup = $tbody = [];
        $i   = 1;
        self::sort($modules);
        foreach ($modules as $module) {
            if (isset($dup[$module->get('root')])) {
                continue;
            }

            $dup[$module->get('root')] = 1;

            $tbody[] = (new Para(null, 'tr'))
                ->class('line')
                ->items([
                    (new Para(null, 'td'))
                        ->class('nowrap')
                        ->items([
                            (new Checkbox(['modules[' . Html::escapeHTML($module->get('root')) . ']', 'r_modules_' . $type . $i], false))
                                ->value(Html::escapeHTML($module->getId())),
                            (new Label(Html::escapeHTML($module->getId()), Label::OUTSIDE_LABEL_AFTER))
                                ->class('classic')
                                ->for('r_modules_' . $type . $i)
                                ->title(Html::escapeHTML($module->get('root'))),
                        ]),
                    (new Text('td', Html::escapeHTML($module->get('version'))))
                        ->class('nowrap count'),
                    (new Text('td', Html::escapeHTML($module->get('name'))))
                        ->class('nowrap'),
                    (new Para(null, 'td'))
                        ->class('nowrap')
                        ->items([
                            (new Text('a', Html::escapeHTML(basename($module->get('root')))))
                                ->class('packman-download')
                                ->extra(
                                    'href="' . dcCore::app()->adminurl?->get('admin.plugin.' . My::id(), [
                                        'package' => basename($module->get('root')),
                                        'repo'    => $type,
                                    ]) . '"'
                                )
                                ->title(__('Download')),
                        ]),
                    (new Text('td', Html::escapeHTML(Date::str(__('%Y-%m-%d %H:%M'), (int) @filemtime($module->get('root'))))))
                        ->class('nowrap maximal'),
                ]);

            $i++;
        }

        echo
        (new Div('packman-repository-' . $type))
            ->class('multi-part')
            ->title($title)
            ->items([
                (new Text('h3', $title)),
                (new Form('packman-form-repository-' . $type))
                    ->method('post')
                    ->action('plugin.php')
                    ->fields([
                        (new Para(null, 'table'))
                            ->class('clear')
                            ->items([
                                (new Para(null, 'tr'))
                                    ->items([
                                        (new Text('th', Html::escapeHTML(__('Id'))))
                                            ->class('nowrap'),
                                        (new Text('th', Html::escapeHTML(__('Version'))))
                                            ->class('nowrap'),
                                        (new Text('th', Html::escapeHTML(__('Name'))))
                                            ->class('nowrap'),
                                        (new Text('th', Html::escapeHTML(__('File'))))
                                            ->class('nowrap'),
                                        (new Text('th', Html::escapeHTML(__('Date'))))
                                            ->class('nowrap'),
                                    ]),
                                (new Para(null, 'tbody'))
                                    ->items($tbody),
                            ]),
                        (new Para())
                            ->class('checkboxes-helpers'),
                        (new Para())->class('col right')
                            ->items([
                                (new Text(null, __('Selected modules action:') . ' ')),
                                (new Select(['action']))
                                    ->items($combo_action),
                                (new Submit(['packup']))
                                    ->value(__('ok')),
                                (new Hidden(['p'], My::id())),
                                (new Hidden(['tab'], 'repository')),
                                (new Hidden(['type'], $type)),
                                dcCore::app()->formNonce(false),
                            ]),
                    ]),
            ])
            ->render();

        return true;
    }

    protected static function sort(array &$modules): void
    {
        uasort($modules, fn ($a, $b) => $a->get('version') <=> $b->get('version'));
        uasort($modules, fn ($a, $b) => strtolower($a->get('id')) <=> strtolower($b->get('id')));
    }
}
