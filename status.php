<?php
/*
 * Retro Radio theme - ultra-light ON AIR / STANDBY endpoint.
 *
 * Polled far more often than api.php, so it does the minimum possible work:
 * it reads only the last few KB of today's log and scans backwards for the
 * most recent transmission marker. Cost is constant regardless of how large
 * the log directory grows.
 *
 *   "Received data from CALL to TARGET at GATEWAY"  -> transmission started
 *   "Received end of transmission"                  -> transmission ended
 *   "watchdog has expired"                          -> transmission died
 *
 * Whichever of those appears last decides the state.
 *
 * Local file - not part of the upstream DG9VH dashboard.
 */

@ini_set('display_errors', '0');
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

// config.php sets date_default_timezone_set('UTC'); the reflector names its log
// files and stamps its lines in UTC, so this include must come first.
include __DIR__ . '/config/config.php';
include_once __DIR__ . '/config/aliases.php';

$TAIL_BYTES  = 16384;  // plenty for the last few minutes of activity
$STALE_AFTER = 120;    // seconds; guards against a missed "end" line

$logFile = rtrim(YSFREFLECTORLOGPATH, '/') . '/'
         . YSFREFLECTORLOGPREFIX . '-' . date('Y-m-d') . '.log';

$state = array(
    'active'   => false,
    'callsign' => '',
    'display'  => '',
    'target'   => '',
    'since'    => '',
);

if (is_readable($logFile) && ($fh = @fopen($logFile, 'rb'))) {
    $size = @filesize($logFile);
    if ($size > $TAIL_BYTES) {
        @fseek($fh, -$TAIL_BYTES, SEEK_END);
        @fgets($fh);                 // discard the partial first line
    }
    $tail = (string) @stream_get_contents($fh);
    @fclose($fh);

    $lines = explode("\n", $tail);

    for ($i = count($lines) - 1; $i >= 0; $i--) {
        $line = $lines[$i];

        if ($line === '' || strpos($line, 'M:') !== 0) {
            continue;
        }

        // an end marker means we are idle - stop looking
        if (strpos($line, 'Received end of transmission') !== false
            || strpos($line, 'watchdog has expired') !== false) {
            break;
        }

        if (preg_match('/Received data from\s+(\S+)\s+to\s+(.+?)\s+at\s+(\S+)/', $line, $m)) {
            $ts  = substr($line, 3, 19);          // "YYYY-MM-DD HH:MM:SS"
            $age = time() - (int) strtotime($ts);

            // If the last thing we saw was a transmission start but it is old,
            // the matching end line probably fell outside our tail window.
            // Treat that as idle rather than showing ON AIR forever.
            if ($age >= 0 && $age > $STALE_AFTER) {
                break;
            }

            $state = array(
                'active'   => true,
                'callsign' => trim($m[1]),
                'display'  => gatewayDisplayName($m[1]),
                'target'   => trim($m[2]),
                'since'    => $ts,
            );
            break;
        }
    }
}

echo json_encode(array('ok' => true, 'onair' => $state), JSON_UNESCAPED_UNICODE);
