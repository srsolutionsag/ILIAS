<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Survey evaluation graphical output
*
* The ilSurveyEvaluationGUI class creates the evaluation output for the ilObjSurveyGUI
* class. This saves some heap space because the ilObjSurveyGUI class will be
* smaller.
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id$
* @ingroup ModulesSurvey
*/
class ilSurveyEvaluationGUI
{	
	const TYPE_XLS = "excel";
	const TYPE_SPSS = "csv";
	
	const EXCEL_SUBTITLE = "DDDDDD";
	
	var $object;
	var $lng;
	var $tpl;
	var $ctrl;
	var $appr_id = null;
	
/**
* ilSurveyEvaluationGUI constructor
*
* The constructor takes possible arguments an creates an instance of the ilSurveyEvaluationGUI object.
*
* @param object $a_object Associated ilObjSurvey class
* @access public
*/
  function __construct($a_object)
  {
		global $lng, $tpl, $ilCtrl;

		$this->lng = $lng;
		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->object = $a_object;
		if ($this->object->get360Mode())
		{
			$this->determineAppraiseeId();
		}
	}
	
	/**
	* execute command
	*/
	function executeCommand()
	{
		include_once("./Services/Skill/classes/class.ilSkillManagementSettings.php");
		$skmg_set = new ilSkillManagementSettings();
		if ($this->object->get360SkillService() && $skmg_set->isActivated())
		{
			$cmd = $this->ctrl->getCmd("competenceEval");
		}
		else
		{
			$cmd = $this->ctrl->getCmd("evaluation");
		}
		
		$next_class = $this->ctrl->getNextClass($this);

		$cmd = $this->getCommand($cmd);
		switch($next_class)
		{
			default:
				$this->setEvalSubTabs();
				$ret =& $this->$cmd();
				break;
		}
		return $ret;
	}

	function getCommand($cmd)
	{
		return $cmd;
	}

