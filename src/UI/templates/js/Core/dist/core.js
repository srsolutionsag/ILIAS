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
(function (il, document) {
  'use strict';

  function _interopDefaultLegacy (e) { return e && typeof e === 'object' && 'default' in e ? e : { 'default': e }; }

  var il__default = /*#__PURE__*/_interopDefaultLegacy(il);
  var document__default = /*#__PURE__*/_interopDefaultLegacy(document);

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
   * This represents one tooltip on the page.
   */
  class Tooltip {
    /**
     * @type {HTMLElement}
     */
    #tooltip;

    /**
     * The tooltip element itself.
     * @type {Element}
     */
    #element;

    /**
     * The container of the tooltip and the trigger element.
     * @type {Element}
     */
    #container;

    /**
     * The HTMLDocument this all exists inside.
     * @type {HTMLDocument}
     */
    #document;

    /**
     * The Window through which we see that stuff.
     * @type {Window}
     */
    #window;

    /**
     * This will be the "main"-container if the tooltip is inside one.
     * @type {?Element}
     */
    #main = null;

    constructor(element) {
      this.#container = element.parentElement;
      this.#element = element;
      this.#document = element.ownerDocument;
      this.#window = this.#document.defaultView || this.#document.parentWindow;

      var tooltip_id = this.#element.getAttribute("aria-describedby");
      if (tooltip_id === null) {
        throw new Error("Could not find expected attribute aria-describedby for element with tooltip.");
      }

      this.#tooltip = this.#document.getElementById(tooltip_id);
      if (this.#tooltip === null) {
        throw new Error("Tooltip " + tooltip_id + " not found.", { cause: this.#element });
      }

      let main = getVisibleMainElement(this.#document);
      if (null !== main && main.contains(this.#tooltip)) {
        this.#main = main;
      }

      this.showTooltip = this.showTooltip.bind(this);
      this.hideTooltip = this.hideTooltip.bind(this);
      this.onKeyDown = this.onKeyDown.bind(this);
      this.onPointerDown = this.onPointerDown.bind(this);

      this.bindElementEvents();
      this.bindContainerEvents();
    }

    /**
     * @returns {HTMLElement}
     */
    get tooltip() {
      return this.#tooltip;
    }

    /**
     * @returns {undefined}
     */
    showTooltip() {
      this.#container.classList.add("c-tooltip--visible");
      this.bindDocumentEvents();

      this.checkVerticalBounds();
      this.checkHorizontalBounds();
    }

    /**
     * @returns {undefined}
     */
    hideTooltip() {
      this.#container.classList.remove("c-tooltip--visible");
      this.unbindDocumentEvents();

      this.#container.classList.remove("c-tooltip--top");
      this.#tooltip.style.transform = null;
    }

    /**
     * @returns {undefined}
     */
    bindElementEvents() {
      this.#element.addEventListener("focus", this.showTooltip);
      this.#element.addEventListener("blur", this.hideTooltip);
    }

    /**
     * @returns {undefined}
     */
    bindContainerEvents() {
      this.#container.addEventListener("mouseenter", this.showTooltip);
      this.#container.addEventListener("touchstart", this.showTooltip);
      this.#container.addEventListener("mouseleave", this.hideTooltip);
    }

    /**
     * @returns {undefined}
     */
    bindDocumentEvents() {
      this.#document.addEventListener("keydown", this.onKeyDown);
      this.#document.addEventListener("pointerdown", this.onPointerDown);
    }

    /**
     * @returns {undefined}
     */
    unbindDocumentEvents() {
      this.#document.removeEventListener("keydown", this.onKeyDown);
      this.#document.removeEventListener("pointerdown", this.onPointerDown);
    }

    /**
     * @returns {undefined}
     */
    onKeyDown(event) {
      if (event.key === "Esc" || event.key === "Escape") {
        this.hideTooltip();
      }
    }

    /**
     * @returns {undefined}
     */
    onPointerDown(event) {
      if (event.target === this.#element || event.target === this.#tooltip) {
        event.preventDefault();
      } else {
        this.hideTooltip();
        this.#element.blur();
      }
    }

    /**
     * @returns {undefined}
     */
    checkVerticalBounds() {
      var ttRect = this.#tooltip.getBoundingClientRect();
      var dpRect = this.getDisplayRect();

      if (ttRect.bottom > (dpRect.top + dpRect.height)) {
        this.#container.classList.add("c-tooltip--top");
      }
    }

    /**
     * @returns {undefined}
     */
    checkHorizontalBounds() {
      var ttRect = this.#tooltip.getBoundingClientRect();
      var dpRect = this.getDisplayRect();

      if ((dpRect.width - dpRect.left) < ttRect.right) {
        this.#tooltip.style.transform = "translateX(" + ((dpRect.width - dpRect.left) - ttRect.right) + "px)";
      }
      if (ttRect.left < dpRect.left) {
        this.#tooltip.style.transform = "translateX(" + ((dpRect.left - ttRect.left) - ttRect.width / 2) + "px)";
      }
    }

    /**
     * @returns {{left: number, top: number, width: number, height: number}}
     */
    getDisplayRect() {
      if (this.#main !== null) {
        return this.#main.getBoundingClientRect();
      }

      return {
        left: 0,
        top: 0,
        width: this.#window.innerWidth,
        height: this.#window.innerHeight
      }
    }
  }

  /**
   * Returns the visible main-element of the given document.
   *
   * A document may contain multiple main-elemets, only one must be visible
   * (not have a hidden-attribute).
   *
   * @param {HTMLDocument} document
   * @returns {HTMLElement|null}
   * @see https://html.spec.whatwg.org/multipage/grouping-content.html#the-main-element
   */
  function getVisibleMainElement(document) {
    const main_elements = document.getElementsByTagName("main");
    const visible_main = main_elements.find((element) => !element.hasOwnProperty('hidden'));

    return (undefined !== visible_main) ? visible_main : null;
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
   *
   * @author Thibeau Fuhrer <thibeau@sr.solutions>
   */

  /**
   * Hydrates all components marked by tge 'c-component--unhydrated' class in the
   * given document.
   *
   * @param {HydrationRegistry} registry
   * @param {HTMLElement} element
   */
  function hydrateComponents(registry, element) {
    const components = element.querySelectorAll('.c-component--unhydrated');
    for (let i = 0; i < components.length; i += 1) {
      const component = components.item(i);
      if (!component.hasAttribute('data-hydrated-by')) {
        continue;
      }

      const hydratorId = component.getAttribute('data-hydrated-by');
      const hydrator = registry.getHydrator(hydratorId);
      if (hydrator !== null) {
        hydrator(component.firstElementChild);
      }

      component.classList.remove('c-component--unhydrated');
      component.classList.add('c-component--hydrated');
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

  /**
   * @author Thibeau Fuhrer <thibeau@sr.solutions>
   */
  class AsyncRenderer {
    /** @type {HydrationRegistry} */
    #hydrationRegistry;

    /** @type {HTMLDocument} */
    #document;

    /**
     * @param {HydrationRegistry} hydrationRegistry
     * @param {HTMLDocument} document
     */
    constructor(hydrationRegistry, document) {
      this.#hydrationRegistry = hydrationRegistry;
      this.#document = document;
    }

    /**
     * @param {string} renderUrl
     * @param {HTMLElement} element
     */
    replaceContent(renderUrl, element) {
      this.#fetchElement(renderUrl).then(
        (newElement) => {
          element.replaceChildren(newElement);
        }
      );
    }

    /**
     * @param {string} renderUrl
     * @param {HTMLElement} element
     */
    appendContent(renderUrl, element) {
      this.#fetchElement(renderUrl).then(
        (newElement) => {
          element.appendChild(newElement);
        }
      );
    }

    /**
     * Asynchronously rendered <script> tags must be restored in order to be
     * executed when added to the DOM by e.g. HTMLElement.appendChild().
     *
     * This method only preserves a <script> tags 'src' and 'type' attributes,
     * along with the scripts content. All other attributes are discarded.
     *
     * @param {HTMLScriptElement} script
     * @returns {HTMLScriptElement}
     */
    #restoreScript(script) {
      const newScript = this.#document.createElement('div');

      if (script.hasAttribute('type')) {
        newScript.setAttribute('type', script.getAttribute('type'));
      }
      if (script.hasAttribute('src')) {
        newScript.setAttribute('src', script.getAttribute('src'));
      }
      if (script.textContent.length > 0) {
        newScript.textContent = script.textContent;
      }

      return newScript;
    }

    /**
     * @param {string} html
     * @returns {HTMLElement}
     */
    #createElement(html) {
      const newElement = this.#document.createElement('div');
      newElement.innerHTML = html.trim();

      // restore possible <script> tags in the new element.
      newElement.firstElementChild.querySelectorAll('script').forEach((oldScript) => {
        newElement.firstElementChild.appendChild(this.#restoreScript(oldScript));
        oldScript.remove();
      });

      return newElement.firstElementChild;
    }

    /**
     * @param {string} url
     * @returns {Promise<HTMLElement>}
     * @throws {Error} if the request with fetch() failed
     */
    #fetchElement(url) {
      return fetch(url).then(
        (response) => response.text()
      ).then(
        (html) => this.#createElement(html)
      ).catch(
        (error) => {
          throw new Error(`Could not create element from '${url}': ${error.message}`)
        }
      );
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

  /**
   * This registry will be used to remember hydrators, functions which hydrate or
   * initialize the JavaScript of a component, mapped to an id.
   *
   * The general management of said ids is of no concern to this object. It is
   * merely a global registry, available to all UI components, used to store
   * functions which can be used several times by several components.
   *
   * @author Thibeau Fuhrer <thibeau@sr.solutions>
   */
  class HydrationRegistry {
    /** @type {Map<string, {function(HTMLElement)}>} */
    #hydrators = new Map();

    /**
     * @param {string} id
     * @param {function(HTMLElement)} hydrator
     */
    addHydrator(id, hydrator) {
      if (this.#hydrators.has(id)) {
        throw new Error(`Hydrator with id "${id}" already exists.`);
      }

      this.#hydrators.set(id, hydrator);
    }

    /**
     * @param {string} id
     * @returns {function(HTMLElement)|null}
     */
    getHydrator(id) {
      if (this.#hydrators.has(id)) {
        return this.#hydrators.get(id);
      }

      return null;
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

  il__default["default"].UI = il__default["default"].UI || {};
  il__default["default"].UI.HydrationRegistry = new HydrationRegistry();
  il__default["default"].UI.AsyncRenderer = new AsyncRenderer(il__default["default"].UI.HydrationRegistry, document__default["default"]);
  il__default["default"].UI.Tooltip = Tooltip;
  il__default["default"].UI.hydrateComponents = (element) => {
    hydrateComponents(il__default["default"].UI.HydrationRegistry, element);
  };

})(il, document);
