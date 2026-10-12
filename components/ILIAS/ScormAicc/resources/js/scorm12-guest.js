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
 * SCORM 1.2 runtime for the guest frame, see README ("SCORM and User Content
 * Isolation").
 *
 * Runs in the guest frame next to the SCO, which finds the API in its parent
 * window as before. The runtime itself is the unchanged basisAPI.js and
 * SCORM1_2standard.js (loaded from the same origin as this guest frame);
 * afterwards the few functions that need the frameset (requests, tree icons,
 * debug frame) are replaced: they hand their work to the frameset through the
 * channel, see scorm12-host.js.
 */
(function (window, document, il) {
  // relative to assets/scorm/guest-frame.html
  var SCRIPTS = [
    '../../components/ILIAS/ScormAicc/scripts/basisAPI.js',
    '../../components/ILIAS/ScormAicc/scripts/SCORM1_2standard.js'
  ];

  // what the API object of the former apiInitData() provided
  var API_FUNCTIONS = [
    'LMSInitialize', 'LMSFinish', 'LMSGetValue', 'LMSSetValue', 'LMSCommit',
    'LMSGetLastError', 'LMSGetErrorString', 'LMSGetDiagnostic',
    'IliasLaunch', 'IliasAbortSco', 'IliasWaitLaunch', 'IliasWaitTree', 'SchedulePing'
  ];

  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      var script = document.createElement('script');
      script.src = new URL(src, window.location.href).href;
      script.onload = resolve;
      script.onerror = function () {
        reject(new Error('SCORM 1.2 runtime could not be loaded: ' + src));
      };
      document.head.appendChild(script);
    });
  }

  function loadRuntime() {
    return SCRIPTS.reduce(function (previous, src) {
      return previous.then(function () { return loadScript(src); });
    }, Promise.resolve());
  }

  il.ScormGuestFrame.register('scorm12', function (guest, init) {
    var channel = guest.channel;
    var unloading = false;

    // relative paths of the runtime (e.g. dummy.html) are relative to the
    // installation, not to assets/scorm/
    var base = document.createElement('base');
    base.href = new URL('../../', window.location.href).href;
    document.head.insertBefore(base, document.head.firstChild);

    // the runtime launches SCOs via frames.sahs_content (window.frames is the window itself)
    Object.defineProperty(window, 'sahs_content', {
      configurable: true,
      get: function () { return guest.getFrame().contentWindow; }
    });

    // While the SCO is unloaded (window closed, another SCO launched) the
    // frameset may already be gone, the runtime then sends its requests itself.
    guest.getFrame().addEventListener('load', function () {
      unloading = false;
      try {
        var win = guest.getFrame().contentWindow;
        win.addEventListener('beforeunload', function () { unloading = true; });
        win.addEventListener('pagehide', function () { unloading = true; });
      } catch (e) {
        // content of another origin cannot reach the API anyway
      }
    });
    window.addEventListener('pagehide', function () { unloading = true; });

    // Data of the former apiInitData(), read by basisInit() when basisAPI.js is
    // loaded. The data directory is reduced to its path, the runtime
    // concatenates it with the href of the SCO. The first SCO is launched below,
    // once the functions are replaced; the frameset keeps the session alive.
    var vars = init.vars;
    var launchId = vars.launchId;
    vars.launchId = 0;
    vars.pingSession = 0;
    vars.dataDirectory = encodeURIComponent(new URL(decodeURIComponent(vars.dataDirectory)).pathname);
    window.IliasScormVars = vars;
    window.IliasScormResources = init.resources;
    window.IliasScormTree = init.tree;
    window.IliasScormData = init.data;

    return loadRuntime().then(function () {
      // requests need the session of the frameset; only the known kinds are passed on
      window.sendRequest = function (url, data) {
        var kind = null;
        if (url.indexOf('storeScorm.php') > -1 && url.indexOf('do=store') > -1) kind = 'store';
        else if (url.indexOf('storeScorm.php') > -1 && url.indexOf('do=unload') > -1) kind = 'unload';
        else if (url.indexOf('cmd=logMessage') > -1) kind = 'message';
        else if (url.indexOf('cmd=logWarning') > -1) kind = 'warning';
        if (kind === null) return '';

        if (kind === 'unload' || (kind === 'store' && unloading)) {
          // storeScorm.php authorises with hash and user id, it needs no session
          try {
            fetch(new URL(url, init.base).href, {
              method: 'POST',
              mode: 'no-cors',
              keepalive: true,
              credentials: 'omit',
              body: data ? String(data) : ''
            });
          } catch (e) {
            return '';
          }
          return 'ok';
        }
        channel.send('request', {kind: kind, data: data ? String(data) : ''});
        return 'ok';
      };

      // the tree is a frame of the frameset
      window.status4tree = function (i_sco, s_status, s_time) {
        channel.send('status', {sco: i_sco, status: s_status, time: s_time});
      };

      // the debug frame is a frame of the frameset
      window.showCalls = function (APIcall, callResult, err, dia) {
        if (!window.iv.b_debug) return;
        if (err == null) err = '';
        if (dia == null) dia = '';
        if (err != 0 && err != '') dia = window.getErrorStringIntern(err) + '; ' + dia;
        channel.send('debug', {
          ms: new Date().getTime() - window.APIcallStartTimeMS,
          call: APIcall,
          result: callResult != '' ? '"' + callResult + '"' : '',
          error: '' + err,
          diagnostic: dia
        });
      };

      var api = {};
      API_FUNCTIONS.forEach(function (name) {
        api[name] = window[name];
      });
      window[init.apiName || 'API'] = api;

      // links of the tree in the frameset
      channel.on('launch', function (payload) {
        api.IliasLaunch(Number(payload.sco));
      });

      if (launchId != 0) {
        api.IliasWaitLaunch(launchId);
      }

      return {ready: true};
    });
  });
}(window, document, window.il));
