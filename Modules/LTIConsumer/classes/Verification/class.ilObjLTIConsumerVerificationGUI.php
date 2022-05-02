<?php declare(strict_types=1);

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
 * Class ilObjLTIConsumerVerificationGUI
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 *
 * @package     Module/LTIConsumer
 */
class ilObjLTIConsumerVerificationGUI extends ilObject2GUI
{
    public function getType() : string
    {
        return "ltiv";
    }
    
    /**
     * List all tests in which current user participated
     */
    public function create() : void
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $this->lng->loadLanguageModule("ltiv");
        
        $DIC->tabs()->setBackTarget(
            $this->lng->txt("back"),
            $this->ctrl->getLinkTarget($this, "cancel")
        );
        $table = new ilLTIConsumerVerificationTableGUI($this, "create");
        $this->tpl->setContent($table->getHTML());
    }
    
    /**
     * create new instance and save it
     */
    public function save() : void
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $objId = $this->getRequestValue("lti_id");

        if ($objId) {
            $certificateVerificationFileService = new ilCertificateVerificationFileService(
                $DIC->language(),
                $DIC->database(),
                $DIC->logger()->root(),
                new ilCertificateVerificationClassMap()
            );

            $userCertificateRepository = new ilUserCertificateRepository();

            $userCertificatePresentation = $userCertificateRepository->fetchActiveCertificateForPresentation(
                $DIC->user()->getId(),
                (int) $objId
            );

            try {
                $newObj = $certificateVerificationFileService->createFile($userCertificatePresentation);
            } catch (\Exception $exception) {
                $this->tpl->setOnScreenMessage('failure', $this->lng->txt('error_creating_certificate_pdf'));
                $this->create();
                return;
            }

            if ($newObj !== null) {
                $parent_id = $this->node_id;
                $this->node_id = null;
                $this->putObjectInTree($newObj, $parent_id);
                
                $this->afterSave($newObj);
            } else {
                $this->tpl->setOnScreenMessage('failure', $this->lng->txt("msg_failed"));
            }
        } else {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt("select_one"));
        }
        
        $this->create();
    }
    
    public function deliver() : void
    {
        $file = $this->object->getFilePath();
        
        if ($file) {
            ilFileDelivery::deliverFileLegacy($file, $this->object->getTitle() . ".pdf");
        }
    }

    /**
     * Render content
     */
    public function render(bool $a_return = false, bool $a_url = false) : string
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        $message = '';

        if (!$a_return) {
            $this->deliver();
        } else {
            $tree = new ilWorkspaceTree($DIC->user()->getId());
            $wsp_id = $tree->lookupNodeId($this->object->getId());
            
            $caption = $DIC->language()->txt("wsp_type_ltiv") . ' "' . $this->object->getTitle() . '"';
            
            $valid = true;
            if (!file_exists($this->object->getFilePath())) {
                $valid = false;
                $message = $DIC->language()->txt("url_not_found");
            } elseif (!$a_url) {
                $access_handler = new ilWorkspaceAccessHandler($tree);
                if (!$access_handler->checkAccess("read", "", $wsp_id)) {
                    $valid = false;
                    $message = $DIC->language()->txt("permission_denied");
                }
            }
            
            if ($valid) {
                if (!$a_url) {
                    $a_url = $this->getAccessHandler()->getGotoLink($wsp_id, $this->object->getId());
                }
                return '<div><a href="' . $a_url . '">' . $caption . '</a></div>';
            } else {
                return '<div>' . $caption . ' (' . $message . ')</div>';
            }
        }
        return '';
    }
    
    public function downloadFromPortfolioPage(ilPortfolioPage $a_page) : void
    {
        global $DIC;
        if (ilPCVerification::isInPortfolioPage($a_page, $this->object->getType(), $this->object->getId())) {
            $this->deliver();
        }
        
        $DIC['ilErr']->raiseError($this->lng->txt('permission_denied'), $DIC['ilErr']->MESSAGE);
    }
    
    public static function _goto($a_target) : void
    {
        global $DIC;
        $ctrl = $DIC->ctrl();
        $id = explode("_", $a_target);

        $ctrl->setParameterByClass(
            "ilsharedresourceGUI",
            "wsp_id",
            $id[0]
        );
        $ctrl->redirectByClass(ilSharedResourceGUI::class);
    }

    /**
     * @param mixed  $default
     * @return mixed|null
     */
    protected function getRequestValue(string $key, $default = null)
    {
        if (isset($this->request->getQueryParams()[$key])) {
            return $this->request->getQueryParams()[$key];
        }

        if (isset($this->request->getParsedBody()[$key])) {
            return $this->request->getParsedBody()[$key];
        }

        return $default ?? null;
    }
}
