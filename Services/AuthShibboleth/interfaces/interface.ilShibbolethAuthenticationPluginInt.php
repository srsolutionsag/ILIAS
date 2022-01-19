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
 * Class ilShibbolethAuthenticationPluginInt
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
interface ilShibbolethAuthenticationPluginInt
{

    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function beforeLogin(ilObjUser $user);


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function afterLogin(ilObjUser $user);


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function beforeLogout(ilObjUser $user);


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function afterLogout(ilObjUser $user);


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function beforeCreateUser(ilObjUser $user);


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function afterCreateUser(ilObjUser $user);


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function beforeUpdateUser(ilObjUser $user);


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function afterUpdateUser(ilObjUser $user);
}
