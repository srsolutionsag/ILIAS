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

namespace ILIAS\Certificate;

use ilObjCertificateSettings;
use ilUserCertificateRepository;
use ILIAS\ResourceStorage\Services as IRSS;
use ilCertificateTemplateDatabaseRepository;
use ILIAS\Certificate\File\ilCertificateTemplateStakeholder;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;

class CertificateResourceHandler
{
    public function __construct(
        private readonly ilUserCertificateRepository $user_certificate_repo,
        private readonly ilCertificateTemplateDatabaseRepository $certificate_template_repo,
        private readonly IRSS $irss,
        private readonly ilObjCertificateSettings $global_certificate_settings,
        private readonly ilCertificateTemplateStakeholder $stakeholder
    ) {
    }

    public function handleResourceChange(ResourceIdentification $background_image): void
    {
        if (
            !$this->user_certificate_repo->isResourceUsed($background_image->serialize()) &&
            !$this->certificate_template_repo->isResourceUsed($background_image->serialize()) &&
            (
                $this->global_certificate_settings->getBackgroundImageIdentification() === null ||
                $this->global_certificate_settings->getBackgroundImageIdentification()->serialize(
                ) !== $background_image->serialize()
            )
        ) {
            $this->irss->manage()->remove($background_image, $this->stakeholder);
        }
    }
}
