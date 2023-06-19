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
export default class HydratorRegistry {
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
