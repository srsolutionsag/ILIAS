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
 * Description of class class
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 *
 */
class ilAuthFrontendCredentialsShibboleth extends ilAuthFrontendCredentials implements ilAuthCredentials
{
    /**
     * @var ilSetting
     */
    private $settings = null;
    

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->settings = $GLOBALS['DIC']['ilSetting'];
    }
    
    
    /**
     * @return \ilSetting
     */
    protected function getSettings()
    {
        return $this->settings;
    }
    
    /**
     * Init credentials from request
     */
    public function initFromRequest()
    {
        //$this->getLogger()->dump($_SERVER, ilLogLevel::DEBUG);
        $this->setUsername($this->settings->get('shib_login', ''));
        $this->setPassword('');
    }
}
