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
    Para,
    Text
};
use Exception;

class Config extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init == defined('DC_CONTEXT_ADMIN')
            && dcCore::app()->auth?->isSuperAdmin();

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        if (empty($_POST['save'])) {
            return true;
        }

        $s = new Settings();

        # -- Set settings --
        try {
            foreach ($s->listSettings() as $key => $value) {
                if (is_bool($value)) {
                    $s->writeSetting($key, !empty($_POST[$key]));
                } else {
                    $s->writeSetting($key, $_POST[$key] ?? $value);
                }
            }

            dcPage::addSuccessNotice(
                __('Configuration has been successfully updated.')
            );
            dcCore::app()->adminurl?->redirect('admin.plugins', [
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
        if (!static::$init) {
            return;
        }

        # -- Get settings --
        $s = new Settings();

        # -- Check config --
        $img     = '<img alt="%1$s" title="%1$s" src="images/%2$s" /> ';
        $img_on  = sprintf($img, __('writable'), 'check-on.png');
        $img_off = sprintf($img, __('not writable'), 'check-off.png');

        $repo         = Utils::getRepositoryDir($s->pack_repository);
        $check_repo   = Utils::isWritable($repo, '_.zip') ? $img_on : $img_off;
        $check_first  = !empty($s->pack_filename)       && Utils::isWritable($repo, $s->pack_filename) ? $img_on : $img_off;
        $check_second = !empty($s->secondpack_filename) && Utils::isWritable($repo, $s->secondpack_filename) ? $img_on : $img_off;

        $is_configured = Utils::isConfigured(
            $repo,
            $s->pack_filename,
            $s->secondpack_filename
        );
        $check_conf = $is_configured ? $img_on . sprintf(__('%s is well configured.'), My::name()) : $img_off . sprintf(__('%s is not well configured.'), My::name());

        # -- Display form --
        echo
        (new Div())->items([
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Interface'))))->fields([
                // hide_distrib
                (new Para())->items([
                    (new Checkbox('hide_distrib', $s->hide_distrib))->value(1),
                    (new Label(__('Hide distributed modules from lists'), Label::OUTSIDE_LABEL_AFTER))->for('hide_distrib')->class('classic'),
                ]),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Root'))))->fields([
                // pack_repository
                (new Para())->items([
                    (new Label($check_repo . __('Path to repository:')))->for('pack_repository'),
                    (new Input('pack_repository'))->class('maximal')->size(65)->maxlenght(255)->value($s->pack_repository),
                ]),
                (new Note())->class('form-note')->text(
                    sprintf(
                        __('Preconization: %s'),
                        dcCore::app()->blog?->public_path ?
                        dcCore::app()->blog->public_path : __("Blog's public directory")
                    ) . ' ' . __('Leave it empty to use Dotclear VAR directory')
                ),
                // pack_overwrite
                (new Para())->items([
                    (new Checkbox('pack_typedrepo', $s->pack_typedrepo))->value(1),
                    (new Label(__('Seperate themes and plugins'), Label::OUTSIDE_LABEL_AFTER))->for('pack_typedrepo')->class('classic'),
                ]),
                (new Note())->class('form-note')->text(__('This creates one repository sub folder for themes and one for plugins')),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Files'))))->fields([
                // pack_filename
                (new Para())->items([
                    (new Label($check_first . __('Name of exported package:')))->for('pack_filename'),
                    (new Input('pack_filename'))->class('maximal')->size(65)->maxlenght(255)->value($s->pack_filename),
                ]),
                (new Note())->text(sprintf(__('Preconization: %s'), '%type%-%id%'))->class('form-note'),
                // secondpack_filename
                (new Para())->items([
                    (new Label($check_second . __('Name of second exported package:')))->for('secondpack_filename'),
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
