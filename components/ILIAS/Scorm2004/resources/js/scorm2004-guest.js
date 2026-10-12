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
 * SCORM 2004 runtime for the guest frame, see ScormAicc README ("SCORM and User
 * Content Isolation").
 *
 * Runs in the guest frame next to the SCO, so the SCO finds API_1484_11 in its
 * parent window as before. The runtime itself is the unchanged rte-min.js of
 * the player (loaded from the same origin as this guest frame); afterwards the
 * functions it expects from the player page are replaced: they hand the data
 * to the player page (scripts/rtemain/bridge.js), which runs sequencing and
 * storing.
 */
(function (window, document, il) {
  // the same scripts as on the player page (tpl.scorm2004.player.html), relative
  // to assets/scorm/guest-frame.html
  var SCRIPTS = [
    '../js/jquery.js',
    '../../components/ILIAS/Scorm2004/scripts/buildrte/rte-min.js'
  ];

  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      var script = document.createElement('script');
      script.src = new URL(src, window.location.href).href;
      script.onload = resolve;
      script.onerror = function () {
        reject(new Error('SCORM 2004 runtime could not be loaded: ' + src));
      };
      document.head.appendChild(script);
    });
  }

  function loadRuntime() {
    return SCRIPTS.reduce(function (previous, src) {
      return previous.then(function () { return loadScript(src); });
    }, Promise.resolve());
  }

  function copyOf(data) {
    return JSON.parse(JSON.stringify(data));
  }

  function setUp(guest, init) {
    var channel = guest.channel;
    var pending = null;
    var apiName = init.apiName || window.Runtime.apiname;

    function flush() {
      if (pending) {
        channel.send('commit', pending);
        pending = null;
      }
    }

    // globals of the player page the runtime (rte.js) reads
    window.config = init.cfg || {};
    window.gConfig = {debug_fields: init.debugFields || [], debug_fields_test: init.debugFieldsTest || []};
    window.logActive = false;

    // rte.js toggles this element of the player page
    var noCredit = document.createElement('div');
    noCredit.id = 'noCredit';
    noCredit.hidden = true;
    document.body.appendChild(noCredit);

    // Commit() of the runtime calls syncCMIADLTree(), onCommit(), saveSharedData()
    // and save(); the player page runs them with the data sent here.
    window.syncCMIADLTree = function () { return []; };
    window.saveSharedData = function () {
      if (pending) pending.shared = true;
      return true;
    };
    window.save = function () {
      if (pending) pending.save = true;
      return true;
    };
    window.sendLogEntry = function () {
      channel.send('log', copyOf(Array.prototype.slice.call(arguments)));
    };
    window.createSummary = function () {
      channel.send('summary', null);
    };
    window.sclog = function () {};
    window.sclogdump = function () {};
    window.sclogflush = function () {};
    // the player page updates its navigation when the termination arrives there
    window.Runtime.onTerminate = function () {};

    function onCommitOfRuntime(cmiItem) {
      pending = {cmiItem: copyOf(cmiItem), save: false, shared: false};
      // save() and saveSharedData() are called right after this callback
      Promise.resolve().then(flush);
      return true;
    }

    function onTerminateOfRuntime(cmiItem) {
      flush();
      channel.send('terminated', {cmiItem: copyOf(cmiItem)});
      return true;
    }

    channel.on('deliver', function (payload) {
      return guest.unload().then(function () {
        window.logActive = Boolean(payload.logActive);
        window.saved_score_scaled = payload.savedScoreScaled || 0;
        window.saveOnCommit = true;
        if (payload.cmiItem) {
          window.currentAPI = window[apiName] = new window.Runtime(payload.cmiItem, onCommitOfRuntime, onTerminateOfRuntime);
        } else {
          window.currentAPI = window[apiName] = null;
        }
        guest.load(payload.url);
        return true;
      });
    });

    channel.on('unload', function () {
      return guest.unload();
    });

    channel.on('setIntern', function (payload) {
      if (window.currentAPI) {
        window.currentAPI.SetValueIntern(payload.path, payload.value);
      }
    });

    channel.on('logActive', function (value) {
      window.logActive = Boolean(value);
    });
  }

  il.ScormGuestFrame.register('scorm2004', function (guest, init) {
    return loadRuntime().then(function () {
      setUp(guest, init);
      return {ready: true};
    });
  });
}(window, document, window.il));
