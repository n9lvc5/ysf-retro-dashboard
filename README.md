# YSF Retro Dashboard

An amber-phosphor terminal theme and live-update rework of the
[YSFReflector-Dashboard by DG9VH](https://github.com/dg9vh/YSFReflector-Dashboard),
for [G4KLX YSFReflector](https://github.com/g4klx/YSFReflector).

Upstream is explicitly no longer developed. This fork keeps its data layer,
replaces the presentation, and removes the full-page refresh.

```
┌──────────────────────────────────────────────────────────────────────┐
│  MY REFLECTOR / MY REFLECTOR (#YSF12345)      YSFREFLECTOR 20210824  │
├──────────────────────────────────────────────────────────────────────┤
│  ┌────────┐ ┌────────────┐ ┌──────────┐ ┌────────┐ ┌──────────────┐  │
│  │   03   │ │   W1AW     │ │  104°F   │ │ 1h 08m │ │    42000     │  │
│  │        │ │            │ │          │ │        │ │ 203.0.113.7  │  │
│  │GATEWAYS│ │ LAST HEARD │ │ CPU TEMP │ │ UPTIME │ │ UDP PORT/IP  │  │
│  └────────┘ └────────────┘ └──────────┘ └────────┘ └──────────────┘  │
│                                                                      │
│  >> CURRENTLY TXING                                                  │
│                     ●  ON AIR W1AW                                   │
│                                                                      │
│  >> CONNECTED YSFGATEWAYS                                            │
│     N0CALL          2026-08-17 09:41:28                              │
│     My Friend's Hub 2026-08-17 09:41:28                              │
└──────────────────────────────────────────────────────────────────────┘
```

## What this fork adds

**Live updates instead of page reloads.** Upstream used
`<meta http-equiv="refresh" content="60">`, which reloaded the whole page every
minute and destroyed your table sort, search filter and scroll position. This
fork polls in the background and swaps table contents through the DataTables
API, so all of that survives.

**A big ON AIR indicator that responds in about half a second.** A dedicated
`status.php` reads only the tail of today's log and reports whether a
transmission is in progress. It costs ~5 ms per request, so it can be polled
twice a second without loading the Pi.

**Stat tiles** — connected gateways, last heard, CPU temperature, uptime, UDP
port and public IP — at a glance, above the tables.

**Callsign aliases.** Display a friendly name for a gateway while still linking
to the real callsign on QRZ.

**QRZ links on every table.** Upstream linked callsigns only in Last Heard.
Portable suffixes are stripped for lookup (`G4KLX/P` → `qrz.com/db/G4KLX`), and
non-callsigns like `ALL` or `??????????` are correctly left unlinked.

**Temperature warnings that mean something.** Amber pulse at your configured
warn level, red blink only at 80 °C where a Pi actually starts throttling.

**Fahrenheit or Celsius** display, with the alert threshold still evaluated in
Celsius so it keeps working.

**A bounded Alltime Heard list.** Capped at the N most recently active
callsigns (default 100) so the page cannot grow without limit. Nothing is
deleted — the raw logs are untouched.

## Requirements

- A working [YSFReflector](https://github.com/g4klx/YSFReflector) install
- Apache (or nginx) + PHP 8.x with `curl` (or `allow_url_fopen`)
- Read access for the web server to your `YSFReflector` log directory

Developed against Raspberry Pi OS Bookworm / Debian 12, PHP 8.2, on a Pi 5.

## Install

```bash
# back up whatever is there now
sudo cp -a /var/www/html /var/www/html.bak-$(date +%F)

git clone https://github.com/YOUR-GITHUB-USER/ysf-retro-dashboard.git
sudo cp -a ysf-retro-dashboard/. /var/www/html/

# create your config
cd /var/www/html
sudo cp config/config.php.example config/config.php
sudo nano config/config.php

# cache dir for the public-IP lookup
sudo mkdir -p /var/cache/ysf-dashboard
sudo chown www-data:www-data /var/cache/ysf-dashboard

sudo chown -R www-data:www-data /var/www/html
```

If you are starting from scratch, follow
[the QSO365 guide](https://qso365.co.uk/2020/02/how-to-set-up-a-yaesu-system-fusion-reflector-ysfreflector/)
to build the reflector first, then install this over the dashboard it gives you.

> **Note:** upstream's `setup.php` is deliberately **not** included. It writes
> `config/config.php` from a web form, and leaving it reachable on a public
> server lets anyone rewrite your dashboard config. Use
> `config/config.php.example` instead.

## Configure

### `config/config.php`

Paths to your reflector binary, ini and logs, plus display options
(`TIMEZONE`, `SHOWQRZ`, `GDPR`, `TEMPERATUREHIGHLEVEL`, `LOGO`, …).
Start from `config.php.example`.

### `config/aliases.php`

Everything specific to this fork:

```php
// Show a friendly name in place of a callsign. The QRZ link still uses
// the real callsign underneath.
$GATEWAY_ALIASES = array(
    'N0CALL' => "My Friend's Hub",
);

define('RR_ALLTIME_LIMIT', 100);  // Alltime Heard rows; 0 = unlimited
define('RR_TEMP_CRITICAL', 80);   // °C at which the temp tile blinks red
```

### `js/live.js`

```js
var STATUS_MS = 500;    // ON AIR / STANDBY poll
var POLL_MS   = 5000;   // tables + tiles poll
```

### Celsius instead of Fahrenheit

In `include/tiles.php` use `$rrTemp` (°C) rather than `$rrTempF`, and in
`api.php` return `$temp` instead of `$tempF`. Thresholds are always Celsius.

## How it works

| File | Role |
|---|---|
| `status.php` | Tail-reads today's log, reports ON AIR state. Polled ~2×/s. |
| `api.php` | Tables + tiles as JSON. Polled every 5 s. |
| `js/live.js` | Two poll loops, DataTables-aware updates, edge detection. |
| `include/tiles.php` | The stat tile row. |
| `include/rrstats.php` | Uptime, CPU temp, load, public IP (cached 1 h). |
| `config/aliases.php` | Aliases, limits, thresholds, `callsignCell()`. |
| `css/retro.css` | The entire theme. Delete it for stock styling. |

`api.php` deliberately does **not** include `include/init.php`, because that
parses every log file ever written in order to build the Alltime list. At a 5 s
poll that becomes slower forever as logs accumulate. It replicates only what it
needs instead — a ~38× reduction in per-poll cost.

## Credits

- **DG9VH (Kim Hübel)** — the original
  [YSFReflector-Dashboard](https://github.com/dg9vh/YSFReflector-Dashboard),
  which this is built on. All log parsing is still essentially his.
- **G4KLX (Jonathan Naylor)** — [YSFReflector](https://github.com/g4klx/YSFReflector).
- **KC1AWV** — contributor to the upstream dashboard.
- **QSO365 / M0JMR** — the setup guide most YSFReflector operators use.

## Licence

**CC0 1.0 Universal**, the same as upstream — see [LICENSE](LICENSE).
Public domain: do whatever you like with it. Attribution is appreciated but not
required.
