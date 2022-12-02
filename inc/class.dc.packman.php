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
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

class dcPackman
{
    /** @var array Excluded files */
    public static $exclude = [
        '.',
        '..',
        '__MACOSX',
        '.svn',
        '.hg*',
        '.git*',
        'CVS',
        '.DS_Store',
        'Thumbs.db',
    ];

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

        $i = 0;
        foreach ($zip_files as $zip_file) {
            $zip = new fileUnzip($root . '/' . $zip_file);

            $zip_root_dir = $zip->getRootDir();

            if ($zip_root_dir != false) {
                $define     = $zip_root_dir . '/_define.php';
                $has_define = $zip->hasFile($define);
            } else {
                $define     = '_define.php';
                $has_define = $zip->hasFile($define);
            }

            if (!$has_define) {
                continue;
            }

            $zip->unzip($define, $cache . '/_define.php');

            $modules = new dcModules();
            $modules->requireDefine($cache, $zip_root_dir);
            if ($modules->moduleExists($zip_root_dir)) {
                $res[$i] = $modules->getModules($zip_root_dir);
            } else {
                $themes = new dcThemes();
                $themes->requireDefine($cache, $zip_root_dir);
                $res[$i] = $themes->getModules($zip_root_dir);
            }
            if (is_array($res[$i])) {
                $res[$i] = array_merge($res[$i], [
                    'id'   => $zip_root_dir,
                    'root' => $root . '/' . $zip_file,
                ]);

                unlink($cache . '_define.php');
                $i++;
            }
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
            $fp = fopen($dest, 'wb');

            if ($nocomment) {
                packmanFileZip::$remove_comment = true;
            }
            if ($fixnewline) {
                packmanFileZip::$fix_newline = true;
            }
            $zip = new packmanFileZip($fp);

            foreach ($exclude as $e) {
                $zip->addExclusion($e);
            }
            $zip->addDirectory(
                path::real($info['root']),
                $info['id'],
                true
            );

            $zip->write();
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
        $exclude = array_merge(self::$exclude, $exclude);

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
            @mkdir($c);
        }
        if (!is_writable($c)) {
            throw new Exception(__('Failed to get temporary directory'));
        }

        return $c;
    }
}
