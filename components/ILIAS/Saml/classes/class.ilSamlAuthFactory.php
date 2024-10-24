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

use ILIAS\Filesystem\Exception\IOException;

/**
 * Class ilSamlAuthFactory
 */
final class ilSamlAuthFactory
{
    private const METADATA_PATH = 'auth/saml/config';

    /**
     * @throws Exception
     */
    public function auth(string $authSourceName = 'default-sp'): ilSamlAuth
    {
        return new ilSimpleSAMLphpWrapper(
            $authSourceName,
            $this->getConfigDirectory()
        );
    }

    /**
     * @throws IOException
     */
    public function getConfigDirectory(): string
    {
        global $DIC;

        $fs = $DIC->filesystem()->storage();

        if (!$fs->hasDir(self::METADATA_PATH)) {
            $fs->createDir(self::METADATA_PATH);
        }

        return rtrim(ilFileUtils::getDataDir(), '/') . '/' . self::METADATA_PATH;
    }
}
