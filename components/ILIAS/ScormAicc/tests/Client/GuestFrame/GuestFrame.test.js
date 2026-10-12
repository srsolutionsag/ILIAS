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
 ********************************************************************
 */

import { describe, it } from 'node:test';
import { strict } from 'node:assert/strict';
import Channel from '../../../../HTTP/resources/js/postmessage/src/Channel.js';
import GuestFrameHost from '../../../resources/js/guest-frame/src/GuestFrameHost.js';
import GuestFrame from '../../../resources/js/guest-frame/src/GuestFrame.js';

class FakeWindow {
  constructor(href) {
    this.location = new URL(href);
    this.listeners = [];
    this.parent = this;
  }

  addEventListener(type, listener) {
    this.listeners.push(listener);
  }

  removeEventListener(type, listener) {
    this.listeners = this.listeners.filter((l) => l !== listener);
  }

  postMessageFrom(sender) {
    return (data, targetOrigin) => {
      if (targetOrigin !== this.location.origin) {
        return;
      }
      setTimeout(() => this.listeners.forEach((l) => l({
        data, origin: sender.location.origin, source: sender,
      })), 0);
    };
  }
}

class FakeFrame {
  constructor() {
    this.src = '';
    this.listeners = [];
    this.contentWindow = null;
  }

  addEventListener(type, listener) {
    this.listeners.push(listener);
  }

  removeEventListener(type, listener) {
    this.listeners = this.listeners.filter((l) => l !== listener);
  }
}

class FakeDocument {
  constructor() {
    this.head = { appendChild: (script) => { this.loaded = script.src; } };
  }

  createElement() {
    return {};
  }
}

const GUEST_FRAME = 'https://content.example.org/ilias/assets/scorm/guest-frame.html';

/**
 * Wires a host page and a guest frame: once the host sets the src of the
 * iframe, the guest window is created with that address and booted.
 */
function setup() {
  const hostWindow = new FakeWindow('https://ilias.example.org/ilias/ilias.php');
  const iframe = new FakeFrame();
  const contentFrame = new FakeFrame();
  const document = new FakeDocument();
  const result = {
    hostWindow, iframe, contentFrame, document, guest: null,
  };

  const host = new GuestFrameHost(hostWindow, iframe, { guestFrame: GUEST_FRAME, adapter: 'scorm12', channel: 'c1' }, Channel);
  result.host = host;

  let src = '';
  Object.defineProperty(iframe, 'src', {
    get: () => src,
    set: (value) => {
      src = value;
      const guestWindow = new FakeWindow(value);
      guestWindow.parent = hostWindow;
      iframe.contentWindow = guestWindow;
      guestWindow.postMessage = guestWindow.postMessageFrom(hostWindow);
      hostWindow.postMessage = hostWindow.postMessageFrom(guestWindow);
      result.guest = new GuestFrame(guestWindow, document, contentFrame, Channel);
      result.guest.boot();
    },
  });
  return result;
}

