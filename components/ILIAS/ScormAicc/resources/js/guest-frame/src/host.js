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

import GuestFrameHost from './GuestFrameHost.js';

// needs il.HTTP.Channel (assets/js/postmessage-channel.js)
window.il = window.il || {};
window.il.ScormGuestFrame = {
  /**
   * @param {HTMLIFrameElement} iframe
   * @param {{guestFrame: string, adapter: string, channel?: string}} options
   * @returns {GuestFrameHost}
   */
  host: (iframe, options) => new GuestFrameHost(window, iframe, options, window.il.HTTP.Channel),
};
