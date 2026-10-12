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
   * Runtimes the guest frame may load, relative to assets/scorm/guest-frame.html.
   */
  const ADAPTERS = {
    scorm12: '../js/scorm12-guest.js',
    scorm2004: '../js/scorm2004-guest.js',
  };

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
   * The guest frame (assets/scorm/guest-frame.html) on the origin of the package.
   * It loads the SCORM runtime the player asks for (one of ADAPTERS, from the
   * same origin) and the SCO into its own iframe. SCO and runtime share the
   * origin, so the SCO finds the API in window.parent as before.
   */
  class GuestFrame {
    /** @type {Map<string, function(GuestFrame, *): *>} */
    static #adapters = new Map();

    /** @type {Map<string, function(function(GuestFrame, *): *): void>} */
    static #waiting = new Map();

    /** @type {Window} */
    #window;

    /** @type {Document} */
    #document;

    /** @type {HTMLIFrameElement} */
    #frame;

    /** @type {Function} */
    #Channel;

    /** @type {?il.HTTP.Channel} */
    channel = null;

    /**
     * Called by the runtime scripts to make themselves available.
     *
     * @param {string} name
     * @param {function(GuestFrame, *): *} factory
     */
    static register(name, factory) {
      GuestFrame.#adapters.set(name, factory);
      const waiting = GuestFrame.#waiting.get(name);
      if (waiting) {
        GuestFrame.#waiting.delete(name);
        waiting(factory);
      }
    }

    /**
     * @param {Window} window
     * @param {Document} document
     * @param {HTMLIFrameElement} frame the iframe the content is loaded into
     * @param {Function} Channel il.HTTP.Channel
     */
    constructor(window, document, frame, Channel) {
      this.#window = window;
      this.#document = document;
      this.#frame = frame;
      this.#Channel = Channel;
    }

    /**
     * Opens the channel to the parent window and announces readiness.
     */
    boot() {
      const params = new URLSearchParams(this.#window.location.hash.substring(1));
      const origin = params.get('origin');
      const name = params.get('channel') ?? 'default';
      if (this.#window.parent === this.#window || origin === null) {
        return;
      }
      // Where the browser tells who embeds this frame, it has to be the origin
      // the guest frame was told to talk to (location.ancestorOrigins is not
      // available in all browsers, the channel checks the origin of every
      // message anyway).
      const ancestors = this.#window.location.ancestorOrigins;
      if (ancestors && ancestors.length > 0 && ancestors[0] !== origin) {
        return;
      }
      this.channel = new this.#Channel(this.#window, () => this.#window.parent, origin, name);
      this.channel.on(INIT, (payload) => this.#init(payload));
      this.channel.send(HELLO, {});
    }

    /**
     * @returns {HTMLIFrameElement}
     */
    getFrame() {
      return this.#frame;
    }

    /**
     * @returns {Window}
     */
    getWindow() {
      return this.#window;
    }

    /**
     * Loads content into the frame. Only URLs of the own origin are accepted,
     * the guest frame must not be turned into a frame for arbitrary sites.
     *
     * @param {string} src
     */
    load(src) {
      const url = new URL(src, this.#window.location.href);
      if (url.origin !== this.#window.location.origin) {
        throw new Error(`Content of origin ${url.origin} cannot be loaded into the frame bridge.`);
      }
      this.#frame.src = url.href;
    }

    /**
     * Unloads the current content, so its unload handlers run while the guest frame
     * is still alive. Resolves once the frame is empty.
     *
     * @returns {Promise<void>}
     */
    unload() {
      return new Promise((resolve) => {
        const done = () => {
          this.#frame.removeEventListener('load', done);
          resolve();
        };
        this.#frame.addEventListener('load', done);
        this.#frame.src = 'about:blank';
      });
    }

    /**
     * @param {{adapter: string, src: ?string, data: *}} payload
     * @returns {Promise<*>}
     */
    async #init(payload) {
      const factory = await this.#loadAdapter(String(payload?.adapter ?? ''));
      const result = await factory(this, payload.data ?? null);
      if (payload?.src) {
        this.load(payload.src);
      }
      return result;
    }

    /**
     * @param {string} name
     * @returns {Promise<function(GuestFrame, *): *>}
     */
    #loadAdapter(name) {
      if (!Object.prototype.hasOwnProperty.call(ADAPTERS, name)) {
        return Promise.reject(new Error(`Unknown adapter: ${name}`));
      }
      if (GuestFrame.#adapters.has(name)) {
        return Promise.resolve(GuestFrame.#adapters.get(name));
      }
      return new Promise((resolve, reject) => {
        GuestFrame.#waiting.set(name, resolve);
        const script = this.#document.createElement('script');
        script.src = new URL(ADAPTERS[name], this.#window.location.href).href;
        script.onerror = () => {
          GuestFrame.#waiting.delete(name);
          reject(new Error(`Adapter ${name} could not be loaded.`));
        };
        this.#document.head.appendChild(script);
      });
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

})();
