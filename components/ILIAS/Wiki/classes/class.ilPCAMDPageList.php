<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

/**
 * Class ilPCAMDPageList
 *
 * Advanced MD page list content object (see ILIAS DTD)
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 */
class ilPCAMDPageList extends ilPageContent
{
    protected \ILIAS\Wiki\InternalDomainService $wiki_domain;
    protected ilDBInterface $db;
    protected ilLanguage $lng;

    public function init(): void
    {
        global $DIC;

        $this->db = $DIC->database();
        $this->lng = $DIC->language();
        $this->setType("amdpl");
        $this->wiki_domain = $DIC->wiki()->internal()->domain();
    }

    public static function getLangVars(): array
    {
        return array("ed_insert_amd_page_list", "pc_amdpl");
    }

    public function create(
        ilPageObject $a_pg_obj,
        string $a_hier_id,
        string $a_pc_id = ""
    ): void {
        $this->createPageContentNode();
        $a_pg_obj->insertContent($this, $a_hier_id, IL_INSERT_AFTER, $a_pc_id);
        $amdpl_node = $this->dom_doc->createElement("AMDPageList");
        $amdpl_node = $this->getDomNode()->appendChild($amdpl_node);
    }

    public function setData(
        array $a_fields_data,
        int $a_mode = null
    ): void {
        $ilDB = $this->db;

        $data_id = $this->getChildNode()->getAttribute("Id");
        if ($data_id) {
            $ilDB->manipulate("DELETE FROM pg_amd_page_list" .
                " WHERE id = " . $ilDB->quote($data_id, "integer"));
        } else {
            $data_id = $ilDB->nextId("pg_amd_page_list");
            $this->getChildNode()->setAttribute("Id", $data_id);
        }

        $this->getChildNode()->setAttribute("Mode", (int) $a_mode);

        foreach ($a_fields_data as $field_id => $field_data) {
            $fields = array(
                "id" => array("integer", $data_id)
                ,"field_id" => array("integer", $field_id)
                ,"sdata" => array("text", serialize($field_data))
            );
            $ilDB->insert("pg_amd_page_list", $fields);
        }
    }

    public function getMode(): int
    {
        if (is_object($this->getChildNode())) {
            return (int) $this->getChildNode()->getAttribute("Mode");
        }
        return 0;
    }

    /**
     * Get filter field values
     */
    public function getFieldValues(
        int $a_data_id = null
    ): array {
        $ilDB = $this->db;

        $res = array();

        if (!$a_data_id) {
            if (is_object($this->getChildNode())) {
                $a_data_id = $this->getChildNode()->getAttribute("Id");
            }
        }

        if ($a_data_id) {
            $set = $ilDB->query("SELECT * FROM pg_amd_page_list" .
                " WHERE id = " . $ilDB->quote($a_data_id, "integer"));
            while ($row = $ilDB->fetchAssoc($set)) {
                $res[$row["field_id"]] = unserialize((string) $row["sdata"], ["allowed_classes" => false]);
            }
        }
        return $res;
    }
    public static function handleCopiedContent(
        DOMDocument $a_domdoc,
        bool $a_self_ass = true,
        bool $a_clone_mobs = false,
        int $new_parent_id = 0,
        int $obj_copy_id = 0
    ): void {
        global $DIC;

        $ilDB = $DIC->database();
        $old_id = 0;
        $node = null;

        // #15688

        $xpath = new DOMXPath($a_domdoc);
        $nodes = $xpath->query("//AMDPageList");
        foreach ($nodes as $node) {
            $old_id = $node->getAttribute("Id");
            break;
        }

        if ($old_id) {
            $new_id = $ilDB->nextId("pg_amd_page_list");

            $set = $ilDB->query("SELECT * FROM pg_amd_page_list" .
                    " WHERE id = " . $ilDB->quote($old_id, "integer"));
            while ($row = $ilDB->fetchAssoc($set)) {
                $fields = array(
                    "id" => array("integer", $new_id)
                    ,"field_id" => array("integer", $row["field_id"])
                    ,"sdata" => array("text", $row["sdata"])
                );
                $ilDB->insert("pg_amd_page_list", $fields);
            }

            $node->setAttribute("Id", $new_id);
        }
    }


    //
    // presentation
    //

