<?php
/******************************************************************************
 *
 * This file is part of ILIAS, a powerful learning management system.
 *
 * ILIAS is licensed with the GPL-3.0, you should have received a copy
 * of said license along with the source code.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 *      https://www.ilias.de
 *      https://github.com/ILIAS-eLearning
 *
 *****************************************************************************/
/**
* @deprecated
*/
abstract class ilFileSystemStorage
{
    public const STORAGE_WEB = 1;
    public const STORAGE_DATA = 2;
    public const STORAGE_SECURED = 3;

    protected const FACTOR = 100;
    protected const MAX_EXPONENT = 3;
    protected const SECURED_DIRECTORY = "sec";

    private int $container_id;
    private int $storage_type;
    private bool $path_conversion = false;

    protected string $path;

    /**
     * Constructor
     *
     * @access public
     * @param int storage type
     * @param bool En/Disable automatic path conversion. If enabled files with id 123 will be stored in directory files/1/file_123
     * @param int object id of container (e.g file_id or mob_id)
     *
     */
    public function __construct(int $a_storage_type, bool $a_path_conversion, int $a_container_id)
    {
        $this->storage_type = $a_storage_type;
        $this->path_conversion = $a_path_conversion;
        $this->container_id = $a_container_id;

        // Get path info
        $this->init();
    }

    public function getContainerId()
    {
        return $this->container_id;
    }

    /**
     * Create a path from an id: e.g 12345 will be converted to 12/34/<name>_5
     *
     * @access public
     * @static
     *
     * @param int container id
     * @param string name
     */
    public static function _createPathFromId($a_container_id, $a_name): string
    {
        $path_string = "";
        $path = array();
        $found = false;
        $num = $a_container_id;
        for ($i = self::MAX_EXPONENT; $i > 0;$i--) {
            $factor = pow(self::FACTOR, $i);
            if (($tmp = (int) ($num / $factor)) or $found) {
                $path[] = $tmp;
                $num = $num % $factor;
                $found = true;
            }
        }

        if (count($path)) {
            $path_string = (implode('/', $path) . '/');
        }
        return $path_string . $a_name . '_' . $a_container_id;
    }

    /**
     * Get path prefix. Prefix that will be prepended to the path
     * No trailing slash. E.g ilFiles for files
     *
     * @abstract
     * @access protected
     *
     * @return string path prefix e.g files
     */
    abstract protected function getPathPrefix(): string;

    /**
     * Get directory name. E.g for files => file
     * Only relative path, no trailing slash
     * '_<obj_id>' will be appended automatically
     *
     * @abstract
     * @access protected
     *
     * @return string directory name
     */
    abstract protected function getPathPostfix(): string;

    /**
     * Create directory
     *
     * @access public
     *
     */
    public function create(): bool
    {
        if (!file_exists($this->path)) {
            ilUtil::makeDirParents($this->path);
        }
        return true;
    }


    /**
     * Get absolute path of storage directory
     *
     * @access public
     *
     */
    public function getAbsolutePath()
    {
        return $this->path;
    }

    /**
     * Read path info
     *
     * @access private
     */
    protected function init(): bool
    {
        switch ($this->storage_type) {
            case self::STORAGE_DATA:
                $this->path = ilUtil::getDataDir();
                break;

            case self::STORAGE_WEB:
                $this->path = ilUtil::getWebspaceDir();
                break;

            case self::STORAGE_SECURED:
                $this->path = ilUtil::getWebspaceDir();
                $this->path = ilUtil::removeTrailingPathSeparators($this->path);
                $this->path .= '/' . self::SECURED_DIRECTORY;
                break;
        }
        $this->path = ilUtil::removeTrailingPathSeparators($this->path);
        $this->path .= '/';

        // Append path prefix
        $this->path .= ($this->getPathPrefix() . '/');

        if ($this->path_conversion) {
            $this->path .= self::_createPathFromId($this->container_id, $this->getPathPostfix());
        } else {
            $this->path .= ($this->getPathPostfix() . '_' . $this->container_id);
        }
        return true;
    }

    /**
     * Write data to file
     *
     * @access public
     * @param
     *
     */
    public function writeToFile($a_data, $a_absolute_path): bool
    {
        if (!$fp = @fopen($a_absolute_path, 'w+')) {
            return false;
        }
        if (@fwrite($fp, $a_data) === false) {
            @fclose($fp);
            return false;
        }
        @fclose($fp);
        return true;
    }

    /**
     * Delete file
     *
     * @access public
     * @param string absolute name
     *
     */
    public function deleteFile($a_abs_name): bool
    {
        if (@file_exists($a_abs_name)) {
            @unlink($a_abs_name);
            return true;
        }
        return false;
    }

    /**
     * Delete directory
     *
     * @access public
     * @param string absolute name
     *
     */
    public function deleteDirectory($a_abs_name): bool
    {
        if (@file_exists($a_abs_name)) {
            ilUtil::delDir($a_abs_name);
            return true;
        }
        return false;
    }


    /**
     * Delete complete directory
     *
     * @access public
     * @param
     *
     */
    public function delete()
    {
        return ilUtil::delDir($this->getAbsolutePath());
    }


    /**
     * Copy files
     *
     * @access public
     * @param string absolute source
     * @param string absolute target
     *
     */
    public function copyFile($a_from, $a_to): bool
    {
        if (@file_exists($a_from)) {
            @copy($a_from, $a_to);
            return true;
        }
        return false;
    }

    /**
     * Copy directory and all contents
     *
     * @param string $a_source absolute source path
     * @param string $a_target absolute target path
     */
    public static function _copyDirectory(string $a_source, string $a_target): bool
    {
        return ilUtil::rCopy($a_source, $a_target);
    }

    public function appendToPath($a_appendix)
    {
        $this->path .= $a_appendix;
    }

    public function getStorageType()
    {
        return $this->storage_type;
    }

    /**
     * Get path
     */
    public function getPath()
    {
        return $this->path;
    }
}
