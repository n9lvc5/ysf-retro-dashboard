<?php
/*
 * Retro Radio theme - shared stat helpers.
 * Used by include/tiles.php (initial server-side render) and api.php (live poll)
 * so both always report identical numbers.
 *
 * Local file - not part of the upstream DG9VH dashboard.
 */

if (!function_exists('rr_uptime')) {

    /** System uptime, compactly formatted: "19h 41m" / "3d 04h" / "12m" */
    function rr_uptime() {
        if (!is_readable('/proc/uptime')) {
            return '--';
        }
        $raw = @file_get_contents('/proc/uptime');
        if ($raw === false) {
            return '--';
        }
        $secs = (int) floatval(strtok($raw, ' '));

        $d = intdiv($secs, 86400);
        $h = intdiv($secs % 86400, 3600);
        $m = intdiv($secs % 3600, 60);

        if ($d > 0) {
            return sprintf('%dd %02dh', $d, $h);
        }
        if ($h > 0) {
            return sprintf('%dh %02dm', $h, $m);
        }
        return $m . 'm';
    }

    /**
     * CPU temperature in whole degrees C, or null if unavailable.
     * Kept in Celsius because TEMPERATUREHIGHLEVEL in config.php is Celsius -
     * the alert threshold must be compared in the same unit it was written in.
     */
    function rr_cputemp() {
        $path = '/sys/class/thermal/thermal_zone0/temp';
        if (!is_readable($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return null;
        }
        return (int) round(((float) $raw) / 1000);
    }

    /** CPU temperature in whole degrees F, or null. Display only. */
    function rr_cputemp_f() {
        $c = rr_cputemp();
        if ($c === null) {
            return null;
        }
        return (int) round($c * 9 / 5 + 32);
    }

    /**
     * The reflector's public IP.
     *
     * Cached on disk for an hour: the dashboard polls every 5 s and we are not
     * going to hammer a third-party service 720 times an hour. On lookup
     * failure the last known value is reused indefinitely rather than showing
     * nothing - a slightly stale IP beats a blank tile.
     *
     * Cache lives in /var/cache/ysf-dashboard (owned by www-data). Not /tmp or
     * /var/tmp: apache2.service runs with PrivateTmp=true, which namespaces
     * those per-service - the file would be invisible and lost on restart.
     */
    function rr_extip() {
        $cacheFile = '/var/cache/ysf-dashboard/extip';
        $ttl       = 3600;

        // fresh cache wins
        if (is_readable($cacheFile)) {
            $age = time() - (int) @filemtime($cacheFile);
            $hit = trim((string) @file_get_contents($cacheFile));
            if ($hit !== '' && $age < $ttl) {
                return $hit;
            }
        }

        $services = array(
            'https://api.ipify.org',
            'https://ifconfig.me/ip',
            'https://icanhazip.com',
        );

        foreach ($services as $url) {
            $ip = rr_http_get($url, 3);
            $ip = trim((string) $ip);

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                @file_put_contents($cacheFile, $ip, LOCK_EX);
                @chmod($cacheFile, 0644);
                return $ip;
            }
        }

        // every lookup failed - fall back to a stale cache if we have one
        if (is_readable($cacheFile)) {
            $stale = trim((string) @file_get_contents($cacheFile));
            if ($stale !== '') {
                return $stale;
            }
        }
        return '--';
    }

    /** Minimal HTTP GET with a hard timeout. curl if present, streams otherwise. */
    function rr_http_get($url, $timeout) {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 2,
                CURLOPT_USERAGENT      => 'YSFReflector-Dashboard',
            ));
            $body = curl_exec($ch);
            curl_close($ch);
            return $body === false ? '' : $body;
        }

        if (!ini_get('allow_url_fopen')) {
            return '';
        }
        $ctx = stream_context_create(array(
            'http' => array('timeout' => $timeout, 'user_agent' => 'YSFReflector-Dashboard'),
        ));
        $body = @file_get_contents($url, false, $ctx);
        return $body === false ? '' : $body;
    }

    /** 1-minute load average, or null. */
    function rr_load() {
        if (!function_exists('sys_getloadavg')) {
            return null;
        }
        $l = @sys_getloadavg();
        if (!is_array($l) || !isset($l[0])) {
            return null;
        }
        return round((float) $l[0], 2);
    }

    /**
     * Is anyone transmitting right now?
     * getHeardList() marks an in-progress transmission with the literal
     * duration "transmitting" - that is our live flag.
     *
     * Returns array('active' => bool, 'callsign' => string)
     */
    function rr_onair($lastHeard) {
        if (is_array($lastHeard)) {
            foreach ($lastHeard as $row) {
                if (isset($row[4]) && trim($row[4]) === 'transmitting') {
                    return array(
                        'active'   => true,
                        'callsign' => isset($row[1]) ? trim($row[1]) : '',
                    );
                }
            }
        }
        return array('active' => false, 'callsign' => '');
    }

    /** Most recent callsign heard, or "--". */
    function rr_lastcall($lastHeard) {
        if (is_array($lastHeard) && isset($lastHeard[0][1])) {
            $cs = trim($lastHeard[0][1]);
            if ($cs !== '' && $cs !== '??????????') {
                return $cs;
            }
        }
        return '--';
    }
}