    protected function findPages(
        int $a_list_id
    ): array {
        $ilDB = $this->db;

        $list_values = $this->getFieldValues($a_list_id);

        /** @var ilWikiPage $wpage */
        $wpage = $this->getPage();
        $wiki_id = $wpage->getWikiId();
        $wiki_ref_id = $wpage->getWikiRefId();

        $found_result = array();

        // only search in active fields
        $found_ids = null;
        $recs = ilAdvancedMDRecord::_getSelectedRecordsByObject("wiki", $wiki_ref_id, "wpg");
        foreach ($recs as $record) {
            foreach (ilAdvancedMDFieldDefinition::getInstancesByRecordId($record->getRecordId(), true) as $field) {
                if (isset($list_values[$field->getFieldId()]) && $list_values[$field->getFieldId()] !== "") {
                    $field_form = ilADTFactory::getInstance()->getSearchBridgeForDefinitionInstance($field->getADTDefinition(), true, false);
                    $field->setSearchValueSerialized($field_form, $list_values[$field->getFieldId()]);
                    $found_pages = $field->searchSubObjects($field_form, $wiki_id, "wpg");
                    if (is_array($found_ids)) {
                        $found_ids = array_intersect($found_ids, $found_pages);
                    } else {
                        $found_ids = $found_pages;
                    }
                }
            }
        }

        if (is_array($found_ids) && count($found_ids) > 0) {
            $sql = "SELECT id,title FROM il_wiki_page" .
                " WHERE " . $ilDB->in("id", $found_ids, "", "integer") .
                " AND lang = " . $ilDB->quote($wpage->getLanguage(), "text") .
                " ORDER BY title";
            $set = $ilDB->query($sql);
            while ($row = $ilDB->fetchAssoc($set)) {
                $found_result[$row["id"]] = $row["title"];
            }
        }

        return $found_result;
    }

    public function modifyPageContentPostXsl(
        string $a_output,
        string $a_mode,
        bool $a_abstract_only = false
    ): string {
        if ($this->getPage()->getParentType() !== "wpg") {
            return $a_output;
        }
        $end = 0;
        $wiki_id = $this->getPage()->getWikiId();
        $pm = $this->wiki_domain->page()->page($this->getPage()->getWikiRefId());

        $start = strpos($a_output, "[[[[[AMDPageList;");
        if (is_int($start)) {
            $end = strpos($a_output, "]]]]]", $start);
        }
        while ($end > 0) {
            $parts = explode(";", substr($a_output, $start + 17, $end - $start - 17));

            $list_id = (int) $parts[0];
            $list_mode = (count($parts) === 2)
                ? (int) $parts[1]
                : 0;

            $ltpl = new ilTemplate("tpl.wiki_amd_page_list.html", true, true, "components/ILIAS/Wiki");

            $pages = $this->findPages($list_id);
            if (count($pages)) {
                $ltpl->setCurrentBlock("page_bl");
                foreach ($pages as $page_id => $page_title) {
                    // see ilWikiUtil::makeLink()
                    $frag = new stdClass();
                    $frag->mFragment = null;
                    $frag->mTextform = $page_title;

                    $href = $pm->getPermaLink($page_id, $this->getPage()->getLanguage());
                    $ltpl->setVariable("PAGE", "<a href='" . $href . "'>" . $page_title . "</a>");
                    $ltpl->parseCurrentBlock();
                }
            } else {
                $ltpl->touchBlock("no_hits_bl");
            }

            $ltpl->setVariable("LIST_MODE", $list_mode ? "ol" : "ul");

            $a_output = substr($a_output, 0, $start) .
                $ltpl->get() .
                substr($a_output, $end + 5);

            $start = strpos($a_output, "[[[[[AMDPageList;", $start + 5);
            $end = 0;
            if (is_int($start)) {
                $end = strpos($a_output, "]]]]]", $start);
            }
        }

        return $a_output;
    }

    /**
     * Migrate search/filter values on advmd change. In the mapping, keys are
     * indices of old options, and values indices of associated new options.
     * @param int   $a_field_id
     * @param string[] $mapping
     */
    public static function migrateField(
        int $a_field_id,
        array $mapping
    ): void {
        global $DIC;

        $ilDB = $DIC->database();

        // this does only work for select and select multi

        $set = $ilDB->query("SELECT * FROM pg_amd_page_list" .
            " WHERE field_id = " . $ilDB->quote($a_field_id, "integer"));
        while ($row = $ilDB->fetchAssoc($set)) {
            $data = unserialize(unserialize((string) $row["sdata"], ["allowed_classes" => false]), ["allowed_classes" => false]);
            if (!is_array($data)) {
                continue;
            }
            $updated_data = $data;
            foreach ($mapping as $old_option => $new_option) {
                if (!in_array($old_option, $data)) {
                    continue;
                }
                $idx = array_search($old_option, $data);
                if ($new_option !== '') {
                    $updated_data[$idx] = $new_option;
                } else {
                    unset($updated_data[$idx]);
                }
            }

            $serialized_updated_data = serialize(empty($updated_data) ? '' : serialize($updated_data));

            $fields = array(
                "sdata" => array("text", $serialized_updated_data)
            );
            $primary = array(
                "id" => array("integer", $row["id"]),
                "field_id" => array("integer", $row["field_id"])
            );
            $ilDB->update("pg_amd_page_list", $fields, $primary);
        }
    }
}
