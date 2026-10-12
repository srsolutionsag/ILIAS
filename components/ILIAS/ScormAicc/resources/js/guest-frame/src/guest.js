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

import GuestFrame from './GuestFrame.js';

// runs in assets/scorm/guest-frame.html after assets/js/postmessage-channel.js
window.il.ScormGuestFrame = {
  register: (name, factory) => GuestFrame.register(name, factory),
};

const guest = new GuestFrame(
  window,
  document,
  document.getElementById('il-scorm-guest-frame-content'),
  window.il.HTTP.Channel,
);
guest.boot();
