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

class Settings
{
    // Remove comments from files
    public readonly bool $pack_nocomment;

    // Remove comments from files
    public readonly bool $pack_fixnewline;

    // Overwrite existing package
    public readonly bool $pack_overwrite;

    // Name of package
    public readonly string $pack_filename;

    // Name of second package
    public readonly string $secondpack_filename;

    // Path to package repository
    public readonly string $pack_repository;

    // Extra files to exclude from package
    public readonly string $pack_excludefiles;

    // Hide distributed modules from lists
    public readonly bool $hide_distrib;

    /**
     * Constructor set up plugin settings
     */
    public function __construct()
    {
        $s = dcCore::app()->blog->settings->get(My::id());

        $this->pack_nocomment      = (bool) ($s->get('pack_nocomment') ?? false);
        $this->pack_fixnewline     = (bool) ($s->get('pack_fixnewline') ?? false);
        $this->pack_overwrite      = (bool) ($s->get('pack_overwrite') ?? false);
        $this->pack_filename       = (string) ($s->get('pack_filename') ?? '%type%-%id%');
        $this->secondpack_filename = (string) ($s->get('secondpack_filename') ?? '%type%-%id%-%version%');
        $this->pack_repository     = (string) ($s->get('pack_repository') ?? '');
        $this->pack_excludefiles   = (string) ($s->get('pack_excludefiles') ?? '*.zip,*.tar,*.tar.gz,.directory,.hg');
        $this->hide_distrib        = (bool) ($s->get('hide_distrib') ?? false);
    }

    public function getSetting(string $key): mixed
    {
        return $this->{$key} ?? null;
    }

    /**
     * Overwrite a plugin settings (in db)
     *
     * @param   string  $key    The setting ID
     * @param   mixed   $value  The setting value
     *
     * @return  bool True on success
     */
    public function writeSetting(string $key, mixed $value): bool
    {
        if (property_exists($this, $key) && settype($value, gettype($this->{$key})) === true) {
            dcCore::app()->blog->settings->get(My::id())->drop($key);
            dcCore::app()->blog->settings->get(My::id())->put($key, $value, gettype($this->{$key}), '', true, true);

            return true;
        }

        return false;
    }

    /**
     * List defined settings keys
     *
     * @return  array   The settings keys
     */
    public function listSettings(): array
    {
        return array_keys(get_class_vars(Settings::class));
    }
}
