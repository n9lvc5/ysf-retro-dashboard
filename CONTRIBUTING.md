# Contributing

Issues and pull requests welcome.

This fork tries to keep a clear split:

- **Upstream files** (`include/functions.php`, `include/tools.php`,
  `include/init.php`, `include/sysinfo.php`, `include/disk.php`) are DG9VH's
  data layer. Change them only when necessary, and say why in the PR.
- **Fork files** (`css/retro.css`, `js/live.js`, `status.php`, `api.php`,
  `include/tiles.php`, `include/rrstats.php`, `config/aliases.php`) are where
  new behaviour belongs.

Please keep in mind:

- The dashboard runs on a Raspberry Pi that is also running a reflector.
  Anything polled frequently must stay cheap; measure before and after.
- Never parse the whole log directory in a polled endpoint. It grows forever.
- Temperature thresholds are Celsius internally regardless of display unit.
- Test with both `SHOWQRZ` and `GDPR` set and unset.
- `php -l` every changed PHP file.
