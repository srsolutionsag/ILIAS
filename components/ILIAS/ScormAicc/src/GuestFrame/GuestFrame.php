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

namespace ILIAS\ScormAicc\GuestFrame;

/**
 * The SCORM runtime runs in a guest frame on the origin of the package (e.g.
 * the content domain of User Content Isolation), next to the SCO. See README.
 *
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class GuestFrame
{
    public const SCRIPTS = [
        './assets/js/postmessage-channel.js',
        './assets/js/scorm-guest-frame-host.js',
    ];

    private const PATH = 'assets/scorm/guest-frame.html';

    /**
     * @param string $http_path the URL this installation is reached under, e.g. ILIAS_HTTP_PATH
     */
    public function __construct(
        private string $http_path,
    ) {
    }

    /**
     * URL of the guest frame on the origin of the package. Packages addressed
     * relatively are served from the origin of the player, the guest frame URL
     * is relative then as well.
     *
     * @throws \InvalidArgumentException for URLs other than http(s)
     */
    public function getURL(string $package_url): string
    {
        if (!preg_match('#^([a-z][a-z0-9+.\-]*:|//)#i', $package_url)) {
            return './' . self::PATH;
        }

        $parts = parse_url($package_url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || ($parts['host'] ?? '') === '') {
            throw new \InvalidArgumentException("$package_url cannot be embedded.");
        }

        $origin = $scheme . '://' . strtolower($parts['host']) . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $path = rtrim((string) (parse_url($this->http_path, PHP_URL_PATH) ?? ''), '/');

        return $origin . $path . '/' . self::PATH;
    }
}
