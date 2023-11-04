<?php

declare(strict_types=1);

namespace Dotclear\Plugin\pacKman;

use Dotclear\App;
use Dotclear\Helper\Date;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Form,
    Hidden,
    Label,
    Link,
    Para,
    Select,
    Submit,
    Text
};
use Dotclear\Helper\Html\Html;
use Dotclear\Module\ModuleDefine;
use Exception;

/**
 * @brief       pacKman utils class.
 * @ingroup     pacKman
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Utils
{
    public static function getPluginsPath(): string
    {
        $e = explode(PATH_SEPARATOR, App::config()->pluginsRoot());
        $p = array_pop($e);

        return (string) Path::real($p);
    }

    public static function getThemesPath(): string
    {
        return App::blog()->themesPath();
    }

    public static function isConfigured(string $repo, string $file_a, string $file_b): bool
    {
        sleep(1);
        if (!is_writable($repo)) {
            App::error()->add(
                __('Path to repository is not writable.')
            );
        }

        if (empty($file_a)) {
            App::error()->add(
                __('You must specify the name of package to export.')
            );
        }

        if (!is_writable(dirname($repo . DIRECTORY_SEPARATOR . $file_a))) {
            App::error()->add(
                __('Path to first export package is not writable.')
            );
        }

        if (!empty($file_b) && !is_writable(dirname($repo . DIRECTORY_SEPARATOR . $file_b))) {
            App::error()->add(
                __('Path to second export package is not writable.')
            );
        }

        return !App::error()->flag();
    }

    public static function isWritable(string $path, string $file): bool
    {
        return !(empty($path) || empty($file) || !is_writable(dirname($path . DIRECTORY_SEPARATOR . $file)));
    }

    public static function getRepositoryDir(?string $dir, ?string $typed = null): string
    {
        $typed = empty($typed) ? '' : DIRECTORY_SEPARATOR . ($typed == 'themes' ? 'themes' : 'plugins');
        $dir   = empty($dir) ? App::config()->varRoot() . DIRECTORY_SEPARATOR . 'packman' . $typed : $dir . $typed;

        try {
            @Files::makeDir($dir, true);
        } catch (Exception $e) {
            $dir = '';
        }

        return $dir;
    }

    /**
     * Get modules list form.
     *
     * @param   array<int|string, mixed>    $modules    The modules
     * @param   string                      $type       The modules type
     * @param   string                      $title      The list title
     *
     * @return  null|bool   True on render
     */
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
            if (!is_a($module, ModuleDefine::class)) {
                continue;
            }
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
                    ->action(My::manageUrl())
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
                                (new Submit(['packup']))
                                    ->value(__('Pack up selected modules')),
                                ... My::hiddenFields([
                                    'type'   => $type,
                                    'action' => 'packup',
                                    'redir'  => Html::escapeHTML($_REQUEST['redir'] ?? ''),
                                ]),
                            ]),
                    ]),
            ])
            ->render();

        return true;
    }

    /**
     * Get modules repository list form.
     *
     * @param   array<int,ModuleDefine>     $modules    The modules
     * @param   string                      $type       The modules type
     * @param   string                      $title      The list title
     *
     * @return  null|bool   True on render
     */
    public static function repository(array $modules, string $type, string $title): ?bool
    {
        if (empty($modules)) {
            return null;
        }
        if (!in_array($type, ['plugins', 'themes', 'repository', 'repository-themes', 'repository-plugins'])) {
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
        if (!str_contains($type, 'repository')) {
            $combo_action[sprintf(__('copy to %s directory'), __('repository'))] = 'copy_to_repository';
            $combo_action[sprintf(__('move to %s directory'), __('repository'))] = 'move_to_repository';
        }

        $helpers_addon = [];
        if (str_contains($type, 'repository')) {
            $helpers_addon[] = (new Link())
                ->class('button')
                ->href(App::backend()->url()->get('admin.plugin.' . My::id(), ['purge' => 1]) . '#packman-repository-' . $type)
                ->text(__('Select non lastest versions'))
            ;
        }

        $versions = [];
        if (!empty($_REQUEST['purge']) && str_contains($type, 'repository')) {
            foreach ($modules as $module) {
                if (!isset($versions[$module->getId()]) || version_compare($module->get('version'), $versions[$module->getId()], '>')) {
                    $versions[$module->getId()] = $module->get('version');
                }
            }
        }

        $dup = $tbody = [];
        $i   = 1;
        self::sort($modules);
        foreach ($modules as $module) {
            if (isset($dup[$module->get('root')])) {
                //continue;
            }
            $checked = isset($versions[$module->getId()]) && version_compare($versions[$module->getId()], $module->get('version'), '>');

            $dup[$module->get('root')] = 1;

            $tbody[] = (new Para(null, 'tr'))
                ->class('line')
                ->items([
                    (new Para(null, 'td'))
                        ->class('nowrap')
                        ->items([
                            (new Checkbox(['modules[' . Html::escapeHTML($module->get('root')) . ']', 'r_modules_' . $type . $i], $checked))
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
                                    'href="' . App::backend()->url()->get('admin.plugin.' . My::id(), [
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
                    ->action(My::manageUrl())
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
                        (new Para())
                            ->items($helpers_addon),
                        (new Para())->class('col right')
                            ->items([
                                (new Text(null, __('Selected modules action:') . ' ')),
                                (new Select(['action']))
                                    ->items($combo_action),
                                (new Submit(['packup']))
                                    ->value(__('ok')),
                                ... My::hiddenFields([
                                    'tab'  => 'repository',
                                    'type' => $type,
                                ]),
                            ]),
                    ]),
            ])
            ->render();

        return true;
    }

    /**
     * Sort modules by id.
     *
     * @param   array<int,ModuleDefine>     $modules    The modules
     */
    protected static function sort(array &$modules): void
    {
        uasort($modules, fn ($a, $b) => $a->get('version') <=> $b->get('version'));
        uasort($modules, fn ($a, $b) => strtolower($a->get('id')) <=> strtolower($b->get('id')));
    }
}
