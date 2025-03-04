<?php

declare(strict_types=1);

namespace Dotclear\Plugin\pacKman;

use Dotclear\Helper\File\Zip\Zip as HelperZip;

/**
 * @brief       pacKman zip class.
 * @ingroup     pacKman
 *
 * This class extends dotclear zip class
 * to tweak writeFile method.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Zip extends HelperZip
{
    /**
     * Remove comments from files content.
     *
     * @var     bool    $remove_comment
     */
    public static $remove_comment = false;

    /**
     * Fix newline from files content.
     *
     * @var     bool    $fix_newline
     */
    public static $fix_newline = false;

    /**
     * Replace clearbricks fileZip::writeFile
     *
     * @param      string     $name   The name
     * @param      string     $file   The file
     * @param      float|int  $size   The size
     * @param      float|int  $mtime  The mtime
     *
     * @return     void
     */
    protected function writeFile(string $name, string $file, int|float $size, int|float $mtime): void
    {
        if (!isset($this->entries[$name])) {
            return;
        }

        $size = filesize($file);
        $this->memoryAllocate($size * 3);

        $content = (string) file_get_contents($file);

        //cleanup file contents
        // at this time only php files
        if (self::$remove_comment && substr($file, -4) == '.php') {
            $content = self::removePHPComment($content);
        }
        if (self::$fix_newline && substr($file, -4) == '.php') {
            $content = self::fixNewline($content);
        }

        $unc_len = strlen($content);
        $crc     = crc32($content);
        $zdata   = (string) gzdeflate($content);
        $c_len   = strlen($zdata);

        unset($content);

        $mdate = $this->makeDate((int) $mtime);
        $mtime = $this->makeTime((int) $mtime);

        # Data descriptor
        $data_desc = "\x50\x4b\x03\x04" .
        "\x14\x00" .         # ver needed to extract
        "\x00\x00" .         # gen purpose bit flag
        "\x08\x00" .         # compression method
        pack('v', $mtime) .       # last mod time
        pack('v', $mdate) .       # last mod date
        pack('V', $crc) .     # crc32
        pack('V', $c_len) .       # compressed filesize
        pack('V', $unc_len) .     # uncompressed filesize
        pack('v', strlen($name)) .    # length of filename
        pack('v', 0) .            # extra field length
        $name .              # end of "local file header" segment
        $zdata .             # "file data" segment
        pack('V', $crc) .     # crc32
        pack('V', $c_len) .       # compressed filesize
        pack('V', $unc_len);     # uncompressed filesize

        fwrite($this->fp, $data_desc);
        unset($zdata);

        $new_offset = $this->old_offset + strlen($data_desc);

        # Add to central directory record
        $cdrec = "\x50\x4b\x01\x02" .
        "\x00\x00" .             # version made by
        "\x14\x00" .             # version needed to extract
        "\x00\x00" .             # gen purpose bit flag
        "\x08\x00" .             # compression method
        pack('v', $mtime) .           # last mod time
        pack('v', $mdate) .           # last mod date
        pack('V', $crc) .         # crc32
        pack('V', $c_len) .           # compressed filesize
        pack('V', $unc_len) .         # uncompressed filesize
        pack('v', strlen($name)) .        # length of filename
        pack('v', 0) .                # extra field length
        pack('v', 0) .                # file comment length
        pack('v', 0) .                # disk number start
        pack('v', 0) .                # internal file attributes
        pack('V', 32) .               # external file attributes - 'archive' bit set
        pack('V', $this->old_offset) .    # relative offset of local header
        $name;

        $this->old_offset = $new_offset;
        $this->ctrl_dir[] = $cdrec;
    }

    protected static function removePHPComment(string $content): string
    {
        $comment = [T_COMMENT];
        if (defined('T_DOC_COMMENT')) {
            $comment[] = T_DOC_COMMENT; // PHP 5
        }
        if (defined('T_ML_COMMENT')) {
            $comment[] = T_ML_COMMENT; // PHP 4
        }

        $newStr = '';
        $tokens = token_get_all($content);

        foreach ($tokens as $token) {
            if (is_array($token)) {
                if (in_array($token[0], $comment)) {
                    //$newStr .= "\n";
                } else {
                    $newStr .= $token[1];
                }
            } else {
                $newStr .= $token;
            }
        }

        return $newStr;
    }

    protected static function fixNewline(string $content): string
    {
        return str_replace("\r\n", "\n", $content);
    }
}
