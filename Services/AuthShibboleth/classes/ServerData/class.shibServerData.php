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
 * Class shibServerData
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class shibServerData extends shibConfig
{

    /**
     * @var bool
     */
    protected static $cache = null;


    /**
     * @param array $data
     */
    protected function __construct($data)
    {
        $shibConfig = shibConfig::getInstance();
        foreach (array_keys(get_class_vars('shibConfig')) as $field) {
            $str = $shibConfig->getValueByKey($field);
            if ($str !== null) {
                $this->{$field} = $data[$str];
            }
        }
    }


    /**
     * @return bool|\shibServerData
     */
    public static function getInstance()
    {
        if (!isset(self::$cache)) {
            self::$cache = new self($_SERVER);
        }

        return self::$cache;
    }
}
