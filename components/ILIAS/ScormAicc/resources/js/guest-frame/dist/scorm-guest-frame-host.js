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
(function () {
  'use strict';

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
  const HELLO = 'guest-frame.hello';
  const INIT = 'guest-frame.init';

  /**
   * @param {string} url
   * @param {string} base
   * @returns {string}
   */
  function originOf(url, base) {
    return new URL(url, base).origin;
  }

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
   * Player side of the guest frame: lives in the SCORM player (ILIAS origin) and
   * loads the guest frame (assets/scorm/guest-frame.html on the origin of the
   * package) into the given iframe. The guest frame
   * loads the actual content and the requested adapter next to it, both talk to
   * the host through the channel only.
   */
  class GuestFrameHost {
    /** @type {HTMLIFrameElement} */
    #iframe;

    /** @type {string} */
    #guestFrame;

    /** @type {string} */
    #adapter;

    /** @type {string} */
    #ownOrigin;

    /** @type {string} */
    #name;

    /** @type {il.HTTP.Channel} */
    channel;

    /**
     * @param {Window} window
     * @param {HTMLIFrameElement} iframe
     * @param {{guestFrame: string, adapter: string, channel?: string}} options
     * @param {Function} Channel il.HTTP.Channel
     */
    constructor(window, iframe, options, Channel) {
      this.#iframe = iframe;
      this.#guestFrame = new URL(options.guestFrame, window.location.href).href;
      this.#adapter = options.adapter;
      this.#ownOrigin = window.location.origin;
      this.#name = options.channel ?? 'default';
      this.channel = new Channel(
        window,
        () => this.#iframe.contentWindow,
        originOf(this.#guestFrame, window.location.href),
        this.#name,
      );
    }

    /**
     * The origin the guest runs on, i.e. the origin of the guest frame.
     *
     * @returns {string}
     */
    getGuestOrigin() {
      return this.channel.getOrigin();
    }

    /**
     * Loads the guest frame and, once it reports back, hands over the adapter, the
     * content URL and the initial data. Resolves with the answer of the adapter.
     * The content URL never appears in the address of the guest frame, so tokens in it
     * do not leak via history or referrer.
     *
     * @param {string} src URL of the content, must be on the origin of the guest frame
     * @param {*} data initial data for the adapter
     * @param {number} timeout in milliseconds
     * @returns {Promise<*>}
     */
    start(src, data = null, timeout = 30000) {
      return new Promise((resolve, reject) => {
        const timer = setTimeout(
          () => reject(new Error('Frame bridge guest did not respond.')),
          timeout,
        );
        const init = () => this.channel
          .request(INIT, { adapter: this.#adapter, src, data }, timeout)
          .then(resolve, reject);
        // The guest frame is initialised once per load of the frame. Content
        // shares the origin of the guest frame and could send further hellos to
        // get the data again; they only count if the frame really loads anew.
        let initialised = false;
        let pending = false;
        this.#iframe.addEventListener('load', () => {
          if (pending) {
            pending = false;
            init();
          }
        });
        this.channel.on(HELLO, () => {
          if (initialised) {
            pending = true;
            return;
          }
          initialised = true;
          clearTimeout(timer);
          init();
        });
        const fragment = new URLSearchParams({ origin: this.#ownOrigin, channel: this.#name });
        this.#iframe.src = `${this.#guestFrame}#${fragment.toString()}`;
      });
    }

    destroy() {
      this.channel.destroy();
    }
  }

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

})();
