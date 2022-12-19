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

/* clearbricks ns */
use form;
use http;
use path;

/* php ns */
use Exception;

class Config
{
    private static $init = false;

    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            dcCore::app()->blog->settings->addNamespace(Core::id());
            self::$init = true;
        }

        return self::$init;
    }

    public static function process(): void
    {
        if (!self::$init) {
            return;
        }

        if (empty($_POST['save'])) {
            return;
        }

        # -- Set settings --
        try {
            $pack_nocomment      = !empty($_POST['pack_nocomment']);
            $pack_fixnewline     = !empty($_POST['pack_fixnewline']);
            $pack_overwrite      = !empty($_POST['pack_overwrite']);
            $pack_filename       = (string) $_POST['pack_filename'];
            $secondpack_filename = (string) $_POST['secondpack_filename'];
            $pack_repository     = (string) path::real($_POST['pack_repository'], false);
            $pack_excludefiles   = (string) $_POST['pack_excludefiles'];

            $check = Utils::is_configured(
                $pack_repository,
                $pack_filename,
                $secondpack_filename
            );

            if ($check) {
                $s = dcCore::app()->blog->settings->__get(Core::id());
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
                    dcCore::app()->admin->__get('list')->getURL('module=' . Core::id() . '&conf=1&redir=' .
                    dcCore::app()->admin->__get('list')->getRedir())
                );
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    public static function render()
    {
        if (!self::$init) {
            return false;
        }

        # -- Get settings --
        $s = dcCore::app()->blog->settings->__get(Core::id());

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
        ) . '</p>
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
