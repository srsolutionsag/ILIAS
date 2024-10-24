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

/**
 * @author  Niels Theen <ntheen@databay.de>
 */
class ilCertificateTemplateDeleteAction implements ilCertificateDeleteAction
{
    private readonly ilCertificateObjectHelper $objectHelper;

    public function __construct(
        private readonly ilCertificateTemplateRepository $templateRepository,
        private readonly string $iliasVersion = ILIAS_VERSION_NUMERIC,
        ?ilCertificateObjectHelper $objectHelper = null
    ) {
        if (null === $objectHelper) {
            $objectHelper = new ilCertificateObjectHelper();
        }
        $this->objectHelper = $objectHelper;
    }

    public function delete(int $templateId, int $objectId): void
    {
        $template = $this->templateRepository->fetchCurrentlyUsedCertificate($objectId);

        $this->templateRepository->deleteTemplate($templateId, $objectId);

        $version = $template->getVersion();
        $certificateTemplate = new ilCertificateTemplate(
            $objectId,
            $this->objectHelper->lookupType($objectId),
            '',
            hash('sha256', ''),
            '',
            $version + 1,
            $this->iliasVersion,
            time(),
            false,
            '',
            '',
            '',
            '',
        );

        $this->templateRepository->save($certificateTemplate);
    }
}
