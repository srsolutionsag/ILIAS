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

declare(strict_types=1);

namespace ILIAS\MediaPool;

use ILIAS\MetaData\Services\ServicesInterface as LOMServices;
use ILIAS\MetaData\Repository\Search\Clauses\Mode;
use ILIAS\MetaData\Repository\Search\Clauses\Operator;

/**
 * Media pool repository
 * @author Alexander Killing <killing@leifos.de>
 */
class MediaPoolRepository
{
    protected \ilDBInterface $db;
    protected LOMServices $lom_services;

    public function __construct(\ilDBInterface $db = null)
    {
        global $DIC;
        $this->db = ($db) ?: $DIC->database();
        $this->lom_services = $DIC->learningObjectMetadata();
    }

    /**
     * @return array[]
     */
    protected function getMediaObjects(
        int $pool_id,
        string $title_filter = "",
        string $format_filter = "",
        string $keyword_filter = '',
        string $caption_filter = ""
    ): array {
        $db = $this->db;

        $query = "SELECT DISTINCT mep_tree.*, object_data.* " .
            "FROM mep_tree JOIN mep_item ON (mep_tree.child = mep_item.obj_id) " .
            " JOIN object_data ON (mep_item.foreign_id = object_data.obj_id) ";

        if ($format_filter != "" or $caption_filter != '') {
            $query .= " JOIN media_item ON (media_item.mob_id = object_data.obj_id) ";
        }

        $query .=
            " WHERE mep_tree.mep_id = " . $db->quote($pool_id, "integer") .
            " AND object_data.type = " . $db->quote("mob", "text");

        // filter
        if (trim($title_filter) != "") {    // title
            $query .= " AND " . $db->like("object_data.title", "text", "%" . trim($title_filter) . "%");
        }
        if (!in_array($format_filter, ["", "mob"])) {            // format
            $filter = ($format_filter === "unknown")
                ? ""
                : $format_filter;
            $query .= " AND " . $db->equals("media_item.format", $filter, "text", true);
        }
        if (trim($caption_filter)) {
            $query .= 'AND ' . $db->like('media_item.caption', 'text', '%' . trim($caption_filter) . '%');
        }

        $query .=
            " ORDER BY object_data.title";

        $objs = [];
        $obj_ids = [];
        $set = $db->query($query);
        while ($rec = $db->fetchAssoc($set)) {
            $obj_ids[] = $rec['obj_id'];
            $rec["foreign_id"] = $rec["obj_id"];
            $rec["obj_id"] = "";
            $objs[] = $rec;
        }

        // Keyword filter
        if ($keyword_filter) {
            $res = $this->filterSubIDsByLOMKeywords($keyword_filter, 0, 'mob', ...$obj_ids);
            $filtered = [];
            foreach ($objs as $obj) {
                if (in_array($obj['foreign_id'], $res)) {
                    $filtered[] = $obj;
                }
            }
            return (array) $filtered;
        }
        return $objs;
    }

    /**
     * @return array[]
     */
    protected function getContentSnippets(
        int $pool_id,
        string $title_filter = "",
        string $format_filter = "",
        string $keyword_filter = '',
        string $caption_filter = ""
    ): array {
        // format filter snippets come with internal "pg" format
        if (!in_array($format_filter, ["pg", ""])) {
            return [];
        }

        // snippets do not have no caption
        if ($caption_filter != "") {
            return [];
        }

        $db = $this->db;

        $query = "SELECT DISTINCT mep_tree.*, mep_item.* " .
            "FROM mep_tree JOIN mep_item ON (mep_tree.child = mep_item.obj_id) ";

        $query .=
            " WHERE mep_tree.mep_id = " . $db->quote($pool_id, "integer") .
            " AND mep_item.type = " . $db->quote("pg", "text");

        // filter
        if (trim($title_filter) != "") {    // title
            $query .= " AND " . $db->like("mep_item.title", "text", "%" . trim($title_filter) . "%");
        }

        $objs = [];
        $obj_ids = [];
        $set = $db->query($query);
        while ($rec = $db->fetchAssoc($set)) {
            //$rec["foreign_id"] = $rec["obj_id"];
            //$rec["obj_id"] = "";
            $objs[] = $rec;
            $obj_ids[] = $rec['obj_id'];
        }

        // Keyword filter
        if ($keyword_filter) {
            $res = $this->filterSubIDsByLOMKeywords($keyword_filter, $pool_id, 'mpg', ...$obj_ids);
            $filtered = [];
            foreach ($objs as $obj) {
                if (in_array($obj['obj_id'], $res)) {
                    $filtered[] = $obj;
                }
            }
            return $filtered;
        }
        return $objs;
    }

    /**
     * @return array[]
     */
    public function getItems(
        int $pool_id,
        string $title_filter = "",
        string $format_filter = "",
        string $keyword_filter = "",
        string $caption_filter = ""
    ): array {
        $mobs = $this->getMediaObjects(
            $pool_id,
            $title_filter,
            $format_filter,
            $keyword_filter,
            $caption_filter
        );

        $snippets = $this->getContentSnippets(
            $pool_id,
            $title_filter,
            $format_filter,
            $keyword_filter,
            $caption_filter
        );

        return \ilArrayUtil::sortArray(array_merge($mobs, $snippets), "title", "asc");
    }

    /**
     * @return int[]
     */
    protected function filterSubIDsByLOMKeywords(
        string $keywords,
        int $pool_id,
        string $type,
        int ...$sub_ids
    ): array {
        if (trim($keywords) === "" || empty($sub_ids)) {
            return $sub_ids;
        }

        $searcher = $this->lom_services->search();
        $paths = $this->lom_services->paths();

        $basic_clauses = [];
        foreach (explode(' ', $keywords) as $keyword) {
            $basic_clauses = $searcher->getClauseFactory()->getBasicClause(
                $paths->keywords(),
                Mode::CONTAINS,
                $keyword
            );
        }
        $search_clause = $searcher->getClauseFactory()->getJoinedClauses(
            Operator::OR,
            ...$basic_clauses
        );

        $filters = [];
        foreach ($sub_ids as $sub_id) {
            $filters[] = $searcher->getFilter($pool_id, $sub_id, $type);
        }

        $search_results = $searcher->execute($search_clause, null, null, ...$filters);
        $filtered_sub_ids = [];
        foreach ($search_results as $result) {
            $filtered_sub_ids[] = $result->subID();
        }
        return $filtered_sub_ids;
    }
}
