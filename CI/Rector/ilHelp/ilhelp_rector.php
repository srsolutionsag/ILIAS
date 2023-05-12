<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use ILIAS\CI\Rector\DIC\DICMemberResolver;
use ILIAS\CI\Rector\DIC\DICDependencyManipulator;
use Rector\Config\RectorConfig;
use ILIAS\CI\Rector\ilHelp\ReplaceHelpMethodsRector;
use ILIAS\CI\Rector\ilHelp\IntroduceHelpScreenIdRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->disableParallel();
    $rectorConfig->parameters()->set(Option::SKIP, [
        // there a several classes which make Rector break (multiple classes
        // in one file, wrong declarations in inheritance, ...)
//        "Modules/LTIConsumer",
        "Services/LTI",
        "Services/SOAPAuth/include"
    ]);

    $rectorConfig->rule(ReplaceHelpMethodsRector::class);
    $rectorConfig->rule(IntroduceHelpScreenIdRector::class);
};
