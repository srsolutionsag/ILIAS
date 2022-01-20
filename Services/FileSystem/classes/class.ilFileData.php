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
class ilFileData extends ilFile
{

    /**
    * Constructor
    * class bas constructor and read path of directory from ilias.ini
    * setup an mail object
    * @access	public
    */
    public function __construct()
    {
        parent::__construct();
        $this->path = CLIENT_DATA_DIR;
    }

    /**
    * check if path exists and is writable
    * @param string path to check
    * @access	public
    * @return bool
    */
    public function checkPath($a_path)
    {
        if (is_writable($a_path)) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * get Path
    * @access	public
    * @return string path
    */
    public function getPath()
    {
        return $this->path;
    }
}
