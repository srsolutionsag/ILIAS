<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once ('./Services/Verification/classes/class.ilVerificationObject.php');

/**
* Course Verification
*
* @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
*
* @version $Id$
*
* @ingroup ModulesCourse
*/
class ilObjCourseVerification extends ilVerificationObject
{
	protected function initType()
	{
		$this->type = "crsv";
	}

	protected function getPropertyMap()
	{
		return array("issued_on" => self::TYPE_DATE,
			"file" => self::TYPE_STRING
			);
	}

	/**
	 * Import relevant properties from given course
	 *
	 * @param ilObjCourse $a_course
	 * @return object
	 */
	public static function createFromCourse(ilObjCourse $a_course, $a_user_id)
	{
		global $DIC;

		$lng = $DIC['lng'];
		$database = $DIC->database();
		$logger = $DIC->logger()->root();
		
		$lng->loadLanguageModule("crs");
		
		$newObj = new self();
		$newObj->setTitle($a_course->getTitle());
		$newObj->setDescription($a_course->getDescription());

		include_once "Services/Tracking/classes/class.ilLPMarks.php";
		$lp_marks = new ilLPMarks($a_course->getId(), $a_user_id);
		$newObj->setProperty("issued_on", 
			new ilDate($lp_marks->getStatusChanged(), IL_CAL_DATETIME));

		$ilUserCertificateRepository = new ilUserCertificateRepository($database, $logger);
		$pdfGenerator = new ilPdfGenerator($ilUserCertificateRepository, $logger);

		$pdfAction = new ilCertificatePdfAction($logger, $pdfGenerator);

		$certificate = $pdfAction->createPDF($a_user_id, $a_course->getid());

		// save pdf file
		if($certificate)
		{
			// we need the object id for storing the certificate file
			$newObj->create();
			
			$path = self::initStorage($newObj->getId(), "certificate");
			
			$file_name = "crs_".$a_course->getId()."_".$a_user_id.".pdf";			
			if(file_put_contents($path.$file_name, $certificate))
			{							
				$newObj->setProperty("file", $file_name);
				$newObj->update();
				
				return $newObj;
			}
		
			// file creation failed, so remove to object, too
			$newObj->delete();
		}
	}
}

?>
