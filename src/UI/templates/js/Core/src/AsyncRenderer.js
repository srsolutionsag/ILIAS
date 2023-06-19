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

import hydrateComponents from './hydrateComponents';

/**
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
export default class ContentReplacer {
  /** @type {HydrationRegistry} */
  #hydrationRegistry;

  /** @type {DOMParser} */
  #domParser;

  /** @type {HTMLDocument} */
  #document;

  /**
   * @param {HydrationRegistry} hydrationRegistry
   * @param {DOMParser} domParser
   * @param {HTMLDocument} document
   */
  constructor(hydrationRegistry, domParser, document) {
    this.#hydrationRegistry = hydrationRegistry;
    this.#domParser = domParser;
    this.#document = document;
  }

  /**
   * @param {string} renderUrl
   * @param {string} replaceMarker
   * @throws {Error} if the element for the given marker could not be found, or
   *                 if the request with fetch() failed.
   */
  replaceContent(renderUrl, replaceMarker) {
    const markerElement = this.#document.querySelector(`[data-replace-marker="${replaceMarker}"]`);
    if (markerElement === null) {
      throw new Error(`Could not find element with marker '${replaceMarker}'.`);
    }

    fetch(renderUrl).then(
      (response) => response.text()
    ).then(
      (html) => {
        markerElement.firstElementChild.remove();
        this.#renderContent(markerElement, html);
      }
    ).catch(
      (error) => throw new Error(`Could not fetch valid HTML from '${renderUrl}': ${error.message}`)
    );
  }

  /**
   * @param {HTMLElement} element
   * @param {string} html
   */
  #renderContent(element, html) {
    const newElement = this.#createElement(html);
    element.appendChild(newElement);
    hydrateComponents(this.#hydrationRegistry, element);
  }

  /**
   * @param {string} html
   * @returns {HTMLElement}
   */
  #createElement(html) {
    return this.#domParser.parseFromString(html, 'text/html').body.firstElementChild;
  }
}
