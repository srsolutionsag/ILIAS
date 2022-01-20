<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 */

/**
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 */
class ilObjBookingPoolAccess extends ilObjectAccess implements ilWACCheckingClass
{
    protected ilObjUser $user;
    protected ilRbacSystem $rbacsystem;

    public function __construct()
    {
        global $DIC;

        $this->user = $DIC->user();
        $this->rbacsystem = $DIC->rbac()->system();
    }

    public static function _getCommands()
    {
        $commands = array();
        $commands[] = array("permission" => "read", "cmd" => "render", "lang_var" => "show", "default" => true);
        $commands[] = array("permission" => "write", "cmd" => "render", "lang_var" => "edit_content");
        $commands[] = array("permission" => "write", "cmd" => "edit", "lang_var" => "settings");
        
        return $commands;
    }
    
    public static function _checkGoto($a_target) : bool
    {
        global $DIC;

        $ilAccess = $DIC->access();
        
        $t_arr = explode("_", $a_target);

        if ($t_arr[0] != "book" || ((int) $t_arr[1]) <= 0) {
            return false;
        }

        if ($ilAccess->checkAccess("read", "", $t_arr[1]) ||
            $ilAccess->checkAccess("visible", "", $t_arr[1])) {
            return true;
        }
        return false;
    }

    public function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = "")
    {
        $ilUser = $this->user;
        $rbacsystem = $this->rbacsystem;

        if ($a_user_id == "") {
            $a_user_id = $ilUser->getId();
        }

        // add no access info item and return false if access is not granted
        // $ilAccess->addInfoItem(ilAccessInfo::IL_NO_OBJECT_ACCESS, $a_text, $a_data = "");
        //
        // for all RBAC checks use checkAccessOfUser instead the normal checkAccess-method:
        // $rbacsystem->checkAccessOfUser($a_user_id, $a_permission, $a_ref_id)

        //TODO refactor this: first check if the object is online and then the permissions.
        #22653
        if (($a_permission == "visible" || $a_permission == "read") && !$rbacsystem->checkAccessOfUser($a_user_id, 'write', $a_ref_id)) {
            $pool = new ilObjBookingPool($a_ref_id);
            if ($pool->isOffline()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check whether booking pool is online (legacy version)
     * @deprecated
     */
    public static function _lookupOnlineStatus(array $a_ids) : array
    {
        global $DIC;

        $ilDB = $DIC->database();

        $q = "SELECT booking_pool_id, pool_offline FROM booking_settings WHERE " .
            $ilDB->in("booking_pool_id", $a_ids, false, "integer");
        $lm_set = $ilDB->query($q);
        $status = [];
        while ($r = $ilDB->fetchAssoc($lm_set)) {
            $status[$r["booking_pool_id"]] = !$r["pool_offline"];
        }
        return $status;
    }

    public function canBeDelivered(ilWACPath $ilWACPath)
    {

        // we return always false, since the files in the file/ and post/ directoies
        // are server by php (they could/should be moved to the data dir outside of the web doc root)
        return false;
    }
}
