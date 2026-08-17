<?php
/*
 * Retro Radio theme - JSON endpoint for live updates.
 *
 * Returns the same data the page renders, as JSON, so js/live.js can refresh
 * the tables in place instead of reloading the whole page every 60 seconds.
 *
 * Read-only. Exposes nothing the dashboard page does not already show.
 * Local file - not part of the upstream DG9VH dashboard.
 */

// Keep the JSON clean: never let a PHP notice leak into the response body.
@ini_set('display_errors', '0');
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

$start = microtime(true);

include __DIR__ . '/config/config.php';
include __DIR__ . '/include/tools.php';
include __DIR__ . '/include/functions.php';
include_once __DIR__ . '/include/rrstats.php';
include_once __DIR__ . '/config/aliases.php';

/*
 * Deliberately NOT including include/init.php.
 *
 * init.php calls getOldYSFReflectorLog(), which opens and parses EVERY .log
 * file in /var/log/YSFReflector to build the Alltime Heard list. That is fine
 * once per page load, but this endpoint is polled every 5 seconds and does not
 * use the alltime data at all - live.js only refreshes the gateways and
 * last-heard tables. Since the 180-day log purge was removed the log directory
 * grows without bound, so re-parsing it 720 times an hour would get steadily
 * slower forever.
 *
 * We therefore replicate only the parts of init.php this endpoint needs:
 * today's log, and the last-heard list derived from it.
 */
$configs = getYSFReflectorConfig();
if (!defined('TIMEZONE')) {
    define('TIMEZONE', 'UTC');
}

$logLines = getYSFReflectorLog();          // today's log only

$reverseLogLines = $logLines;
array_multisort($reverseLogLines, SORT_DESC);
$lastHeard = getLastHeard($reverseLogLines);

/* ---------- Connected gateways ---------- */

$gateways    = getLinkedGateways($logLines);
$gatewayRows = array();

foreach ($gateways as $gw) {
    $gatewayRows[] = array(
        convertTimezone($gw['timestamp']),
        '<span nowrap>' . callsignCell($gw['callsign']) . '</span>',
    );
}

/* ---------- Last heard ---------- */

$lhRows = array();

foreach ((array) $lastHeard as $e) {
    $call = isset($e[1]) ? $e[1] : '';
    $gw   = isset($e[3]) ? $e[3] : '';

    $lhRows[] = array(
        isset($e[0]) ? $e[0] : '',
        callsignCell($call),
        isset($e[2]) ? htmlspecialchars($e[2], ENT_QUOTES, 'UTF-8') : '',
        callsignCell($gw),
        isset($e[4]) ? htmlspecialchars($e[4], ENT_QUOTES, 'UTF-8') : '',
    );
}

/* ---------- Tiles + on-air ---------- */

$temp  = rr_cputemp();      // Celsius, for the threshold test
$tempF = rr_cputemp_f();    // Fahrenheit, for display
$onair = rr_onair($lastHeard);

echo json_encode(array(
    'ok'       => true,
    'onair'    => $onair,
    'tiles'    => array(
        'gateways' => str_pad((string) count($gateways), 2, '0', STR_PAD_LEFT),
        'lastcall' => rr_lastcall($lastHeard),
        'temp'     => $tempF === null ? '--' : $tempF . '&deg;F',
        'tempc'    => $temp,
        'temphot'  => ($temp !== null && $temp >= (defined('RR_TEMP_CRITICAL') ? (int) RR_TEMP_CRITICAL : 80)),
        'tempwarm' => ($temp !== null
                        && $temp <  (defined('RR_TEMP_CRITICAL') ? (int) RR_TEMP_CRITICAL : 80)
                        && $temp >= (defined('TEMPERATUREHIGHLEVEL') ? (int) TEMPERATUREHIGHLEVEL : 60)),
        'uptime'   => rr_uptime(),
        'load'     => rr_load(),
        'extip'    => rr_extip(),   // disk-cached for an hour, cheap to call
    ),
    'gateways' => $gatewayRows,
    'lastheard' => $lhRows,
    'generated' => round(microtime(true) - $start, 4),
), JSON_UNESCAPED_UNICODE);
