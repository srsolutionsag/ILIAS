<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Form/classes/class.ilIdentifiedMultiValuesInputGUI.php';
require_once 'Services/UIComponent/Glyph/classes/class.ilGlyphGUI.php';

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Modules/Test(QuestionPool)
 */
abstract class ilMultipleImagesInputGUI extends ilIdentifiedMultiValuesInputGUI
{
	/**
	 * @var bool
	 */
	protected $editElementOccuranceEnabled = false;
	
	/**
	 * @var bool
	 */
	protected $editElementOrderEnabled = false;
	
	/**
	 * @var array
	 */
	protected $suffixes = array();
	
	protected $imageRemovalCommand = 'removeImage';
	
	protected $imageUploadCommand = 'uploadImage';
	
	/**
	 * Constructor
	 *
	 * @param	string	$a_title	Title
	 * @param	string	$a_postvar	Post Variable
	 */
	function __construct($a_title = "", $a_postvar = "")
	{
		parent::__construct($a_title, $a_postvar);
		
		$this->setSuffixes(array("jpg", "jpeg", "png", "gif"));
		$this->setSize('25');
		$this->validationRegexp = "";

		require_once 'Services/Form/classes/class.ilMultipleImagesAdditionalIndexLevelRemover.php';
		$manipulator = new ilMultipleImagesAdditionalIndexLevelRemover();
		$this->addFormValuesManipulator($manipulator);
		
		require_once 'Services/Form/classes/class.ilIdentifiedMultiFilesJsPositionIndexRemover.php';
		$manipulator = new ilIdentifiedMultiFilesJsPositionIndexRemover();
		$manipulator->setPostVar($this->getPostVar());
		$this->addFormValuesManipulator($manipulator);
		
		require_once 'Services/Form/classes/class.ilMultiFilesSubmitRecursiveSlashesStripper.php';
		$manipulator = new ilMultiFilesSubmitRecursiveSlashesStripper();
		$manipulator->setPostVar($this->getPostVar());
		$this->addFormValuesManipulator($manipulator);
	}
	
	/**
	 * Set Accepted Suffixes.
	 *
	 * @param	array	$a_suffixes	Accepted Suffixes
	 */
	function setSuffixes($a_suffixes)
	{
		$this->suffixes = $a_suffixes;
	}
	
	/**
	 * Get Accepted Suffixes.
	 *
	 * @return	array	Accepted Suffixes
	 */
	function getSuffixes()
	{
		return $this->suffixes;
	}
	
	/**
	 * @return string
	 */
	public function getImageRemovalCommand()
	{
		return $this->imageRemovalCommand;
	}
	
	/**
	 * @param string $imageRemovalCommand
	 */
	public function setImageRemovalCommand($imageRemovalCommand)
	{
		$this->imageRemovalCommand = $imageRemovalCommand;
	}
	
	/**
	 * @return string
	 */
	public function getImageUploadCommand()
	{
		return $this->imageUploadCommand;
	}
	
	/**
	 * @param string $imageUploadCommand
	 */
	public function setImageUploadCommand($imageUploadCommand)
	{
		$this->imageUploadCommand = $imageUploadCommand;
	}
	
	/**
	 * @return	boolean $editElementOccuranceEnabled
	 */
	public function isEditElementOccuranceEnabled()
	{
		return $this->editElementOccuranceEnabled;
	}
	
	/**
	 * @param	boolean	$editElementOccuranceEnabled
	 */
	public function setEditElementOccuranceEnabled($editElementOccuranceEnabled)
	{
		$this->editElementOccuranceEnabled = $editElementOccuranceEnabled;
	}
	
	/**
	 * @return boolean
	 */
	public function isEditElementOrderEnabled()
	{
		return $this->editElementOrderEnabled;
	}
	
	/**
	 * @param boolean $editElementOrderEnabled
	 */
	public function setEditElementOrderEnabled($editElementOrderEnabled)
	{
		$this->editElementOrderEnabled = $editElementOrderEnabled;
	}
	
	/**
	 * @param mixed $value
	 * @return bool
	 */
	abstract protected function isValidFilenameInput($filenameInput);
	
