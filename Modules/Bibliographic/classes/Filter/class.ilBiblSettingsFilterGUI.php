<?php

/**
 * Class ilBiblSettingsFilterGUI
 *
 * @author: Benjamin Seglias   <bs@studer-raimann.ch>
 */

class ilBiblSettingsFilterGUI {

	const FIELD_IDENTIFIER = 'field_identifier';
	const CMD_STANDARD = 'content';
	const CMD_ADD = 'add';
	const CMD_CANCEL = 'cancel';
	const CMD_EDIT = 'edit';
	const CMD_UPDATE = 'update';
	const CMD_DELETE = 'delete';
	const CMD_APPLY_FILTER = 'applyFilter';
	const CMD_RESET_FILTER = 'resetFilter';
	const CMD_TRANSLATE = 'translate';

	/**
	 * @var ilObjBibliographic
	 */
	protected $object;
	/**
	 * @var \ILIAS\DI\Container
	 */
	protected $dic;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;
	/**
	 * @var ilTabsGUI
	 */
	protected $tabs;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;

	public function __construct() {
		global $DIC;
		$this->dic = $DIC;
		$this->tpl = $this->dic['tpl'];
		$this->tabs = $DIC->tabs();
		$this->ctrl = $this->dic->ctrl();
		$this->tpl = $this->dic['tpl'];
		$this->object = ilObjectFactory::getInstanceByRefId($_GET['ref_id']);
	}

	public function executeCommand() {

		$nextClass = $this->ctrl->getNextClass();
		switch ($nextClass) {
			default:
				$this->tabs->activateTab(ilObjBibliographicGUI::TAB_SETTINGS);
				$this->performCommand();
		}
	}

	protected function performCommand() {
		$cmd = $this->ctrl->getCmd(self::CMD_STANDARD);
		switch ($cmd) {
			case self::CMD_STANDARD:
			case self::CMD_ADD:
			case self::CMD_EDIT:
			case self::CMD_UPDATE:
			case self::CMD_DELETE:
			case self::CMD_APPLY_FILTER:
			case self::CMD_RESET_FILTER:
				if ($this->dic->access()->checkAccess('write', "", $this->object->getRefId())) {
					$this->{$cmd}();
					break;
				} else {
					ilUtil::sendFailure($this->dic->language()->txt("no_permission"), true);
					break;
				}
		}
	}

	public function content() {
		$this->ctrl->saveParameterByClass(ilBiblSettingsFilterTableGUI::class, self::FIELD_IDENTIFIER);
		$ilBiblSettingsFilterTableGUI = new ilBiblSettingsFilterTableGUI($this, self::CMD_STANDARD, $this->object);
		$this->tpl->setContent($ilBiblSettingsFilterTableGUI->getHTML());

/*		$data = ilBiblField::getAvailableFieldsForObjId($this->object->getId());

		$this->tpl->setContent("<pre>".print_r($data, true). "</pre>");*/
	}

	protected function add() { // Formular für neues Anlegen
		//$this->il_bibl_field = new ilBiblField($_GET[self::FIELD_IDENTIFIER]);

		//$this->tabs->activateTab(self::CMD_STANDARD);
		$ilBiblSettingsFilterFormGUI = new ilBiblSettingsFilterFormGUI($this, new ilBiblField());
		$this->tpl->setContent($ilBiblSettingsFilterFormGUI->getHTML());

	}

	protected function create() { // verarbeiten von add()

	}

	public function edit() { // Formular zum Bearbeiten eines bestehenden Eintrages
		//$this->tabs->activateTab(self::CMD_STANDARD);
		$field = $this->dic->http()->request()->getQueryParams()[self::FIELD_IDENTIFIER];
		$ilBiblSettingsFilterFormGUI = new ilBiblSettingsFilterFormGUI($this, ilBiblField::findOrFail($field));
		$ilBiblSettingsFilterFormGUI->fillForm();
		$this->tpl->setContent($ilBiblSettingsFilterFormGUI->getHTML());
	}


	public function update() { // Verarbeiten
		$field = $this->dic->http()->request()->getQueryParams()[self::FIELD_IDENTIFIER];

		$this->tabs->activateTab(self::CMD_STANDARD);
		$ilBiblSettingsFilterFormGUI = new ilBiblSettingsFilterFormGUI($this, ilBiblField::findOrFail($field));
		if ($ilBiblSettingsFilterFormGUI->saveObject()) {
			ilUtil::sendSuccess($this->dic->language()->txt('changes_saved_success'), true);
			$this->ctrl->redirect($this, self::CMD_STANDARD);
		}
		$ilBiblSettingsFilterFormGUI->setValuesByPost();
		$this->tpl->setContent($ilBiblSettingsFilterFormGUI->getHTML());
	}
}