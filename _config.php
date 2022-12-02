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
if (!defined('DC_CONTEXT_MODULE')) {
    return null;
}

$redir = empty($_REQUEST['redir']) ?
    dcCore::app()->admin->__get('list')->getURL() . '#plugins' : $_REQUEST['redir'];

# -- Get settings --
dcCore::app()->blog->settings->addNamespace('pacKman');
$s = dcCore::app()->blog->settings->pacKman;

$packman_pack_nocomment      = $s->packman_pack_nocomment;
$packman_pack_fixnewline     = $s->packman_pack_fixnewline;
$packman_pack_overwrite      = $s->packman_pack_overwrite;
$packman_pack_filename       = $s->packman_pack_filename;
$packman_secondpack_filename = $s->packman_secondpack_filename;
$packman_pack_repository     = $s->packman_pack_repository;
$packman_pack_excludefiles   = $s->packman_pack_excludefiles;

# -- Set settings --
if (!empty($_POST['save'])) {
    try {
        $packman_pack_nocomment      = !empty($_POST['packman_pack_nocomment']);
        $packman_pack_fixnewline     = !empty($_POST['packman_pack_fixnewline']);
        $packman_pack_overwrite      = !empty($_POST['packman_pack_overwrite']);
        $packman_pack_filename       = (string) $_POST['packman_pack_filename'];
        $packman_secondpack_filename = (string) $_POST['packman_secondpack_filename'];
        $packman_pack_repository     = (string) path::real($_POST['packman_pack_repository'], false);
        $packman_pack_excludefiles   = (string) $_POST['packman_pack_excludefiles'];

        $check = libPackman::is_configured(
            $packman_pack_repository,
            $packman_pack_filename,
            $packman_secondpack_filename
        );

        if ($check) {
            $s->put('packman_pack_nocomment', $packman_pack_nocomment);
            $s->put('packman_pack_fixnewline', $packman_pack_fixnewline);
            $s->put('packman_pack_overwrite', $packman_pack_overwrite);
            $s->put('packman_pack_filename', $packman_pack_filename);
            $s->put('packman_secondpack_filename', $packman_secondpack_filename);
            $s->put('packman_pack_repository', $packman_pack_repository);
            $s->put('packman_pack_excludefiles', $packman_pack_excludefiles);

            dcPage::addSuccessNotice(
                __('Configuration has been successfully updated.')
            );
            http::redirect(
                dcCore::app()->admin->__get('list')->getURL('module=pacKman&conf=1&redir=' .
                dcCore::app()->admin->__get('list')->getRedir())
            );
        }
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

# -- Display form --
echo '
<div class="fieldset">
<h4>' . __('Root') . '</h4>

<p><label for="packman_pack_repository">' . __('Path to repository:') . ' ' .
form::field('packman_pack_repository', 65, 255, $packman_pack_repository, 'maximal') .
'</label></p>' .
'<p class="form-note">' . sprintf(
    __('Preconization: %s'),
    dcCore::app()->blog->public_path ?
    dcCore::app()->blog->public_path : __("Blog's public directory")
) . '</p>
</div>

<div class="fieldset">
<h4>' . __('Files') . '</h4>

<p><label for="packman_pack_filename">' . __('Name of exported package:') . ' ' .
form::field('packman_pack_filename', 65, 255, $packman_pack_filename, 'maximal') .
'</label></p>
<p class="form-note">' . sprintf(__('Preconization: %s'), '%type%-%id%') . '</p>

<p><label for="packman_secondpack_filename">' . __('Name of second exported package:') . ' ' .
form::field('packman_secondpack_filename', 65, 255, $packman_secondpack_filename, 'maximal') .
'</label></p>
<p class="form-note">' . sprintf(__('Preconization: %s'), '%type%-%id%-%version%') . '</p>

<p><label class="classic" for="packman_pack_overwrite">' .
form::checkbox('packman_pack_overwrite', 1, $packman_pack_overwrite) . ' ' .
__('Overwrite existing package') . '</label></p>

</div>

<div class="fieldset">
<h4>' . __('Content') . '</h4>

<p><label for="packman_pack_excludefiles">' . __('Extra files to exclude from package:') . ' ' .
form::field('packman_pack_excludefiles', 65, 255, $packman_pack_excludefiles, 'maximal') .
'</label></p>
<p class="form-note">' . sprintf(__('Preconization: %s'), '*.zip,*.tar,*.tar.gz') . '</p>

<p><label class="classic" for="packman_pack_nocomment">' .
form::checkbox('packman_pack_nocomment', 1, $packman_pack_nocomment) . ' ' .
__('Remove comments from files') . '</label></p>

<p><label class="classic" for="packman_pack_fixnewline">' .
form::checkbox('packman_pack_fixnewline', 1, $packman_pack_fixnewline) . ' ' .
__('Fix newline style from files content') . '</label></p>

</div>';
