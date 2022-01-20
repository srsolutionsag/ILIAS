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
 * File System Explorer GUI class
 * @deprecated
 */
class ilFileSystemGUI
{
    const PARAMETER_CDIR = "cdir";
    const SESSION_LAST_COMMAND = "fsys_lastcomm";

    protected ilCtrl $ctrl;
    protected bool $use_upload_directory = false;
    protected array $allowed_suffixes = array();
    protected array $forbidden_suffixes = array();
    protected ilLanguage $lng;
    protected string $main_dir;
    protected bool $post_dir_path = false;
    protected ilGlobalTemplateInterface $tpl;
    protected array $file_labels = [];
    protected bool $label_enable = false;
    protected bool $allow_directories = true;
    protected string $table_id = '';
    protected string $title = '';
    protected array $commands = [];
    protected string $label_header = '';
    protected bool $directory_creation = false;
    protected bool $file_creation = false;

    public function __construct(string $a_main_directory)
    {
        global $DIC;
        $lng = $DIC['lng'];
        $ilCtrl = $DIC['ilCtrl'];
        $tpl = $DIC['tpl'];

        $this->ctrl = $ilCtrl;
        $this->lng = $lng;
        $this->tpl = $tpl;
        $this->main_dir = $a_main_directory;

        $this->defineCommands();

        $this->ctrl->saveParameter($this, self::PARAMETER_CDIR);
        $lng->loadLanguageModule("content");
        $this->setAllowDirectories(true);
        $this->setAllowDirectoryCreation(true);
        $this->setAllowFileCreation(true);
    }

    public function setAllowedSuffixes(array $a_suffixes) : void
    {
        $this->allowed_suffixes = $a_suffixes;
    }

    public function getAllowedSuffixes() : array
    {
        return $this->allowed_suffixes;
    }

    public function setForbiddenSuffixes(array $a_suffixes) : void
    {
        $this->forbidden_suffixes = $a_suffixes;
    }

    public function getForbiddenSuffixes() : array
    {
        return $this->forbidden_suffixes;
    }

    public function isValidSuffix(string $a_suffix) : bool
    {
        if (is_array($this->getForbiddenSuffixes()) && in_array($a_suffix, $this->getForbiddenSuffixes())) {
            return false;
        }
        if (is_array($this->getAllowedSuffixes()) && in_array($a_suffix, $this->getAllowedSuffixes())) {
            return true;
        }
        if (!is_array($this->getAllowedSuffixes()) || count($this->getAllowedSuffixes()) == 0) {
            return true;
        }
        return false;
    }

    public function setAllowDirectories(bool $a_val) : void
    {
        $this->allow_directories = $a_val;
    }

    public function getAllowDirectories() : bool
    {
        return $this->allow_directories;
    }

    public function setPostDirPath(bool $a_val) : void
    {
        $this->post_dir_path = $a_val;
    }

    public function getPostDirPath() : bool
    {
        return $this->post_dir_path;
    }

    public function setTableId(string $a_val) : void
    {
        $this->table_id = $a_val;
    }

    public function getTableId() : string
    {
        return $this->table_id;
    }

