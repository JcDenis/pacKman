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
use Exception;

class Config extends dcNsProcess
{
    public static function init(): bool
    {
        self::$init = defined('DC_CONTEXT_ADMIN');

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

        # -- Set settings --
        try {
            $pack_nocomment      = !empty($_POST['pack_nocomment']);
            $pack_fixnewline     = !empty($_POST['pack_fixnewline']);
            $pack_overwrite      = !empty($_POST['pack_overwrite']);
            $pack_filename       = (string) $_POST['pack_filename'];
            $secondpack_filename = (string) $_POST['secondpack_filename'];
            $pack_repository     = (string) $_POST['pack_repository'];
            $pack_excludefiles   = (string) $_POST['pack_excludefiles'];

            $check = Utils::is_configured(
                Utils::getRepositoryDir($pack_repository),
                $pack_filename,
                $secondpack_filename
            );

            if ($check) {
                $s = dcCore::app()->blog->settings->get(My::id());
                $s->put('pack_nocomment', $pack_nocomment);
                $s->put('pack_fixnewline', $pack_fixnewline);
                $s->put('pack_overwrite', $pack_overwrite);
                $s->put('pack_filename', $pack_filename);
                $s->put('secondpack_filename', $secondpack_filename);
                $s->put('pack_repository', $pack_repository);
                $s->put('pack_excludefiles', $pack_excludefiles);

                dcPage::addSuccessNotice(
                    __('Configuration has been successfully updated.')
                );
                dcCore::app()->adminurl->redirect('admin.plugins', [
                    'module' => My::id(),
                    'conf'   => '1',
                    'redir'  => dcCore::app()->admin->__get('list')->getRedir(),
                ]);
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

        # -- Get settings --
        $s = dcCore::app()->blog->settings->get(My::id());

        # -- Display form --
        echo
        (new Div())->items([
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Root'))))->fields([
                // pack_repository
                (new Para())->items([
                    (new Label(__('Path to repository:')))->for('pack_repository'),
                    (new Input('pack_repository'))->class('maximal')->size(65)->maxlenght(255)->value((string) $s->get('pack_repository')),
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
                    (new Input('pack_filename'))->class('maximal')->size(65)->maxlenght(255)->value((string) $s->get('pack_filename')),
                ]),
                (new Note())->text(sprintf(__('Preconization: %s'), '%type%-%id%'))->class('form-note'),
                // secondpack_filename
                (new Para())->items([
                    (new Label(__('Name of second exported package:')))->for('secondpack_filename'),
                    (new Input('secondpack_filename'))->class('maximal')->size(65)->maxlenght(255)->value((string) $s->get('secondpack_filename')),
                ]),
                (new Note())->text(sprintf(__('Preconization: %s'), '%type%-%id%-%version%'))->class('form-note'),
                // pack_overwrite
                (new Para())->items([
                    (new Checkbox('pack_overwrite', (bool) $s->get('pack_overwrite')))->value(1),
                    (new Label(__('Overwrite existing package'), Label::OUTSIDE_LABEL_AFTER))->for('pack_overwrite')->class('classic'),
                ]),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Content'))))->fields([
                // pack_excludefiles
                (new Para())->items([
                    (new Label(__('Extra files to exclude from package:')))->for('pack_excludefiles'),
                    (new Input('pack_excludefiles'))->class('maximal')->size(65)->maxlenght(255)->value((string) $s->get('pack_excludefiles')),
                ]),
                (new Note())->text(sprintf(__('Preconization: %s'), '*.zip,*.tar,*.tar.gz'))->class('form-note'),
                // pack_nocomment
                (new Para())->items([
                    (new Checkbox('pack_nocomment', (bool) $s->get('pack_nocomment')))->value(1),
                    (new Label(__('Remove comments from files'), Label::OUTSIDE_LABEL_AFTER))->for('pack_nocomment')->class('classic'),
                ]),
                // pack_fixnewline
                (new Para())->items([
                    (new Checkbox('pack_fixnewline', (bool) $s->get('pack_fixnewline')))->value(1),
                    (new Label(__('Fix newline style from files content'), Label::OUTSIDE_LABEL_AFTER))->for('pack_fixnewline')->class('classic'),
                ]),

            ]),
        ])->render();
    }
}
