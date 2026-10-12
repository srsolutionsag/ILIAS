# SCORM / AICC

## SCORM and User Content Isolation

With [User Content Isolation](../FileDelivery/README.md#irss-user-content-isolation)
user content is served from a separate content domain. SCORM packages are a
special case: a SCO searches its parent windows for the API object (`API` for
SCORM 1.2, `API_1484_11` for SCORM 2004) and calls it **synchronously**, e.g.
`API.LMSGetValue('cmi.core.lesson_status')`. Traditionally the API lived in the
ILIAS player page; once the SCO is served from another origin, the browser
blocks the SCO from reading `window.parent.API`, and the player from reading
the SCO's frame.

`postMessage`, the only channel between windows of different origins, is
asynchronous and can therefore not answer the synchronous API calls directly.
The SCORM players solve this with a **guest frame**.

### How it fits together

```
+--------------------------------------------------------------------+
| Player  https://ilias.example.org/ilias.php?...   (ILIAS origin)   |
|   frameset (1.2) / player page (2004): tree, navigation,           |
|   sequencing, storing with the session                             |
|                                                                    |
|   +--------------------------------------------------------------+ |
|   | Guest frame  https://content.example.org/assets/scorm/       | |
|   |              guest-frame.html#origin=...&channel=scorm       | |
|   |   the SCORM runtime (window.API / window.API_1484_11)        | |
|   |                                                              | |
|   |   +--------------------------------------------------------+ | |
|   |   | SCO  https://content.example.org/...                    | | |
|   |   |   finds the API in window.parent, as before             | | |
|   |   +--------------------------------------------------------+ | |
|   +--------------------------------------------------------------+ |
+--------------------------------------------------------------------+

      player  <--- postMessage (il.HTTP.Channel) --->  guest frame
      guest frame  <--- direct calls (same origin) --->  SCO
```

* The guest frame is a static page on the **origin of the package**. Runtime
  and SCO share that origin, so the SCO finds the API in its parent window and
  every call is answered synchronously from the data the runtime holds.
* Everything that needs ILIAS (storing, logging, the session ping, the tree,
  sequencing for SCORM 2004) stays in the player on the ILIAS origin. The
  runtime hands it over through a channel, see
  [postMessage Channel](../HTTP/README.md#postmessage-channel-talking-to-other-origins).
* The runtimes are the existing ones, unchanged and loaded as they are:
  `scripts/basisAPI.js` and `scripts/SCORM1_2standard.js` (1.2), `rte-min.js` of
  `components/ILIAS/Scorm2004` (2004). In the guest frame only the few global
  functions are replaced that need the player (requests, tree icons, debug
  output, `save()` etc.).
* The same mechanism is used for packages on the ILIAS origin (e.g. not yet
  migrated packages in `lm_data`, or without isolation). The guest frame is then
  on the ILIAS origin, there is a single code path.

### Why the guest frame is a static file

* On the content domain no application PHP is executed (`HttpPathBuilder`
  rejects the regular entry points there). The static file is served by the web
  server, since the content domain points to the same document root.
* It contains no data, the player hands everything over at runtime. It is the
  same for all users and packages.
* The content domain has no session cookie, the guest frame never calls ILIAS
  on behalf of the user (with one exception, see below).

### Lifecycle

```
Player (ILIAS origin)                          Guest frame (origin of the package)
---------------------                          ----------------------------------
1. PHP: GuestFrame::getURL($package_url)
2. il.ScormGuestFrame.host(iframe, {guestFrame, adapter}).start(null, data)
   iframe.src = guest-frame.html#origin=<ILIAS origin>&channel=scorm
                                               3. reads origin and channel from the fragment,
                                                  refuses to start if location.ancestorOrigins
                                                  names another embedding origin
                                  <---------- 4. guest-frame.hello
5. request guest-frame.init {adapter, data} ->
                                               6. loads the runtime of the adapter from the
                                                  same origin, only scorm12 or scorm2004:
                                                  assets/js/scorm12-guest.js, scorm2004-guest.js
                                               7. the runtime creates window.API / API_1484_11
                                                  from the data (tracking data of the user)
                                  <---------- 8. answer to guest-frame.init
9. runtime messages, e.g.
   1.2:  guest -> player  request {kind: store|message|warning|ping}, status, debug
         player -> guest  launch {sco}                       (tree click)
   2004: player -> guest  deliver {url, cmiItem}, unload, setIntern
         guest -> player  commit {cmiItem, save}, terminated, log, summary
```

The player initialises the guest frame once per load of the frame; further
hellos (which the SCO could send, it shares the origin) are ignored until the
frame really loads again.

### Who is the user?

The guest frame has no session. Everything stored is sent by the player, which
runs on the ILIAS origin with the session cookie:

* SCORM 2004 stores via `ilias.php?...&cmd=cmi` (session), or via
  `storeScorm2004.php` if the session is deactivated for the module.
* SCORM 1.2 has always stored via `storeScorm.php` without session: it
  authorises with `hash` (random, per user and package in `sahs_user`, valid one
  day) and `p` (user id), see `ilObjSCORMTracking::checkIfAllowed()`.

**Exception (1.2 only):** if the SCO calls `LMSFinish` while the window is
closed, the player may already be gone. The guest frame then sends the last
data itself with `fetch(..., {keepalive: true, credentials: 'omit'})` to
`storeScorm.php`, which works without cookie because of `hash`/`p`. These values
are therefore known on the origin of the package; they only allow writing the
tracking data of this user for this package, which the SCO can do through the
API anyway.

### Security considerations

* **The SCO is the guest.** SCO and guest frame share an origin, the SCO can call
  the runtime and the channel directly and send any message the runtime may
  send. The handlers of the players are therefore trust boundaries:
  * 1.2: requests are only made for the known kinds (`store`, `message`,
    `warning`, `ping`) to URLs of the player; log messages are limited in
    number; received strings are never inserted as HTML.
  * 2004: data of a commit is only accepted for the SCO delivered last; its
    `cp_node_id` and the elements the SCO cannot write through the API
    (credit, mode, entry, learner_id, ...) are restored from the player's own
    data; "store only a better score" is decided by the player; navigation
    targets must belong to the package; log entries are only relayed while the
    debugger is active and only for the actions of the runtime; entries with the
    id `__proto__`, `constructor` or `prototype` are dropped before the data
    reaches the player's own code.
* **Other content on the content domain** is not isolated from SCOs (all user
  content shares the domain). Content that gets hold of a player window (e.g.
  `window.open()` and walking `frames`) could use the API like the SCO inside.
  Both players therefore send `Cross-Origin-Opener-Policy: same-origin`. A player
  embedded in an iframe of a regular ILIAS page (open mode "iframe") is only
  protected if that page sends the header as well.
* **Third parties framing `guest-frame.html`** gain nothing: it loads only the
  two runtimes and content of its own origin, holds no data of its own, and the
  content domain has no session.
* **Unchanged:** a SCO can still write arbitrary tracking data for the current
  user through the API; this was never protected against the client.

### Files

| File | Purpose |
|---|---|
| `resources/guest-frame/guest-frame.html` | the guest frame, published as `public/assets/scorm/guest-frame.html` |
| `resources/js/guest-frame/src` | `GuestFrameHost` (player side) and `GuestFrame` (guest side), built with `npx rollup -c` in `resources/js/guest-frame` |
| `src/GuestFrame/GuestFrame.php` | URL of the guest frame on the origin of a package |
| `resources/js/scorm12-host.js` | SCORM 1.2: frameset side (requests, tree icons, debug frame) |
| `resources/js/scorm12-guest.js` | SCORM 1.2: loads `scripts/basisAPI.js` and `scripts/SCORM1_2standard.js` in the guest frame and replaces the frameset functions |
| `../Scorm2004/scripts/rtemain/bridge.js` | SCORM 2004: player side |
| `../Scorm2004/resources/js/scorm2004-guest.js` | SCORM 2004: loads `jquery.js` and `rte-min.js` in the guest frame and replaces the player functions |

Tests: `tests/Client/GuestFrame` (`npm test`) and `tests/GuestFrame` (PHPUnit).

### Troubleshooting

* **Which origin does the SCO come from?** In the browser developer tools select
  the SCO frame in the context selector of the console and evaluate
  `location.origin`.
* **404 for the guest frame on the content domain:** the web server does not
  serve `public/assets/` for the content host, or `composer dump-autoload` has
  not been run.
* **The SCO does not find the API:** the SCO and the guest frame must have the
  same origin; the guest frame refuses content of other origins. SCOs searching
  `window.top` first reach the ILIAS origin, which the browser blocks; SCORM
  defines searching the parents bottom-up.