    public function setTitle(string $a_val) : void
    {
        $this->title = $a_val;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function setUseUploadDirectory(bool $a_val) : void
    {
        $this->use_upload_directory = $a_val;
    }

    public function getUseUploadDirectory() : bool
    {
        return $this->use_upload_directory;
    }

    /**
     * @param array|string $command
     * @param array        $pars
     * @return void
     */
    protected function setPerformedCommand($command, array $pars = []) : void
    {
        if (!is_array($pars)) {
            $pars = [];
        }
        ilSession::set(self::SESSION_LAST_COMMAND, array_merge(
            ["cmd" => $command],
            $pars
        ));
    }

    public function getLastPerformedCommand() : array
    {
        if (!ilSession::has(self::SESSION_LAST_COMMAND)) {
            return [];
        }
        $ret = ilSession::get(self::SESSION_LAST_COMMAND);
        ilSession::set(self::SESSION_LAST_COMMAND, null);
        return (array) $ret;
    }

    public function executeCommand() : string
    {
        $next_class = $this->ctrl->getNextClass($this);
        $cmd = $this->ctrl->getCmd("listFiles");
        if (substr($cmd, 0, 11) == "extCommand_") {
            $ret = $this->extCommand(substr($cmd, 11, strlen($cmd) - 11));
        } else {
            $ret = $this->$cmd();
        }

        return $ret ?? '';
    }

    public function addCommand(
        object $a_obj,
        string $a_func,
        string $a_name,
        bool $a_single = true,
        bool $a_allow_dir = false
    ) : void {
        $i = count($this->commands);

        $this->commands[$i]["object"] = $a_obj;
        $this->commands[$i]["method"] = $a_func;
        $this->commands[$i]["name"] = $a_name;
        $this->commands[$i]["single"] = $a_single;
        $this->commands[$i]["allow_dir"] = $a_allow_dir;
    }

    public function clearCommands() : void
    {
        $this->commands = [];
    }

    public function labelFile(string $a_file, string $a_label) : void
    {
        $this->file_labels[$a_file][] = $a_label;
    }

    public function activateLabels(bool $a_act, string $a_label_header) : void
    {
        $this->label_enable = $a_act;
        $this->label_header = $a_label_header;
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseCurrentDirectory() : array
    {
        // determine directory
        // FIXME: I have to call stripSlashes here twice, because I could not
        //        determine where the second layer of slashes is added to the
        //        URL Parameter
        $cur_subdir = ilUtil::stripSlashes(ilUtil::stripSlashes($_GET[self::PARAMETER_CDIR]));
        $new_subdir = ilUtil::stripSlashes(ilUtil::stripSlashes($_GET["newdir"]));

        if ($new_subdir === "..") {
            $cur_subdir = substr($cur_subdir, 0, strrpos($cur_subdir, "/"));
        } else {
            if (!empty($new_subdir)) {
                if (!empty($cur_subdir)) {
                    $cur_subdir = $cur_subdir . "/" . $new_subdir;
                } else {
                    $cur_subdir = $new_subdir;
                }
            }
        }

        $cur_subdir = str_replace("..", "", $cur_subdir);
        $cur_dir = (!empty($cur_subdir))
            ? $this->main_dir . "/" . $cur_subdir
            : $this->main_dir;

        return [
            "dir" => $cur_dir,
            "subdir" => $cur_subdir
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getFileList(string $a_dir, ?string $a_subdir = null) : array
    {
        $items = [];

        $entries = (is_dir($a_dir))
            ? ilUtil::getDir($a_dir)
            : array(array("type" => "dir", "entry" => ".."));

        $items = array();
        foreach ($entries as $e) {
            if (($e["entry"] == ".") ||
                ($e["entry"] == ".." && empty($a_subdir))) {
                continue;
            }

            $cfile = (!empty($a_subdir))
                ? $a_subdir . "/" . $e["entry"]
                : $e["entry"];

            $items[] = array(
                "file" => $cfile,
                "entry" => $e["entry"],
                "type" => $e["type"],
                "size" => $e["size"],
                "hash" => md5($e["entry"])
            );
        }

        return $items;
    }

    protected function getIncomingFiles() : array
    {
        $sel_files = $hashes = array();
        if (isset($_POST["file"])) {
            $hashes = $_POST["file"];
        } elseif (isset($_GET["fhsh"])) {
            $hashes = array($_GET["fhsh"]);
        }

        if (sizeof($hashes)) {
            $dir = $this->parseCurrentDirectory();
            $all_files = $this->getFileList($dir["dir"], $dir["subdir"]);
            foreach ($hashes as $hash) {
                foreach ($all_files as $file) {
                    if ($file["hash"] == $hash) {
                        $sel_files[] = $this->getPostDirPath()
                            ? $file["file"]
                            : $file["entry"];
                        break;
                    }
                }
            }
        }

        return $sel_files;
    }

    private function extCommand(int $a_nr) : string
    {
        $selected = $this->getIncomingFiles();

        if (!count($selected)) {
            ilUtil::sendFailure($this->lng->txt("no_checkbox"), true);
            $this->ctrl->redirect($this, "listFiles");
        }

        // check if only one item is select, if command does not allow multiple selection
        if (count($selected) > 1 && $this->commands[$a_nr]["single"]) {
            ilUtil::sendFailure($this->lng->txt("cont_select_max_one_item"), true);
            $this->ctrl->redirect($this, "listFiles");
        }

        $cur_subdir = $this->sanitizeCurrentDirectory();

        // collect files and
        $files = array();
        foreach ($selected as $file) {
            $file = ilUtil::stripSlashes($file);
            $file = (!empty($cur_subdir))
                ? $cur_subdir . "/" . $file
                : $file;

            // check wether selected item is a directory
            if (@is_dir($this->main_dir . "/" . $file) &&
                !$this->commands[$a_nr]["allow_dir"]) {
                ilUtil::sendFailure($this->lng->txt("select_a_file"), true);
                $this->ctrl->redirect($this, "listFiles");
            }

            $files[] = $file;
        }

        if ($this->commands[$a_nr]["single"]) {
            $files = array_shift($files);
        }

        $obj = $this->commands[$a_nr]["object"];
        $method = $this->commands[$a_nr]["method"];

        return (string) $obj->$method($files);
    }

    public function setAllowDirectoryCreation(bool $a_val) : void
    {
        $this->directory_creation = $a_val;
    }

    public function getAllowDirectoryCreation() : bool
    {
        return $this->directory_creation;
    }

    /**
     * Set allowed file creation
     */
    public function setAllowFileCreation(bool $a_val) : void
    {
        $this->file_creation = $a_val;
    }

    public function getAllowFileCreation() : bool
    {
        return $this->file_creation;
    }

    public function listFiles(?ilTable2GUI $a_table_gui = null) : void
    {
        global $DIC;
        $ilToolbar = $DIC['ilToolbar'];
        $lng = $DIC['lng'];
        $ilCtrl = $DIC['ilCtrl'];

        $dir = $this->parseCurrentDirectory();

        $this->ctrl->setParameter($this, self::PARAMETER_CDIR, $dir["subdir"]);

        // toolbar for adding files/directories
        $ilToolbar->setFormAction($ilCtrl->getFormAction($this), true);

        if ($this->getAllowDirectories() && $this->getAllowDirectoryCreation()) {
            $ti = new ilTextInputGUI($this->lng->txt("cont_new_dir"), "new_dir");
            $ti->setMaxLength(80);
            $ti->setSize(10);
            $ilToolbar->addInputItem($ti, true);
            $ilToolbar->addFormButton($lng->txt("create"), "createDirectory");

            $ilToolbar->addSeparator();
        }
        if ($this->getAllowFileCreation()) {
            $fi = new ilFileInputGUI($this->lng->txt("cont_new_file"), "new_file");
            $fi->setSize(10);
            $ilToolbar->addInputItem($fi, true);
            $ilToolbar->addFormButton($lng->txt("upload"), "uploadFile");
        }
        if (ilUploadFiles::_getUploadDirectory() && $this->getAllowFileCreation() && $this->getUseUploadDirectory()) {
            $ilToolbar->addSeparator();
            $files = ilUploadFiles::_getUploadFiles();
            $options[""] = $lng->txt("cont_select_from_upload_dir");
            foreach ($files as $file) {
                $file = htmlspecialchars($file, ENT_QUOTES, "utf-8");
                $options[$file] = $file;
            }
            $si = new ilSelectInputGUI($this->lng->txt("cont_uploaded_file"), "uploaded_file");
            $si->setOptions($options);
            $ilToolbar->addInputItem($si, true);
            $ilToolbar->addFormButton($lng->txt("copy"), "uploadFile");
        }

        $fs_table = $this->getTable($dir["dir"], $dir["subdir"]);

        if ($this->getTitle() != "") {
            $fs_table->setTitle($this->getTitle());
        }
        if ($_GET["resetoffset"] == 1) {
            $fs_table->resetOffset();
        }
        $this->tpl->setContent($fs_table->getHTML());
    }

    public function getTable(string $a_dir, string $a_subdir) : \ilFileSystemTableGUI
    {
        return new ilFileSystemTableGUI(
            $this,
            "listFiles",
            $a_dir,
            $a_subdir,
            $this->label_enable,
            $this->file_labels,
            $this->label_header,
            $this->commands,
            $this->getPostDirPath(),
            $this->getTableId()
        );
    }

    public function renameFileForm(string $a_file) : void
    {
        global $DIC;
        $lng = $DIC['lng'];
        $ilCtrl = $DIC['ilCtrl'];

        $cur_subdir = $this->sanitizeCurrentDirectory();
        $file = $this->main_dir . "/" . $a_file;

        $this->ctrl->setParameter($this, "old_name", basename($a_file));
        $this->ctrl->setParameter($this, self::PARAMETER_CDIR, ilUtil::stripSlashes($_GET[self::PARAMETER_CDIR]));
        $form = new ilPropertyFormGUI();

        // file/dir name
        $ti = new ilTextInputGUI($this->lng->txt("name"), "new_name");
        $ti->setMaxLength(200);
        $ti->setSize(40);
        $ti->setValue(basename($a_file));
        $form->addItem($ti);

        // save and cancel commands
        $form->addCommandButton("renameFile", $lng->txt("rename"));
        $form->addCommandButton("cancelRename", $lng->txt("cancel"));
        $form->setFormAction($ilCtrl->getFormAction($this, "renameFile"));

        if (@is_dir($file)) {
            $form->setTitle($this->lng->txt("cont_rename_dir"));
        } else {
            $form->setTitle($this->lng->txt("rename_file"));
        }

        $this->tpl->setContent($form->getHTML());
    }

    public function renameFile() : void
    {
        global $DIC;
        $lng = $DIC['lng'];

        $new_name = str_replace("..", "", ilUtil::stripSlashes($_POST["new_name"]));
        $new_name = str_replace("/", "", $new_name);
        if ($new_name === "") {
            throw new LogicException($this->lng->txt("enter_new_name"));
        }

        $pi = pathinfo($new_name);
        $suffix = $pi["extension"];
        if ($suffix != "" && !$this->isValidSuffix($suffix)) {
            ilUtil::sendFailure($this->lng->txt("file_no_valid_file_type") . " ($suffix)", true);
            $this->ctrl->redirect($this, "listFiles");
        }

        $cur_subdir = $this->sanitizeCurrentDirectory();
        $dir = (!empty($cur_subdir))
            ? $this->main_dir . "/" . $cur_subdir . "/"
            : $this->main_dir . "/";

        if (is_dir($dir . ilUtil::stripSlashes($_GET["old_name"]))) {
            rename($dir . ilUtil::stripSlashes($_GET["old_name"]), $dir . $new_name);
        } else {
            try {
                ilFileUtils::rename($dir . ilUtil::stripSlashes($_GET["old_name"]), $dir . $new_name);
            } catch (ilException $e) {
                ilUtil::sendFailure($e->getMessage(), true);
                $this->ctrl->redirect($this, "listFiles");
            }
        }

        ilUtil::renameExecutables($this->main_dir);
        if (@is_dir($dir . $new_name)) {
            ilUtil::sendSuccess($lng->txt("cont_dir_renamed"), true);
            $this->setPerformedCommand("rename_dir", ["old_name" => $_GET["old_name"],
                                                      "new_name" => $new_name
            ]);
        } else {
            ilUtil::sendSuccess($lng->txt("cont_file_renamed"), true);
            $this->setPerformedCommand("rename_file", array("old_name" => $_GET["old_name"],
                                                            "new_name" => $new_name
            ));
        }
        $this->ctrl->redirect($this, "listFiles");
    }

    public function cancelRename() : void
    {
        $this->ctrl->redirect($this, "listFiles");
    }

    public function createDirectory() : void
    {
        global $DIC;
        $lng = $DIC['lng'];

        // determine directory
        $cur_subdir = $this->sanitizeCurrentDirectory();
        $cur_dir = (!empty($cur_subdir))
            ? $this->main_dir . "/" . $cur_subdir
            : $this->main_dir;

        $new_dir = str_replace(".", "", ilUtil::stripSlashes($_POST["new_dir"]));
        $new_dir = str_replace("/", "", $new_dir);

        if (!empty($new_dir)) {
            ilUtil::makeDir($cur_dir . "/" . $new_dir);
            if (is_dir($cur_dir . "/" . $new_dir)) {
                ilUtil::sendSuccess($lng->txt("cont_dir_created"), true);
                $this->setPerformedCommand("create_dir", array("name" => $new_dir));
            }
        } else {
            ilUtil::sendFailure($lng->txt("cont_enter_a_dir_name"), true);
        }
        $this->ctrl->saveParameter($this, self::PARAMETER_CDIR);
        $this->ctrl->redirect($this, 'listFiles');
    }

    public function uploadFile() : void
    {
        global $DIC;
        $lng = $DIC['lng'];

        // determine directory
        $cur_subdir = $this->sanitizeCurrentDirectory();
        $cur_dir = (!empty($cur_subdir))
            ? $this->main_dir . "/" . $cur_subdir
            : $this->main_dir;

        $tgt_file = null;

        $pi = pathinfo($_FILES["new_file"]["name"]);
        $suffix = $pi["extension"];
        if (!$this->isValidSuffix($suffix)) {
            ilUtil::sendFailure($this->lng->txt("file_no_valid_file_type") . " ($suffix)", true);
            $this->ctrl->redirect($this, "listFiles");
        }

        if (is_file($_FILES["new_file"]["tmp_name"])) {
            $name = ilUtil::stripSlashes($_FILES["new_file"]["name"]);
            $tgt_file = $cur_dir . "/" . $name;

            ilUtil::moveUploadedFile($_FILES["new_file"]["tmp_name"], $name, $tgt_file);
        } elseif ($_POST["uploaded_file"]) {
            // check if the file is in the ftp directory and readable
            if (ilUploadFiles::_checkUploadFile($_POST["uploaded_file"])) {
                $tgt_file = $cur_dir . "/" . ilUtil::stripSlashes($_POST["uploaded_file"]);

                // copy uploaded file to data directory
                ilUploadFiles::_copyUploadFile($_POST["uploaded_file"], $tgt_file);
            }
        } elseif (trim($_FILES["new_file"]["name"]) == "") {
            ilUtil::sendFailure($lng->txt("cont_enter_a_file"), true);
        }

        if ($tgt_file && is_file($tgt_file)) {
            $unzip = null;
            if (ilMimeTypeUtil::getMimeType($tgt_file) == "application/zip") {
                $this->ctrl->setParameter($this, "upfile", basename($tgt_file));
                $url = $this->ctrl->getLinkTarget($this, "unzipFile");
                $this->ctrl->setParameter($this, "upfile", "");
                $unzip = ilLinkButton::getInstance();
                $unzip->setCaption("unzip");
                $unzip->setUrl($url);
                $unzip = " " . $unzip->render();
            }

            ilUtil::sendSuccess($lng->txt("cont_file_created") . $unzip, true);

            $this->setPerformedCommand(
                "create_file",
                array("name" => substr($tgt_file, strlen($this->main_dir) + 1))
            );
        }

        $this->ctrl->saveParameter($this, self::PARAMETER_CDIR);

        ilUtil::renameExecutables($this->main_dir);

        $this->ctrl->redirect($this, 'listFiles');
    }

    public function confirmDeleteFile(array $a_files) : void
    {
        global $DIC;
        $ilCtrl = $DIC['ilCtrl'];
        $tpl = $DIC['tpl'];
        $lng = $DIC['lng'];

        $cgui = new ilConfirmationGUI();
        $cgui->setFormAction($ilCtrl->getFormAction($this));
        $cgui->setHeaderText($lng->txt("info_delete_sure"));
        $cgui->setCancel($lng->txt("cancel"), "listFiles");
        $cgui->setConfirm($lng->txt("delete"), "deleteFile");

        foreach ($a_files as $i) {
            $cgui->addItem("file[]", $i, $i);
        }

        $tpl->setContent($cgui->getHTML());
    }

    public function deleteFile() : void
    {
        global $DIC;
        $lng = $DIC['lng'];

        if (!isset($_POST["file"])) {
            throw new LogicException($this->lng->txt("no_checkbox"));
        }

        foreach ($_POST["file"] as $post_file) {
            if (ilUtil::stripSlashes($post_file) == "..") {
                throw new LogicException($this->lng->txt("no_checkbox"));
                break;
            }

            $cur_subdir = $this->sanitizeCurrentDirectory();
            $cur_dir = (!empty($cur_subdir))
                ? $this->main_dir . "/" . $cur_subdir
                : $this->main_dir;
            $pi = pathinfo($post_file);
            $file = $cur_dir . "/" . ilUtil::stripSlashes($pi["basename"]);

            if (@is_file($file)) {
                unlink($file);
            }

            if (@is_dir($file)) {
                $is_dir = true;
                ilUtil::delDir($file);
            }
        }

        $this->ctrl->saveParameter($this, self::PARAMETER_CDIR);
        if ($is_dir) {
            ilUtil::sendSuccess($lng->txt("cont_dir_deleted"), true);
            $this->setPerformedCommand(
                "delete_dir",
                array("name" => ilUtil::stripSlashes($post_file))
            );
        } else {
            ilUtil::sendSuccess($lng->txt("cont_file_deleted"), true);
            $this->setPerformedCommand(
                "delete_file",
                array("name" => ilUtil::stripSlashes($post_file))
            );
        }
        $this->ctrl->redirect($this, 'listFiles');
    }

    public function unzipFile(?string $a_file = null) : void
    {
        global $DIC;
        $lng = $DIC['lng'];

        // #17470 - direct unzip call (after upload)
        if (is_null($a_file)
            && isset($_GET["upfile"])
        ) {
            $a_file = basename($_GET["upfile"]);
        }

        $cur_subdir = $this->sanitizeCurrentDirectory();
        $cur_dir = (!empty($cur_subdir))
            ? $this->main_dir . "/" . $cur_subdir
            : $this->main_dir;
        $a_file = $this->main_dir . "/" . $a_file;

        if (@is_file($a_file)) {
            $cur_files = array_keys(ilUtil::getDir($cur_dir));
            $cur_files_r = iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cur_dir)));

            if ($this->getAllowDirectories()) {
                ilUtil::unzip($a_file, true);
            } else {
                ilUtil::unzip($a_file, true, true);
            }

            $new_files = array_keys(ilUtil::getDir($cur_dir));
            $new_files_r = iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cur_dir)));

