<?php

declare(strict_types=1);

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

/**
* Class ilSCORM2004DeleteData
*
* @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
*
* @ingroup components\ILIASScormAicc
*/
class ilSCORM2004DeleteData
{
    public static function removeCMIDataForPackage(int $packageId): void
    {
        global $DIC;

        $ilDB = $DIC->database();

        $res = $ilDB->queryF(
            '
			SELECT cmi_node.cmi_node_id 
			FROM cmi_node, cp_node 
			WHERE cp_node.slm_id = %s AND cmi_node.cp_node_id = cp_node.cp_node_id',
            array('integer'),
            array($packageId)
        );
        $cmi_node_values = [];
        while ($data = $ilDB->fetchAssoc($res)) {
            $cmi_node_values[] = $data['cmi_node_id'];
        }
        self::removeCMIDataForNodes($cmi_node_values);

        //custom
        //TODO: delete cmi_custom
        $query = 'DELETE FROM cmi_custom WHERE obj_id = %s';
        $ilDB->manipulateF($query, array('integer'), array($packageId));

        //sahs_user
        $query = 'DELETE FROM sahs_user WHERE obj_id = %s';
        $ilDB->manipulateF($query, array('integer'), array($packageId));

        //g_objective
        $query = 'DELETE FROM cmi_gobjective WHERE scope_id = %s';
        $ilDB->manipulateF($query, array('integer'), array($packageId));

        $s_globalObjectiveId = self::getGlobalToSystemObjectiveIdStringForPackage($packageId);
        if ($s_globalObjectiveId != "") {
            $ilDB->manipulateF(
                'DELETE FROM cmi_gobjective WHERE scope_id = %s AND objective_id in (' . $s_globalObjectiveId . ')',
                array('integer'),
                array(0)
            );
        }
    }

    public static function removeCMIDataForUser(int $user_id): void
    {
        global $DIC;

        $ilDB = $DIC->database();

        //get all cmi_nodes to delete
        $res = $ilDB->queryF(
            '
			SELECT cmi_node.cmi_node_id 
			FROM cmi_node, cp_node 
			WHERE cmi_node.user_id = %s AND cmi_node.cp_node_id = cp_node.cp_node_id',
            array('integer'),
            array($user_id)
        );

        $cmi_node_values = [];
        while ($data = $ilDB->fetchAssoc($res)) {
            $cmi_node_values[] = $data['cmi_node_id'];
        }
        self::removeCMIDataForNodes($cmi_node_values);

        //custom
        //TODO: delete cmi_custom
        $ilDB->manipulateF(
            'DELETE FROM cmi_custom WHERE user_id = %s',
            array('integer'),
            array($user_id)
        );

        //sahs_user
        $ilDB->manipulateF(
            'DELETE FROM sahs_user WHERE user_id = %s',
            array('integer'),
            array($user_id)
        );

        //gobjective
        $ilDB->manipulateF(
            'DELETE FROM cmi_gobjective WHERE user_id = %s',
            array('integer'),
            array($user_id)
        );
    }

    public static function removeCMIDataForUserAndPackage(int $user_id, int $packageId): void
    {
        global $DIC;

        $ilDB = $DIC->database();

        //get all cmi_nodes to delete
        $res = $ilDB->queryF(
            '
			SELECT cmi_node.cmi_node_id 
			FROM cmi_node, cp_node 
			WHERE cmi_node.user_id = %s AND cmi_node.cp_node_id = cp_node.cp_node_id AND cp_node.slm_id = %s',
            array('integer','integer'),
            array($user_id,$packageId)
        );
        $cmi_node_values = [];
        while ($data = $ilDB->fetchAssoc($res)) {
            $cmi_node_values[] = $data['cmi_node_id'];
        }
        self::removeCMIDataForNodes($cmi_node_values);

        //custom
        //TODO: delete cmi_custom
        $ilDB->manipulateF(
            'DELETE FROM cmi_custom WHERE user_id = %s AND obj_id = %s',
            array('integer','integer'),
            array($user_id,$packageId)
        );

        //sahs_user
        $ilDB->manipulateF(
            'DELETE FROM sahs_user WHERE user_id = %s AND obj_id = %s',
            array('integer','integer'),
            array($user_id,$packageId)
        );

        //gobjective
        $ilDB->manipulateF(
            'DELETE FROM cmi_gobjective WHERE user_id = %s AND scope_id = %s',
            array('integer','integer'),
            array($user_id,$packageId)
        );

        $s_globalObjectiveId = self::getGlobalToSystemObjectiveIdStringForPackage($packageId);
        if ($s_globalObjectiveId != "") {
            $ilDB->manipulateF(
                'DELETE FROM cmi_gobjective WHERE user_id = %s AND scope_id = %s AND objective_id in (' . $s_globalObjectiveId . ')',
                array('integer','integer'),
                array($user_id,0)
            );
        }
    }

