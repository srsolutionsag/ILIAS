<?php

/* Copyright (c) 1998-2021 ILIAS open source, GPLv3, see LICENSE */


use ILIAS\Filesystem\Util\LegacyPathHelper;
use ILIAS\FileUpload\DTO\ProcessingStatus;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileDelivery\Delivery;
use ILIAS\Filesystem\Stream\Streams;

/**
 * Util class
 * various functions, usage as namespace
 * @author     Sascha Hofmann <saschahofmann@gmx.de>
 * @author     Alex Killing <alex.killing@gmx.de>
 *
 * @deprecated The 2021 Technical Board has decided to mark the ilUtil class as deprecated. The ilUtil is a historically
 * grown helper class with many different UseCases and functions. The class is not under direct maintainership and the
 * responsibilities are unclear. In this context, the class should no longer be used in the code and existing uses
 * should be converted to their own service in the medium term. If you need ilUtil for the implementation of a new
 * function in ILIAS > 7, please contact the Technical Board.
 */
class ilUtil
{
    

    /**
    * Builds an html image tag
    * @deprecated Use UI-Service!
    */
    public static function getImageTagByType(string $a_type, string $a_path, bool $a_big = false) : string
    {
        global $DIC;

        $lng = $DIC->language();

        $size = ($a_big)
            ? "big"
            : "small";

        $filename = ilObject::_getIcon("", $size, $a_type);

        return "<img src=\"" . $filename . "\" alt=\"" . $lng->txt("obj_" . $a_type) . "\" title=\"" . $lng->txt("obj_" . $a_type) . "\" border=\"0\" vspace=\"0\"/>";
    }


    /**
    * get image path (for images located in a template directory)
    *
    * @deprecated use UI Service!
    *
    */
    public static function getImagePath(string $img, string $module_path = "", string $mode = "output", bool $offline = false) : string
    {
        global $DIC;

        $styleDefinition = null;
        if (isset($DIC["styleDefinition"])) {
            $styleDefinition = $DIC["styleDefinition"];
        }

        if (is_int(strpos($_SERVER["PHP_SELF"], "setup.php"))) {
            $module_path = "..";
        }
        if ($module_path != "") {
            $module_path = "/" . $module_path;
        }

        // default image
        $default_img = "." . $module_path . "/templates/default/images/" . $img;

        // use ilStyleDefinition instead of account to get the current skin and style
        $current_skin = ilStyleDefinition::getCurrentSkin();
        $current_style = ilStyleDefinition::getCurrentStyle();

        if (is_object($styleDefinition)) {
            $image_dir = $styleDefinition->getImageDirectory($current_style);
        }
        $skin_img = "";
        if ($current_skin == "default") {
            $user_img = "." . $module_path . "/templates/default/" . $image_dir . "/" . $img;
            $skin_img = "." . $module_path . "/templates/default/images/" . $img;
        } elseif (is_object($styleDefinition) && $current_skin != "default") {
            $user_img = "./Customizing/global/skin/" .
                $current_skin . $module_path . "/" . $image_dir . "/" . $img;
            $skin_img = "./Customizing/global/skin/" .
                $current_skin . $module_path . "/images/" . $img;
        }


        if ($offline) {
            return "./images/" . $img;
        } elseif (@file_exists($user_img) && $image_dir != "") {
            return $user_img;		// found image for skin and style
        } elseif (file_exists($skin_img)) {
            return $skin_img;		// found image in skin/images
        }

        return $default_img;			// take image in default
    }

    /**
    * get url of path
    *
    * @param $relative_path string: complete path to file, relative to web root (e.g.  /data/pfplms103/mobs/mm_732/athena_standing.jpg)
    * @deprecated
    */
    public static function getHtmlPath(string $relative_path) : string
    {
        if (substr($relative_path, 0, 2) == './') {
            $relative_path = (substr($relative_path, 1));
        }
        if (substr($relative_path, 0, 1) != '/') {
            $relative_path = '/' . $relative_path;
        }
        $htmlpath = ILIAS_HTTP_PATH . $relative_path;
        return $htmlpath;
    }

    /**
    * get full style sheet file name (path inclusive) of current user
    *
    * @param $mode           string Output mode of the style sheet ("output" or "filesystem"). !"filesystem" generates the ILIAS
    * version number as attribute to force the reload of the style sheet in a different ILIAS version
    * @param $a_css_name     string The name of the style sheet. If empty, the default style name will be chosen
    * @param $a_css_location string The location of the style sheet e.g. a module path. This parameter only makes sense
    * when $a_css_name is used
    * @deprecated
    */
    public static function getStyleSheetLocation(
        string $mode = "output", string $a_css_name = "", string $a_css_location = "") : string
    {
        global $DIC;

        $ilSetting = $DIC->settings();

        // add version as parameter to force reload for new releases
        // use ilStyleDefinition instead of account to get the current style
        $stylesheet_name = (strlen($a_css_name))
            ? $a_css_name
            : ilStyleDefinition::getCurrentStyle() . ".css";
        if (strlen($a_css_location) && (strcmp(substr($a_css_location, -1), "/") != 0)) {
            $a_css_location = $a_css_location . "/";
        }

        $filename = "";
        // use ilStyleDefinition instead of account to get the current skin
        if (ilStyleDefinition::getCurrentSkin() != "default") {
            $filename = "./Customizing/global/skin/" . ilStyleDefinition::getCurrentSkin() . "/" . $a_css_location . $stylesheet_name;
        }
        if (strlen($filename) == 0 || !file_exists($filename)) {
            $filename = "./" . $a_css_location . "templates/default/" . $stylesheet_name;
        }
        $vers = "";
        if ($mode != "filesystem") {
            $vers = str_replace(" ", "-", $ilSetting->get("ilias_version"));
            $vers = "?vers=" . str_replace(".", "-", $vers);
            // use version from template xml to force reload on changes
            $skin = ilStyleDefinition::getSkins()[ilStyleDefinition::getCurrentSkin()];
            $vers .= ($skin->getVersion() != '' ? str_replace(".", "-", '-' . $skin->getVersion()) : '');
        }
        return $filename . $vers;
    }

    /**
    * get full style sheet file name (path inclusive) of current user
    *
    * @deprecated
    */
    public static function getNewContentStyleSheetLocation(string $mode = "output") : string
    {
        global $DIC;

        $ilSetting = $DIC->settings();

        // add version as parameter to force reload for new releases
        if ($mode != "filesystem") {
            $vers = str_replace(" ", "-", $ilSetting->get("ilias_version"));
            $vers = "?vers=" . str_replace(".", "-", $vers);
        }

        // use ilStyleDefinition instead of account to get the current skin and style
        if (ilStyleDefinition::getCurrentSkin() == "default") {
            $in_style = "./templates/" . ilStyleDefinition::getCurrentSkin() . "/"
                                    . ilStyleDefinition::getCurrentStyle() . "_cont.css";
        } else {
            $in_style = "./Customizing/global/skin/" . ilStyleDefinition::getCurrentSkin() . "/"
                                                    . ilStyleDefinition::getCurrentStyle() . "_cont.css";
        }

        if (is_file("./" . $in_style)) {
            return $in_style . $vers;
        } else {
            return "templates/default/delos_cont.css" . $vers;
        }
    }

    /**
    * Builds a select form field with options and shows the selected option first
    *
    * @access	public
    * @param	string/array	value to be selected
    * @param	string			variable name in formular
    * @param	array			array with $options (key = lang_key, value = long name)
    * @param	boolean			multiple selection list true/false
    * @param	boolean			if true, the option values are displayed directly, otherwise
    *							they are handled as language variable keys and the corresponding
    *							language variable is displayed
    * @param	int				size
    * @param	string			style class
    * @param	array			additional attributes (key = attribute name, value = attribute value)
    * @param    boolean			disabled
    * @deprecated
    */
    public static function formSelect(
        $selected,
        string $varname,
        array $options,
        bool $multiple = false,
        bool $direct_text = false,
        int $size = 0,
        string $style_class = "",
        array $attribs = [],
        bool $disabled = false
    ) : string {
        global $DIC;

        $lng = $DIC->language();

        if ($multiple == true) {
            $multiple = " multiple=\"multiple\"";
        } else {
            $multiple = "";
            $size = 0;
        }

        $class = " class=\" form-control " . $style_class . "\"";

        // use form-inline!
        // this is workaround the whole function should be set deprecated
        // $attributes = " style='display:inline-block;' ";

        $attributes = "";
        if (is_array($attribs)) {
            foreach ($attribs as $key => $val) {
                $attributes .= " " . $key . "=\"" . $val . "\"";
            }
        }
        if ($disabled) {
            $disabled = ' disabled=\"disabled\"';
        }

        $size_str = "";
        if ($size > 0) {
            $size_str = ' size="' . $size . '" ';
        }
        $str = "<select name=\"" . $varname . "\"" . $multiple . " $class " . $size_str . " $disabled>\n";

        foreach ((array) $options as $key => $val) {
            $style = "";
            if (is_array($val)) {
                $style = $val["style"];
                $val = $val["text"];		// mus be last line, since we overwrite
            }

            $sty = ($style != "")
                ? ' style="' . $style . '" '
                : "";

            if ($direct_text) {
                $str .= " <option $sty value=\"" . $key . "\"";
            } else {
                $str .= " <option $sty value=\"" . $val . "\"";
            }
            if (is_array($selected)) {
                if (in_array($key, $selected)) {
                    $str .= " selected=\"selected\"";
                }
            } elseif ($selected == $key) {
                $str .= " selected=\"selected\"";
            }

            if ($direct_text) {
                $str .= ">" . $val . "</option>\n";
            } else {
                $str .= ">" . $lng->txt($val) . "</option>\n";
            }
        }

        $str .= "</select>\n";

        return $str;
    }
    
