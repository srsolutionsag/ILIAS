<?php

/**
 * Class ilADNNotificationGUI
 * @ilCtrl_IsCalledBy ilADNNotificationGUI: ilObjAdministrativeNotificationGUI
 * @ilCtrl_IsCalledBy ilADNNotificationGUI: ilObjAdministrativeNotificationGUI
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 */
class ilADNNotificationGUI extends ilADNAbstractGUI
{
    public const TAB_TABLE = 'notifications';
    public const CMD_DEFAULT = 'index';
    public const CMD_ADD = 'add';
    public const CMD_CREATE = 'save';
    public const CMD_UPDATE = 'update';
    public const CMD_EDIT = 'edit';
    public const CMD_CANCEL = 'cancel';
    public const CMD_DELETE = 'delete';
    public const CMD_CONFIRM_DELETE = 'confirmDelete';
    public const CMD_RESET_FOR_ALL = 'confirmForAll';

    protected function dispatchCommand($cmd) : string
    {
        $this->tab_handling->initTabs(ilObjAdministrativeNotificationGUI::TAB_MAIN, ilMMSubItemGUI::CMD_VIEW_SUB_ITEMS, true, self::class);
        switch ($cmd) {
            case self::CMD_ADD:
                return $this->add();
            case self::CMD_CREATE:
                return $this->create();
            case self::CMD_EDIT:
                return $this->edit();
            case self::CMD_UPDATE:
                return $this->update();
            case self::CMD_CONFIRM_DELETE:
                return $this->confirmDelete();
            case self::CMD_DELETE:
                return $this->delete();
            case self::CMD_DEFAULT:
            default:

                return $this->index();

        }

        return "";
    }

    protected function index() : string
    {
        $button = ilLinkButton::getInstance();
        $button->setCaption($this->lng->txt('common_add_msg'), false);
        $button->setUrl($this->ctrl->getLinkTarget($this, self::CMD_ADD));
        $this->toolbar->addButtonInstance($button);
        $notMessageTableGUI = new ilADNNotificationTableGUI($this, self::CMD_DEFAULT);
        return $notMessageTableGUI->getHTML();
    }

    protected function add() : string
    {
        $form = new ilADNNotificationFormGUI($this, new ilADNNotification());
        $form->fillForm();
        return $form->getHTML();
    }

    //todo

    protected function create() : string
    {
        $form = new ilADNNotificationFormGUI($this, new ilADNNotification());
        $form->setValuesByPost();
        if ($form->saveObject()) {
            ilUtil::sendInfo($this->lng->txt('msg_success'), true);
            $this->ctrl->redirect($this, self::CMD_DEFAULT);
        }
        $this->tpl->setContent($form->getHTML());
    }

    protected function cancel() : string
    {
        $this->ctrl->setParameter($this, self::IDENTIFIER, null);
        $this->ctrl->redirect($this, self::CMD_DEFAULT);
    }

    protected function edit() : string
    {
        $notification = $this->getNotificationFromRequest();
        $this->ctrl->setParameter($this, ilADNNotificationGUI::IDENTIFIER, $notification->getId());

        $form = new ilADNNotificationFormGUI($this, $notification);
        $form->fillForm();
        return $form->getHTML();
    }

    protected function update() : string
    {
        $notification = $this->getNotificationFromRequest();
        $form         = new ilADNNotificationFormGUI($this, $notification);
        $form->setValuesByPost();
        if ($form->saveObject()) {
            ilUtil::sendInfo($this->lng->txt('msg_success'), true);
            $this->ctrl->redirect($this, self::CMD_DEFAULT);
        }
        return $form->getHTML();
    }

    protected function confirmDelete() : string
    {
        $notification = $this->getNotificationFromRequest();
        $confirmation = new ilConfirmationGUI();
        $confirmation->setFormAction($this->ctrl->getFormAction($this));
        $confirmation->addItem(self::IDENTIFIER, $notification->getId(), $notification->getTitle());
        $confirmation->setCancel($this->lng->txt('msg_form_button_cancel'), self::CMD_CANCEL);
        $confirmation->setConfirm($this->lng->txt('msg_form_button_delete'), self::CMD_DELETE);

        return $confirmation->getHTML();
    }

    protected function delete() : void
    {
        $notification = $this->getNotificationFromRequest();
        $notification->delete();
        ilUtil::sendInfo($this->lng->txt('msg_success'), true);
        $this->cancel();
    }

    protected function resetForAll()
    {
        $this->notMessage->resetForAllUsers();
        $this->cancel();
    }

    /**
     * @return ilADNNotification
     */
    protected function getNotificationFromRequest() : ActiveRecord
    {
        if (isset($this->http->request()->getParsedBody()[self::IDENTIFIER])) {
            $identifier = $this->http->request()->getParsedBody()[self::IDENTIFIER];
        } else {
            $identifier = $this->http->request()->getQueryParams()[self::IDENTIFIER];
        }

        return ilADNNotification::findOrFail($identifier);
    }
}
