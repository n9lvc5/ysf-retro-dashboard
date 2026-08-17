# Changelog

## 1.0.0

First release of the fork. Base: DG9VH YSFReflector-Dashboard `20210331-2`.

### Added
- `css/retro.css` — amber-phosphor terminal theme (scanlines, VT323/Share Tech
  Mono, restyled cards, tables and DataTables chrome). Theme-only; deleting the
  stylesheet returns the stock Bootstrap look.
- `status.php` — lightweight ON AIR endpoint. Tail-reads today's log, ~5 ms.
- `api.php` — JSON endpoint for tiles and table bodies.
- `js/live.js` — background polling, DataTables-aware row swapping, on-air edge
  detection, tab-visibility pausing, exponential backoff.
- `include/tiles.php` — stat tile row (gateways, last heard, CPU temp, uptime,
  UDP port + public IP).
- `include/rrstats.php` — uptime, CPU temp (C and F), load, public IP with a
  1-hour disk cache.
- `config/aliases.php` — gateway display aliases, `callsignCell()`,
  `RR_ALLTIME_LIMIT`, `RR_TEMP_CRITICAL`.
- Large blinking ON AIR / STANDBY badge in the Currently TXing panel.
- QRZ links in every table, in both Callsign and Gateway columns.
- Two-tier CPU temperature warning: amber pulse at `TEMPERATUREHIGHLEVEL`,
  red blink at `RR_TEMP_CRITICAL` (80 °C, where a Pi 5 soft-throttles).
- Alltime Heard cap with a visible "showing newest N of M" caption.

### Changed
- **Removed the 60-second `<meta http-equiv="refresh">`.** Updates now happen in
  the background, preserving table sort, filter, paging and scroll position.
- `api.php` does not include `include/init.php`; it avoids re-parsing the entire
  log history on every poll (~0.234 s → ~0.006 s per request).
- Callsign rendering consolidated into one `callsignCell()` used by all tables,
  instead of four divergent copies.
- CPU temperature displays in Fahrenheit; thresholds stay in Celsius so the
  existing `TEMPERATUREHIGHLEVEL` setting keeps working.
- Navbar shows only reflector identity and version; dashboard branding moved to
  the footer.
- `getGitVersion()` links commits to this fork via `FORK_REPO` rather than
  hard-coding the upstream repository.
- Bare `.card-header` elements now get the same display font as `<h2>` ones.

### Fixed
- Last Heard built its QRZ URL from the raw space-padded log field, producing
  links with trailing whitespace. Now trimmed and URL-encoded.
- Portable callsigns (`G4KLX/P`) linked to a non-existent QRZ page; the base
  callsign is now used for lookup while the full form is still displayed.
- Placeholders (`ALL`, `??????????`, numeric IDs) were rendered as QRZ links.

### Removed
- `setup.php` is not shipped. It rewrites `config/config.php` from an unauthenticated
  web form; leaving it on a reachable server is a security hole. Use
  `config/config.php.example`.