    /**
     * @deprecated
     */
    public static function formCheckbox(bool $checked, string $varname, string $value, bool $disabled = false) : string
    {
        $str = "<input type=\"checkbox\" name=\"" . $varname . "\"";

        if ($checked === true) {
            $str .= " checked=\"checked\"";
        }

        if ($disabled === true) {
            $str .= " disabled=\"disabled\"";
        }

        $array_var = false;

        if (substr($varname, -2) == "[]") {
            $array_var = true;
        }

        // if varname ends with [], use varname[-2] + _ + value as id tag (e.g. "user_id[]" => "user_id_15")
        if ($array_var) {
            $varname_id = substr($varname, 0, -2) . "_" . $value;
        } else {
            $varname_id = $varname;
        }

        // dirty removal of other "[]" in string
        $varname_id = str_replace("[", "_", $varname_id);
        $varname_id = str_replace("]", "", $varname_id);

        $str .= " value=\"" . $value . "\" id=\"" . $varname_id . "\" />\n";

        return $str;
    }
    
    /**
     * @deprecated
     */
    public static function formRadioButton(
        bool $checked, string $varname, string $value, string $onclick = null, bool $disabled = false) : string
    {
        $str = '<input ';

        if ($onclick !== null) {
            $str .= ('onclick="' . $onclick . '"');
        }

        $str .= (" type=\"radio\" name=\"" . $varname . "\"");
        if ($checked === true) {
            $str .= " checked=\"checked\"";
        }

        if ($disabled === true) {
            $str .= " disabled=\"disabled\"";
        }

        $str .= " value=\"" . $value . "\"";

        $str .= " id=\"" . $value . "\" />\n";

        return $str;
    }


   


    /**
    * switches style sheets for each even $a_num
    * (used for changing colors of different result rows)
    * @deprecated
    */
    public static function switchColor(int $a_num, string $a_css1, string $a_css2) : string
    {
        if (!($a_num % 2)) {
            return $a_css1;
        } else {
            return $a_css2;
        }
    }


    /**
    * @depracated Use the respective `Refinery` transformation `$refinery->string()->makeClickable("foo bar")` to convert URL-like string parts to an HTML anchor (`<a>`) element (the boolean flag is removed)
    */
    public static function makeClickable(string $a_text, bool $detectGotoLinks = false) : string
    {
        global $DIC;
        
        return $DIC->refinery()->string()->makeClickable()->transform($a_text);
    }
    
    /**
    * Creates a combination of HTML selects for time inputs
    *
    * Creates a combination of HTML selects for time inputs.
    * The select names are $prefix[h] for hours, $prefix[m]
    * for minutes and $prefix[s] for seconds.
    *
    * @access	public
    * @param string $prefix  Prefix of the select name
    * @param boolean $short  Set TRUE for a short time input (only hours and minutes). Default is TRUE
    * @param integer $hour   Default hour value
    * @param integer $minute Default minute value
    * @param integer $second Default second value
    * @deprecated
    */
    public static function makeTimeSelect(
        string $prefix, bool $short = true, int $hour = 0, int $minute = 0, int $second = 0, bool $a_use_default = true, array $a_further_options = []
    ) : string {
        global $DIC;

        $lng = $DIC->language();
        $ilUser = $DIC->user();

        $minute_steps = 1;
        $disabled = '';
        if (count($a_further_options)) {
            if (isset($a_further_options['minute_steps'])) {
                $minute_steps = $a_further_options['minute_steps'];
            }
            if (isset($a_further_options['disabled']) and $a_further_options['disabled']) {
                $disabled = 'disabled="disabled" ';
            }
        }

        if ($a_use_default and !strlen("$hour$minute$second")) {
            $now = localtime();
            $hour = $now[2];
            $minute = $now[1];
            $second = $now[0];
        } else {
            $hour = (int) $hour;
            $minute = (int) $minute;
            $second = (int) $second;
        }
        // build hour select
        $sel_hour = '<select ';
        if (isset($a_further_options['select_attributes'])) {
            foreach ($a_further_options['select_attributes'] as $name => $value) {
                $sel_hour .= $name . '=' . $value . ' ';
            }
        }
        $sel_hour .= " " . $disabled . "name=\"" . $prefix . "[h]\" id=\"" . $prefix . "_h\" class=\"form-control\">\n";

        $format = $ilUser->getTimeFormat();
        for ($i = 0; $i <= 23; $i++) {
            if ($format == ilCalendarSettings::TIME_FORMAT_24) {
                $sel_hour .= "<option value=\"$i\">" . sprintf("%02d", $i) . "</option>\n";
            } else {
                $sel_hour .= "<option value=\"$i\">" . date("ga", mktime($i, 0, 0)) . "</option>\n";
            }
        }
        $sel_hour .= "</select>\n";
        $sel_hour = preg_replace("/(value\=\"$hour\")/", "$1 selected=\"selected\"", $sel_hour);

        // build minutes select
        $sel_minute .= "<select " . $disabled . "name=\"" . $prefix . "[m]\" id=\"" . $prefix . "_m\" class=\"form-control\">\n";

        for ($i = 0; $i <= 59; $i = $i + $minute_steps) {
            $sel_minute .= "<option value=\"$i\">" . sprintf("%02d", $i) . "</option>\n";
        }
        $sel_minute .= "</select>\n";
        $sel_minute = preg_replace("/(value\=\"$minute\")/", "$1 selected=\"selected\"", $sel_minute);

        if (!$short) {
            // build seconds select
            $sel_second .= "<select " . $disabled . "name=\"" . $prefix . "[s]\" id=\"" . $prefix . "_s\" class=\"form-control\">\n";

            for ($i = 0; $i <= 59; $i++) {
                $sel_second .= "<option value=\"$i\">" . sprintf("%02d", $i) . "</option>\n";
            }
            $sel_second .= "</select>\n";
            $sel_second = preg_replace("/(value\=\"$second\")/", "$1 selected=\"selected\"", $sel_second);
        }
        $timeformat = $lng->text["lang_timeformat"];
        if (strlen($timeformat) == 0) {
            $timeformat = "H:i:s";
        }
        $timeformat = strtolower(preg_replace("/\W/", "", $timeformat));
        $timeformat = preg_replace("/(\w)/", "%%$1", $timeformat);
        $timeformat = preg_replace("/%%h/", $sel_hour, $timeformat);
        $timeformat = preg_replace("/%%i/", $sel_minute, $timeformat);
        if ($short) {
            $timeformat = preg_replace("/%%s/", "", $timeformat);
        } else {
            $timeformat = preg_replace("/%%s/", $sel_second, $timeformat);
        }
        return $timeformat;
    }

    /**
     * This preg-based function checks whether an e-mail address is formally valid.
     * It works with all top level domains including the new ones (.biz, .info, .museum etc.)
     * and the special ones (.arpa, .int etc.)
     * as well as with e-mail addresses based on IPs (e.g. webmaster@123.45.123.45)
     * Valid top level domains: http://data.iana.org/TLD/tlds-alpha-by-domain.txt
     *
     * @deprecated use ilMailRfc822AddressParserFactory directly
     */
    public static function is_email(string $a_email, ilMailRfc822AddressParserFactory $mailAddressParserFactory = null
    ) : bool
    {
        if ($mailAddressParserFactory === null) {
            $mailAddressParserFactory = new ilMailRfc822AddressParserFactory();
        }

        try {
            $parser = $mailAddressParserFactory->getParser($a_email);
            $addresses = $parser->parse();
            return count($addresses) == 1 && $addresses[0]->getHost() != ilMail::ILIAS_HOST;
        } catch (ilException $e) {
            return false;
        }
    }
    
    /**
     * @deprecated
     */
    public static function isLogin(string $a_login) : bool
    {
        if (empty($a_login)) {
            return false;
        }

        if (strlen($a_login) < 3) {
            return false;
        }

        // FIXME - If ILIAS is configured to use RFC 822
        //         compliant mail addresses we should not
        //         allow the @ character.
        if (!preg_match("/^[A-Za-z0-9_\.\+\*\@!\$\%\~\-]+$/", $a_login)) {
            return false;
        }

        return true;
    }
    
    
    /**
    * get convert command
    *
    * @deprecated
    * @see ilUtil::execConvert()
    * @static
    *
    */
    public static function getConvertCmd()
    {
        return PATH_TO_CONVERT;
    }

