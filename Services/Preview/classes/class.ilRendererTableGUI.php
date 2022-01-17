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
 * Displays an overview of all loaded preview renderers.
 *
 * @author Stefan Born <stefan.born@phzh.ch>
 * @version $Id$
 *
 * @ingroup ServicesPreview
 */
class ilRendererTableGUI extends ilTable2GUI
{
    public function __construct(ilObjFileAccessSettingsGUI $a_parent_obj, string $a_parent_cmd)
    {
        global $DIC;
        $ilCtrl = $DIC['ilCtrl'];
        $lng = $DIC['lng'];
        
        parent::__construct($a_parent_obj, $a_parent_cmd);
        
        // general properties
        $this->setRowTemplate("tpl.renderer_row.html", "Services/Preview");
        $this->setLimit(9999);
        $this->setEnableHeader(true);
        $this->disable("footer");
        $this->setExternalSorting(true);
        $this->setEnableTitle(true);
        $this->setTitle($lng->txt("loaded_preview_renderers"));
        
        $this->addColumn($lng->txt("name"));
        $this->addColumn($lng->txt("type"));
        $this->addColumn($lng->txt("renderer_supported_repo_types"));
        $this->addColumn($lng->txt("renderer_supported_file_types"));
    }

    /**
     * Standard Version of Fill Row. Most likely to
     * be overwritten by derived class.
     */
    protected function fillRow(array $a_set) : void
    {
        global $DIC;
        $lng = $DIC['lng'];
        $ilCtrl = $DIC['ilCtrl'];
        $ilAccess = $DIC['ilAccess'];
        
        $name = $a_set->getName();
        $type = $lng->txt("renderer_type_" . ($a_set->isPlugin() ? "plugin" : "builtin"));
        
        $repo_types = array();
        foreach ($a_set->getSupportedRepositoryTypes() as $repo_type) {
            $repo_types[] = $lng->txt($repo_type);
        }
        
        // supports files?
        $file_types = "";
        if ($a_set instanceof ilFilePreviewRenderer) {
            $file_types = implode(", ", $a_set->getSupportedFileFormats());
        }
        
        // fill template
        $this->tpl->setVariable("TXT_NAME", $name);
        $this->tpl->setVariable("TXT_TYPE", $type);
        $this->tpl->setVariable("TXT_REPO_TYPES", implode(", ", $repo_types));
        $this->tpl->setVariable("TXT_FILE_TYPES", $file_types);
    }
}
