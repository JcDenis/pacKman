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
use dcNsProcess;

/* clearbricks ns */
use form;
use http;

/* php ns */
use Exception;

class Config extends dcNsProcess
{
    private static $pid    = '';

    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            self::$pid  = basename(dirname(__DIR__));
            self::$init = true;
        }

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init || !defined('DC_CONTEXT_MODULE')) {
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
                $s = dcCore::app()->blog->settings->__get(self::$pid);
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
                http::redirect(
                    dcCore::app()->admin->__get('list')->getURL('module=' . self::$pid . '&conf=1&redir=' .
                    dcCore::app()->admin->__get('list')->getRedir())
                );
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
        $s = dcCore::app()->blog->settings->__get(self::$pid);

        # -- Display form --
        echo '
        <div class="fieldset">
        <h4>' . __('Root') . '</h4>

        <p><label for="pack_repository">' . __('Path to repository:') . ' ' .
        form::field('pack_repository', 65, 255, (string) $s->get('pack_repository'), 'maximal') .
        '</label></p>' .
        '<p class="form-note">' . sprintf(
            __('Preconization: %s'),
            dcCore::app()->blog->public_path ?
            dcCore::app()->blog->public_path : __("Blog's public directory")
        ) . '<br />' . __('Leave it empty to use Dotclear VAR directory') . '</p>
        </div>

        <div class="fieldset">
        <h4>' . __('Files') . '</h4>

        <p><label for="pack_filename">' . __('Name of exported package:') . ' ' .
        form::field('pack_filename', 65, 255, (string) $s->get('pack_filename'), 'maximal') .
        '</label></p>
        <p class="form-note">' . sprintf(__('Preconization: %s'), '%type%-%id%') . '</p>

        <p><label for="secondpack_filename">' . __('Name of second exported package:') . ' ' .
        form::field('secondpack_filename', 65, 255, (string) $s->get('secondpack_filename'), 'maximal') .
        '</label></p>
        <p class="form-note">' . sprintf(__('Preconization: %s'), '%type%-%id%-%version%') . '</p>

        <p><label class="classic" for="pack_overwrite">' .
        form::checkbox('pack_overwrite', 1, (bool) $s->get('pack_overwrite')) . ' ' .
        __('Overwrite existing package') . '</label></p>

        </div>

        <div class="fieldset">
        <h4>' . __('Content') . '</h4>

        <p><label for="pack_excludefiles">' . __('Extra files to exclude from package:') . ' ' .
        form::field('pack_excludefiles', 65, 255, (string) $s->get('pack_excludefiles'), 'maximal') .
        '</label></p>
        <p class="form-note">' . sprintf(__('Preconization: %s'), '*.zip,*.tar,*.tar.gz') . '</p>

        <p><label class="classic" for="pack_nocomment">' .
        form::checkbox('pack_nocomment', 1, (bool) $s->get('pack_nocomment')) . ' ' .
        __('Remove comments from files') . '</label></p>

        <p><label class="classic" for="pack_fixnewline">' .
        form::checkbox('pack_fixnewline', 1, (bool) $s->get('pack_fixnewline')) . ' ' .
        __('Fix newline style from files content') . '</label></p>

        </div>';
    }
}