    /**
     * execute convert command
     *
     * @param	string	$args
     * @static
     *
     */
    public static function execConvert($args)
    {
        $args = self::escapeShellCmd($args);
        ilUtil::execQuoted(PATH_TO_CONVERT, $args);
    }

    /**
     * Compare convert version numbers
     *
     * @param string $a_version w.x.y-z
     * @return bool
     */
    public static function isConvertVersionAtLeast($a_version)
    {
        $current_version = ilUtil::execQuoted(PATH_TO_CONVERT, "--version");
        $current_version = self::processConvertVersion($current_version[0]);
        $version = self::processConvertVersion($a_version);
        if ($current_version >= $version) {
            return true;
        }
        return false;
    }

    /**
     * Parse convert version string, e.g. 6.3.8-3, into integer
     *
     * @param string $a_version w.x.y-z
     * @return int
     */
    protected static function processConvertVersion($a_version)
    {
        if (preg_match("/([0-9]+)\.([0-9]+)\.([0-9]+)([\.|\-]([0-9]+))?/", $a_version, $match)) {
            $version = str_pad($match[1], 2, 0, STR_PAD_LEFT) .
                str_pad($match[2], 2, 0, STR_PAD_LEFT) .
                str_pad($match[3], 2, 0, STR_PAD_LEFT) .
                str_pad($match[5], 2, 0, STR_PAD_LEFT);
            return (int) $version;
        }
    }

    /**
    * convert image
    *
    * @param	string		$a_from				source file
    * @param	string		$a_to				target file
    * @param	string		$a_target_format	target image file format
    * @static
    *
    */
    public static function convertImage(
        $a_from,
        $a_to,
        $a_target_format = "",
        $a_geometry = "",
        $a_background_color = ""
    ) {
        $format_str = ($a_target_format != "")
            ? strtoupper($a_target_format) . ":"
            : "";
        $geometry = "";
        if ($a_geometry != "") {
            if (is_int(strpos($a_geometry, "x"))) {
                $geometry = " -geometry " . $a_geometry . " ";
            } else {
                $geometry = " -geometry " . $a_geometry . "x" . $a_geometry . " ";
            }
        }

        $bg_color = ($a_background_color != "")
            ? " -background color " . $a_background_color . " "
            : "";
        $convert_cmd = ilUtil::escapeShellArg($a_from) . " " . $bg_color . $geometry . ilUtil::escapeShellArg($format_str . $a_to);
        ilUtil::execConvert($convert_cmd);
    }

    /**
    * resize image
    *
    * @param	string		$a_from				source file
    * @param	string		$a_to				target file
    * @param	string		$a_width			target width
    * @param	string		$a_height			target height
    * @static
    *
    */
    public static function resizeImage($a_from, $a_to, $a_width, $a_height, $a_constrain_prop = false)
    {
        if ($a_constrain_prop) {
            $size = " -geometry " . $a_width . "x" . $a_height . " ";
        } else {
            $size = " -resize " . $a_width . "x" . $a_height . "! ";
        }
        $convert_cmd = ilUtil::escapeShellArg($a_from) . " " . $size . ilUtil::escapeShellArg($a_to);

        ilUtil::execConvert($convert_cmd);
    }

    /**
    * Build img tag
    *
    * @static
    * @deprecated
    */
    public static function img($a_src, $a_alt = null, $a_width = "", $a_height = "", $a_border = 0, $a_id = "", $a_class = "")
    {
        $img = '<img src="' . $a_src . '"';
        if (!is_null($a_alt)) {
            $img .= ' alt="' . htmlspecialchars($a_alt) . '"';
        }
        if ($a_width != "") {
            $img .= ' width="' . htmlspecialchars($a_width) . '"';
        }
        if ($a_height != "") {
            $img .= ' height="' . htmlspecialchars($a_height) . '"';
        }
        if ($a_class != "") {
            $img .= ' class="' . $a_class . '"';
        }
        if ($a_id != "") {
            $img .= ' id="' . $a_id . '"';
        }
        $img .= ' />';

        return $img;
    }
    
    /**
     * @deprecated use ilFileDelivery
     */
    public static function deliverData(
        string $a_data,
        string $a_filename,
        string $mime = "application/octet-stream"
    ) : void {
        global $DIC;
        $delivery = new Delivery(
            Delivery::DIRECT_PHP_OUTPUT,
            $DIC->http()
        );
        $delivery->setMimeType($mime);
        $delivery->setSendMimeType(true);
        $delivery->setDisposition(Delivery::DISP_ATTACHMENT);
        $delivery->setDownloadFileName($a_filename);
        $delivery->setConvertFileNameToAsci(true);
        $repsonse = $DIC->http()->response()->withBody(Streams::ofString($a_data));
        $DIC->http()->saveResponse($repsonse);
        $delivery->deliver();
    }
    

    // convert utf8 to ascii filename

    /**
     * @deprecated
     */
    public static function appendUrlParameterString(string $a_url, string $a_par, bool $xml_style = false) : string
    {
        $amp = $xml_style
            ? "&amp;"
            : "&";

        $url = (is_int(strpos($a_url, "?")))
            ? $a_url . $amp . $a_par
            : $a_url . "?" . $a_par;

        return $url;
    }

    /**
     * @deprecated
     */
    public static function stripSlashesArray(array $a_arr, bool $a_strip_html = true, string $a_allow = "") : array
    {
        foreach ($a_arr as $k => $v) {
            $a_arr[$k] = ilUtil::stripSlashes($v, $a_strip_html, $a_allow);
        }
        
        return $a_arr;
    }
    
    /**
     * @param $data string|array
     * @deprecated
     */
    public static function stripSlashesRecursive($a_data, bool $a_strip_html = true, string $a_allow = "") : array
    {
        if (is_array($a_data)) {
            foreach ($a_data as $k => $v) {
                if (is_array($v)) {
                    $a_data[$k] = ilUtil::stripSlashesRecursive($v, $a_strip_html, $a_allow);
                } else {
                    $a_data[$k] = ilUtil::stripSlashes($v, $a_strip_html, $a_allow);
                }
            }
        } else {
            $a_data = ilUtil::stripSlashes($a_data, $a_strip_html, $a_allow);
        }

        return $a_data;
    }
    
    /**
     * @deprecated
     */
    public static function stripSlashes(string $a_str, bool $a_strip_html = true, string $a_allow = "") : string
    {
        if (ini_get("magic_quotes_gpc")) {
            $a_str = stripslashes($a_str);
        }

        return ilUtil::secureString($a_str, $a_strip_html, $a_allow);
    }
    
    /**
     * @deprecated
     */
    public static function stripOnlySlashes(string $a_str) : string
    {
        if (ini_get("magic_quotes_gpc")) {
            $a_str = stripslashes($a_str);
        }

        return $a_str;
    }
    
    /**
     * @deprecated
     */
    public static function secureString(string $a_str, bool $a_strip_html = true, string $a_allow = "") : string
    {
        // check whether all allowed tags can be made secure
        $only_secure = true;
        $allow_tags = explode(">", $a_allow);
        $sec_tags = ilUtil::getSecureTags();
        $allow_array = [];
        foreach ($allow_tags as $allow) {
            if ($allow != "") {
                $allow = str_replace("<", "", $allow);

                if (!in_array($allow, $sec_tags)) {
                    $only_secure = false;
                }
                $allow_array[] = $allow;
            }
        }

        // default behaviour: allow only secure tags 1:1
        if (($only_secure || $a_allow == "") && $a_strip_html) {
            if ($a_allow === "") {
                $allow_array = ["b", "i", "strong", "em", "code", "cite",
                                "gap", "sub", "sup", "pre", "strike", "bdo"
                ];
            }

            // this currently removes parts of strings like "a <= b"
            // because "a <= b" is treated like "<spam onclick='hurt()'>ss</spam>"
            $a_str = ilUtil::maskSecureTags($a_str, $allow_array);
            $a_str = strip_tags($a_str);		// strip all other tags
            $a_str = ilUtil::unmaskSecureTags($a_str, $allow_array);

        // a possible solution could be something like:
            // $a_str = str_replace("<", "&lt;", $a_str);
            // $a_str = str_replace(">", "&gt;", $a_str);
            // $a_str = ilUtil::unmaskSecureTags($a_str, $allow_array);
            //
            // output would be ok then, but input fields would show
            // "a &lt;= b" for input "a <= b" if data is brought back to a form
        } else {
            // only for scripts, that need to allow more/other tags and parameters
            if ($a_strip_html) {
                $a_str = ilUtil::stripScriptHTML($a_str, $a_allow);
            }
        }

        return $a_str;
    }

    public static function getSecureTags() : array
    {
        return ["strong", "em", "u", "strike", "ol", "li", "ul", "p", "div",
                "i", "b", "code", "sup", "sub", "pre", "gap", "a", "img", "bdo"
        ];
    }

