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

use ILIAS\ResourceStorage\Services as IRSS;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;
use ILIAS\Filesystem\Filesystem;

/**
 * @author  Niels Theen <ntheen@databay.de>
 */
class ilPdfGenerator
{
    private readonly ilCertificateRpcClientFactoryHelper $rpcHelper;
    private readonly ilCertificatePdfFileNameFactory $pdfFilenameFactory;

    public function __construct(
        private readonly ilUserCertificateRepository $certificateRepository,
        private ?IRSS $irss = null,
        private ?Filesystem $filesystem = null,
        ?ilCertificateRpcClientFactoryHelper $rpcHelper = null,
        ?ilCertificatePdfFileNameFactory $pdfFileNameFactory = null,
        ?ilLanguage $lng = null,
    ) {
        global $DIC;

        if (null === $irss) {
            $irss = $DIC->resourceStorage();
        }
        $this->irss = $irss;

        if (null === $this->filesystem) {
            $filesystem = $DIC->filesystem()->web();
        }
        $this->filesystem = $filesystem;

        if (null === $rpcHelper) {
            $rpcHelper = new ilCertificateRpcClientFactoryHelper();
        }
        $this->rpcHelper = $rpcHelper;

        if (null === $lng) {
            $lng = $DIC->language();
        }

        if (null === $pdfFileNameFactory) {
            $pdfFileNameFactory = new ilCertificatePdfFileNameFactory($lng);
        }
        $this->pdfFilenameFactory = $pdfFileNameFactory;
    }

    /**
     * @throws ilException
     */
    public function generate(int $userCertificateId): string
    {
        $certificate = $this->certificateRepository->fetchCertificate($userCertificateId);

        return $this->createPDFScalar($certificate);
    }

    /**
     * @throws ilException
     */
    public function generateCurrentActiveCertificate(int $userId, int $objId): string
    {
        $certificate = $this->certificateRepository->fetchActiveCertificate($userId, $objId);

        return $this->createPDFScalar($certificate);
    }

    /**
     * @throws ilDatabaseException
     * @throws ilException
     * @throws ilObjectNotFoundException
     */
    public function generateFileName(int $userId, int $objId): string
    {
        $certificate = $this->certificateRepository->fetchActiveCertificateForPresentation($userId, $objId);

        $user = ilObjectFactory::getInstanceByObjId($userId);
        if (!$user instanceof ilObjUser) {
            throw new ilException(sprintf('The usr_id "%s" does NOT reference a user', $userId));
        }

        return $this->pdfFilenameFactory->create($certificate);
    }

    private function createPDFScalar(ilUserCertificate $certificate): string
    {
        $certificateContent = $certificate->getCertificateContent();

        $background_rid = $this->irss->manage()->find($certificate->getCurrentBackgroundImageUsed());
        $background_src = '';
        if ($background_rid instanceof ResourceIdentification) {
            $background_src = $this->irss->consume()->src($background_rid)->getSrc();

            $certificateContent = str_replace(
                ['[BACKGROUND_IMAGE]'],
                [$background_src],
                $certificateContent
            );
        } elseif ($this->filesystem->has($certificate->getCurrentBackgroundImageUsed())) {
            $certificateContent = str_replace(
                ['[BACKGROUND_IMAGE]', '[CLIENT_WEB_DIR]'],
                ['[CLIENT_WEB_DIR]' . $certificate->getCurrentBackgroundImageUsed(), 'file://' . CLIENT_WEB_DIR],
                $certificateContent
            );
        }



        $pdf_base64 = $this->rpcHelper->ilFO2PDF('RPCTransformationHandler', $certificateContent);

        return $pdf_base64->scalar;
    }
}
