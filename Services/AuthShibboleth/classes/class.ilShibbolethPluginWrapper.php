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
 * Class ilShibbolethPluginWrapper
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilShibbolethPluginWrapper implements ilShibbolethAuthenticationPluginInt
{
    /**
     * @var ilComponentFactory
     */
    protected $component_factory;
    /**
     * @var ilLog
     */
    protected $log;
    /**
     * @var ilShibbolethPluginWrapper
     */
    protected static $cache = null;


    protected function __construct()
    {
        global $DIC;
        $ilLog = $DIC['ilLog'];
        $this->log = $ilLog;
        $this->component_factory = $DIC["component.factory"];
    }


    /**
     * @return ilShibbolethPluginWrapper
     */
    public static function getInstance()
    {
        if (!self::$cache instanceof ilShibbolethPluginWrapper) {
            self::$cache = new self();
        }

        return self::$cache;
    }


    /**
     * @return ilShibbolethAuthenticationPlugin[]
     */
    protected function getPluginObjects()
    {
        return $this->component_factory->getActivePluginsInSlot('shibhk');
    }


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function beforeLogin(ilObjUser $user)
    {
        foreach ($this->getPluginObjects() as $pl) {
            $user = $pl->beforeLogin($user);
        }

        return $user;
    }


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function afterLogin(ilObjUser $user)
    {
        foreach ($this->getPluginObjects() as $pl) {
            $user = $pl->afterLogin($user);
        }

        return $user;
    }


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function beforeCreateUser(ilObjUser $user)
    {
        foreach ($this->getPluginObjects() as $pl) {
            $user = $pl->beforeCreateUser($user);
        }

        return $user;
    }


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function afterCreateUser(ilObjUser $user)
    {
        foreach ($this->getPluginObjects() as $pl) {
            $user = $pl->afterCreateUser($user);
        }

        return $user;
    }


    public function beforeLogout(ilObjUser $user)
    {
        foreach ($this->getPluginObjects() as $pl) {
            $user = $pl->beforeLogout($user);
        }

        return $user;
    }


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function afterLogout(ilObjUser $user)
    {
        $this->log->write('afterlogout');
        foreach ($this->getPluginObjects() as $pl) {
            $user = $pl->afterLogout($user);
        }

        return $user;
    }


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function beforeUpdateUser(ilObjUser $user)
    {
        foreach ($this->getPluginObjects() as $pl) {
            $user = $pl->beforeUpdateUser($user);
        }

        return $user;
    }


    /**
     * @param ilObjUser $user
     *
     * @return ilObjUser
     */
    public function afterUpdateUser(ilObjUser $user)
    {
        foreach ($this->getPluginObjects() as $pl) {
            $user = $pl->afterUpdateUser($user);
        }

        return $user;
    }
}
