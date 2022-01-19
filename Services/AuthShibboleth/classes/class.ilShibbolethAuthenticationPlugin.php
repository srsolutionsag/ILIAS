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
 * Plugin definition
 *
 * @author  Stefan Meyer <meyer@leifos.com>
 * @version $Id$
 *
 *
 * @ingroup ServicesAuthShibboleth
 */
abstract class ilShibbolethAuthenticationPlugin extends ilPlugin implements ilShibbolethAuthenticationPluginInt
{

    /**
     * @var array
     */
    protected $active_plugins = array();

    /**
     * @param $a_user_data
     * @param $a_keyword
     * @param $a_value
     *
     * @return bool
     */
    protected function checkValue($a_user_data, $a_keyword, $a_value)
    {
        if (!$a_user_data[$a_keyword]) {
            return false;
        }
        if (is_array($a_user_data[$a_keyword])) {
            foreach ($a_user_data[$a_keyword] as $values) {
                if (strcasecmp(trim($values), $a_value) == 0) {
                    return true;
                }
            }

            return false;
        }
        if (strcasecmp(trim($a_user_data[$a_keyword]), trim($a_value)) == 0) {
            return true;
        }

        return false;
    }


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function beforeLogin(ilObjUser $user)
    {
        return $user;
    }


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function afterLogin(ilObjUser $user)
    {
        return $user;
    }


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function beforeCreateUser(ilObjUser $user)
    {
        return $user;
    }


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function afterCreateUser(ilObjUser $user)
    {
        return $user;
    }


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function beforeLogout(ilObjUser $user)
    {
        return $user;
    }


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function afterLogout(ilObjUser $user)
    {
        return $user;
    }


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function beforeUpdateUser(ilObjUser $user)
    {
        return $user;
    }


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function afterUpdateUser(ilObjUser $user)
    {
        return $user;
    }
}
