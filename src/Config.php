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
use dcPage;
use dcNsProcess;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Fieldset,
    Input,
    Label,
    Legend,
    Note,
    Para
};

class Config extends dcNsProcess
{
    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            if (version_compare(phpversion(), My::PHP_MIN, '>=')) {
                self::$init = true;
            } else {
                dcCore::app()->error->add(sprintf(__('%s required php >= %s'), My::id(), My::PHP_MIN));
            }
        }

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        if (empty($_POST['save'])) {
            return true;
        }

        $s = new Settings();

        # -- Set settings --
        try {
            foreach ($s->listSettings() as $key) {
                $s->writeSetting($key, $_POST[$key] ?? '');
            }

            dcPage::addSuccessNotice(
                __('Configuration has been successfully updated.')
            );
            dcCore::app()->adminurl->redirect('admin.plugins', [
                'module' => My::id(),
                'conf'   => '1',
                'redir'  => dcCore::app()->admin->__get('list')->getRedir(),
            ]);
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

        # -- Get settings --
        $s = new Settings();

        # -- Display form --
        echo
        (new Div())->items([
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Root'))))->fields([
                // pack_repository
                (new Para())->items([
                    (new Label(__('Path to repository:')))->for('pack_repository'),
                    (new Input('pack_repository'))->class('maximal')->size(65)->maxlenght(255)->value($s->pack_repository),
                ]),
                (new Note())->class('form-note')->text(
                    sprintf(
                        __('Preconization: %s'),
                        dcCore::app()->blog->public_path ?
                        dcCore::app()->blog->public_path : __("Blog's public directory")
                    ) . ' ' . __('Leave it empty to use Dotclear VAR directory')
                ),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Files'))))->fields([
                // pack_filename
                (new Para())->items([
                    (new Label(__('Name of exported package:')))->for('pack_filename'),
                    (new Input('pack_filename'))->class('maximal')->size(65)->maxlenght(255)->value($s->pack_filename),
                ]),
                (new Note())->text(sprintf(__('Preconization: %s'), '%type%-%id%'))->class('form-note'),
                // secondpack_filename
                (new Para())->items([
                    (new Label(__('Name of second exported package:')))->for('secondpack_filename'),
                    (new Input('secondpack_filename'))->class('maximal')->size(65)->maxlenght(255)->value($s->secondpack_filename),
                ]),
                (new Note())->text(sprintf(__('Preconization: %s'), '%type%-%id%-%version%'))->class('form-note'),
                // pack_overwrite
                (new Para())->items([
                    (new Checkbox('pack_overwrite', $s->pack_overwrite))->value(1),
                    (new Label(__('Overwrite existing package'), Label::OUTSIDE_LABEL_AFTER))->for('pack_overwrite')->class('classic'),
                ]),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Content'))))->fields([
                // pack_excludefiles
                (new Para())->items([
                    (new Label(__('Extra files to exclude from package:')))->for('pack_excludefiles'),
                    (new Input('pack_excludefiles'))->class('maximal')->size(65)->maxlenght(255)->value($s->pack_excludefiles),
                ]),
                (new Note())->text(sprintf(__('Preconization: %s'), '*.zip,*.tar,*.tar.gz'))->class('form-note'),
                // pack_nocomment
                (new Para())->items([
                    (new Checkbox('pack_nocomment', $s->pack_nocomment))->value(1),
                    (new Label(__('Remove comments from files'), Label::OUTSIDE_LABEL_AFTER))->for('pack_nocomment')->class('classic'),
                ]),
                // pack_fixnewline
                (new Para())->items([
                    (new Checkbox('pack_fixnewline', $s->pack_fixnewline))->value(1),
                    (new Label(__('Fix newline style from files content'), Label::OUTSIDE_LABEL_AFTER))->for('pack_fixnewline')->class('classic'),
                ]),

            ]),
        ])->render();
    }
}
