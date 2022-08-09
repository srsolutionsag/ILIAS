<?php

declare(strict_types=1);

namespace ILIAS\CI\MaintenanceInfo\Inventory\Migrator\Helpers;

class DocuUsername
{
    protected int $docu_user_id;
    protected string $docu_user_name;

    public function __construct(int $docu_user_id, string $docu_user_name)
    {
        $this->docu_user_id = $docu_user_id;
        $this->docu_user_name = $docu_user_name;
    }


    public function getDocuUserId(): int
    {
        return $this->docu_user_id;
    }


    public function getDocuUserName(): string
    {
        return $this->docu_user_name;
    }


    public function __asArray(): array
    {
        return get_object_vars($this);
    }
}