describe('Guest frame and player', () => {
  it('derives the guest origin from the guest frame', () => {
    const { host } = setup();
    strict.equal(host.getGuestOrigin(), 'https://content.example.org');
  });

  it('hands over adapter, data and content after the handshake', async () => {
    const env = setup();
    let adapterData = null;
    GuestFrame.register('scorm12', (guest, data) => {
      adapterData = data;
      return { ready: true };
    });
    const answer = await env.host.start(
      'https://content.example.org/ilias/deliver.php/token/-/index.html',
      { user: 'x' },
    );
    strict.deepEqual(answer, { ready: true });
    strict.deepEqual(adapterData, { user: 'x' });
    strict.equal(env.contentFrame.src, 'https://content.example.org/ilias/deliver.php/token/-/index.html');
    strict.match(env.iframe.src, /^https:\/\/content\.example\.org\/ilias\/assets\/scorm\/guest-frame\.html#/);
    strict.doesNotMatch(env.iframe.src, /token/);
  });

  it('refuses content of other origins', async () => {
    const env = setup();
    GuestFrame.register('scorm12', () => null);
    await strict.rejects(
      env.host.start('https://evil.example.org/index.html'),
      /cannot be loaded/,
    );
  });

  it('loads unknown adapters from the public assets of its own origin', async () => {
    const env = setup();
    const hostWindow = new FakeWindow('https://ilias.example.org/ilias/ilias.php');
    const iframe = new FakeFrame();
    const host = new GuestFrameHost(hostWindow, iframe, { guestFrame: GUEST_FRAME, adapter: 'scorm2004', channel: 'c2' }, Channel);
    Object.defineProperty(iframe, 'src', {
      set: (value) => {
        const guestWindow = new FakeWindow(value);
        guestWindow.parent = hostWindow;
        iframe.contentWindow = guestWindow;
        guestWindow.postMessage = guestWindow.postMessageFrom(hostWindow);
        hostWindow.postMessage = hostWindow.postMessageFrom(guestWindow);
        new GuestFrame(guestWindow, env.document, env.contentFrame, Channel).boot();
      },
    });
    const started = host.start('https://content.example.org/ilias/x.html');
    await new Promise((resolve) => { setTimeout(resolve, 10); });
    strict.equal(env.document.loaded, 'https://content.example.org/ilias/assets/js/scorm2004-guest.js');
    GuestFrame.register('scorm2004', () => 'lazy-ready');
    strict.equal(await started, 'lazy-ready');
  });

  it('refuses adapters which are not known', async () => {
    const hostWindow = new FakeWindow('https://ilias.example.org/ilias/ilias.php');
    const iframe = new FakeFrame();
    const document = new FakeDocument();
    const host = new GuestFrameHost(hostWindow, iframe, { guestFrame: GUEST_FRAME, adapter: '../../evil', channel: 'c3' }, Channel);
    Object.defineProperty(iframe, 'src', {
      set: (value) => {
        const guestWindow = new FakeWindow(value);
        guestWindow.parent = hostWindow;
        iframe.contentWindow = guestWindow;
        guestWindow.postMessage = guestWindow.postMessageFrom(hostWindow);
        hostWindow.postMessage = hostWindow.postMessageFrom(guestWindow);
        new GuestFrame(guestWindow, document, new FakeFrame(), Channel).boot();
      },
    });
    await strict.rejects(host.start('https://content.example.org/x.html'), /Unknown adapter/);
    strict.equal(document.loaded, undefined);
  });
});

describe('Guest frame hardening', () => {
  it('initialises the guest frame only once per load', async () => {
    const env = setup();
    let calls = 0;
    GuestFrame.register('scorm12', () => { calls += 1; return calls; });
    const hostWindow = new FakeWindow('https://ilias.example.org/ilias/ilias.php');
    const iframe = new FakeFrame();
    const host = new GuestFrameHost(hostWindow, iframe, { guestFrame: GUEST_FRAME, adapter: 'scorm12', channel: 'c4' }, Channel);
    let guest = null;
    Object.defineProperty(iframe, 'src', {
      set: (value) => {
        const guestWindow = new FakeWindow(value);
        guestWindow.parent = hostWindow;
        iframe.contentWindow = guestWindow;
        guestWindow.postMessage = guestWindow.postMessageFrom(hostWindow);
        hostWindow.postMessage = hostWindow.postMessageFrom(guestWindow);
        guest = new GuestFrame(guestWindow, env.document, new FakeFrame(), Channel);
        guest.boot();
      },
    });
    await host.start('https://content.example.org/ilias/x.html');
    // content of the guest origin says hello again
    guest.channel.send('guest-frame.hello', {});
    await new Promise((resolve) => { setTimeout(resolve, 20); });
    strict.equal(calls, 1);
    // a real reload of the frame initialises it again
    iframe.listeners.forEach((l) => l());
    await new Promise((resolve) => { setTimeout(resolve, 20); });
    strict.equal(calls, 2);
  });

  it('does not boot if the embedding origin differs from the given one', () => {
    const window = new FakeWindow('https://content.example.org/assets/scorm/guest-frame.html#origin=https%3A%2F%2Filias.example.org');
    window.parent = new FakeWindow('https://evil.example.org/');
    window.location.ancestorOrigins = ['https://evil.example.org'];
    const guest = new GuestFrame(window, new FakeDocument(), new FakeFrame(), Channel);
    guest.boot();
    strict.equal(guest.channel, null);
  });

  it('boots if the embedding origin matches', () => {
    const window = new FakeWindow('https://content.example.org/assets/scorm/guest-frame.html#origin=https%3A%2F%2Filias.example.org');
    window.parent = new FakeWindow('https://ilias.example.org/');
    window.parent.postMessage = () => {};
    window.location.ancestorOrigins = ['https://ilias.example.org'];
    const guest = new GuestFrame(window, new FakeDocument(), new FakeFrame(), Channel);
    guest.boot();
    strict.notEqual(guest.channel, null);
  });
});

describe('Guest frame', () => {
  it('does not boot without parent or origin', () => {
    const window = new FakeWindow('https://content.example.org/guest-frame.html');
    const guest = new GuestFrame(window, new FakeDocument(), new FakeFrame(), Channel);
    guest.boot();
    strict.equal(guest.channel, null);
  });

  it('only loads content of its own origin', () => {
    const window = new FakeWindow('https://content.example.org/ilias/assets/scorm/guest-frame.html');
    const frame = new FakeFrame();
    const guest = new GuestFrame(window, new FakeDocument(), frame, Channel);
    guest.load('../../deliver.php/abc/-/index.html');
    strict.equal(frame.src, 'https://content.example.org/ilias/deliver.php/abc/-/index.html');
    strict.throws(() => guest.load('https://ilias.example.org/ilias/ilias.php'), /cannot be loaded/);
    strict.throws(() => guest.load('javascript:alert(1)'), /cannot be loaded/);
  });
});