    private static function maskSecureTags(string $a_str, array $allow_array) : string
    {
        foreach ($allow_array as $t) {
            switch ($t) {
                case "a":
                    $a_str = ilUtil::maskAttributeTag($a_str, "a", "href");
                    break;

                case "img":
                    $a_str = ilUtil::maskAttributeTag($a_str, "img", "src");
                    break;

                case "p":
                case "div":
                    $a_str = ilUtil::maskTag($a_str, $t, [
                        ["param" => "align", "value" => "left"],
                        ["param" => "align", "value" => "center"],
                        ["param" => "align", "value" => "justify"],
                        ["param" => "align", "value" => "right"]
                    ]);
                break;

                default:
                    $a_str = ilUtil::maskTag($a_str, $t);
                    break;
            }
        }

        return $a_str;
    }

    private static function unmaskSecureTags($a_str, array $allow_array) : string
    {
        foreach ($allow_array as $t) {
            switch ($t) {
                case "a":
                    $a_str = ilUtil::unmaskAttributeTag($a_str, "a", "href");
                    break;

                case "img":
                    $a_str = ilUtil::unmaskAttributeTag($a_str, "img", "src");
                    break;

                case "p":
                case "div":
                    $a_str = ilUtil::unmaskTag($a_str, $t, [
                        ["param" => "align", "value" => "left"],
                        ["param" => "align", "value" => "center"],
                        ["param" => "align", "value" => "justify"],
                        ["param" => "align", "value" => "right"]
                    ]);
                break;

                default:
                    $a_str = ilUtil::unmaskTag($a_str, $t);
                    break;
            }
        }

        return $a_str;
    }
    
    /**
     * @deprecated
     */
    public static function securePlainString(string $a_str) : string
    {
        if (ini_get("magic_quotes_gpc")) {
            return stripslashes($a_str);
        } else {
            return $a_str;
        }
    }
    /**
    * Encodes a plain text string into HTML for display in a browser.
    * This function encodes HTML special characters: < > & with &lt; &gt; &amp;
    * and converts newlines into <br>
    *
    * If $a_make_links_clickable is set to true, URLs in the plain string which
    * are considered to be safe, are made clickable.
    *
    *
    * @param string the plain text string
    * @param boolean set this to true, to make links in the plain string
    * clickable.
    * @param boolean set this to true, to detect goto links
    * @static
    *
    */
    public static function htmlencodePlainString(string $a_str, bool $a_make_links_clickable, bool $a_detect_goto_links = false) : string
    {
        $encoded = "";

        if ($a_make_links_clickable) {
            // Find text sequences in the plain text string which match
            // the URI syntax rules, and pass them to ilUtil::makeClickable.
            // Encode all other text sequences in the plain text string using
            // htmlspecialchars and nl2br.
            // The following expressions matches URI's as specified in RFC 2396.
            //
            // The expression matches URI's, which start with some well known
            // schemes, like "http:", or with "www.". This must be followed
            // by at least one of the following RFC 2396 expressions:
            // - alphanum:           [a-zA-Z0-9]
            // - reserved:           [;\/?:|&=+$,]
            // - mark:               [\\-_.!~*\'()]
            // - escaped:            %[0-9a-fA-F]{2}
            // - fragment delimiter: #
            // - uric_no_slash:      [;?:@&=+$,]
            $matches = array();
            $numberOfMatches = preg_match_all('/(?:(?:http|https|ftp|ftps|mailto):|www\.)(?:[a-zA-Z0-9]|[;\/?:|&=+$,]|[\\-_.!~*\'()]|%[0-9a-fA-F]{2}|#|[;?:@&=+$,])+/', $a_str, $matches, PREG_OFFSET_CAPTURE);
            $pos1 = 0;
            $encoded = "";

            foreach ($matches[0] as $match) {
                $matched_text = $match[0];
                $pos2 = $match[1];

                // encode plain text
                $encoded .= nl2br(htmlspecialchars(substr($a_str, $pos1, $pos2 - $pos1)));

                // encode URI
                $encoded .= ilUtil::makeClickable($matched_text, $a_detect_goto_links);


                $pos1 = $pos2 + strlen($matched_text);
            }
            if ($pos1 < strlen($a_str)) {
                $encoded .= nl2br(htmlspecialchars(substr($a_str, $pos1)));
            }
        } else {
            $encoded = nl2br(htmlspecialchars($a_str));
        }
        return $encoded;
    }


    private static function maskAttributeTag(string $a_str, string $tag, string $tag_att) : string
    {
        global $DIC;

        $ilLog = $DIC["ilLog"];

        $ws = "[\s]*";
        $att = $ws . "[^>]*" . $ws;

        while (preg_match(
            '/<(' . $tag . $att . '(' . $tag_att . $ws . '="' . $ws . '(([$@!*()~;,_0-9A-z\/:=%.&#?+\-])*)")' . $att . ')>/i',
            $a_str,
            $found
        )) {
            $old_str = $a_str;
            $a_str = preg_replace(
                "/<" . preg_quote($found[1], "/") . ">/i",
                '&lt;' . $tag . ' ' . $tag_att . $tag_att . '="' . $found[3] . '"&gt;',
                $a_str
            );
            if ($old_str == $a_str) {
                $ilLog->write("ilUtil::maskA-" . htmlentities($old_str) . " == " .
                    htmlentities($a_str));
                return $a_str;
            }
        }
        $a_str = str_ireplace(
            "</$tag>",
            "&lt;/$tag&gt;",
            $a_str
        );
        return $a_str;
    }

    private static function unmaskAttributeTag(string $a_str, string $tag, string $tag_att) : string
    {
        global $DIC;

        $ilLog = $DIC["ilLog"];

        while (preg_match(
            '/&lt;(' . $tag . ' ' . $tag_att . $tag_att . '="(([$@!*()~;,_0-9A-z\/:=%.&#?+\-])*)")&gt;/i',
            $a_str,
            $found
        )) {
            $old_str = $a_str;
            $a_str = preg_replace(
                "/&lt;" . preg_quote($found[1], "/") . "&gt;/i",
                '<' . $tag . ' ' . $tag_att . '="' . ilUtil::secureLink($found[2]) . '">',
                $a_str
            );
            if ($old_str == $a_str) {
                $ilLog->write("ilUtil::unmaskA-" . htmlentities($old_str) . " == " .
                    htmlentities($a_str));
                return $a_str;
            }
        }
        $a_str = str_replace('&lt;/' . $tag . '&gt;', '</' . $tag . '>', $a_str);
        return $a_str;
    }

    public static function maskTag(string $a_str, string $tag, array $fix_param = []) : string
    {
        $a_str = str_replace(
            array("<$tag>", "<" . strtoupper($tag) . ">"),
            "&lt;" . $tag . "&gt;",
            $a_str
        );
        $a_str = str_replace(
            array("</$tag>", "</" . strtoupper($tag) . ">"),
            "&lt;/" . $tag . "&gt;",
            $a_str
        );
        
            foreach ($fix_param	 as $p) {
                $k = $p["param"];
                $v = $p["value"];
                $a_str = str_replace(
                    "<$tag $k=\"$v\">",
                    "&lt;" . "$tag $k=\"$v\"" . "&gt;",
                    $a_str
                );
            }

        return $a_str;
    }
    
    private static function unmaskTag(string $a_str, string $tag, array $fix_param = []) : string
    {
        $a_str = str_replace("&lt;" . $tag . "&gt;", "<" . $tag . ">", $a_str);
        $a_str = str_replace("&lt;/" . $tag . "&gt;", "</" . $tag . ">", $a_str);
        
        foreach ($fix_param as $p) {
            $k = $p["param"];
            $v = $p["value"];
            $a_str = str_replace(
                "&lt;$tag $k=\"$v\"&gt;",
                "<" . "$tag $k=\"$v\"" . ">",
                $a_str
            );
        }
        return $a_str;
    }
    
    /**
     * @deprecated
     */
    public static function secureLink(string $a_str) : string
    {
        $a_str = str_ireplace("javascript", "jvscrpt", $a_str);
        $a_str = str_ireplace(array("%00", "%0a", "%0d", "%1a", "&#00;", "&#x00;",
            "&#0;", "&#x0;", "&#x0a;", "&#x0d;", "&#10;", "&#13;"), "-", $a_str);
        return $a_str;
    }
    
