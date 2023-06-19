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

// ============================================================
// BEGIN: core bundle
// ============================================================

il.UI = il.UI || {};
il.UI.JavaScriptBinding = Object.create({
  functions: new Map(),
  getFunction(id) {
    if (this.functions.has(id)){
      return this.functions.get(id);
    }
    return null;
  },
  addFunction(id, fn) {
    this.functions.set(id, fn);
  },
  getHydrator(id) {
    return this.getFunction(id);
  },
  addHydrator(id, fn) {
    return this.addFunction(id, fn);
  },
  createId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
  }
});

function hydrateElement(element) {
  if (!element.hasAttribute('data-hydrated-by')) {
    return;
  }

  const hydrator = il.UI.JavaScriptBinding.getHydrator(element.getAttribute('data-hydrated-by'));
  if (hydrator !== null) {
    hydrator(element.firstElementChild);
  }

  if (element.firstElementChild.hasAttribute('id')) {
    const customFunction = il.UI.JavaScriptBinding.getFunction(element.firstElementChild.getAttribute('id'));
    if (customFunction !== null) {
      customFunction(element.firstElementChild);
    }
  }

  element.classList.remove('c-component--unhydrated');
  element.classList.add('c-component--hydrated');
}

il.UI.hydrateComponents = function hydrateComponents(element) {
  if (element.classList.contains('c-component--unhydrated')) {
    hydrateElement(element);
    return;
  }

  const components = element.querySelectorAll('.c-component--unhydrated');
  for (let i = 0; i < components.length; i += 1) {
    hydrateElement(components.item(i));
  }
};

  // ============================================================
// END: core bundle
// ============================================================

// ============================================================
// BEGIN: counter example
// ============================================================

il.UI.Counter = il.UI.Counter || {};
il.UI.Counter.createCounter = function createCounter(element) {
  const counterElement = element.querySelector('span');
  let currentCount = parseInt(counterElement.textContent, 10);

  element.querySelectorAll('button').forEach((button) => {
    button.addEventListener('click', () => {
      switch (button.textContent) {
        case '+': currentCount += 1; break;
        case '-': currentCount -= 1; break;
        default: return;
      }

      counterElement.textContent = currentCount;
    });
  });
};

il.UI.JavaScriptBinding.addHydrator('counter/createCounter', il.UI.Counter.createCounter);

// ============================================================
// END: counter example
// ============================================================

// ============================================================
// BEGIN: list example
// ============================================================

il.UI.List = il.UI.List || {};

il.UI.List.Renderer = class Renderer {
  #listElement;
  #template;
  #parentId;

  constructor(listElement, template, parentId) {
    this.#listElement = listElement;
    this.#template = template;
    this.#parentId = parentId;
  }

  addListItem() {
    const clone = this.#template.content.firstElementChild.cloneNode(true);

    this.#listElement.prepend(clone);

    for (let counter = 0; counter < Infinity; counter += 1) {
      const currentId = 'placeholder_id_' + counter;
      const element = clone.querySelector(`#${currentId}`);
      if (element === null) {
        break;
      }

      const newId = il.UI.JavaScriptBinding.createId();
      element.id = newId;

      const customFunction = il.UI.JavaScriptBinding.getFunction(`${this.#parentId}/${currentId}`);
      if (null !== customFunction) {
        customFunction(element);
      }
    }

    il.UI.hydrateComponents(clone);

    return clone;
  }
};

il.UI.List.createList = function createList(element) {
  const template = element.querySelector('template');
  const renderer = new il.UI.List.Renderer(element, template, element.id);
  console.log(renderer);

  element.querySelector('button')?.addEventListener('click', () => {
    renderer.addListItem();
  });
};

il.UI.JavaScriptBinding.addHydrator('list/createList', il.UI.List.createList);

// ============================================================
// END: list example
// ============================================================

// ============================================================
// BEGIN: signal example
// ============================================================

il.UI.Signal = il.UI.Signal || {};
il.UI.Signal.createTriggerer = function createTriggerer(element) {
  if (!element.hasAttribute('data-triggered-signals')) {
    return;
  }

  const signals = JSON.parse(element.getAttribute('data-triggered-signals'));
  if (signals instanceof Array) {
    return;
  }

  signals.forEach((signal) => {
    if (!signal.hasOwnProperty('id') ||
      !signal.hasOwnProperty('event') ||
      !signal.hasOwnProperty('options')
    ) {
      return;
    }

    document.addEventListener(signal.event, (event) => {
      document.dispatchEvent(
        new Event(signal.id, Object.assign(signal.options, event))
      )
    });
  });
};

il.UI.Signal.createListener = function createListener(element) {
  if (!element.hasAttribute('data-triggering-signals')) {
    return;
  }

  const signals = JSON.parse(element.getAttribute('data-triggering-signals'));
  if (signals instanceof Array) {
    return;
  }

  signals.forEach((signal) => {
    document.addEventListener(signal, (signalData) => {
      // call the function with the signal data
      // get function by mapping of sorts.
    });
  });
};

il.UI.JavaScriptBinding.addHydrator('signal/createTriggerer', il.UI.Signal.createTriggerer);
il.UI.JavaScriptBinding.addHydrator('signal/createListener', il.UI.Signal.createListener);

// ============================================================
// END: signal example
// ============================================================
