<?php declare(strict_types=1);

/* Copyright (c) 2022 Thibeau Fuhrer <thibeau@sr.solutions> Extended GPL, see docs/LICENSE */

/**
 * @author       Thibeau Fuhrer <thibeau@sr.solutions>
 * @noinspection AutoloadingIssuesInspection
 */
class ilFileObjectDatabaseObjective implements ilDatabaseUpdateSteps
{
    private ?ilDBInterface $database = null;

    public function prepare(ilDBInterface $db) : void
    {
        $this->database = $db;
    }

    /**
     * adds a new table column called 'direct_download' that is used to
     * determine if the on-click action in the ilObjFileListGUI should
     * download the file directly or redirect to the objects info-page.
     * ---
     * NOTE: this won't affect the default-behaviour which currently
     * downloads the file directly, since '1' or true is added as the
     * default value to the new column.
     */
    public function step_1() : void
    {
        $this->abortIfNotPrepared();
        if ($this->database->tableExists('file_data')) {
            $this->database->addTableColumn(
                'file_data',
                'on_click_mode',
                [
                    'type' => 'integer',
                    'length' => '1',
                    'notnull' => '1',
                    'default' => ilObjFile::CLICK_MODE_DOWNLOAD,
                ]
            );
        }
    }

    /**
     * Halts the execution of these update steps if no database was
     * provided.
     * @throws LogicException if the database update steps were not
     *                        yet prepared.
     */
    private function abortIfNotPrepared() : void
    {
        if (null === $this->database) {
            throw new LogicException(self::class . "::prepare() must be called before db-update-steps execution.");
        }
    }
}