	/**
	 * Check input, strip slashes etc. set alert, if input is not ok.
	 *
	 * @return	boolean	$validationSuccess
	 */
	function onCheckInput()
	{
		global $lng;
		if (is_array($_FILES[$this->getPostVar()]['error']['image']))
		{
			foreach ($_FILES[$this->getPostVar()]['error']['image'] as $index => $error)
			{
				// error handling
				if ($error > 0)
				{
					switch ($error)
					{
						case UPLOAD_ERR_INI_SIZE:
							$this->setAlert($lng->txt("form_msg_file_size_exceeds"));
							return false;
							break;
						
						case UPLOAD_ERR_FORM_SIZE:
							$this->setAlert($lng->txt("form_msg_file_size_exceeds"));
							return false;
							break;
						
						case UPLOAD_ERR_PARTIAL:
							$this->setAlert($lng->txt("form_msg_file_partially_uploaded"));
							return false;
							break;
						
						case UPLOAD_ERR_NO_FILE:
							$filenameInput = $_POST[$this->getPostVar()][$index];
							if( $this->getRequired() && !$this->isValidFilenameInput($filenameInput) )
							{
								$this->setAlert($lng->txt("form_msg_file_no_upload"));
								return false;
							}
							break;
						
						case UPLOAD_ERR_NO_TMP_DIR:
							$this->setAlert($lng->txt("form_msg_file_missing_tmp_dir"));
							return false;
							break;
						
						case UPLOAD_ERR_CANT_WRITE:
							$this->setAlert($lng->txt("form_msg_file_cannot_write_to_disk"));
							return false;
							break;
						
						case UPLOAD_ERR_EXTENSION:
							$this->setAlert($lng->txt("form_msg_file_upload_stopped_ext"));
							return false;
							break;
					}
				}
			}
		}
		else
		{
			if ($this->getRequired())
			{
				$this->setAlert($lng->txt("form_msg_file_no_upload"));
				return false;
			}
		}
		
		if (is_array($_FILES[$this->getPostVar()]['tmp_name']['image']))
		{
			foreach ($_FILES[$this->getPostVar()]['tmp_name']['image'] as $index => $tmpname)
			{
				$filename = $_FILES[$this->getPostVar()]['name']['image'][$index];
				$filename_arr = pathinfo($filename);
				$suffix = $filename_arr["extension"];
				$mimetype = $_FILES[$this->getPostVar()]['type']['image'][$index];
				$size_bytes = $_FILES[$this->getPostVar()]['size']['image'][$index];
				// check suffixes
				if (strlen($tmpname) && is_array($this->getSuffixes()))
				{
					if (!in_array(strtolower($suffix), $this->getSuffixes()))
					{
						$this->setAlert($lng->txt("form_msg_file_wrong_file_type"));
						return false;
					}
				}
			}
		}
		
		if (is_array($_FILES[$this->getPostVar()]['tmp_name']['image']))
		{
			foreach ($_FILES[$this->getPostVar()]['tmp_name']['image'] as $index => $tmpname)
			{
				$filename = $_FILES[$this->getPostVar()]['name']['image'][$index];
				$filename_arr = pathinfo($filename);
				$suffix = $filename_arr["extension"];
				$mimetype = $_FILES[$this->getPostVar()]['type']['image'][$index];
				$size_bytes = $_FILES[$this->getPostVar()]['size']['image'][$index];
				// virus handling
				if (strlen($tmpname))
				{
					$vir = ilUtil::virusHandling($tmpname, $filename);
					if ($vir[0] == false)
					{
						$this->setAlert($lng->txt("form_msg_file_virus_found")."<br />".$vir[1]);
						return false;
					}
				}
			}
		}
		
		return $this->checkSubItemsInput();
	}
	
