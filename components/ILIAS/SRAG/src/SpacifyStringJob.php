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
 */

declare(strict_types=1);

namespace ILIAS\SRAG;

use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractJob;
use ILIAS\BackgroundTasks\Types\Type;
use ILIAS\BackgroundTasks\Observer;
use ILIAS\BackgroundTasks\Value;
use ILIAS\BackgroundTasks\Types\SingleType;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\StringValue;

/**
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
class SpacifyStringJob extends AbstractJob
{
    public function run(array $input, Observer $observer): Value
    {
        $observer->notifyCurrentTask($this);

        [$string_value] = $input;

        $raw_value = $string_value->getValue();
        $max_length = strlen($raw_value);
        $new_value = '';

        foreach (mb_str_split($raw_value) as $num => $char) {
            sleep(3);
            $observer->notifyPercentage($this, (int) floor(100 / $max_length * $num));
            if (($num + 1) < $max_length) {
                $new_value .= "$char ";
            } else {
                $new_value .= $char;
            }
        }

        $output_value = new StringValue();
        $output_value->setValue($new_value);

        return $output_value;
    }

    public function isStateless(): bool
    {
        return true;
    }

    public function getExpectedTimeOfTaskInSeconds(): int
    {
        return 3_600;
    }

    public function getInputTypes(): array
    {
        return [new SingleType(StringValue::class),];
    }

    public function getOutputType(): Type
    {
        return new SingleType(StringValue::class);
    }
}
