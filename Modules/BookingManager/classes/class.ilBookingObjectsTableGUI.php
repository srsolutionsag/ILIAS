<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Table/classes/class.ilTable2GUI.php");

/**
 * List booking objects (for booking type)
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com> 
 * @version $Id$
 *
 * @ingroup ModulesBookingManager
 */
class ilBookingObjectsTableGUI extends ilTable2GUI
{
	/**
	 * Constructor
	 * @param	object	$a_parent_obj
	 * @param	string	$a_parent_cmd
	 * @param	int		$a_ref_id
	 * @param	int		$a_type_id
	 */
	function __construct($a_parent_obj, $a_parent_cmd, $a_ref_id, $a_type_id)
	{
		global $ilCtrl, $lng, $ilAccess, $lng, $ilObjDataCache;

		$this->ref_id = $a_ref_id;
		$this->type_id = $a_type_id;
		$this->setId("bkobj");

		$ilCtrl->setParameter($a_parent_obj, 'type_id', $this->type_id);

		parent::__construct($a_parent_obj, $a_parent_cmd);

		include_once 'Modules/BookingManager/classes/class.ilBookingType.php';
		$type = new ilBookingType($this->type_id);
		$this->setTitle($lng->txt("book_objects_list").$type->getTitle());

		$this->setLimit(9999);
		
		$this->addColumn($this->lng->txt("title"), "title");

		if ($ilAccess->checkAccess('write', '', $this->ref_id))
		{
			$this->addCommandButton('create', $this->lng->txt('book_add_object'));

			$this->addColumn($this->lng->txt("status"));
			$this->addColumn($this->lng->txt("book_current_user"));
			$this->addColumn($this->lng->txt("book_period"));
		}
		
		$this->addColumn($this->lng->txt("actions"));

		$this->setEnableHeader(true);
		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj, $a_parent_cmd));
		$this->setRowTemplate("tpl.booking_object_row.html", "Modules/BookingManager");
		$this->initFilter();

		$this->getItems($this->type_id, $this->getCurrentFilter());
	}

	/**
	* Init filter
	*/
	function initFilter()
	{
		global $lng;

		/*
		$item = $this->addFilterItemByMetaType("country", ilTable2GUI::FILTER_TEXT, true);
		$this->filter["country"] = $item->getValue();
		 */
	}

	/**
	 * Get current filter settings
	 * @return	array
	 */
	function getCurrentFilter()
	{

	}
	
	/**
	 * Gather data and build rows
	 * @param	int	$a_type_id
	 */
	function getItems($a_type_id)
	{
		global $lng;

		include_once 'Modules/BookingManager/classes/class.ilBookingObject.php';
		$data = ilBookingObject::getList($a_type_id);
		
		$this->setMaxCount(sizeof($data));
		$this->setData($data);
	}

	/**
	 * Fill table row
	 * @param	array	$a_set
	 */
	protected function fillRow($a_set)
	{
		global $lng, $ilAccess, $ilCtrl;

	    $this->tpl->setVariable("TXT_TITLE", $a_set["title"]);

		if ($ilAccess->checkAccess('write', '', $this->ref_id))
		{
			include_once 'Modules/BookingManager/classes/class.ilBookingReservation.php';
			$reservation = ilBookingReservation::getCurrentOrUpcomingReservation($a_set['booking_object_id']);
		}

		if ($ilAccess->checkAccess('write', '', $this->ref_id))
		{
			$this->tpl->setCurrentBlock('details');

			if($reservation)
			{
				$date_from = new ilDateTime($reservation['date_from'], IL_CAL_UNIX);
				$date_to = new ilDateTime($reservation['date_to'], IL_CAL_UNIX);
				$this->tpl->setVariable("TXT_STATUS", $lng->txt('book_reservation_status_'.$reservation['status']));
				$this->tpl->setVariable("TXT_CURRENT_USER", ilObjUser::_lookupFullName($reservation['user_id']));
				$this->tpl->setVariable("VALUE_DATE", ilDatePresentation::formatPeriod($date_from, $date_to));
			}

			$this->tpl->parseCurrentBlock();
		}

		$ilCtrl->setParameter($this->parent_obj, 'object_id', $a_set['booking_object_id']);
		
		$this->tpl->setCurrentBlock('item_command');

		if ($a_set["schedule_id"])
		{
			$this->tpl->setVariable('HREF_COMMAND', $ilCtrl->getLinkTarget($this->parent_obj, 'book'));
			$this->tpl->setVariable('TXT_COMMAND', $lng->txt('book_book'));
			$this->tpl->parseCurrentBlock();
		}

		if ($ilAccess->checkAccess('write', '', $this->ref_id))
		{
			if(!$reservation)
			{
				$this->tpl->setVariable('HREF_COMMAND', $ilCtrl->getLinkTarget($this->parent_obj, 'confirmDelete'));
				$this->tpl->setVariable('TXT_COMMAND', $lng->txt('delete'));
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setVariable('HREF_COMMAND', $ilCtrl->getLinkTarget($this->parent_obj, 'edit'));
			$this->tpl->setVariable('TXT_COMMAND', $lng->txt('edit'));
			$this->tpl->parseCurrentBlock();
		}
	}
}

?>