<?php

declare(strict_types=1);

namespace ILIAS\CI\MaintenanceInfo\Inventory\Migrator\Helpers;

class DocuUsernameFactory
{
    const UNKNOWN_USER_ID = -1;
    const UNKNOWN_USER_NAME = '';
    private const USER_STRING_REXEX = '/(.*)\(([\d]*)\)/';
    private const USER_STRING_SPRINTF = '%s(%s)';

    public function fromString(string $user_string): DocuUsername
    {
        $matches = [];
        preg_match(self::USER_STRING_REXEX, $user_string, $matches);

        $user_id = (int)($matches[2] ?? self::UNKNOWN_USER_ID);
        $docu_username = (string)($matches[1] ?? self::UNKNOWN_USER_NAME);

        return new DocuUsername($user_id, $docu_username);
    }

    public function toString(DocuUsername $username): string
    {
        return sprintf(self::USER_STRING_SPRINTF, $username->getDocuUserName(), $username->getDocuUserId());
    }
}
