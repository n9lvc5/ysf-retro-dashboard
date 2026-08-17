/* ============================================================
   Retro Radio theme - live updates.

   Replaces the old <meta http-equiv="refresh"> full-page reload.
   Polls api.php and swaps table contents through the DataTables API, so the
   user's sort order, search filter, page position and scroll all survive.

   Local file - not part of the upstream DG9VH dashboard.
   ============================================================ */
(function () {
  'use strict';

  /* Two cadences on purpose:
     - status.php is tiny (reads only the tail of today's log), so we can poll
       it fast for a responsive ON AIR badge.
     - api.php rebuilds whole tables and drives DataTables redraws, which is
       wasteful and visually noisy to do every second.
     A change in on-air state also triggers an immediate full refresh, so the
     Last Heard table updates the instant a transmission ends. */
  var STATUS_MS    = 500;    // ON AIR / STANDBY cadence (~5 ms/call server-side)
  var POLL_MS      = 5000;   // full table + tile cadence
  var BACKOFF_MAX  = 60000;  // give up slowly if the Pi stops answering
  var failures     = 0;
  var statusFails  = 0;
  var timer        = null;
  var statusTimer  = null;
  var lastOnAir    = null;   // previous on-air state, for edge detection
  var lastTopKey   = null;   // first last-heard row we have already seen

  function $id(id) { return document.getElementById(id); }

  function setText(id, value) {
    var el = $id(id);
    if (el && value !== undefined && value !== null && el.innerHTML !== value) {
      el.innerHTML = value;
    }
  }

  /* ---------- ON AIR badge ---------- */

  function renderOnAir(onair) {
    var el = $id('rr-onair');
    if (!el) return;

    var label  = el.querySelector('.rr-label');
    var active = !!(onair && onair.active);
    var who    = onair ? (onair.display || onair.callsign || '') : '';

    if (active) {
      el.classList.add('is-live');
      if (label) label.innerHTML = who ? 'ON AIR ' + who : 'ON AIR';
    } else {
      el.classList.remove('is-live');
      if (label) label.textContent = 'STANDBY';
    }

    // On any transition, pull the full payload straight away so the tables and
    // tiles reflect the change without waiting out the slow cadence.
    if (lastOnAir !== null && lastOnAir !== active) {
      schedule(50);
    }
    lastOnAir = active;
  }

  /* ---------- fast status poll ---------- */

  function scheduleStatus(ms) {
    if (statusTimer) clearTimeout(statusTimer);
    statusTimer = setTimeout(pollStatus, ms);
  }

  function pollStatus() {
    fetch('status.php', { cache: 'no-store', credentials: 'same-origin' })
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(function (data) {
        if (!data || !data.ok) throw new Error('bad payload');
        statusFails = 0;
        renderOnAir(data.onair);
        scheduleStatus(STATUS_MS);
      })
      .catch(function () {
        statusFails++;
        scheduleStatus(Math.min(STATUS_MS * Math.pow(2, statusFails), BACKOFF_MAX));
      });
  }

  /* ---------- table swap via DataTables ---------- */

  function refreshTable(selector, rows) {
    if (!window.jQuery || !rows) return false;

    var $t = window.jQuery(selector);
    if (!$t.length) return false;

    // Only touch it if DataTables actually initialised on this table.
    if (!window.jQuery.fn.dataTable || !window.jQuery.fn.dataTable.isDataTable(selector)) {
      return false;
    }

    var dt = $t.DataTable();
    var page = dt.page();

    dt.clear();
    dt.rows.add(rows);
    dt.draw(false);           // false = keep paging position

    if (page && dt.page.info() && page < dt.page.info().pages) {
      dt.page(page).draw(false);
    }
    return true;
  }

  /* ---------- flash the newest last-heard row ---------- */

  function flashIfNew(rows) {
    if (!rows || !rows.length) return;

    // key on time + callsign of the newest entry
    var key = String(rows[0][0]) + '|' + String(rows[0][1]);
    if (lastTopKey !== null && key !== lastTopKey) {
      var body = document.querySelector('#lh tbody');
      if (body && body.rows.length) {
        var tr = body.rows[0];
        tr.classList.remove('rr-new');
        void tr.offsetWidth;      // restart the CSS animation
        tr.classList.add('rr-new');
      }
    }
    lastTopKey = key;
  }

  /* ---------- stamp the footer so staleness is visible ---------- */

  function stampClock(ok) {
    var el = $id('rr-clock');
    if (!el) return;
    var now = new Date();
    var hh  = String(now.getHours()).padStart(2, '0');
    var mm  = String(now.getMinutes()).padStart(2, '0');
    var ss  = String(now.getSeconds()).padStart(2, '0');
    el.textContent = (ok ? 'LIVE ' : 'STALE ') + hh + ':' + mm + ':' + ss;
    el.style.color = ok ? '' : '#ff3b30';
  }

  /* ---------- poll ---------- */

  function schedule(ms) {
    if (timer) clearTimeout(timer);
    timer = setTimeout(poll, ms);
  }

  function poll() {
    fetch('api.php', { cache: 'no-store', credentials: 'same-origin' })
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(function (data) {
        if (!data || !data.ok) throw new Error('bad payload');

        failures = 0;

        if (data.tiles) {
          setText('rr-gateways', data.tiles.gateways);
          setText('rr-lastcall', data.tiles.lastcall);
          setText('rr-temp',     data.tiles.temp);
          setText('rr-uptime',   data.tiles.uptime);
          setText('rr-extip',    data.tiles.extip);

          var tempTile = $id('rr-temp-tile');
          if (tempTile) {
            tempTile.classList.toggle('is-hot',  !!data.tiles.temphot);
            tempTile.classList.toggle('is-warm', !!data.tiles.tempwarm);
          }
        }

        refreshTable('#gateways', data.gateways);
        refreshTable('#lh', data.lastheard);
        flashIfNew(data.lastheard);

        stampClock(true);
        schedule(POLL_MS);
      })
      .catch(function () {
        failures++;
        stampClock(false);
        // exponential-ish backoff, capped
        schedule(Math.min(POLL_MS * Math.pow(2, failures), BACKOFF_MAX));
      });
  }

  // Pause polling while the tab is hidden; resume immediately on return.
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      if (timer) clearTimeout(timer);
      if (statusTimer) clearTimeout(statusTimer);
    } else {
      schedule(200);
      scheduleStatus(100);
    }
  });

  function start() {
    scheduleStatus(100);      // badge should settle almost immediately
    schedule(POLL_MS);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
