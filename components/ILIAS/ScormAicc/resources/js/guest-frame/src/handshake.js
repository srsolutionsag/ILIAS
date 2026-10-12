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

/**
 * Message types of the handshake between the player (GuestFrameHost) and the
 * guest frame (GuestFrame).
 */
export const HELLO = 'guest-frame.hello';
export const INIT = 'guest-frame.init';

/**
 * Runtimes the guest frame may load, relative to assets/scorm/guest-frame.html.
 */
export const ADAPTERS = {
  scorm12: '../js/scorm12-guest.js',
  scorm2004: '../js/scorm2004-guest.js',
};

/**
 * @param {string} url
 * @param {string} base
 * @returns {string}
 */
export function originOf(url, base) {
  return new URL(url, base).origin;
}