    public static function removeCMIDataForNodes(array $cmi_node_values): void
    {
        global $DIC;

        $ilDB = $DIC->database();

        //cmi interaction nodes
        $cmi_inode_values = array();

        $query = 'SELECT cmi_interaction_id FROM cmi_interaction WHERE '
            . $ilDB->in('cmi_interaction.cmi_node_id', $cmi_node_values, false, 'integer');
        $res = $ilDB->query($query);
        while ($data = $ilDB->fetchAssoc($res)) {
            $cmi_inode_values[] = $data['cmi_interaction_id'];
        }

        //response
        $query = 'DELETE FROM cmi_correct_response WHERE '
               . $ilDB->in('cmi_correct_response.cmi_interaction_id', $cmi_inode_values, false, 'integer');
        $ilDB->manipulate($query);

        //objective interaction
        $query = 'DELETE FROM cmi_objective WHERE '
               . $ilDB->in('cmi_objective.cmi_interaction_id', $cmi_inode_values, false, 'integer');
        $ilDB->manipulate($query);

        //objective
        $query = 'DELETE FROM cmi_objective WHERE '
               . $ilDB->in('cmi_objective.cmi_node_id', $cmi_node_values, false, 'integer');
        $ilDB->manipulate($query);

        //interaction
        $query = 'DELETE FROM cmi_interaction WHERE '
               . $ilDB->in('cmi_interaction.cmi_node_id', $cmi_node_values, false, 'integer');
        $ilDB->manipulate($query);

        //comment
        $query = 'DELETE FROM cmi_comment WHERE '
               . $ilDB->in('cmi_comment.cmi_node_id', $cmi_node_values, false, 'integer');
        $ilDB->manipulate($query);

        //node
        $query = 'DELETE FROM cmi_node WHERE '
               . $ilDB->in('cmi_node.cmi_node_id', $cmi_node_values, false, 'integer');
        $ilDB->manipulate($query);
    }

    public static function getGlobalToSystemObjectiveIdStringForPackage(int $packageId): string
    {
        global $DIC;

        $ilDB = $DIC->database();

        $existing_key_template = "";
        $global_to_system = 1;

        $res = $ilDB->queryF(
            'SELECT global_to_system FROM cp_package WHERE obj_id = %s',
            array('integer'),
            array($packageId)
        );
        while ($data = $ilDB->fetchAssoc($res)) {
            $global_to_system = $data['global_to_system'];
        }
        if ($global_to_system == 0) {
            return "";
        }

        $res = $ilDB->queryF(
            '
			SELECT targetobjectiveid 
			FROM cp_mapinfo, cp_node 
			WHERE cp_node.slm_id = %s 
			AND cp_node.nodename = %s
			AND cp_mapinfo.cp_node_id = cp_node.cp_node_id',
            array('integer', 'text'),
            array($packageId, 'mapInfo')
        );
        while ($data = $ilDB->fetchAssoc($res)) {
            $existing_key_template .= "'" . $data['targetobjectiveid'] . "',";
        }
        //remove trailing ','
        $existing_key_template = substr($existing_key_template, 0, -1);
        if ($existing_key_template == false) {
            return "";
        }

        return $existing_key_template;
    }
}
