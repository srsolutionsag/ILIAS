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
 * Connection of the SCORM 2004 player page to the runtime in the guest frame
 * (see ScormAicc README, "SCORM and User Content Isolation"). Loaded next to the
 * runtime (rte-min.js) by tpl.scorm2004.player.html. The SCO and its API object
 * run in the guest, possibly on the content domain. Sequencing, the tree and
 * storing stay on this page: whenever the SCO commits, the guest sends a copy
 * of its data, which is wrapped in a mirror of the runtime (currentAPI) so the
 * existing functions (syncCMIADLTree, setAPI, save, ...) work as before.
 */

var deliveredCmiItem = null; // data the runtime of the next SCO is created with

var scormBridge = (function () {
	var frame = null;
	var ready = null;
	var guestFrameUrl = null;

	function absolute(url) {
		return new URL(url, window.location.href).href;
	}

	function send(type, payload) {
		if (ready) {
			ready.then(function () { frame.channel.send(type, payload); });
		}
	}

	function request(type, payload) {
		if (!ready) {
			return Promise.resolve(null);
		}
		return ready.then(function () { return frame.channel.request(type, payload, 0); });
	}

	function copy(data) {
		return data === null || data === undefined ? null : JSON.parse(JSON.stringify(data));
	}

	// elements the SCO cannot write through the API; they are taken from the
	// data the runtime was created with, not from the guest
	var HOST_ELEMENTS = ['cp_node_id', 'learner_id', 'learner_name', 'credit', 'mode', 'entry',
		'launch_data', 'completion_threshold', 'scaled_passing_score', 'time_limit_action',
		'max_time_allowed'];

	// the actions the runtime logs, see rte.js
	var LOG_ACTIONS = /^(Initialize|Terminate|Commit|GetValue|SetValue|GetLastError|GetErrorString|GetDiagnostic|ANALYZE|ANALYZETEST|INFO)$/;

	function escapeHtml(text) {
		return String(text).replace(/[&<>"']/g, function (c) {
			return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
		});
	}

	// ids of objectives, interactions, ... become property names in setAPI()
	var FORBIDDEN_IDS = ['__proto__', 'constructor', 'prototype'];

	function withoutForbiddenIds(data) {
		if (Array.isArray(data)) {
			return data
				.filter(function (row) { return !(row && typeof row === 'object' && FORBIDDEN_IDS.indexOf(String(row.id)) > -1); })
				.map(withoutForbiddenIds);
		}
		if (data && typeof data === 'object') {
			Object.keys(data).forEach(function (k) {
				data[k] = withoutForbiddenIds(data[k]);
			});
		}
		return data;
	}

	/**
	 * Everything coming from the guest is as trustworthy as the SCO itself,
	 * which shares the origin of the guest frame and may send any message. The
	 * data is only accepted for the SCO delivered last, and elements the SCO
	 * cannot write through the API are restored from the data of the delivery.
	 */
	function acceptedCmiItem(payload) {
		if (!deliveredCmiItem || !payload || typeof payload.cmiItem !== 'object' || payload.cmiItem === null
			|| typeof payload.cmiItem.cmi !== 'object' || payload.cmiItem.cmi === null) {
			return null;
		}
		var cmiItem = withoutForbiddenIds(copy(payload.cmiItem));
		for (var i = 0; i < HOST_ELEMENTS.length; i++) {
			var k = HOST_ELEMENTS[i];
			if (Object.prototype.hasOwnProperty.call(deliveredCmiItem.cmi, k)) {
				cmiItem.cmi[k] = copy(deliveredCmiItem.cmi[k]);
			} else {
				delete cmiItem.cmi[k];
			}
		}
		cmiItem.scoid = deliveredCmiItem.scoid;
		return cmiItem;
	}

	// "store only if score is equal or higher than in previous attempt", see Commit() in rte.js
	function scoreAllowsSaving(cmiItem) {
		if (config.auto_review != 's' || !cmiItem.cmi.score) {
			return true;
		}
		var scaled = cmiItem.cmi.score.scaled;
		if (scaled == "" || scaled === undefined || scaled === null) {
			return !(saved_score_scaled > 0);
		}
		return !(parseFloat(scaled) <= saved_score_scaled);
	}

	function onGuestCommit(payload) {
		var cmiItem = acceptedCmiItem(payload);
		if (!cmiItem) {
			return false;
		}
		currentAPI = new Runtime(cmiItem, onCommit, onTerminate);
		var allowed = scoreAllowsSaving(cmiItem);
		if (config.auto_review == 's' && payload.save) {
			setNoCreditDisplay(!allowed);
		}
		if (cmiItem.cmi.credit != "no-credit") {
			syncCMIADLTree();
			if (onCommit(cmiItem) && payload.save && allowed) {
				if (config.fourth_edition && payload.shared) {
					saveSharedData(cmiItem);
				}
				save();
			}
		}
		return true;
	}

	function onGuestTerminated(payload) {
		var cmiItem = acceptedCmiItem(payload);
		if (!cmiItem) {
			return false;
		}
		// a navigation request may only target activities of this package
		var request = cmiItem.adl && cmiItem.adl.nav ? String(cmiItem.adl.nav.request) : '';
		var target = request.match(/^\{target=([^\}]+)\}/);
		if (target && !Object.prototype.hasOwnProperty.call(activities, target[1])) {
			cmiItem.adl.nav.request = '_none_';
		}
		currentAPI = new Runtime(cmiItem, onCommit, onTerminate);
		Runtime.onTerminate(cmiItem);
		onTerminate(cmiItem);
		return true;
	}

	function onGuestLog(args) {
		if (!logActive || !Array.isArray(args) || !LOG_ACTIONS.test(String(args[1]))) {
			return;
		}
		sendLogEntry.apply(null, args.slice(0, 6));
	}

	return {
		start: function (config) {
			guestFrameUrl = config.bridge_guest_frame;
			var iframe = document.getElementById("res");
			frame = il.ScormGuestFrame.host(iframe, {
				guestFrame: absolute(guestFrameUrl),
				adapter: 'scorm2004',
				channel: 'scorm'
			});
			frame.channel.on('commit', onGuestCommit);
			frame.channel.on('terminated', onGuestTerminated);
			frame.channel.on('log', onGuestLog);
			frame.channel.on('summary', function () { if (logActive) { createSummary(); } });
			ready = frame.start(null, {
				apiName: Runtime.apiname,
				cfg: {
					time_from_lms: config.time_from_lms,
					auto_suspend: config.auto_suspend,
					auto_review: config.auto_review,
					fourth_edition: config.fourth_edition,
					checkSetValues: config.checkSetValues
				},
				debugFields: config.debug_fields || [],
				debugFieldsTest: config.debug_fields_test || []
			});
			ready.catch(function (e) {
				sclogdump("SCORM guest frame: " + escapeHtml(e.message), "error");
			});
		},
		/**
		 * Hands the SCO (or asset) over to the guest; the runtime is created there
		 * from the data before the content is loaded.
		 */
		deliver: function (url, cmiItem) {
			showSpecialPage(null);
			request('deliver', {
				url: absolute(url),
				cmiItem: copy(cmiItem),
				logActive: logActive,
				savedScoreScaled: saved_score_scaled
			}).catch(function (e) {
				sclogdump("SCORM guest frame: " + escapeHtml(e.message), "error");
			});
		},
		unload: function () {
			showSpecialPage(null);
			send('unload', null);
		},
		setIntern: function (path, value) {
			send('setIntern', {path: path, value: value});
		},
		setLogActive: function (value) {
			send('logActive', Boolean(value));
		}
	};
}());

/**
 * Pages of ILIAS (e.g. the end of the session) are shown in a frame of their
 * own, the guest only loads content of its own origin.
 */
function showSpecialPage(src) {
	var special = document.getElementById("specialPage");
	var res = document.getElementById("res");
	if (src) {
		special.src = src;
		special.style.display = "block";
		res.style.display = "none";
	} else {
		special.style.display = "none";
		res.style.display = "block";
	}
}

function setNoCreditDisplay(show) {
	var elm = document.getElementById("noCredit");
	if (elm) {
		elm.style.display = show ? "inline" : "none";
	}
}

/**
 * Called by the debugger window.
 */
function setLogActive(value) {
	logActive = Boolean(value);
	scormBridge.setLogActive(logActive);
}