	/**
	* Set the tabs for the evaluation output
	*
	* @access private
	*/
	function setEvalSubtabs()
	{
		global $ilTabs;
		global $ilAccess;

		include_once("./Services/Skill/classes/class.ilSkillManagementSettings.php");
		$skmg_set = new ilSkillManagementSettings();
		if ($this->object->get360SkillService() && $skmg_set->isActivated())
		{
			$ilTabs->addSubTabTarget(
				"svy_eval_competences", 
				$this->ctrl->getLinkTarget($this, "competenceEval"), 
				array("competenceEval")
			);
		}

		$ilTabs->addSubTabTarget(
			"svy_eval_cumulated", 
			$this->ctrl->getLinkTarget($this, "evaluation"), 
			array("evaluation", "checkEvaluationAccess")
		);

		$ilTabs->addSubTabTarget(
			"svy_eval_detail", 
			$this->ctrl->getLinkTarget($this, "evaluationdetails"), 
			array("evaluationdetails")
		);
		
		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$ilTabs->addSubTabTarget(
				"svy_eval_user", 
				$this->ctrl->getLinkTarget($this, "evaluationuser"), 
				array("evaluationuser")
			);
		}
	}

	
	/**
	 * Set appraisee id
	 *
	 * @param int $a_val appraisee id	
	 */
	function setAppraiseeId($a_val)
	{
		$this->appr_id = $a_val;
	}
	
	/**
	 * Get appraisee id
	 *
	 * @return int appraisee id
	 */
	function getAppraiseeId()
	{
		return $this->appr_id;
	}
	
	/**
	 * Determine appraisee id
	 */
	function determineAppraiseeId()
	{
		global $ilUser, $rbacsystem;
		
		$appr_id = "";
		
		// always start with current user
		if ($_REQUEST["appr_id"] == "")
		{
			$req_appr_id = $ilUser->getId();
		}
		else
		{
			$req_appr_id = (int) $_REQUEST["appr_id"];
		}
		
		// write access? allow selection
		if ($req_appr_id > 0)
		{
			$all_appr = ($this->object->get360Results() == ilObjSurvey::RESULTS_360_ALL);
			
			$valid = array();				
			foreach($this->object->getAppraiseesData() as $item)
			{				
				if ($item["closed"] &&
					($item["user_id"] == $ilUser->getId() ||
					$rbacsystem->checkAccess("write", $this->object->getRefId()) ||
					$all_appr))
				{
					$valid[] = $item["user_id"];
				}				
			}
			if(in_array($req_appr_id, $valid))
			{
				$appr_id = $req_appr_id;
			}
			else 
			{
				// current selection / user is not valid, use 1st valid instead
				$appr_id = array_shift($valid);
			}				
		}
		
		$this->ctrl->setParameter($this, "appr_id", $appr_id);		
		$this->setAppraiseeId($appr_id);	
	}
	
	
	/**
	* Show the detailed evaluation
	*
	* Show the detailed evaluation
	*
	* @access private
	*/
	function checkAnonymizedEvaluationAccess()
	{
		global $ilUser;
		
		if($this->object->getAnonymize() == 1 && 
			$_SESSION["anon_evaluation_access"] == $_GET["ref_id"])
		{
			return true;
		}
		
		include_once "Modules/Survey/classes/class.ilObjSurveyAccess.php";
		if(ilObjSurveyAccess::_hasEvaluationAccess(ilObject::_lookupObjId($_GET["ref_id"]), $ilUser->getId()))
		{
			if($this->object->getAnonymize() == 1)
			{
				$_SESSION["anon_evaluation_access"] = $_GET["ref_id"];
			}
			return true;
		}
		
		if($this->object->getAnonymize() == 1)
		{
			// autocode
			$surveycode = $this->object->getUserAccessCode($ilUser->getId());		
			if ($this->object->isAnonymizedParticipant($surveycode))
			{
				$_SESSION["anon_evaluation_access"] = $_GET["ref_id"];
				return true;
			}
			
			/* try to find code for current (registered) user from existing run		
			if($this->object->findCodeForUser($ilUser->getId()))
			{
				$_SESSION["anon_evaluation_access"] = $_GET["ref_id"];
				return true;
			}		
			*/
			
			// code needed
			$this->tpl->setVariable("TABS", "");
			$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_evaluation_checkaccess.html", "Modules/Survey");
			$this->tpl->setCurrentBlock("adm_content");
			$this->tpl->setVariable("AUTHENTICATION_NEEDED", $this->lng->txt("svy_check_evaluation_authentication_needed"));
			$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this, "checkEvaluationAccess"));
			$this->tpl->setVariable("EVALUATION_CHECKACCESS_INTRODUCTION", $this->lng->txt("svy_check_evaluation_access_introduction"));
			$this->tpl->setVariable("VALUE_CHECK", $this->lng->txt("ok"));
			$this->tpl->setVariable("VALUE_CANCEL", $this->lng->txt("cancel"));
			$this->tpl->setVariable("TEXT_SURVEY_CODE", $this->lng->txt("survey_code"));
			$this->tpl->parseCurrentBlock();
		}
		
		$_SESSION["anon_evaluation_access"] = null;
		return false;
	}

	/**
	* Checks the evaluation access after entering the survey access code
	*
	* Checks the evaluation access after entering the survey access code
	*
	* @access private
	*/
	function checkEvaluationAccess()
	{
		$surveycode = $_POST["surveycode"];
		if ($this->object->isAnonymizedParticipant($surveycode))
		{
			$_SESSION["anon_evaluation_access"] = $_GET["ref_id"];
			$this->evaluation();
		}
		else
		{
			ilUtil::sendFailure($this->lng->txt("svy_check_evaluation_wrong_key", true));
			$this->cancelEvaluationAccess();
		}
	}
	
	/**
	* Cancels the input of the survey access code for evaluation access
	*
	* Cancels the input of the survey access code for evaluation access
	*
	* @access private
	*/
	function cancelEvaluationAccess()
	{
		global $ilCtrl, $tree;
		$path = $tree->getPathFull($this->object->getRefID());
		$ilCtrl->setParameterByClass("ilrepositorygui", "ref_id",
			$path[count($path) - 2]["child"]);
		$ilCtrl->redirectByClass("ilrepositorygui", "frameset");
	}
	
	/**
	* Show the detailed evaluation
	*
	* Show the detailed evaluation
	*
	* @access private
	*/
	function evaluationdetails()
	{
		$this->evaluation(1);
	}
	
	function exportCumulatedResults($details = 0)
	{				
		$finished_ids = null;
		if($this->object->get360Mode())
		{
			$appr_id = $_REQUEST["appr_id"];
			if(!$appr_id)
			{
				$this->ctrl->redirect($this, $details ? "evaluationdetails" : "evaluation");
			}			
			$finished_ids = $this->object->getFinishedIdsForAppraiseeId($appr_id);	
			if(!sizeof($finished_ids))
			{
				$finished_ids = array(-1);
			}
		}
		
		// titles
		$title_row = array();				
		$do_title = $do_label = true;
		switch ($_POST['export_label'])
		{
			case 'label_only':
				$title_row[] = $this->lng->txt("label");	
				$do_title = false;
				break;

			case 'title_only':
				$title_row[] = $this->lng->txt("title");
				$do_label = false;
				break;

			default:
				$title_row[] = $this->lng->txt("title");
				$title_row[] = $this->lng->txt("label");
				break;
		}		
		$title_row[] = $this->lng->txt("question");
		$title_row[] = $this->lng->txt("question_type");
		$title_row[] = $this->lng->txt("users_answered");
		$title_row[] = $this->lng->txt("users_skipped");
		$title_row[] = $this->lng->txt("mode");
		$title_row[] = $this->lng->txt("mode_text");
		$title_row[] = $this->lng->txt("mode_nr_of_selections");
		$title_row[] = $this->lng->txt("median");
		$title_row[] = $this->lng->txt("arithmetic_mean");
		
		// creating container
		switch ($_POST["export_format"])
		{
			case self::TYPE_XLS:
				include_once "Services/Excel/classes/class.ilExcel.php";
				$excel = new ilExcel();
				$excel->addSheet($this->lng->txt("svy_eval_cumulated"));				
				$excel->setCellArray(array($title_row), "A1");
				$excel->setBold("A1:".$excel->getColumnCoord(sizeof($title_row)-1)."1");
				break;
			
			case self::TYPE_SPSS:
				$csvfile = array($title_row);				
				break;
		}		
				
		
		// parse answer data in evaluation results
		$ov_row = 2;
		include_once "./Modules/SurveyQuestionPool/classes/class.SurveyQuestion.php";						
		foreach($this->object->getSurveyQuestions() as $qdata)
		{						
			$q_eval = SurveyQuestion::_instanciateQuestionEvaluation($qdata["question_id"], $finished_ids);		
			$q_res =  $q_eval->getResults();			
			$ov_rows = $q_eval->exportResults($q_res, $do_title, $do_label);
			
			switch ($_POST["export_format"])
			{
				case self::TYPE_XLS:
					$excel->setActiveSheet(0);		
					foreach($ov_rows as $row)
					{
						foreach($row as $col => $value)
						{
							$excel->setCell($ov_row, $col, $value);
						}
						$ov_row++;
					}					
					break;
				
				case self::TYPE_SPSS:					
					foreach($ov_rows as $row)
					{
						$csvfile[] = $row;
					}
					break;
			}
			
			if ($details)
			{
				switch ($_POST["export_format"])
				{
					case self::TYPE_XLS:					
						$this->exportResultsDetailsExcel($excel, $q_eval, $q_res, $do_title, $do_label);											
						break;
				}				
			}
			
		}			
		
		// #11179		
		$type = !$details
			? $this->lng->txt("svy_eval_cumulated")
			: $this->lng->txt("svy_eval_detail");
			
		$surveyname = $this->object->getTitle()." ".$type." ".date("Y-m-d");
		$surveyname = preg_replace("/\s/", "_", trim($surveyname));
		$surveyname = ilUtil::getASCIIFilename($surveyname);
		
		// send to client
		switch ($_POST["export_format"])
		{
			case self::TYPE_XLS:
				$excel->sendToClient($surveyname);
				break;
			
			case self::TYPE_SPSS:
				$csv = "";
				$separator = ";";
				foreach ($csvfile as $csvrow)
				{
					$csvrow = $this->processCSVRow($csvrow, TRUE, $separator);
					$csv .= join($csvrow, $separator) . "\n";
				}
				ilUtil::deliverData($csv, $surveyname.".csv");
				exit();
				break;
		}
	}
	
	/**
	 * Export details (excel only)
	 * 
	 * @param ilExcel $a_excel
	 * @param SurveyQuestionEvaluation $a_eval
	 * @param ilSurveyEvaluationResults|array $a_results
	 * @param bool $a_do_title
	 * @param bool|array $a_do_label
	 */
	protected function exportResultsDetailsExcel(ilExcel $a_excel, SurveyQuestionEvaluation $a_eval, $a_results, $a_do_title, $a_do_label)
	{								
		$question_res = $a_results;
		$matrix = false;
		if(is_array($question_res))
		{
			$question_res = $question_res[0][1];
			$matrix = true;
		}
		$question = $question_res->getQuestion();
		
		$a_excel->addSheet($question->getTitle());
		
		
		// question "overview"
		
		$kv = array();
		
		if($a_do_title)
		{
			$kv[$this->lng->txt("title")] = $question->getTitle();
		}
		if($a_do_label)
		{
			$kv[$this->lng->txt("label")] = $question->label;
		}
		
		$kv[$this->lng->txt("question")] = $question->getQuestiontext();
		$kv[$this->lng->txt("question_type")] = SurveyQuestion::_getQuestionTypeName($question->getQuestionType());
		
		// :TODO: present subtypes (hrz/vrt, mc/sc mtx)?	
		
		$kv[$this->lng->txt("users_answered")] = (int)$question_res->getUsersAnswered();
		$kv[$this->lng->txt("users_skipped")] = (int)$question_res->getUsersAnswered();
				
		$excel_row = 1;
		
		foreach($kv as $key => $value)
		{
			$a_excel->setCell($excel_row, 0, $key);
			$a_excel->setCell($excel_row++, 1, $value);			
		}
		
		if(!$matrix)
		{
			$this->parseResultsToExcel(
				$a_excel, 
				$question_res, 
				$excel_row, 
				$a_eval->getExportGrid($a_results),  
				$a_eval->getTextAnswers($a_results)
			);			
		}
		else
		{
			// question
			$this->parseResultsToExcel(
				$a_excel, 
				$question_res, 
				$excel_row, 
				null,
				null,
				false
			);	
						
			$texts = $a_eval->getTextAnswers($a_results);
			
			// "rows"
			foreach($a_results as $row_results)
			{
				$row_title = $row_results[0];
				
				$a_excel->setCell($excel_row, 0,  $this->lng->txt("row"));
				$a_excel->setCell($excel_row++, 1, $row_title);	
				
				$this->parseResultsToExcel(
					$a_excel, 
					$row_results[1], 
					$excel_row,
					$a_eval->getExportGrid($row_results[1]),
					is_array($texts[$row_title]) 
						? array(""=>$texts[$row_title])
						: null
				);			
			}			
		}
	
		// 1st column is bold
		$a_excel->setBold("A1:A".$excel_row);									
	}
	
	protected function parseResultsToExcel(ilExcel $a_excel, ilSurveyEvaluationResults $a_results, &$a_excel_row, array $a_grid = null, array $a_text_answers = null, $a_include_mode = true)
	{
		$kv = array();
		
		if($a_include_mode)
		{
			if($a_results->getModeValue() !== null)
			{
				// :TODO:
				$kv[$this->lng->txt("mode")] = is_array($a_results->getModeValue())
					? implode(", ", $a_results->getModeValue())
					: $a_results->getModeValue();
				
				$kv[$this->lng->txt("mode_text")] = $a_results->getModeValueAsText();		
				$kv[$this->lng->txt("mode_nr_of_selections")] = (int)$a_results->getModeNrOfSelections();
			}

			if($a_results->getMedian() !== null)
			{
				$kv[$this->lng->txt("median")] = $a_results->getMedianAsText();
			}

			if($a_results->getMean() !== null)
			{
				$kv[$this->lng->txt("arithmetic_mean")] = $a_results->getMean();
			}	
		}
		
		foreach($kv as $key => $value)
		{
			$a_excel->setCell($a_excel_row, 0, $key);
			$a_excel->setCell($a_excel_row++, 1, $value);			
		}
				
		// grid
		if($a_grid)
		{		
			// header
			$a_excel->setColors("B".$a_excel_row.":E".$a_excel_row, ilSurveyEvaluationGUI::EXCEL_SUBTITLE);
			$a_excel->setCell($a_excel_row, 0, $this->lng->txt("categories"));	
			foreach($a_grid["cols"] as $col_idx => $col)
			{
				$a_excel->setCell($a_excel_row, $col_idx+1, $col);				
			}
			$a_excel_row++;
			
			// rows
			foreach($a_grid["rows"] as $cols)
			{				
				foreach($cols as $col_idx => $col)
				{					
					$a_excel->setCell($a_excel_row, $col_idx+1, $col);
				}				
				$a_excel_row++;		
			}
		}
				
		// text answers			
		if($a_text_answers)
		{						
			// "given_answers" ?
			$a_excel->setCell($a_excel_row, 0, $this->lng->txt("freetext_answers"));
			if(!is_array($a_text_answers[""]))
			{
				$a_excel->setColors("B".$a_excel_row.":C".$a_excel_row, ilSurveyEvaluationGUI::EXCEL_SUBTITLE);		
				$a_excel->setCell($a_excel_row, 1, $this->lng->txt("title"));
				$a_excel->setCell($a_excel_row++, 2, $this->lng->txt("answer"));			
			}
			else
			{
				$a_excel->setColors("B".$a_excel_row.":B".$a_excel_row, ilSurveyEvaluationGUI::EXCEL_SUBTITLE);		
				$a_excel->setCell($a_excel_row++, 1, $this->lng->txt("answer"));		
			}
			foreach($a_text_answers as $var => $items)
			{			
				foreach($items as $item)
				{					
					if(!is_array($a_text_answers[""]))
					{
						$a_excel->setCell($a_excel_row, 1, $var);
						$a_excel->setCell($a_excel_row++, 2, $item);					
					}
					else
					{
						$a_excel->setCell($a_excel_row++, 1, $item);	
					}
				}				
			}
		}
	}
	
	public function exportData()
	{
		if (strlen($_POST["export_format"]))
		{
			$this->exportCumulatedResults(0);
			return;
		}
		else
		{
			$this->ctrl->redirect($this, 'evaluation');
		}
	}
	
	public function exportDetailData()
	{
		if (strlen($_POST["export_format"]))
		{
			$this->exportCumulatedResults(1);
			return;
		}
		else
		{
			$this->ctrl->redirect($this, 'evaluation');
		}
	}
	
	public function printEvaluation()
	{
		ilUtil::sendInfo($this->lng->txt('use_browser_print_function'), true);
		$this->ctrl->redirect($this, 'evaluation');
	}
	
	function evaluation($details = 0)
	{
		global $rbacsystem, $ilToolbar;

		// auth
		if (!$rbacsystem->checkAccess("write", $_GET["ref_id"]))
		{			
			if (!$rbacsystem->checkAccess("read",$_GET["ref_id"]))
			{
				ilUtil::sendFailure($this->lng->txt("permission_denied"));
				return;
			}		
				
			switch ($this->object->getEvaluationAccess())
			{
				case ilObjSurvey::EVALUATION_ACCESS_OFF:
					ilUtil::sendFailure($this->lng->txt("permission_denied"));
					return;

				case ilObjSurvey::EVALUATION_ACCESS_ALL:				
				case ilObjSurvey::EVALUATION_ACCESS_PARTICIPANTS:
					if(!$this->checkAnonymizedEvaluationAccess())
					{
						ilUtil::sendFailure($this->lng->txt("permission_denied"));
						return;
					}
					break;
			}
		}
		
		$ilToolbar->setFormAction($this->ctrl->getFormAction($this));
		include_once "Services/Form/classes/class.ilPropertyFormGUI.php";

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_evaluation.html", "Modules/Survey");
				
		if($this->object->get360Mode())
		{				
			$appr_id = $this->getAppraiseeId();
			$this->addApprSelectionToToolbar();
		}

		$results = array();
		if(!$this->object->get360Mode() || $appr_id)
		{
			$format = new ilSelectInputGUI($this->lng->txt("svy_export_format"), "export_format");
			$format->setOptions(array(
				self::TYPE_XLS => $this->lng->txt('exp_type_excel'),
				self::TYPE_SPSS => $this->lng->txt('exp_type_csv')
				));
			$ilToolbar->addInputItem($format, true);

			$label = new ilSelectInputGUI("", "export_label");
			$label->setOptions(array(
				'label_only' => $this->lng->txt('export_label_only'), 
				'title_only' => $this->lng->txt('export_title_only'), 
				'title_label'=> $this->lng->txt('export_title_label')
				));
			$ilToolbar->addInputItem($label);

			include_once "Services/UIComponent/Button/classes/class.ilSubmitButton.php";		
			$button = ilSubmitButton::getInstance();
			$button->setCaption("export");			
			if ($details)
			{
				$button->setCommand('exportDetailData');					
			}
			else
			{
				$button->setCommand('exportData');				
			}
			$button->setOmitPreventDoubleSubmission(true);
			$ilToolbar->addButtonInstance($button);	
				
			$ilToolbar->addSeparator();

			include_once "Services/UIComponent/Button/classes/class.ilLinkButton.php";
			$button = ilLinkButton::getInstance();
			$button->setCaption("print");
			$button->setOnClick("window.print(); return false;");
			$button->setOmitPreventDoubleSubmission(true);
			$ilToolbar->addButtonInstance($button);		
			
			$finished_ids = null;
			if($appr_id)
			{
				$finished_ids = $this->object->getFinishedIdsForAppraiseeId($appr_id);	
				if(!sizeof($finished_ids))
				{
					$finished_ids = array(-1);
				}
			}
			
			if($details)
			{
				$dtmpl = new ilTemplate("tpl.il_svy_svy_results_details.html", true, true, "Modules/Survey");
			}			
			
			// parse answer data in evaluation results
			include_once "./Modules/SurveyQuestionPool/classes/class.SurveyQuestion.php";						
			foreach($this->object->getSurveyQuestions() as $qdata)
			{						
				$q_eval = SurveyQuestion::_instanciateQuestionEvaluation($qdata["question_id"], $finished_ids);		
				$q_res =  $q_eval->getResults();
				$results[] = $q_res;	
						
				if($details)
				{			
					$this->renderDetails($dtmpl, $qdata, $q_eval, $q_res);										
				}
			}				
		}		
		
		include_once "./Modules/Survey/classes/tables/class.ilSurveyResultsCumulatedTableGUI.php";
		$table_gui = new ilSurveyResultsCumulatedTableGUI($this, $details ? 'evaluationdetails' : 'evaluation', $results);	
		$this->tpl->setVariable('CUMULATED', $table_gui->getHTML().($dtmpl ? $dtmpl->get() : ""));	
		
		$this->tpl->addCss("./Modules/Survey/templates/default/survey_print.css", "print");
		$this->tpl->setVariable('FORMACTION', $this->ctrl->getFormAction($this, 'evaluation'));					
	}
	
	/**
	 * Render details
	 * 
	 * @param ilTemplate $a_tpl
	 * @param array $a_qdata
	 * @param SurveyQuestionEvaluation $a_eval
	 * @param ilSurveyEvaluationResults|array $a_results
	 */
	protected function renderDetails(ilTemplate $a_tpl, array $a_qdata, SurveyQuestionEvaluation $a_eval, $a_results)
	{		
		$question_res = $a_results;
		$matrix = false;
		if(is_array($question_res))
		{
			$question_res = $question_res[0][1];
			$matrix = true;
		}
		$question = $question_res->getQuestion();
		
		// toc (incl. question blocks)
					
		// questionblock title handling
		if($a_qdata["questionblock_id"] && 
			$a_qdata["questionblock_id"] != $this->last_questionblock_id)
		{
			$qblock = ilObjSurvey::_getQuestionblock($a_qdata["questionblock_id"]);

			if($qblock["show_blocktitle"])
			{
				$a_tpl->setCurrentBlock("toc_bl");
				$a_tpl->setVariable("TOC_ITEM", $a_qdata["questionblock_title"]);							
				$a_tpl->parseCurrentBlock();
			}

			$this->last_questionblock_id = $a_qdata["questionblock_id"];
		}	
		
		$anchor_id = "svyrdq".$a_qdata["question_id"];
		
		$a_tpl->setCurrentBlock("toc_bl");
		$a_tpl->setVariable("TOC_ITEM", $a_qdata["title"]);							
		$a_tpl->setVariable("TOC_ID", $anchor_id);							
		$a_tpl->parseCurrentBlock();

		
		// question "overview"
				
		// :TODO: present subtypes (hrz/vrt, mc/sc mtx)?	
		
		$a_tpl->setVariable("QTYPE", SurveyQuestion::_getQuestionTypeName($question->getQuestionType()));				
		$a_tpl->setVariable("VAL_ANSWERED", $question_res->getUsersAnswered());				
		$a_tpl->setVariable("TXT_ANSWERED", $this->lng->txt("users_answered"));				
		$a_tpl->setVariable("VAL_SKIPPED", $question_res->getUsersSkipped());				
		$a_tpl->setVariable("TXT_SKIPPED", $this->lng->txt("users_skipped"));				
		
		if(!$matrix)
		{
			if($question_res->getModeValue() !== null)
			{
				$a_tpl->setVariable("VAL_MODE", $question_res->getModeValueAsText());				
				$a_tpl->setVariable("TXT_MODE", $this->lng->txt("mode"));		
				$a_tpl->setVariable("VAL_MODE_NR", $question_res->getModeNrOfSelections());				
				$a_tpl->setVariable("TXT_MODE_NR", $this->lng->txt("mode_nr_of_selections"));						
			}
			if($question_res->getMedian() !== null)
			{
				$a_tpl->setVariable("VAL_MEDIAN", $question_res->getMedianAsText());				
				$a_tpl->setVariable("TXT_MEDIAN", $this->lng->txt("median"));																					
			}
			if($question_res->getMean() !== null)
			{
				$a_tpl->setVariable("VAL_MEAN", $question_res->getMean());				
				$a_tpl->setVariable("TXT_MEAN", $this->lng->txt("arithmetic_mean"));																					
			}
		}
		
		
		// grid
		
		$grid = $a_eval->getGrid($a_results);
		if($grid)
		{			
			foreach($grid["cols"] as $col)
			{
				$a_tpl->setCurrentBlock("grid_col_header_bl");
				$a_tpl->setVariable("COL_HEADER", $col);														
				$a_tpl->parseCurrentBlock();
			}
			foreach($grid["rows"] as $cols)
			{				
				foreach($cols as $col)
				{
					// :TODO: matrix percentages
					if(is_array($col))
					{
						$col = implode(" / ", $col);
					}
					
					$a_tpl->setCurrentBlock("grid_col_bl");
					$a_tpl->setVariable("COL_CAPTION", trim($col));														
					$a_tpl->parseCurrentBlock();
				}
				
				$a_tpl->touchBlock("grid_row_bl");			
			}
		}
		
		
		// text answers
		
		// :TODO: modal?
		
		$texts = $a_eval->getTextAnswers($a_results);
		if($texts)
		{			
			foreach($texts as $var => $items)
			{			
				foreach($items as $item)
				{
					$a_tpl->setCurrentBlock("text_item_bl");
					$a_tpl->setVariable("TEXT_ITEM", nl2br($item));														
					$a_tpl->parseCurrentBlock();
				}
				if($var)
				{
					$a_tpl->setVariable("TEXT_VAR", $var);				
				}
				$a_tpl->touchBlock("texts_for_var_bl");
			}
		}
		
		
		// chart
		
		$chart = $a_eval->getChart($a_results);
		if($chart)
		{
			if(is_array($chart))
			{
				// legend
				if(is_array($chart[1]))
				{
					foreach($chart[1] as $legend_id => $legend_caption)
					{
						// :TODO: color?
						$a_tpl->setCurrentBlock("legend_bl");
						$a_tpl->setVariable("LEGEND_ID", $legend_id);		
						$a_tpl->setVariable("LEGEND_CAPTION", $legend_caption);								
						$a_tpl->parseCurrentBlock();	
					}
				}
				
				$chart = $chart[0];
			}
			$a_tpl->setVariable("CHART", $chart);	
		}
				
					
		// question "panel"
		
		$a_tpl->setCurrentBlock("question_panel_bl");
		$a_tpl->setVariable("ANCHOR_ID", $anchor_id);		
		$a_tpl->setVariable("QTITLE", $question->getTitle());		
		$a_tpl->setVariable("QTEXT", nl2br($question->getQuestiontext()));	
		$a_tpl->parseCurrentBlock();			
	}
	
	/**
	 * Add appraisee selection to toolbar
	 *
	 * @param
	 * @return
	 */
	function addApprSelectionToToolbar()
	{
		global $ilToolbar, $rbacsystem;
		
		if($this->object->get360Mode())
		{
			$appr_id = $this->getAppraiseeId();

			$options = array();
			if(!$appr_id)
			{
				$options[""] = $this->lng->txt("please_select");
			}
			$no_appr = true;
			foreach($this->object->getAppraiseesData() as $item)
			{
				if($item["closed"])
				{
					$options[$item["user_id"]] = $item["login"];
					$no_appr = false;
				}
			}

			if(!$no_appr)
			{								
				if ($rbacsystem->checkAccess("write", $this->object->getRefId()) ||
					$this->object->get360Results() == ilObjSurvey::RESULTS_360_ALL)
				{			
					include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
					$appr = new ilSelectInputGUI($this->lng->txt("survey_360_appraisee"), "appr_id");
					$appr->setOptions($options);
					$appr->setValue($this->getAppraiseeId());
					$ilToolbar->addInputItem($appr, true);
					
					include_once "Services/UIComponent/Button/classes/class.ilSubmitButton.php";		
					$button = ilSubmitButton::getInstance();
					$button->setCaption("survey_360_select_appraisee");								
					$button->setCommand($this->ctrl->getCmd());															
					$ilToolbar->addButtonInstance($button);	
	
					if($appr_id)
					{
						$ilToolbar->addSeparator();												
					}
				}
			}
			else
			{
				ilUtil::sendFailure($this->lng->txt("survey_360_no_closed_appraisees"));				
			}
		}

	}
	
	
	/**
	* Export the user specific results for the survey
	*
	* Export the user specific results for the survey
	*
	* @access private
	*/
	function exportUserSpecificResults($export_format, $export_label, $finished_ids)
	{
		global $ilLog;
		
		// #13620
		ilDatePresentation::setUseRelativeDates(false);
		
		$csvfile = array();
		$csvrow = array();
		$csvrow2 = array();
		$questions = array();
		$questions =& $this->object->getSurveyQuestions(true);		
		array_push($csvrow, $this->lng->txt("lastname")); // #12756
		array_push($csvrow, $this->lng->txt("firstname"));
		array_push($csvrow, $this->lng->txt("login"));
		array_push($csvrow, $this->lng->txt('workingtime')); // #13622
		array_push($csvrow, $this->lng->txt('survey_results_finished'));
		array_push($csvrow2, "");
		array_push($csvrow2, "");
		array_push($csvrow2, "");
		array_push($csvrow2, "");
		array_push($csvrow2, "");
		if ($this->object->canExportSurveyCode())
		{
			array_push($csvrow, $this->lng->txt("codes"));
			array_push($csvrow2, "");
		}
		/* #8211
		if ($this->object->getAnonymize() == ilObjSurvey::ANONYMIZE_OFF)
		{
			array_push($csvrow, $this->lng->txt("gender"));
		}		 
	    */
		$cellcounter = 1;
		
		foreach ($questions as $question_id => $question_data)
		{
			include_once "./Modules/SurveyQuestionPool/classes/class.SurveyQuestion.php";
			$question = SurveyQuestion::_instanciateQuestion($question_data["question_id"]);
			switch ($export_label)
			{
				case "label_only":
					$question->addUserSpecificResultsExportTitles($csvrow, true);					
					break;
					
				case "title_only":
					$question->addUserSpecificResultsExportTitles($csvrow, false);	
					break;
					
				default:
					$question->addUserSpecificResultsExportTitles($csvrow, false);		
					$question->addUserSpecificResultsExportTitles($csvrow2, true, false);		
					break;
			}
			
			$questions[$question_data["question_id"]] = $question;
		}
		array_push($csvfile, $csvrow);
		if(sizeof($csvrow2) && implode("", $csvrow2))
		{
			array_push($csvfile, $csvrow2);
		}				
		if(!$finished_ids)
		{
			$participants =& $this->object->getSurveyFinishedIds();
		}
		else
		{
			$participants = $finished_ids;
		}
		$finished_data = array();
		foreach($this->object->getSurveyParticipants($participants) as $item)
		{
			$finished_data[$item["active_id"]] = $item;
		}
		foreach ($participants as $user_id)
		{		
			if($user_id < 1)
			{
				continue;
			}
			
			$resultset =& $this->object->getEvaluationByUser($questions, $user_id);			
			$csvrow = array();
			
			// #12756			
			array_push($csvrow, (trim($resultset["lastname"])) 
				? $resultset["lastname"] 
				: $resultset["name"]); // anonymous
			array_push($csvrow, $resultset["firstname"]);
			
			array_push($csvrow, $resultset["login"]); // #10579
			if ($this->object->canExportSurveyCode())
			{
				array_push($csvrow, $user_id);
			}
			/* #8211
			if ($this->object->getAnonymize() == ilObjSurvey::ANONYMIZE_OFF)
			{
				array_push($csvrow, $resultset["gender"]);
			}			
		    */
			$wt = $this->object->getWorkingtimeForParticipant($user_id);
			array_push($csvrow, $wt);
			
			$finished = $finished_data[$user_id];
			if((bool)$finished["finished"])
			{
				$dt = new ilDateTime($finished["finished_tstamp"], IL_CAL_UNIX);
				if($export_format == self::TYPE_XLS)
				{									
					array_push($csvrow, $dt);								
				}			
				else
				{
					array_push($csvrow, ilDatePresentation::formatDate($dt));
				}
			}
			else
			{
				array_push($csvrow, "-");
			}			
			
			foreach ($questions as $question_id => $question)
			{
				$question->addUserSpecificResultsData($csvrow, $resultset);
			}			
			
			array_push($csvfile, $csvrow);
		}
		
		// #11179
		$surveyname = $this->object->getTitle()." ".$this->lng->txt("svy_eval_user")." ".date("Y-m-d");
		$surveyname = preg_replace("/\s/", "_", trim($surveyname));
		$surveyname = ilUtil::getASCIIFilename($surveyname);
		
		switch ($export_format)
		{
			case self::TYPE_XLS:
				include_once "Services/Excel/classes/class.ilExcel.php";
				$excel = new ilExcel();
				$excel->addSheet($this->lng->txt("svy_eval_user"));
							
				// title row(s)
				$row = 1;
				$title_row = array_shift($csvfile);
				foreach($title_row as $col_idx => $title_col)
				{
					if(is_array($title_col))
					{						
						foreach ($title_col as $sub_title_idx => $title)
						{
							$excel->setCell($row+$sub_title_idx, $col_idx, $title);
							$row = max($row, $row+$sub_title_idx);
						}						
					}
					else
					{
						$excel->setCell($row, $col_idx, $title_col);
					}
				}
				$excel->setBold("A1:".$excel->getColumnCoord(sizeof($title_row)-1)."1");
		
				foreach($csvfile as $csvrow)
				{	
					$row++;
					foreach ($csvrow as $col_idx => $text)
					{												
						$excel->setCell($row, $col_idx, $text);							
					}					
				}
				
				$excel->sendToClient($surveyname);				
				break;
				
			case self::TYPE_SPSS:
				$csv = "";
				$separator = ";";				
				foreach ($csvfile as $idx => $csvrow)
				{					
					$csvrow =& str_replace("\n", " ", $this->processCSVRow($csvrow, TRUE, $separator));					
					$csv .= join($csvrow, $separator) . "\n";
				}
				ilUtil::deliverData($csv, "$surveyname.csv");
				exit();
				break;
		}
	}
	
	
	/**
	* Processes an array as a CSV row and converts the array values to correct CSV
	* values. The "converted" array is returned
	*
	* @param array $row The array containing the values for a CSV row
	* @param string $quoteAll Indicates to quote every value (=TRUE) or only values containing quotes and separators (=FALSE, default)
	* @param string $separator The value separator in the CSV row (used for quoting) (; = default)
	* @return array The converted array ready for CSV use
	* @access public
	*/
	function processCSVRow($row, $quoteAll = FALSE, $separator = ";")
	{
		$resultarray = array();
		foreach ($row as $rowindex => $entry)
		{
			if(is_array($entry))
			{
				$entry = implode("/", $entry);
			}			
			$surround = FALSE;
			if ($quoteAll)
			{
				$surround = TRUE;
			}
			if (strpos($entry, "\"") !== FALSE)
			{
				$entry = str_replace("\"", "\"\"", $entry);
				$surround = TRUE;
			}
			if (strpos($entry, $separator) !== FALSE)
			{
				$surround = TRUE;
			}
			// replace all CR LF with LF (for Excel for Windows compatibility
			$entry = str_replace(chr(13).chr(10), chr(10), $entry);
			if ($surround)
			{
				$resultarray[$rowindex] = utf8_decode("\"" . $entry . "\"");
			}
			else
			{
				$resultarray[$rowindex] = utf8_decode($entry);
			}
		}
		return $resultarray;
	}

	
	function exportEvaluationUser()
	{
		$finished_ids = null;
		if($this->object->get360Mode())
		{
			$appr_id = $_REQUEST["appr_id"];
			if(!$appr_id)
			{
				$this->ctrl->redirect($this, "evaluationuser");
			}			
			$finished_ids = $this->object->getFinishedIdsForAppraiseeId($appr_id);	
			if(!sizeof($finished_ids))
			{
				$finished_ids = array(-1);
			}
		}
		
		return $this->exportUserSpecificResults($_POST["export_format"], $_POST["export_label"], $finished_ids);						
	}
	
	/**
	* Print the survey evaluation for a selected user
	*
	* Print the survey evaluation for a selected user
	*
	* @access private
	*/
	function evaluationuser()
	{
		global $ilAccess, $ilToolbar;
		
		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			ilUtil::sendFailure($this->lng->txt("no_permission"), TRUE);
			$this->ctrl->redirectByClass("ilObjSurveyGUI", "infoScreen");
		}
		
		include_once "Services/Form/classes/class.ilPropertyFormGUI.php";
		$ilToolbar->setFormAction($this->ctrl->getFormAction($this, "evaluationuser"));
		
		if($this->object->get360Mode())
		{				
			$appr_id = $this->getAppraiseeId();
			$this->addApprSelectionToToolbar();
		}

		$tabledata = null;
		if(!$this->object->get360Mode() || $appr_id)
		{
			$format = new ilSelectInputGUI($this->lng->txt("svy_export_format"), "export_format");
			$format->setOptions(array(
				self::TYPE_XLS => $this->lng->txt('exp_type_excel'),
				self::TYPE_SPSS => $this->lng->txt('exp_type_csv')
				));
			$ilToolbar->addInputItem($format, true);

			$label = new ilSelectInputGUI("", "export_label");
			$label->setOptions(array(
				'label_only' => $this->lng->txt('export_label_only'), 
				'title_only' => $this->lng->txt('export_title_only'), 
				'title_label'=> $this->lng->txt('export_title_label')
				));
			$ilToolbar->addInputItem($label);
			
			include_once "Services/UIComponent/Button/classes/class.ilSubmitButton.php";
			$button = ilSubmitButton::getInstance();
			$button->setCaption("export");
			$button->setCommand('exportevaluationuser');
			$button->setOmitPreventDoubleSubmission(true);
			$ilToolbar->addButtonInstance($button);		

			$ilToolbar->addSeparator();

			include_once "Services/UIComponent/Button/classes/class.ilLinkButton.php";
			$button = ilLinkButton::getInstance();
			$button->setCaption("print");
			$button->setOnClick("window.print(); return false;");
			$button->setOmitPreventDoubleSubmission(true);
			$ilToolbar->addButtonInstance($button);		
			
			$finished_ids = null;
			if($appr_id)
			{
				$finished_ids = $this->object->getFinishedIdsForAppraiseeId($appr_id);	
				if(!sizeof($finished_ids))
				{
					$finished_ids = array(-1);
				}
			}

			$userResults =& $this->object->getUserSpecificResults($finished_ids);	
			$questions =& $this->object->getSurveyQuestions(true);
			$participants =& $this->object->getSurveyParticipants($finished_ids);
			$tabledata = array();	
			$counter = -1;
			foreach ($participants as $data)
			{				
				$questioncounter = 1;
				$question = "";
				$results = "";
				$first = true;
				foreach ($questions as $question_id => $question_data)
				{
					$found = $userResults[$question_id][$data["active_id"]];
					$text = "";
					if (is_array($found))
					{
						$text = implode("<br />", $found);
					}
					else
					{
						$text = $found;
					}
					if (strlen($text) == 0) $text = ilObjSurvey::getSurveySkippedValue();
					$wt = $this->object->getWorkingtimeForParticipant($data['active_id']);
					if ($first)
					{
						if($data["finished"])
						{
							$finished =  $data["finished_tstamp"];
						}	
						else
						{
							$finished = false;
						}
						$tabledata[++$counter] = array(
								'username' => $data["sortname"],
								// 'gender' => $data["gender"],
								'question' => $questioncounter++ . ". " . $question_data["title"],
								'results' => $text,
								'workingtime' => $wt,
								'finished' => $finished
							);
						$first = false;						
					}
					else
					{
						$tabledata[$counter]["subitems"][] = array(
								'username' => " ",
								// 'gender' => " ",
								'question' => $questioncounter++ . ". " . $question_data["title"],
								'results' => $text,
								'workingtime' => null,
								'finished' => null
							);
					}
				}
			}
		}
		
		$this->tpl->addCss("./Modules/Survey/templates/default/survey_print.css", "print");
		$this->tpl->setCurrentBlock("generic_css");
		$this->tpl->setVariable("LOCATION_GENERIC_STYLESHEET", "./Modules/Survey/templates/default/evaluation_print.css");
		$this->tpl->setVariable("MEDIA_GENERIC_STYLESHEET", "print");
		$this->tpl->parseCurrentBlock();				
		
		include_once "./Modules/Survey/classes/tables/class.ilSurveyResultsUserTableGUI.php";
		$table_gui = new ilSurveyResultsUserTableGUI($this, 'evaluationuser', $this->object->hasAnonymizedResults());
		$table_gui->setData($tabledata);
		$this->tpl->setContent($table_gui->getHTML());			
	}
	
	/**
	 * Competence Evaluation
	 *
	 * @param
	 * @return
	 */
	function competenceEval()
	{
		global $ilUser, $lng, $ilCtrl, $ilToolbar, $tpl, $ilTabs;
		
		$survey = $this->object;
		
		$ilTabs->activateSubtab("svy_eval_competences");
		$ilTabs->activateTab("svy_results");

		$ilToolbar->setFormAction($this->ctrl->getFormAction($this, "competenceEval"));
		
		if($this->object->get360Mode())
		{				
			$appr_id = $this->getAppraiseeId();
			$this->addApprSelectionToToolbar();
		}
		
		if ($appr_id == 0)
		{
			return;
		}
		
		// evaluation modes
		$eval_modes = array();
		
		// get all competences of survey
		include_once("./Modules/Survey/classes/class.ilSurveySkill.php");
		$sskill = new ilSurveySkill($survey);
		$opts = $sskill->getAllAssignedSkillsAsOptions();
		$skills = array();
		foreach ($opts as $id => $o)
		{
			$idarr = explode(":", $id);
			$skills[$id] = array("id" => $id, "title" => $o, "profiles" => array(),
				"base_skill" => $idarr[0], "tref_id" => $idarr[1]);
		}
//var_dump($opts);
		
		// get matching user competence profiles
		// -> add gap analysis to profile
		include_once("./Services/Skill/classes/class.ilSkillProfile.php");
		$profiles = ilSkillProfile::getProfilesOfUser($appr_id);
		foreach ($profiles as $p)
		{
			$prof = new ilSkillProfile($p["id"]);
			$prof_levels = $prof->getSkillLevels();
			foreach ($prof_levels as $pl)
			{
				if (isset($skills[$pl["base_skill_id"].":".$pl["tref_id"]]))
				{
					$skills[$pl["base_skill_id"].":".$pl["tref_id"]]["profiles"][] =
						$p["id"];

					$eval_modes["gap_".$p["id"]] =
						$lng->txt("svy_gap_analysis").": ".$prof->getTitle();
				}
			}
		}
//var_dump($skills);
//var_dump($eval_modes);

		// if one competence does not match any profiles
		// -> add "competences of survey" alternative
		reset($skills);
		foreach ($skills as $sk)
		{
			if (count($sk["profiles"]) == 0)
			{
				$eval_modes["skills_of_survey"] = $lng->txt("svy_all_survey_competences");
			}
		}
		
		// final determination of current evaluation mode
		$comp_eval_mode = $_GET["comp_eval_mode"];
		if ($_POST["comp_eval_mode"] != "")
		{
			$comp_eval_mode = $_POST["comp_eval_mode"];
		}
		
		if (!isset($eval_modes[$comp_eval_mode]))
		{
			reset($eval_modes);
			$comp_eval_mode = key($eval_modes);
			$ilCtrl->setParameter($this, "comp_eval_mode", $comp_eval_mode);
		}
		
		$ilCtrl->saveParameter($this, "comp_eval_mode");
		
		include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
		$mode_sel = new ilSelectInputGUI($lng->txt("svy_analysis"), "comp_eval_mode");
		$mode_sel->setOptions($eval_modes);
		$mode_sel->setValue($comp_eval_mode);
		$ilToolbar->addInputItem($mode_sel, true);
		
		$ilToolbar->addFormButton($lng->txt("select"), "competenceEval");

		if (substr($comp_eval_mode, 0, 4) == "gap_")
		{
			// gap analysis
			$profile_id = (int) substr($comp_eval_mode, 4);
			
			include_once("./Services/Skill/classes/class.ilPersonalSkillsGUI.php");
			$pskills_gui = new ilPersonalSkillsGUI();
			$pskills_gui->setProfileId($profile_id);
			$pskills_gui->setGapAnalysisActualStatusModePerObject($survey->getId(), $lng->txt("survey_360_raters"));
			if ($survey->getFinishedIdForAppraiseeIdAndRaterId($appr_id, $appr_id) > 0)
			{
				$sskill = new ilSurveySkill($survey);
				$self_levels = array();
				foreach ($sskill->determineSkillLevelsForAppraisee($appr_id, true) as $sl)
				{
					$self_levels[$sl["base_skill_id"]][$sl["tref_id"]] = $sl["new_level_id"];
				}
				$pskills_gui->setGapAnalysisSelfEvalLevels($self_levels);
			}
			$html = $pskills_gui->getGapAnalysisHTML($appr_id);
			
			$tpl->setContent($html);
		}
		else // must be all survey competences
		{
			include_once("./Services/Skill/classes/class.ilPersonalSkillsGUI.php");
			$pskills_gui = new ilPersonalSkillsGUI();
			$pskills_gui->setGapAnalysisActualStatusModePerObject($survey->getId(), $lng->txt("survey_360_raters"));
			if ($survey->getFinishedIdForAppraiseeIdAndRaterId($appr_id, $appr_id) > 0)
			{
				$sskill = new ilSurveySkill($survey);
				$self_levels = array();
				foreach ($sskill->determineSkillLevelsForAppraisee($appr_id, true) as $sl)
				{
					$self_levels[$sl["base_skill_id"]][$sl["tref_id"]] = $sl["new_level_id"];
				}
				$pskills_gui->setGapAnalysisSelfEvalLevels($self_levels);
			}
			$sk = array();
			foreach ($skills as $skill)
			{
				$sk[] = array(
					"base_skill_id" => (int) $skill["base_skill"],
					"tref_id" => (int) $skill["tref_id"]
					);
			}
			$html = $pskills_gui->getGapAnalysisHTML($appr_id, $sk);

			$tpl->setContent($html);
		}
		
	}
}

?>