    /**
     * @deprecated
     */
    public static function stripScriptHTML(string $a_str, string $a_allow = "", bool $a_rm_js = true) : string
    {
        $negativestr = "a,abbr,acronym,address,applet,area,base,basefont," .
            "big,blockquote,body,br,button,caption,center,cite,code,col," .
            "colgroup,dd,del,dfn,dir,div,dl,dt,em,fieldset,font,form,frame," .
            "frameset,h1,h2,h3,h4,h5,h6,head,hr,html,i,iframe,img,input,ins,isindex,kbd," .
            "label,legend,li,link,map,menu,meta,noframes,noscript,object,ol," .
            "optgroup,option,p,param,q,s,samp,script,select,small,span," .
            "strike,strong,style,sub,sup,table,tbody,td,textarea,tfoot,th,thead," .
            "title,tr,tt,u,ul,var";
        $a_allow = strtolower($a_allow);
        $negatives = explode(",", $negativestr);
        $outer_old_str = "";
        while ($outer_old_str != $a_str) {
            $outer_old_str = $a_str;
            foreach ($negatives as $item) {
                $pos = strpos($a_allow, "<$item>");

                // remove complete tag, if not allowed
                if ($pos === false) {
                    $old_str = "";
                    while ($old_str != $a_str) {
                        $old_str = $a_str;
                        $a_str = preg_replace("/<\/?\s*$item(\/?)\s*>/i", "", $a_str);
                        $a_str = preg_replace("/<\/?\s*$item(\/?)\s+([^>]*)>/i", "", $a_str);
                    }
                }
            }
        }

        if ($a_rm_js) {
            // remove all attributes if an "on..." attribute is given
            $a_str = preg_replace("/<\s*\w*(\/?)(\s+[^>]*)?(\s+on[^>]*)>/i", "", $a_str);

            // remove all attributes if a "javascript" is within tag
            $a_str = preg_replace("/<\s*\w*(\/?)\s+[^>]*javascript[^>]*>/i", "", $a_str);

            // remove all attributes if an "expression" is within tag
            // (IE allows something like <b style='width:expression(alert(1))'>test</b>)
            $a_str = preg_replace("/<\s*\w*(\/?)\s+[^>]*expression[^>]*>/i", "", $a_str);
        }

        return $a_str;
    }

    /**
    * @deprecated
    */
    public static function prepareFormOutput(string $a_str, bool $a_strip = false) : string
    {
        if ($a_strip) {
            $a_str = ilUtil::stripSlashes($a_str);
        }
        $a_str = htmlspecialchars($a_str);
        // Added replacement of curly brackets to prevent
        // problems with PEAR templates, because {xyz} will
        // be removed as unused template variable
        $a_str = str_replace("{", "&#123;", $a_str);
        $a_str = str_replace("}", "&#125;", $a_str);
        // needed for LaTeX conversion \\ in LaTeX is a line break
        // but without this replacement, php changes \\ to \
        $a_str = str_replace("\\", "&#92;", $a_str);
        return $a_str;
    }
    
    /**
     * @deprecated
     */
    public static function secureUrl(string $url) : string
    {
        // check if url is valid (absolute or relative)
        if (filter_var($url, FILTER_VALIDATE_URL) === false &&
            filter_var("http://" . $url, FILTER_VALIDATE_URL) === false &&
            filter_var("http:" . $url, FILTER_VALIDATE_URL) === false &&
            filter_var("http://de.de" . $url, FILTER_VALIDATE_URL) === false &&
            filter_var("http://de.de/" . $url, FILTER_VALIDATE_URL) === false) {
            return "";
        }
        if (trim(strtolower(parse_url($url, PHP_URL_SCHEME))) == "javascript") {
            return "";
        }
        $url = htmlspecialchars($url, ENT_QUOTES);
        return $url;
    }
    
    /**
     * @deprecated
     */
    public static function extractParameterString(string $a_parstr) : array
    {
        // parse parameters in array
        $par = array();
        $ok = true;
        while (($spos = strpos($a_parstr, "=")) && $ok) {
            // extract parameter
            $cpar = substr($a_parstr, 0, $spos);
            $a_parstr = substr($a_parstr, $spos, strlen($a_parstr) - $spos);
            while (substr($cpar, 0, 1) == "," || substr($cpar, 0, 1) == " " || substr($cpar, 0, 1) == chr(13) || substr($cpar, 0, 1) == chr(10)) {
                $cpar = substr($cpar, 1, strlen($cpar) - 1);
            }
            while (substr($cpar, strlen($cpar) - 1, 1) == " " || substr($cpar, strlen($cpar) - 1, 1) == chr(13) || substr($cpar, strlen($cpar) - 1, 1) == chr(10)) {
                $cpar = substr($cpar, 0, strlen($cpar) - 1);
            }

            // parameter name should only
            $cpar_old = "";
            while ($cpar != $cpar_old) {
                $cpar_old = $cpar;
                $cpar = preg_replace("/[^a-zA-Z0-9_]/i", "", $cpar);
            }

            // extract value
            if ($cpar != "") {
                if ($spos = strpos($a_parstr, "\"")) {
                    $a_parstr = substr($a_parstr, $spos + 1, strlen($a_parstr) - $spos);
                    $spos = strpos($a_parstr, "\"");
                    if (is_int($spos)) {
                        $cval = substr($a_parstr, 0, $spos);
                        $par[$cpar] = $cval;
                        $a_parstr = substr($a_parstr, $spos + 1, strlen($a_parstr) - $spos - 1);
                    } else {
                        $ok = false;
                    }
                } else {
                    $ok = false;
                }
            }
        }
    
        return $ok ? $par : [];
    }
    
