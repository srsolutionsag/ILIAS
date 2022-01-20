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
class ilFileDataImport extends ilFileData
{
    /**
    * path of exercise directory
    * @var string path
    * @access private
    */
    public $import_path;

    /**
    * Constructor
    * call base constructors
    */
    public function __construct()
    {
        define('IMPORT_PATH', 'import');
        parent::__construct();
        $this->import_path = parent::getPath() . "/" . IMPORT_PATH;
        
        // IF DIRECTORY ISN'T CREATED CREATE IT
        // STATIC CALL TO AVOID OVERWRITE PROBLEMS
        ilFileDataImport::_initDirectory();
    }

    /**
    * get exercise path
    * @access	public
    * @return string path
    */
    public function getPath()
    {
        return $this->import_path;
    }

    // PRIVATE METHODS
    public function __checkPath()
    {
        if (!@file_exists($this->getPath())) {
            return false;
        }
        $this->__checkReadWrite();

        return true;
    }
    /**
    * check if directory is writable
    * overwritten method from base class
    * @access	private
    * @return bool
    */
    public function __checkReadWrite()
    {
        if (is_writable($this->import_path) && is_readable($this->import_path)) {
            return true;
        } else {
            $this->ilias->raiseError("Import directory is not readable/writable by webserver", $this->ilias->error_obj->FATAL);
        }
    }
    /**
    * init directory
    * overwritten method
    * @access	public
    * @static
    * @return string path
    */
    public function _initDirectory()
    {
        if (!@file_exists($this->import_path)) {
            ilUtil::makeDir($this->import_path);
        }
        return true;
    }
}
