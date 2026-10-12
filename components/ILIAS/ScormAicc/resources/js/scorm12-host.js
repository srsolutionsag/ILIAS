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
 * SCORM 1.2 frameset (ILIAS origin). The runtime (basisAPI.js and
 * SCORM1_2standard.js, unchanged) runs next to the SCO in a guest frame which
 * replaces the content frame "sahs_content", see scorm12-guest.js and the
 * README ("SCORM and User Content Isolation"). This page keeps the session
 * alive itself. This page keeps what
 * needs ILIAS: requests with the session, the icons of the tree frame and the
 * debug frame.
 *
 * Data from the guest frame is as trustworthy as the SCO, which shares its
 * origin: requests are only made to the endpoints listed in IliasScormBridge,
 * received strings are never inserted as HTML.
 */
(function (window, document, il) {
  'use strict';

  const config = window.IliasScormBridge;
  const vars = window.IliasScormVars;
  const statuses = new Map();
  const debugRows = [];
  const logTimes = [];
  let queue = Promise.resolve();
  let frame = null;

  function absolute(url) {
    return new URL(url, window.location.href).href;
  }

  function decode(value) {
    try {
      return decodeURIComponent(value);
    } catch (e) {
      return String(value);
    }
  }

  function frameDocument(name) {
    try {
      return window.frames[name] ? window.frames[name].document : null;
    } catch (e) {
      return null;
    }
  }

  // see status4tree() in basisAPI.js
  function applyStatus(sco) {
    const tree = frameDocument('tree');
    const icon = tree ? tree.getElementsByName(`scoIcon${sco}`)[0] : null;
    const entry = statuses.get(sco);
    if (!icon || !entry) {
      return;
    }
    let status = entry.status;
    if (status === null || status === undefined || status === 'not attempted') {
      status = 'not_attempted';
    }
    if (!Object.prototype.hasOwnProperty.call(vars.img, status)) {
      return;
    }
    icon.src = decode(vars.img[status]);
    if (status !== 'asset') {
      let title = `${decode(vars.statusTxt.status)}: ${decode(vars.statusTxt[status] ?? status)}`;
      if (entry.time !== null && entry.time !== undefined) {
        title += ` (${entry.time})`;
      }
      icon.title = title;
    }
  }

  function status(payload) {
    const sco = Number(payload?.sco);
    if (!Number.isInteger(sco)) {
      return;
    }
    statuses.set(sco, { status: payload.status, time: payload.time });
    applyStatus(sco);
  }

  // see showCalls() in basisAPI.js
  function debug(row) {
    const log = frameDocument('logframe');
    const container = log ? log.getElementById('APIcalls') : null;
    if (!container) {
      return;
    }
    debugRows.unshift([row?.ms, row?.call, row?.result, row?.error, row?.diagnostic]);
    const table = log.createElement('table');
    table.cellPadding = 0;
    const head = table.insertRow();
    head.className = 'd';
    ['ms', 'sent to API', 'returns', 'error', 'error string; diagnostic'].forEach((text) => {
      head.insertCell().textContent = text;
    });
    debugRows.forEach((values) => {
      const tr = table.insertRow();
      values.forEach((value) => {
        tr.insertCell().textContent = value === undefined || value === null ? '' : String(value);
      });
    });
    container.replaceChildren(table);
  }

  function post(url, body) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: String(body ?? ''),
    }).then((response) => response.text());
  }

  /**
   * Requests of the runtime (sendRequest() in basisAPI.js). Only these kinds
   * exist, the URLs are the ones of this page.
   */
  function request(payload) {
    const kind = payload?.kind;
    if (kind === 'store') {
      // one after the other, so a later commit is never overtaken
      const result = queue.then(() => post(absolute(config.urls.store), payload.data));
      queue = result.catch(() => {});
      return result.then((text) => text.trim());
    }
    if (kind === 'message' || kind === 'warning') {
      const now = Date.now();
      while (logTimes.length > 0 && now - logTimes[0] > 60000) {
        logTimes.shift();
      }
      if (logTimes.length >= 20) {
        return null;
      }
      logTimes.push(now);
      post(absolute(config.urls[kind]), String(payload.data ?? '').substring(0, 4096)).catch(() => {});
      return null;
    }
    return null;
  }

  // see SchedulePing() in basisAPI.js; the runtime in the guest frame has no session
  if (vars.pingSession > 0) {
    window.setInterval(() => {
      fetch(absolute(config.urls.ping), { credentials: 'same-origin' }).catch(() => {});
    }, vars.pingSession * 1000);
  }

  // the tree frame calls parent.API.IliasLaunch(), see ilSCORMExplorer
  window.API = {
    IliasLaunch(id) {
      if (frame) {
        frame.channel.send('launch', { sco: Number(id) });
      }
      return false;
    },
  };

  window.addEventListener('load', () => {
    const contentFrame = document.getElementsByName('sahs_content')[0];
    const tree = document.getElementsByName('tree')[0];
    if (tree) {
      tree.addEventListener('load', () => statuses.forEach((value, sco) => applyStatus(sco)));
    }

    // the guest frame does not share the base of this page
    const init = {
      apiName: config.apiName,
      vars: { ...vars, dataDirectory: encodeURIComponent(absolute(decode(vars.dataDirectory))) },
      resources: window.IliasScormResources,
      tree: window.IliasScormTree,
      data: window.IliasScormData,
      // requests made by the guest frame itself while it is unloaded
      base: absolute('./'),
    };

    frame = il.ScormGuestFrame.host(contentFrame, {
      guestFrame: absolute(config.guestFrame),
      adapter: 'scorm12',
      channel: 'scorm',
    });
    frame.channel.on('request', request);
    frame.channel.on('status', status);
    frame.channel.on('debug', debug);
    frame.start(null, init).catch((e) => {
      // eslint-disable-next-line no-console
      console.error(`SCORM guest frame: ${e.message}`);
    });
  });
}(window, document, window.il));
