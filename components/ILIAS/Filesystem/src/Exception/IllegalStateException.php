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

namespace ILIAS\Filesystem\Exception;

/**
 * The IllegalStateException indicates a wrong state of the object.
 *
 * Example:
 * A tape recorder can't record and seek at the same time because of the sequential access of the tape. Therefore an
 * IllegalStateException is thrown to inform the programmer about the fact that the object is not ready to perform the requested operation due to
 * its internal state. The reason why the exception is called illegal state is due to the fact that the state "play + seek" would be illegal.
 *
 * @author                 Nicolas Schäfli <ns@studer-raimann.ch>
 * @author                 Fabian Schmid <fabian@sr.solutions>
 */
class IllegalStateException extends \RuntimeException
{
}
