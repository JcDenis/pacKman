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

use Dotclear\App;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Module\ModuleDefine;
use Exception;

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

        if (!is_dir($root) || !is_readable($root)) {
            return $res;
        }

        $files     = Files::scanDir($root);
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
            'theme'  => clone App::themes(),
            'plugin' => clone App::plugins(),
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
                $define = $zip_root_dir . '/' . App::plugins()::MODULE_FILE_DEFINE;
                $init   = $zip_root_dir . '/' . App::plugins()::MODULE_FILE_INIT;
            } else {
                $target = dirname($zip_file) . DIRECTORY_SEPARATOR . preg_replace('/\.([^.]+)$/', '', basename($zip_file));
                $path   = $target;
                $define = App::plugins()::MODULE_FILE_DEFINE;
                $init   = App::plugins()::MODULE_FILE_INIT;
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
                    Files::makeDir($path, true);

                    // can't load twice _init.php file !
                    $unlink = false;
                    if ($zip->hasFile($init)) {
                        $unlink = true;
                        $zip->unzip($init, $target . DIRECTORY_SEPARATOR . $init);
                    }

                    $zip->unzip($define, $target . DIRECTORY_SEPARATOR . $define);

                    $sandbox->resetModulesList();
                    $sandbox->requireDefine($path, basename($path));

                    if ($unlink) {
                        unlink($target . DIRECTORY_SEPARATOR . $init);
                    }

                    unlink($target . DIRECTORY_SEPARATOR . $define);

                    if (!$sandbox->getErrors()) {
                        $module = $sandbox->getDefine(basename($path));
                        if ($module->isDefined() && $module->get('type') == $type) {
                            $res[$i] = $module;
                            $res[$i]->set('root', $zip_file);
                            $i++;
                        }
                    }
                } catch (Exception $e) {
                    throw $e;
                }
                Files::deltree($path);
            }
            $zip->close();
        }

        return $res;
    }

    public static function pack(ModuleDefine $define, string $root, array $files, bool $overwrite = false, array $exclude = [], bool $nocomment = false, bool $fixnewline = false): bool
    {
        // check define
        if (!$define->isDefined()
            || empty($define->get('root'))
            || !is_dir($define->get('root'))
        ) {
            throw new Exception(__('Failed to get module info'));
        }

        // check root
        $root = (string) Path::real($root);
        if (!is_dir($root) || !is_writable($root)) {
            throw new Exception(__('Directory is not writable'));
        }

        //set excluded
        $exclude = self::quote_exclude(array_merge(My::EXCLUDED_FILES, $exclude));

        foreach ($files as $file) {
            if (empty($file)) {
                continue;
            }

            // check path
            $path = $root . DIRECTORY_SEPARATOR . self::getFile($file, $define);
            if (file_exists($path) && !$overwrite) {
                // don't break loop
                continue;
            }

            @set_time_limit(300);

            if ($nocomment) {
                Zip::$remove_comment = true;
            }
            if ($fixnewline) {
                Zip::$fix_newline = true;
            }

            $fp  = fopen($path, 'wb');
            $zip = new Zip($fp);

            foreach ($exclude as $e) {
                $zip->addExclusion($e);
            }
            $zip->addDirectory(
                (string) Path::real($define->get('root'), false),
                $define->getId(),
                true
            );

            $zip->write();
            $zip->close();
            unset($zip);
        }

        return true;
    }

    private static function getFile(string $file, ModuleDefine $define): string
    {
        $file = str_replace(
            [
                '\\',
                '%type%',
                '%id%',
                '%version%',
                '%author%',
                '%time%',
            ],
            [
                '/',
                $define->get('type'),
                $define->getId(),
                $define->get('version'),
                $define->get('author'),
                time(),
            ],
            $file
        );
        $parts = explode('/', $file);
        foreach ($parts as $i => $part) {
            $parts[$i] = Files::tidyFileName($part);
        }

        return implode(DIRECTORY_SEPARATOR, $parts) . '.zip';
    }
}