    /**
     * @deprecated use Refinery instead
     */
    public static function yn2tf(string $a_yn) : bool
    {
        if (strtolower($a_yn) === "y") {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * @deprecated
     */
    public static function tf2yn(bool $a_tf) : string
    {
        if ($a_tf) {
            return "y";
        } else {
            return "n";
        }
    }

    /**
     * @deprecated
     */
    protected static function sort_func(array $left, array $right) : int
    {
        global $array_sortby,$array_sortorder;

        if (!isset($array_sortby)) {
            // occured in: setup -> new client -> install languages -> sorting of languages
            $array_sortby = 0;
        }

        $leftValue = (string) ($left[$array_sortby] ?? '');
        $rightValue = (string) ($right[$array_sortby] ?? '');

        // this comparison should give optimal results if
        // locale is provided and mb string functions are supported
        if ($array_sortorder === "asc") {
            return ilStr::strCmp($leftValue, $rightValue);
        } elseif ($array_sortorder === "desc") {
            return ilStr::strCmp($rightValue, $leftValue);
        }

        return 0;
    }

    /**
     * @deprecated
     */
    public static function sort_func_numeric(array $left, array $right) : int
    {
        global $array_sortby,$array_sortorder;

        $leftValue = (string) ($left[$array_sortby] ?? '');
        $rightValue = (string) ($right[$array_sortby] ?? '');

        if ($array_sortorder === "asc") {
            return $leftValue <=> $rightValue;
        } elseif ($array_sortorder === "desc") {
            return $rightValue <=> $leftValue;
        }

        return 0;
    }
    /**
    * @deprecated
    */
    public static function sortArray(
        array $array,
        string $a_array_sortby_key,
        string $a_array_sortorder = "asc",
        bool $a_numeric = false,
        bool $a_keep_keys = false
    ) : array {
        if (!$a_keep_keys) {
            return self::stableSortArray($array, $a_array_sortby_key, $a_array_sortorder, $a_numeric);
        }

        global $array_sortby,$array_sortorder;
        $array_sortby = $a_array_sortby_key;

        if ($a_array_sortorder == "desc") {
            $array_sortorder = "desc";
        } else {
            $array_sortorder = "asc";
        }
        if ($a_numeric) {
            if ($a_keep_keys) {
                uasort($array, array("ilUtil", "sort_func_numeric"));
            } else {
                usort($array, array("ilUtil", "sort_func_numeric"));
            }
        } else {
            if ($a_keep_keys) {
                uasort($array, array("ilUtil", "sort_func"));
            } else {
                usort($array, array("ilUtil", "sort_func"));
            }
        }

        return $array;
    }

    /**
    * Sort an aray using a stable sort algorithm, which preveserves the sequence
    * of array elements which have the same sort value.
    * To sort an array by multiple sort keys, invoke this function for each sort key.
    *
    * @deprecated
    */
    public static function stableSortArray(
        array $array, string $a_array_sortby, string $a_array_sortorder = "asc", bool $a_numeric = false) : array
    {
        global $array_sortby,$array_sortorder;

        $array_sortby = $a_array_sortby;

        if ($a_array_sortorder == "desc") {
            $array_sortorder = "desc";
        } else {
            $array_sortorder = "asc";
        }

        // Create a copy of the array values for sorting
        $sort_array = array_values($array);

        if ($a_numeric) {
            ilUtil::mergesort($sort_array, array("ilUtil", "sort_func_numeric"));
        } else {
            ilUtil::mergesort($sort_array, array("ilUtil", "sort_func"));
        }

        return $sort_array;
    }
    
    /**
     * @param array $array
     * @param callable $cmp_function
     * @return void
     */
    private static function mergesort(array &$array, callable $cmp_function = null) : void
    {
        if ($cmp_function === null) {
            $cmp_function = 'strcmp';
        }
        // Arrays of size < 2 require no action.
        if (count($array) < 2) {
            return;
        }

        // Split the array in half
        $halfway = count($array) / 2;
        $array1 = array_slice($array, 0, $halfway);
        $array2 = array_slice($array, $halfway);

        // Recurse to sort the two halves
        ilUtil::mergesort($array1, $cmp_function);
        ilUtil::mergesort($array2, $cmp_function);

        // If all of $array1 is <= all of $array2, just append them.
        if (call_user_func($cmp_function, end($array1), $array2[0]) < 1) {
            $array = array_merge($array1, $array2);
            return;
        }

        // Merge the two sorted arrays into a single sorted array
        $array = array();
        $ptr1 = $ptr2 = 0;
        while ($ptr1 < count($array1) && $ptr2 < count($array2)) {
            if (call_user_func($cmp_function, $array1[$ptr1], $array2[$ptr2]) < 1) {
                $array[] = $array1[$ptr1++];
            } else {
                $array[] = $array2[$ptr2++];
            }
        }

        // Merge the remainder
        while ($ptr1 < count($array1)) {
            $array[] = $array1[$ptr1++];
        }
        while ($ptr2 < count($array2)) {
            $array[] = $array2[$ptr2++];
        }
    }
    
    /**
    * checks if mime type is provided by getimagesize()
    *
    * @deprecated
    */
    public static function deducibleSize(string $a_mime) : bool
    {
        if (($a_mime == "image/gif") || ($a_mime == "image/jpeg") ||
        ($a_mime == "image/png") || ($a_mime == "application/x-shockwave-flash") ||
        ($a_mime == "image/tiff") || ($a_mime == "image/x-ms-bmp") ||
        ($a_mime == "image/psd") || ($a_mime == "image/iff")) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @deprecated Use $DIC->ctrl()->redirectToURL() instead
     */
    public static function redirect(string $a_script) : void
    {
        global $DIC;

        if (!isset($DIC['ilCtrl']) || !$DIC['ilCtrl'] instanceof ilCtrl) {
            (new InitCtrlService())->init($DIC);
        }
        $DIC->ctrl()->redirectToURL($a_script);
    }

    /**
    * inserts installation id into ILIAS id
    *
    * e.g. "il__pg_3" -> "il_43_pg_3"
    *
    * @deprecated
    */
    public static function insertInstIntoID(string $a_value) : string
    {
        if (substr($a_value, 0, 4) == "il__") {
            $a_value = "il_" . IL_INST_ID . "_" . substr($a_value, 4, strlen($a_value) - 4);
        }

        return $a_value;
    }

    /**
    * checks if group name already exists. Groupnames must be unique for mailing purposes
    * static function
    *
    * @access	public
    * @param	string	groupname
    * @param	integer	obj_id of group to exclude from the check.
    * @return    boolean	true if exists
    * @static
    *
    */
    public static function groupNameExists(string $a_group_name, ?int $a_id = null) : bool
    {
        global $DIC;

        $ilDB = $DIC->database();

        $ilErr = null;
        if (isset($DIC["ilErr"])) {
            $ilErr = $DIC["ilErr"];
        }

        if (empty($a_group_name)) {
            $message = __METHOD__ . ": No groupname given!";
            $ilErr->raiseError($message, $ilErr->WARNING);
        }

        $clause = ($a_id !== null) ? " AND obj_id != " . $ilDB->quote($a_id) . " " : "";

        $q = "SELECT obj_id FROM object_data " .
        "WHERE title = " . $ilDB->quote($a_group_name, "text") . " " .
        "AND type = " . $ilDB->quote("grp", "text") .
        $clause;

        $r = $ilDB->query($q);
    
        return $r->numRows() > 0;
    }
    
    /**
     * @deprecated
     */
    public static function isWindows() : bool
    {
        return (strtolower(substr(php_uname(), 0, 3)) === "win");
    }


    public static function escapeShellArg(string $a_arg) : string
    {
        setlocale(LC_CTYPE, "UTF8", "en_US.UTF-8"); // fix for PHP escapeshellcmd bug. See: http://bugs.php.net/bug.php?id=45132
        // see also ilias bug 5630
        return escapeshellarg($a_arg);
    }

    /**
     * @deprecated
     */
    public static function escapeShellCmd(string $a_arg) : string
    {
        if (ini_get('safe_mode') == 1) {
            return $a_arg;
        }
        setlocale(LC_CTYPE, "UTF8", "en_US.UTF-8"); // fix for PHP escapeshellcmd bug. See: http://bugs.php.net/bug.php?id=45132
        return escapeshellcmd($a_arg);
    }

    /**
     * @deprecated
     */
    public static function execQuoted(string $cmd, ?string $args = null) : array
    {
        global $DIC;

        if (ilUtil::isWindows() && strpos($cmd, " ") !== false && substr($cmd, 0, 1) !== '"') {
            // cmd won't work without quotes
            $cmd = '"' . $cmd . '"';
            if ($args) {
                // args are also quoted, workaround is to quote the whole command AGAIN
                // was fixed in php 5.2 (see php bug #25361)
                if (version_compare(phpversion(), "5.2", "<") && strpos($args, '"') !== false) {
                    $cmd = '"' . $cmd . " " . $args . '"';
                }
                // args are not quoted or php is fixed, just append
                else {
                    $cmd .= " " . $args;
                }
            }
        }
        // nothing todo, just append args
        elseif ($args) {
            $cmd .= " " . $args;
        }
        $arr = [];
        exec($cmd, $arr);
        $DIC->logger()->root()->debug("ilUtil::execQuoted: " . $cmd . ".");
        return $arr;
    }
    


    /**
    * Return current timestamp in Y-m-d H:i:s format
    * @deprecated
    */
    public static function now() : string
    {
        return date("Y-m-d H:i:s");
    }
    
    
    /**
    * Get all objects of a specific type and check access
    * This function is not recursive, instead it parses the serialized rbac_pa entries
    *
    * Get all objects of a specific type where access is granted for the given
    * operation. This function does a checkAccess call for all objects
    * in the object hierarchy and return only the objects of the given type.
    * Please note if access is not granted to any object in the hierarchy
    * the function skips all objects under it.
    * Example:
    * You want a list of all Courses that are visible and readable for the user.
    * The function call would be:
    * $your_list = IlUtil::getObjectsByOperation ("crs", "visible");
    * Lets say there is a course A where the user would have access to according to
    * his role assignments. Course A lies within a group object which is not readable
    * for the user. Therefore course A won't appear in the result list although
    * the queried operations 'read' would actually permit the user
    * to access course A.
    *
    * @access	public
    * @param	string/array	object type 'lm' or array('lm','sahs')
    * @param	string	permission to check e.g. 'visible' or 'read'
    * @param	int id of user in question
    * @param    int limit of results. if not given it defaults to search max hits.If limit is -1 limit is unlimited
    * @return	array of ref_ids
    * @static
    *
    */
    public static function _getObjectsByOperations($a_obj_type, $a_operation, $a_usr_id = 0, $limit = 0)
    {
        global $DIC;

        $ilDB = $DIC->database();
        $rbacreview = $DIC->rbac()->review();
        $ilAccess = $DIC->access();
        $ilUser = $DIC->user();
        $ilSetting = $DIC->settings();
        $tree = $DIC->repositoryTree();

        if (!is_array($a_obj_type)) {
            $where = "WHERE type = " . $ilDB->quote($a_obj_type, "text") . " ";
        } else {
            $where = "WHERE " . $ilDB->in("type", $a_obj_type, false, "text") . " ";
        }

        // limit number of results default is search result limit
        if (!$limit) {
            $limit = $ilSetting->get('search_max_hits', 100);
        }
        if ($limit == -1) {
            $limit = 10000;
        }

        // default to logged in usr
        $a_usr_id = $a_usr_id ? $a_usr_id : $ilUser->getId();
        $a_roles = $rbacreview->assignedRoles($a_usr_id);

        // Since no rbac_pa entries are available for the system role. This function returns !all! ref_ids in the case the user
        // is assigned to the system role
        if ($rbacreview->isAssigned($a_usr_id, SYSTEM_ROLE_ID)) {
            $query = "SELECT ref_id FROM object_reference obr LEFT JOIN object_data obd ON obr.obj_id = obd.obj_id " .
                "LEFT JOIN tree ON obr.ref_id = tree.child " .
                $where .
                "AND tree = 1";

            $res = $ilDB->query($query);
            $counter = 0;
            $ref_ids = [];
            while ($row = $ilDB->fetchObject($res)) {
                // Filter recovery folder
                if ($tree->isGrandChild(RECOVERY_FOLDER_ID, $row->ref_id)) {
                    continue;
                }

                if ($counter++ >= $limit) {
                    break;
                }

                $ref_ids[] = $row->ref_id;
            }
            return $ref_ids;
        } // End Administrators

        // Check ownership if it is not asked for edit_permission or a create permission
        if ($a_operation == 'edit_permissions' or strpos($a_operation, 'create') !== false) {
            $check_owner = ") ";
        } else {
            $check_owner = "OR owner = " . $ilDB->quote($a_usr_id, "integer") . ") ";
        }

        $ops_ids = ilRbacReview::_getOperationIdsByName(array($a_operation));
        $ops_id = $ops_ids[0];

        $and = "AND ((" . $ilDB->in("rol_id", $a_roles, false, "integer") . " ";

        $query = "SELECT DISTINCT(obr.ref_id),obr.obj_id,type FROM object_reference obr " .
            "JOIN object_data obd ON obd.obj_id = obr.obj_id " .
            "LEFT JOIN rbac_pa  ON obr.ref_id = rbac_pa.ref_id " .
            $where .
            $and .
            "AND (" . $ilDB->like("ops_id", "text", "%i:" . $ops_id . "%") . " " .
            "OR " . $ilDB->like("ops_id", "text", "%:\"" . $ops_id . "\";%") . ")) " .
            $check_owner;

        $res = $ilDB->query($query);
        $counter = 0;
        $ref_ids = [];
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            if ($counter >= $limit) {
                break;
            }

            // Filter objects in recovery folder
            if ($tree->isGrandChild(RECOVERY_FOLDER_ID, $row->ref_id)) {
                continue;
            }

            // Check deleted, hierarchical access ...
            if ($ilAccess->checkAccessOfUser($a_usr_id, $a_operation, '', $row->ref_id, $row->type, $row->obj_id)) {
                $counter++;
                $ref_ids[] = $row->ref_id;
            }
        }
        return $ref_ids ?: [];
    }


    /**
    * Prepares a string for a text area output where latex code may be in it
    * If the text is HTML-free, CHR(13) will be converted to a line break
    *
    * @param string $txt_output String which should be prepared for output
    * @access public
    *
    */
    public static function prepareTextareaOutput($txt_output, $prepare_for_latex_output = false, $omitNl2BrWhenTextArea = false)
    {
        $result = $txt_output;
        $is_html = self::isHTML($result);

        // removed: did not work with magic_quotes_gpc = On
        if (!$is_html) {
            if (!$omitNl2BrWhenTextArea) {
                // if the string does not contain HTML code, replace the newlines with HTML line breaks
                $result = preg_replace("/[\n]/", "<br />", $result);
            }
        } else {
            // patch for problems with the <pre> tags in tinyMCE
            if (preg_match_all("/(\<pre>.*?\<\/pre>)/ims", $result, $matches)) {
                foreach ($matches[0] as $found) {
                    $replacement = "";
                    if (strpos("\n", $found) === false) {
                        $replacement = "\n";
                    }
                    $removed = preg_replace("/\<br\s*?\/>/ims", $replacement, $found);
                    $result = str_replace($found, $removed, $result);
                }
            }
        }

        // since server side mathjax rendering does include svg-xml structures that indeed have linebreaks,
        // do latex conversion AFTER replacing linebreaks with <br>. <svg> tag MUST NOT contain any <br> tags.
        if ($prepare_for_latex_output) {
            $result = ilMathJax::getInstance()->insertLatexImages($result, "\<span class\=\"latex\">", "\<\/span>");
            $result = ilMathJax::getInstance()->insertLatexImages($result, "\[tex\]", "\[\/tex\]");
        }

        if ($prepare_for_latex_output) {
            // replace special characters to prevent problems with the ILIAS template system
            // eg. if someone uses {1} as an answer, nothing will be shown without the replacement
            $result = str_replace("{", "&#123;", $result);
            $result = str_replace("}", "&#125;", $result);
            $result = str_replace("\\", "&#92;", $result);
        }

        return $result;
    }

    /**
     * Checks if a given string contains HTML or not
     *
     * @param string $a_text Text which should be checked
     * @return boolean
     * @access public
     * @static
     */
    public static function isHTML($a_text)
    {
        if (strlen(strip_tags($a_text)) < strlen($a_text)) {
            return true;
        }

        return false;
    }

    /**
    * Return a string of time period
    *
    * @param	  ilDateTime $a_from
    * @param	  ilDateTime $a_to
    * @return	 string
    * @static
    *
    */
    public static function period2String(ilDateTime $a_from, $a_to = null)
    {
        global $DIC;

        $lng = $DIC->language();

        if (!$a_to) {
            $a_to = new ilDateTime(time(), IL_CAL_UNIX);
        }

        $from = new DateTime($a_from->get(IL_CAL_DATETIME));
        $to = new DateTime($a_to->get(IL_CAL_DATETIME));
        $diff = $to->diff($from);

        $periods = array();
        $periods["years"] = $diff->format("%y");
        $periods["months"] = $diff->format("%m");
        $periods["days"] = $diff->format("%d");
        $periods["hours"] = $diff->format("%h");
        $periods["minutes"] = $diff->format("%i");
        $periods["seconds"] = $diff->format("%s");

        if (!array_sum($periods)) {
            return;
        }

        foreach ($periods as $key => $value) {
            if ($value) {
                $segment_name = ($value > 1)
                    ? $key
                    : substr($key, 0, -1);
                $array[] = $value . ' ' . $lng->txt($segment_name);
            }
        }

        $len = sizeof($array);
        if ($len > 3) {
            $array = array_slice($array, 0, (3 - $len));
        }

        return implode(', ', $array);
    }

    public static function formatBytes($size, $decimals = 0)
    {
        $unit = array('', 'K', 'M', 'G', 'T', 'P');

        for ($i = 0, $maxUnits = count($unit); $size >= 1024 && $i <= $maxUnits; $i++) {
            $size /= 1024;
        }

        return round($size, $decimals) . $unit[$i];
    }
   

    /**
    *  extract ref id from role title, e.g. 893 from 'il_crs_member_893'
    *	@param role_title with format like il_crs_member_893
    *	@return	ref id or false
    * @static
    *
    */

    public static function __extractRefId($role_title)
    {
        $test_str = explode('_', $role_title);

        if ($test_str[0] == 'il') {
            $test2 = (int) $test_str[3];
            return is_numeric($test2) ? (int) $test2 : false;
        }
        return false;
    }

    /**
    *  extract ref id from role title, e.g. 893 from 'il_122_role_893'
    *	@param ilias id with format like il_<instid>_<objTyp>_ID
    *   @param int inst_id  Installation ID must match inst id in param ilias_id
    *	@return	id or false
    * @static
    *
    *
    */

    public static function __extractId($ilias_id, $inst_id)
    {
        $test_str = explode('_', $ilias_id);

        if ($test_str[0] == 'il' && $test_str[1] == $inst_id && count($test_str) == 4) {
            $test2 = (int) $test_str[3];
            return is_numeric($test2) ? (int) $test2 : false;
        }
        return false;
    }

    /**
    * Function that sorts ids by a given table field using WHERE IN
    * E.g: __sort(array(6,7),'usr_data','lastname','usr_id') => sorts by lastname
    *
    * @param array Array of ids
    * @param string table name
    * @param string table field
    * @param string id name
    * @return array sorted ids
    *
    * @access protected
    * @static
    *
    */
    public static function _sortIds($a_ids, $a_table, $a_field, $a_id_name)
    {
        global $DIC;

        $ilDB = $DIC->database();

        if (!$a_ids) {
            return array();
        }

        // use database to sort user array
        $where = "WHERE " . $a_id_name . " IN (";
        $where .= implode(",", ilUtil::quoteArray($a_ids));
        $where .= ") ";

        $query = "SELECT " . $a_id_name . " FROM " . $a_table . " " .
            $where .
            "ORDER BY " . $a_field;

        $res = $ilDB->query($query);
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $ids[] = $row->$a_id_name;
        }
        return $ids ? $ids : array();
    }

   

    /**
    * Quotes all members of an array for usage in DB query statement.
    *
    * @static
    *
    */
    public static function quoteArray($a_array)
    {
        global $DIC;

        $ilDB = $DIC->database();


        if (!is_array($a_array) or !count($a_array)) {
            return array("''");
        }

        foreach ($a_array as $k => $item) {
            $a_array[$k] = $ilDB->quote($item);
        }

        return $a_array;
    }

    /**
     * Get HTML for a system message
     * @deprecated replace with UI Compoenten in ilGlobalPageTemplate
     * ATTENTION: This method is deprecated. Use MessageBox from the
     * UI-framework instead.
     */
    public static function getSystemMessageHTML($a_txt, $a_type = "info")
    {
        global $DIC;

        $lng = $DIC->language();
        $mtpl = new ilTemplate("tpl.message.html", true, true, "Services/Utilities");
        $mtpl->setCurrentBlock($a_type . "_message");
        $mtpl->setVariable("TEXT", $a_txt);
        $mtpl->setVariable("MESSAGE_HEADING", $lng->txt($a_type . "_message"));
        $mtpl->parseCurrentBlock();

        return $mtpl->get();
    }

    /**
    * Send Info Message to Screen.
    *
    * @param	string	message
    * @param	boolean	if true message is kept in session
    * @static
    *
    */
    public static function sendInfo($a_info = "", $a_keep = false)
    {
        global $DIC;

        $tpl = $DIC["tpl"];
        $tpl->setOnScreenMessage("info", $a_info, $a_keep);
    }

    /**
    * Send Failure Message to Screen.
    *
    * @param	string	message
    * @param	boolean	if true message is kept in session
    * @static
    *
    */
    public static function sendFailure($a_info = "", $a_keep = false)
    {
        global $DIC;

        if (isset($DIC["tpl"])) {
            $tpl = $DIC["tpl"];
            $tpl->setOnScreenMessage("failure", $a_info, $a_keep);
        }
    }

    /**
    * Send Question to Screen.
    *
    * @param	string	message
    * @param	boolean	if true message is kept in session
    * @static	*/
    public static function sendQuestion($a_info = "", $a_keep = false)
    {
        global $DIC;

        $tpl = $DIC["tpl"];
        $tpl->setOnScreenMessage("question", $a_info, $a_keep);
    }

    /**
    * Send Success Message to Screen.
    *
    * @param	string	message
    * @param	boolean	if true message is kept in session
    * @static
    *
    */
    public static function sendSuccess($a_info = "", $a_keep = false)
    {
        global $DIC;

        /** @var ilTemplate $tpl */
        $tpl = $DIC["tpl"];
        $tpl->setOnScreenMessage("success", $a_info, $a_keep);
    }

    public static function infoPanel($a_keep = true)
    {
        global $DIC;

        $tpl = $DIC["tpl"];
        $lng = $DIC->language();
        $ilUser = $DIC->user();

        if (!empty($_SESSION["infopanel"]) and is_array($_SESSION["infopanel"])) {
            $tpl->addBlockFile(
                "INFOPANEL",
                "infopanel",
                "tpl.infopanel.html",
                "Services/Utilities"
            );
            $tpl->setCurrentBlock("infopanel");

            if (!empty($_SESSION["infopanel"]["text"])) {
                $link = "<a href=\"" . $_SESSION["infopanel"]["link"] . "\" target=\"" .
                    ilFrameTargetInfo::_getFrame("MainContent") .
                    "\">";
                $link .= $lng->txt($_SESSION["infopanel"]["text"]);
                $link .= "</a>";
            }

            // deactivated
            if (!empty($_SESSION["infopanel"]["img"])) {
                $link .= "<td><a href=\"" . $_SESSION["infopanel"]["link"] . "\" target=\"" .
                    ilFrameTargetInfo::_getFrame("MainContent") .
                    "\">";
                $link .= "<img src=\"" . "./templates/" . $ilUser->prefs["skin"] . "/images/" .
                    $_SESSION["infopanel"]["img"] . "\" border=\"0\" vspace=\"0\"/>";
                $link .= "</a></td>";
            }

            $tpl->setVariable("INFO_ICONS", $link);
            $tpl->parseCurrentBlock();
        }

        //if (!$a_keep)
        //{
        ilSession::clear("infopanel");
        //}
    }
    
    public static function setCookie($a_cookie_name, $a_cookie_value = '', $a_also_set_super_global = true, $a_set_cookie_invalid = false)
    {
        /*
        if(!(bool)$a_set_cookie_invalid) $expire = IL_COOKIE_EXPIRE;
        else $expire = time() - (365*24*60*60);
        */
        // Temporary fix for feed.php
        if (!(bool) $a_set_cookie_invalid) {
            $expire = 0;
        } else {
            $expire = time() - (365 * 24 * 60 * 60);
        }
        /* We MUST NOT set the global constant here, because this affects the session_set_cookie_params() call as well
        if(!defined('IL_COOKIE_SECURE'))
        {
            define('IL_COOKIE_SECURE', false);
        }
        */
        $secure = false;
        if (defined('IL_COOKIE_SECURE')) {
            $secure = IL_COOKIE_SECURE;
        }

        setcookie(
            $a_cookie_name,
            $a_cookie_value,
            $expire,
            IL_COOKIE_PATH,
            IL_COOKIE_DOMAIN,
            $secure,
            IL_COOKIE_HTTPONLY
        );

        if ((bool) $a_also_set_super_global) {
            $_COOKIE[$a_cookie_name] = $a_cookie_value;
        }
    }
    
    public static function _getHttpPath()
    {
        global $DIC;

        $ilIliasIniFile = $DIC["ilIliasIniFile"];

        if ((isset($_SERVER['SHELL']) && $_SERVER['SHELL']) || PHP_SAPI === 'cli' ||
            // fallback for windows systems, useful in crons
            (class_exists("ilContext") && !ilContext::usesHTTP())) {
            return $ilIliasIniFile->readVariable('server', 'http_path');
        } else {
            return ILIAS_HTTP_PATH;
        }
    }

   

    /**
     * Parse an ilias import id
     * Typically of type il_[IL_INST_ID]_[OBJ_TYPE]_[OBJ_ID]
     * returns array(
     * 'orig' => 'il_4800_rolt_123'
     * 'prefix' => 'il'
     * 'inst_id => '4800'
     * 'type' => 'rolt'
     * 'id' => '123'
     *
     *
     * @param string il_id
     *
     */
    public static function parseImportId($a_import_id)
    {
        $exploded = explode('_', $a_import_id);

        $parsed['orig'] = $a_import_id;
        if ($exploded[0] == 'il') {
            $parsed['prefix'] = $exploded[0];
        }
        if (is_numeric($exploded[1])) {
            $parsed['inst_id'] = (int) $exploded[1];
        }
        $parsed['type'] = $exploded[2];

        if (is_numeric($exploded[3])) {
            $parsed['id'] = (int) $exploded[3];
        }
        return $parsed;
    }

   


  


    //
    //  used to be in ilFormat
    //

    /**
     * Returns the magnitude used for size units.
     *
     * This function always returns the value 1024. Thus the value returned
     * by this function is the same value that Windows and Mac OS X return for a
     * file. The value is a GibiBit, MebiBit, KibiBit or byte unit.
     *
     * For more information about these units see:
     * http://en.wikipedia.org/wiki/Megabyte
     *
     * @return <type>
     */
    protected static function _getSizeMagnitude()
    {
        return 1024;
    }

    /**
    * format a float
    *
    * this functions takes php's number_format function and
    * formats the given value with appropriate thousand and decimal
    * separator.
    * @access	public
    * @param	float		the float to format
    * @param	integer		count of decimals
    * @param	integer		display thousands separator
    * @param	boolean		whether .0 should be suppressed
    * @return	string		formatted number
    */
    protected static function fmtFloat($a_float, $a_decimals = 0, $a_dec_point = null, $a_thousands_sep = null, $a_suppress_dot_zero = false)
    {
        global $DIC;

        $lng = $DIC->language();

        if ($a_dec_point == null) {
            {
                $a_dec_point = ".";
            }
        }
        if ($a_dec_point == '-lang_sep_decimal-') {
            $a_dec_point = ".";
        }

        if ($a_thousands_sep == null) {
            $a_thousands_sep = $lng->txt('lang_sep_thousand');
        }
        if ($a_thousands_sep == '-lang_sep_thousand-') {
            $a_thousands_sep = ",";
        }

        $txt = number_format($a_float, $a_decimals, $a_dec_point, $a_thousands_sep);

        // remove trailing ".0"
        if (($a_suppress_dot_zero == 0 || $a_decimals == 0)
            && substr($txt, -2) == $a_dec_point . '0'
        ) {
            $txt = substr($txt, 0, strlen($txt) - 2);
        }
        if ($a_float == 0 and $txt == "") {
            $txt = "0";
        }

        return $txt;
    }

    /**
     * Returns the specified file size value in a human friendly form.
     * <p>
     * By default, the oder of magnitude 1024 is used. Thus the value returned
     * by this function is the same value that Windows and Mac OS X return for a
     * file. The value is a GibiBig, MebiBit, KibiBit or byte unit.
     * <p>
     * For more information about these units see:
     * http://en.wikipedia.org/wiki/Megabyte
     *
     * @param	integer	size in bytes
     * @param	string	mode:
     *                  "short" is useful for display in the repository
     *                  "long" is useful for display on the info page of an object
     * @param	ilLanguage  The language object, or null if you want to use the system language.
     */
    public static function formatSize($size, $a_mode = 'short', $a_lng = null)
    {
        global $DIC;

        $lng = $DIC->language();
        if ($a_lng == null) {
            $a_lng = $lng;
        }

        $mag = self::_getSizeMagnitude();

        if ($size >= $mag * $mag * $mag) {
            $scaled_size = $size / $mag / $mag / $mag;
            $scaled_unit = 'lang_size_gb';
        } else {
            if ($size >= $mag * $mag) {
                $scaled_size = $size / $mag / $mag;
                $scaled_unit = 'lang_size_mb';
            } else {
                if ($size >= $mag) {
                    $scaled_size = $size / $mag;
                    $scaled_unit = 'lang_size_kb';
                } else {
                    $scaled_size = $size;
                    $scaled_unit = 'lang_size_bytes';
                }
            }
        }

        $result = self::fmtFloat($scaled_size, ($scaled_unit
                                                == 'lang_size_bytes') ? 0 : 1, $a_lng->txt('lang_sep_decimal'), $a_lng->txt('lang_sep_thousand'), true)
                  . ' ' . $a_lng->txt($scaled_unit);
        if ($a_mode == 'long' && $size > $mag) {
            $result .= ' (' . self::fmtFloat($size, 0, $a_lng->txt('lang_sep_decimal'), $a_lng->txt('lang_sep_thousand')) . ' '
                       . $a_lng->txt('lang_size_bytes') . ')';
        }

        return $result;
    }


}