            $diff = array_diff($new_files, $cur_files);
            $diff_r = array_diff($new_files_r, $cur_files_r);

            // unlink forbidden file types
            foreach ($diff_r as $f => $d) {
                $pi = pathinfo($f);
                if (!is_dir($f) && !$this->isValidSuffix(strtolower($pi["extension"]))) {
                    ilUtil::sendFailure(
                        $lng->txt("file_some_invalid_file_types_removed") . " (" . $pi["extension"] . ")",
                        true
                    );
                    unlink($f);
                }
            }

            if (sizeof($diff)) {
                if ($this->getAllowDirectories()) {
                    $new_files = array();

                    foreach ($diff as $new_item) {
                        if (is_dir($cur_dir . "/" . $new_item)) {
                            ilFileUtils::recursive_dirscan($cur_dir . "/" . $new_item, $new_files);
                        }
                    }

                    if (is_array($new_files["path"])) {
                        foreach ($new_files["path"] as $idx => $path) {
                            $path = substr($path, strlen($this->main_dir) + 1);
                            $diff[] = $path . $new_files["file"][$idx];
                        }
                    }
                }

                $this->setPerformedCommand(
                    "unzip_file",
                    array("name" => substr($file, strlen($this->main_dir) + 1),
                          "added" => $diff
                    )
                );
            }
        }

        ilUtil::renameExecutables($this->main_dir);

        $this->ctrl->saveParameter($this, self::PARAMETER_CDIR);
        ilUtil::sendSuccess($lng->txt("cont_file_unzipped"), true);
        $this->ctrl->redirect($this, "listFiles");
    }

    public function downloadFile(string $a_file) : void
    {
        $file = $this->main_dir . "/" . $a_file;

        if (is_file($file) && !(is_dir($file))) {
            ilFileDelivery::deliverFileLegacy($file, basename($a_file));
            exit;
        } else {
            $this->ctrl->saveParameter($this, self::PARAMETER_CDIR);
            $this->ctrl->redirect($this, "listFiles");
        }
    }

    public function getActionCommands() : array
    {
        return $this->commands;
    }

    public function defineCommands() : void
    {
        $this->commands = array(
            0 => array(
                "object" => $this,
                "method" => "downloadFile",
                "name" => $this->lng->txt("download"),
                "int" => true,
                "single" => true
            ),
            1 => array(
                "object" => $this,
                "method" => "confirmDeleteFile",
                "name" => $this->lng->txt("delete"),
                "allow_dir" => true,
                "int" => true
            ),
            2 => array(
                "object" => $this,
                "method" => "unzipFile",
                "name" => $this->lng->txt("unzip"),
                "int" => true,
                "single" => true
            ),
            3 => array(
                "object" => $this,
                "method" => "renameFileForm",
                "name" => $this->lng->txt("rename"),
                "allow_dir" => true,
                "int" => true,
                "single" => true
            ),
        );
    }

    private function sanitizeCurrentDirectory() : string
    {
        global $DIC;

        return str_replace(
            "..",
            "",
            ilUtil::stripSlashes($DIC->http()->request()->getQueryParams()[self::PARAMETER_CDIR])
        );
    }
}