	/**
	 * @return string
	 */
	public function render()
	{
		global $lng;
		
		$tpl = new ilTemplate("tpl.prop_multi_image_inp.html", true, true, "Services/Form");
		$i = 0;
		foreach ($this->getMultiValues() as $identifier => $value)
		{
			if( $this->valueHasContentImageSource($value) )
			{
				$tpl->setCurrentBlock('image');
				$tpl->setVariable('SRC_IMAGE', $this->fetchContentImageSourceFromValue($value));
				$tpl->setVariable('IMAGE_NAME', $this->fetchContentImageTitleFromValue($value));
				$tpl->setVariable('ALT_IMAGE', ilUtil::prepareFormOutput($this->fetchContentImageTitleFromValue($value)));
				$tpl->setVariable("IMAGE_CMD_REMOVE", $this->buildMultiValueSubmitVar($identifier, $i, $this->getImageRemovalCommand()));
				$tpl->setVariable("TXT_DELETE_EXISTING", $lng->txt("delete_existing_file"));
				$tpl->setVariable("IMAGE_POST_VAR", $this->getMultiValuePostVarSubFieldPosIndexed($identifier, 'imagename', $i));
				$tpl->parseCurrentBlock();
			}
			$tpl->setCurrentBlock('addimage');
			$tpl->setVariable("IMAGE_BROWSE", $lng->txt('select_file'));
			$tpl->setVariable("IMAGE_ID", $this->getMultiValuePosIndexedSubFieldId($identifier, 'image', $i));
			$tpl->setVariable("IMAGE_SUBMIT", $lng->txt("upload"));
			$tpl->setVariable("IMAGE_CMD_UPLOAD", $this->buildMultiValueSubmitVar($identifier, $i, $this->getImageUploadCommand()));
			$tpl->setVariable("IMAGE_POST_VAR", $this->getMultiValuePostVarSubFieldPosIndexed($identifier, 'image', $i));
			$tpl->setVariable("COUNT_POST_VAR", $this->getMultiValuePostVarSubFieldPosIndexed($identifier, 'count', $i));
			
			$tpl->parseCurrentBlock();
			
			if( $this->isEditElementOrderEnabled() )
			{
				$tpl->setCurrentBlock("move");
				$tpl->setVariable("CMD_UP", $this->buildMultiValueSubmitVar($identifier, $i, 'up'));
				$tpl->setVariable("CMD_DOWN", $this->buildMultiValueSubmitVar($identifier, $i, 'down'));
				$tpl->setVariable("ID", $this->getPostVar() . "[$i]");
				$tpl->setVariable("UP_BUTTON", ilGlyphGUI::get(ilGlyphGUI::UP));
				$tpl->setVariable("DOWN_BUTTON", ilGlyphGUI::get(ilGlyphGUI::DOWN));
				$tpl->parseCurrentBlock();
			}
			
			if( $this->isEditElementOccuranceEnabled() )
			{
				$tpl->setCurrentBlock("row");
				$tpl->setVariable("CMD_ADD", $this->buildMultiValueSubmitVar($identifier, $i, 'add'));
				$tpl->setVariable("CMD_REMOVE", $this->buildMultiValueSubmitVar($identifier, $i, 'remove'));
				$tpl->setVariable("ADD_BUTTON", ilGlyphGUI::get(ilGlyphGUI::ADD));
				$tpl->setVariable("REMOVE_BUTTON", ilGlyphGUI::get(ilGlyphGUI::REMOVE));
				$tpl->parseCurrentBlock();
			}
			
			$i++;
		}
		
		if (is_array($this->getSuffixes()))
		{
			$suff_str = $delim = "";
			foreach($this->getSuffixes() as $suffix)
			{
				$suff_str.= $delim.".".$suffix;
				$delim = ", ";
			}
			$tpl->setCurrentBlock('allowed_image_suffixes');
			$tpl->setVariable("TXT_ALLOWED_SUFFIXES", $lng->txt("file_allowed_suffixes")." ".$suff_str);
			$tpl->parseCurrentBlock();
		}
		/*
		$tpl->setCurrentBlock("image_heading");
		$tpl->setVariable("ANSWER_IMAGE", $lng->txt('answer_image'));
		$tpl->parseCurrentBlock();
		*/
		
		$tpl->setVariable("TXT_MAX_SIZE", ilUtil::getFileSizeInfo());
		$tpl->setVariable("ELEMENT_ID", $this->getPostVar());
		$tpl->setVariable("TEXT_YES", $lng->txt('yes'));
		$tpl->setVariable("TEXT_NO", $lng->txt('no'));
		$tpl->setVariable("DELETE_IMAGE_HEADER", $lng->txt('delete_image_header'));
		$tpl->setVariable("DELETE_IMAGE_QUESTION", $lng->txt('delete_image_question'));
		$tpl->setVariable("ANSWER_TEXT", $lng->txt('answer_text'));
		$tpl->setVariable("COMMANDS_TEXT", $lng->txt('actions'));
		
		if (!$this->getDisabled())
		{
			$globalTpl = $GLOBALS['DIC'] ? $GLOBALS['DIC']['tpl'] : $GLOBALS['tpl'];
			$globalTpl->addJavascript("./Services/Form/js/ServiceFormWizardInput.js");
			$globalTpl->addJavascript("./Services/Form/js/ServiceFormIdentifiedWizardInputExtend.js");
			$globalTpl->addJavascript("./Services/Form/js/ServiceFormIdentifiedImageWizardInputConcrete.js");
		}
		
		return $tpl->get();
	}
	
	/**
	 * @param $value
	 * @return bool
	 */
	protected function valueHasContentImageSource($value)
	{
		return isset($value['src']);
	}
	
	/**
	 * @param $value
	 * @return string
	 */
	protected function fetchContentImageSourceFromValue($value)
	{
		if( $this->valueHasContentImageSource($value) )
		{
			return $value['src'];
		}
		
		return null;
	}
	
	/**
	 * @param $value
	 * @return bool
	 */
	protected function valueHasContentImageTitle($value)
	{
		return isset($value['title']);
	}
	
	/**
	 * @param string $value
	 */
	protected function fetchContentImageTitleFromValue($value)
	{
		if( $this->valueHasContentImageTitle($value) )
		{
			return $value['title'];
		}
		
		return $this->fetchContentImageSourceFromValue($value);
	}
}