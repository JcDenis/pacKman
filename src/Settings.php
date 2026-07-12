<?php

declare(strict_types=1);

namespace Dotclear\Plugin\pacKman;

/**
 * @brief       pacKman settings class.
 * @ingroup     pacKman
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Settings
{
    /**
     * Remove comments from files.
     *
     * @var     bool   $pack_nocomment
     */
    public readonly bool $pack_nocomment;

    /**
     * Remove comments from files.
     *
     * @var     bool    $pack_fixnewline
     */
    public readonly bool $pack_fixnewline;

    /**
     * Overwrite existing package.
     *
     * @var     bool    $pack_overwrite
     */
    public readonly bool $pack_overwrite;

    /**
     * Name of package.
     *
     * @var     string  $pack_filename
     */
    public readonly string $pack_filename;

    /**
     * Name of second package.
     *
     * @var     string  $secondpack_filename
     */
    public readonly string $secondpack_filename;

    /**
     * Path to package repository.
     *
     * @var     string  $pack_repository
     */
    public readonly string $pack_repository;

    /**
     * Seperate themes and plugins repository.
     *
     * @var     bool    $pack_typedrepo
     */
    public readonly bool $pack_typedrepo;

    /**
     * Extra files to exclude from package.
     *
     * @var     string  $pack_excludefiles
     */
    public readonly string $pack_excludefiles;

    /**
     * Hide distributed modules from lists.
     *
     * @var     bool    $hide_distrib
     */
    public readonly bool $hide_distrib;

    /**
     * Constructor set up plugin settings.
     */
    public function __construct()
    {
        $s = My::settings();

        $this->pack_nocomment      = (bool) ($s->get('pack_nocomment') ?? false);
        $this->pack_fixnewline     = (bool) ($s->get('pack_fixnewline') ?? false);
        $this->pack_overwrite      = (bool) ($s->get('pack_overwrite') ?? false);
        $this->pack_filename       = is_string($s->get('pack_filename')) ? $s->get('pack_filename') : '%type%-%id%';
        $this->secondpack_filename = is_string($s->get('secondpack_filename')) ? $s->get('secondpack_filename') : '%type%-%id%-%version%';
        $this->pack_repository     = is_string($s->get('pack_repository')) ? $s->get('pack_repository') : '';
        $this->pack_typedrepo      = (bool) ($s->get('pack_typedrepo') ?? false);
        $this->pack_excludefiles   = is_string($s->get('pack_excludefiles')) ? $s->get('pack_excludefiles') : '*.zip,*.tar,*.tar.gz,.directory,.hg';
        $this->hide_distrib        = (bool) ($s->get('hide_distrib') ?? false);
    }

    /**
     * Get a setting.
     *
     * @param   string  $key    The key
     *
     * @return  null|bool|string    The value
     */
    public function getSetting(string $key): null|bool|string
    {
        return is_bool($this->{$key}) || is_string($this->{$key}) ? $this->{$key} : null;
    }

    /**
     * Overwrite a plugin settings (in db).
     *
     * @param   string  $key    The setting ID
     * @param   mixed   $value  The setting value
     *
     * @return  bool True on success
     */
    public function writeSetting(string $key, mixed $value): bool
    {
        if (property_exists($this, $key) && settype($value, gettype($this->{$key})) === true) {
            My::settings()->drop($key);
            My::settings()->put($key, $value, gettype($this->{$key}), '', true, true);

            return true;
        }

        return false;
    }

    /**
     * List defined settings keys.
     *
     * @return  array<string, mixed>   The settings keys
     */
    public function listSettings(): array
    {
        return array_filter(get_object_vars($this), fn (mixed $k): bool => is_string($k), ARRAY_FILTER_USE_KEY);
    }
}
