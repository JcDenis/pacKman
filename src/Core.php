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
use dcModules;
use files;
use Dotclear\Helper\File\Zip\Unzip;
use path;

class Core
{
    public static function quote_exclude(array $exclude): array
    {
        foreach ($exclude as $k => $v) {
            $exclude[$k] = '#(^|/)(' . str_replace(
                ['.', '*'],
                ['\.', '.*?'],
                trim($v)
            ) . ')(/|$)#';
        }

        return $exclude;
    }

    public static function getPackages(string $root): array
    {
        $res = [];

        $cache = self::getCache() . '/';
        if (!is_dir($root) || !is_readable($root)) {
            return $res;
        }

        $files     = files::scanDir($root);
        $zip_files = [];
        foreach ($files as $file) {
            if (!preg_match('#(^|/)(.*?)\.zip(/|$)#', $file)) {
                continue;
            }
            $zip_files[] = $file;
        }

        if (empty($zip_files)) {
            return $res;
        }

        $sandboxes = [
            'theme'  => clone dcCore::app()->themes,
            'plugin' => clone dcCore::app()->plugins,
        ];

        $i = 0;
        foreach ($zip_files as $zip_file) {
            $zip_file = $root . DIRECTORY_SEPARATOR . $zip_file;
            $zip      = new Unzip($zip_file);
            $zip->getList(false, '#(^|/)(__MACOSX|\.svn|\.hg.*|\.git.*|\.DS_Store|\.directory|Thumbs\.db)(/|$)#');

            $zip_root_dir = $zip->getRootDir();
            if ($zip_root_dir != false) {
                $target = dirname($zip_file);
                $path   = $target . DIRECTORY_SEPARATOR . $zip_root_dir;
                $define = $zip_root_dir . '/' . dcModules::MODULE_FILE_DEFINE;
                $init   = $zip_root_dir . '/' . dcModules::MODULE_FILE_INIT;
            } else {
                $target = dirname($zip_file) . DIRECTORY_SEPARATOR . preg_replace('/\.([^.]+)$/', '', basename($zip_file));
                $path   = $target;
                $define = dcModules::MODULE_FILE_DEFINE;
                $init   = dcModules::MODULE_FILE_INIT;
            }

            if ($zip->isEmpty()) {
                $zip->close();

                continue;
            }

            if (!$zip->hasFile($define)) {
                $zip->close();

                continue;
            }

            foreach ($sandboxes as $type => $sandbox) {
                try {
                    files::makeDir($path, true);

                    // can't load twice _init.php file !
                    $unlink = false;
                    if ($zip->hasFile($init)) {
                        $unlink = true;
                        $zip->unzip($init, $path);
                    }

                    $zip->unzip($define, $path);

                    $sandbox->resetModulesList();
                    $sandbox->requireDefine($path, basename($path));

                    if ($unlink) {
                        unlink($target . DIRECTORY_SEPARATOR . $init);
                    }

                    unlink($target . DIRECTORY_SEPARATOR . $define);

                    if (!$sandbox->getErrors()) {
                        $module = $sandbox->getDefine(basename($path));
                        if ($module->isDefined() && $module->get('type') == $type) {
                            $res[$i]         = $module->dump();
                            $res[$i]['root'] = $zip_file;
                            $i++;
                        }
                    }
                } catch (Exception $e) {
                }
                files::deltree($path);
            }
            $zip->close();
        }

        return $res;
    }

    public static function pack(array $info, string $root, array $files, bool $overwrite = false, array $exclude = [], bool $nocomment = false, bool $fixnewline = false): bool
    {
        if (!($info = self::getInfo($info))
            || !($root = self::getRoot($root))
        ) {
            return false;
        }

        $exclude = self::getExclude($exclude);

        foreach ($files as $file) {
            if (!($file = self::getFile($file, $info))
                || !($dest = self::getOverwrite($overwrite, $root, $file))
            ) {
                continue;
            }

            @set_time_limit(300);

            if ($nocomment) {
                Zip::$remove_comment = true;
            }
            if ($fixnewline) {
                Zip::$fix_newline = true;
            }
            $zip = new Zip($dest);

            foreach ($exclude as $e) {
                $zip->addExclusion($e);
            }
            $zip->addDirectory(
                path::real($info['root']),
                $info['id'],
                true
            );

            $zip->close();
            unset($zip);
        }

        return true;
    }

    private static function getRoot(string $root): string
    {
        $root = (string) path::real($root);
        if (!is_dir($root) || !is_writable($root)) {
            throw new Exception(__('Directory is not writable'));
        }

        return $root;
    }

    private static function getInfo(array $info): array
    {
        if (!isset($info['root'])
            || !isset($info['id'])
            || !is_dir($info['root'])
        ) {
            throw new Exception(__('Failed to get module info'));
        }

        return $info;
    }

    private static function getExclude(array $exclude): array
    {
        $exclude = array_merge(My::EXCLUDED_FILES, $exclude);

        return self::quote_exclude($exclude);
    }

    private static function getFile(string $file, array $info): ?string
    {
        if (empty($file) || empty($info)) {
            return null;
        }

        $file = str_replace(
            [
                '%type%',
                '%id%',
                '%version%',
                '%author%',
                '%time%',
            ],
            [
                $info['type'],
                $info['id'],
                $info['version'],
                $info['author'],
                time(),
            ],
            $file
        );
        $parts = explode('/', $file);
        foreach ($parts as $i => $part) {
            $parts[$i] = files::tidyFileName($part);
        }

        return implode('/', $parts) . '.zip';
    }

    private static function getOverwrite(bool $overwrite, string $root, string$file): ?string
    {
        $path = $root . '/' . $file;
        if (file_exists($path) && !$overwrite) {
            // don't break loop
            //throw new Exception('File already exists');
            return null;
        }

        return $path;
    }

    private static function getCache(): string
    {
        $c = DC_TPL_CACHE . '/packman';
        if (!file_exists($c)) {
            @files::makeDir($c);
        }
        if (!is_writable($c)) {
            throw new Exception(__('Failed to get temporary directory'));
        }

        return $c;
    }
}
