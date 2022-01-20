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
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id$
*
*
* @ingroup ServicesFileSystemStorage
*/
class ilUploadFiles
{
    /**
    * Get the directory with uploaded files
    *
    * The directory is configured as cont_upload_dir in the settings table.
    * The directory must exist and have read permissions.
    * Currently the user must have admin permissions in ILIAS.
    * Later there may be different directories for different users/roles.
    *
    * @return   string      full path of upload directory on the server or empty
    * @access   static
    */
    public static function _getUploadDirectory(): string
    {
        global $DIC;
        $rbacsystem = $DIC['rbacsystem'];
        
        if (!$rbacsystem->checkAccess('write', SYSTEM_FOLDER_ID)) {
            return '';
        }

        $lm_set = new ilSetting("lm");
        $upload_dir = $lm_set->get("cont_upload_dir");

        $web_directory = $DIC->filesystem()->storage();
        $import_file_factory = new ilImportDirectoryFactory($web_directory);
        try {
            $scorm_import_directory = $import_file_factory->getInstanceForComponent(ilImportDirectoryFactory::TYPE_SAHS);
        } catch (InvalidArgumentException $e) {
            return '';
        }
        return $scorm_import_directory->getAbsolutePath();
    }
    
    /**
    * Get a list of readable files in the upload directory
    *
    * @return  array       list of file names (without path)
    * @access 	static
    */
    public static function _getUploadFiles(): array
    {
        if (!$upload_dir = self::_getUploadDirectory()) {
            return array();
        }

        // get the sorted content of the upload directory
        $handle = opendir($upload_dir);
        $files = array();
        while (false !== ($file = readdir($handle))) {
            $full_path = $upload_dir . "/" . $file;
            if (is_file($full_path) and is_readable($full_path)) {
                $files[] = $file;
            }
        }
        closedir($handle);
        sort($files);
        reset($files);
        
        return $files;
    }
    
    /**
    * Check if a file exists in the upload directory and is readable
    *
    * @param    string      file name
    * @return  	boolean     true/false
    * @access 	static
    */
    public static function _checkUploadFile($a_file): bool
    {
        $files = self::_getUploadFiles();
        
        return in_array($a_file, $files);
    }

    /**
    * copy an uploaded file to the target directory (including virus check)
    *
    * @param    string      file name
    * @param    string      target path and name
    * @return  	boolean     true/false
    * @access 	static
    */
    public static function _copyUploadFile($a_file, $a_target, $a_raise_errors = true)
    {
        global $DIC;
        $lng = $DIC['lng'];
        $ilias = $DIC['ilias'];

        $file = self::_getUploadDirectory() . "/" . $a_file;

        // check if file exists
        if (!is_file($file)) {
            if ($a_raise_errors) {
                $ilias->raiseError($lng->txt("upload_error_file_not_found"), $ilias->error_obj->MESSAGE);
            } else {
                ilUtil::sendFailure($lng->txt("upload_error_file_not_found"), true);
            }
            return false;
        }

        // virus handling
        $vir = ilUtil::virusHandling($file, $a_file);
        if (!$vir[0]) {
            if ($a_raise_errors) {
                $ilias->raiseError(
                    $lng->txt("file_is_infected") . "<br />" .
                    $vir[1],
                    $ilias->error_obj->MESSAGE
                );
            } else {
                ilUtil::sendFailure($lng->txt("file_is_infected") . "<br />" .
                    $vir[1], true);
            }
            return false;
        } else {
            if ($vir[1] != "") {
                ilUtil::sendInfo($vir[1], true);
            }
            return copy($file, $a_target);
        }
    }
}
