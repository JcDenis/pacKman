<?php

declare(strict_types=1);

namespace Dotclear\Plugin\pacKman;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Core\Backend\Notices;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Fieldset,
    Img,
    Input,
    Label,
    Legend,
    Note,
    Para,
    Text
};
use Exception;

/**
 * @brief       pacKman configuration class.
 * @ingroup     pacKman
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Config extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::CONFIG));
    }

    public static function process(): bool
    {
        if (!self::status()) {
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

            Notices::addSuccessNotice(
                __('Configuration has been successfully updated.')
            );
            App::backend()->url()->redirect('admin.plugins', [
                'module' => My::id(),
                'conf'   => '1',
                'redir'  => App::backend()->__get('list')->getRedir(),
            ]);
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

        # -- Get settings --
        $s = new Settings();

        # -- Check config --
        $img_on  = (new Img('images/check-on.svg'))->class(['mark','mark-check-on'])->title(__('writable'))->render();
        $img_off = (new Img('images/check-off.svg'))->class(['mark','mark-check-off'])->title(__('not writable'))->render();

        $repo         = Utils::getRepositoryDir($s->pack_repository);
        $check_repo   = Utils::isWritable($repo, '_.zip') ? $img_on : $img_off;
        $check_first  = !empty($s->pack_filename)       && Utils::isWritable($repo, $s->pack_filename) ? $img_on : $img_off;
        $check_second = !empty($s->secondpack_filename) && Utils::isWritable($repo, $s->secondpack_filename) ? $img_on : $img_off;

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
                    (new Input('pack_repository'))->class('maximal')->size(65)->maxlength(255)->value($s->pack_repository),
                ]),
                (new Note())->class('form-note')->text(
                    sprintf(
                        __('Preconization: %s'),
                        App::blog()->publicPath() == '' ?
                        App::blog()->publicPath() : __("Blog's public directory")
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
                    (new Input('pack_filename'))->class('maximal')->size(65)->maxlength(255)->value($s->pack_filename),
                ]),
                (new Note())->text(sprintf(__('Preconization: %s'), '%type%-%id%'))->class('form-note'),
                // secondpack_filename
                (new Para())->items([
                    (new Label($check_second . __('Name of second exported package:')))->for('secondpack_filename'),
                    (new Input('secondpack_filename'))->class('maximal')->size(65)->maxlength(255)->value($s->secondpack_filename),
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
                    (new Input('pack_excludefiles'))->class('maximal')->size(65)->maxlength(255)->value($s->pack_excludefiles),
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
