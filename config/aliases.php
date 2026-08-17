<?php
/*
 * Local callsign presentation for the YSFReflector dashboard.
 * Not part of the upstream DG9VH dashboard - safe from its updates.
 *
 * Two things live here:
 *   $GATEWAY_ALIASES  - display-name overrides (e.g. N0CALL -> "My Friend's Hub")
 *   callsignCell()    - the single renderer every table uses for a callsign
 *
 * Key   = the real callsign as it appears in the YSFReflector log (untrimmed
 *         padding and case are handled for you).
 * Value = what to show on the dashboard instead.
 */
$GATEWAY_ALIASES = array(
    // 'N0CALL' => "My Friend's Hub",
    // 'W1AW'   => 'Club Repeater',
);

/*
 * Cap on the Alltime Heard List.
 *
 * getLastHeard() returns one row per unique callsign, newest first, so this
 * keeps the N most recently active callsigns and lets the longest-dormant ones
 * drop off. Display only - the raw logs in /var/log/YSFReflector are never
 * touched, so nothing is actually lost. Set to 0 for no limit.
 */
if (!defined('RR_ALLTIME_LIMIT')) {
    define('RR_ALLTIME_LIMIT', 100);
}

/*
 * CPU temperature tiers, in CELSIUS (the sensor's native unit, and the unit
 * TEMPERATUREHIGHLEVEL in config.php already uses - the tile displays F).
 *
 *   warm : >= TEMPERATUREHIGHLEVEL (config.php, currently 60) -> amber pulse
 *   hot  : >= RR_TEMP_CRITICAL                                -> red blink
 *
 * 80 C is where Pi 5 firmware begins soft-throttling, so that is the point at
 * which the CPU is genuinely in trouble rather than merely warm.
 */
if (!defined('RR_TEMP_CRITICAL')) {
    define('RR_TEMP_CRITICAL', 80);
}

if (!function_exists('gatewayDisplayName')) {
    /**
     * Display name for a callsign, without any link markup.
     * Aliased callsigns are shown verbatim; everything else keeps the
     * dashboard's traditional slashed-zero rendering.
     */
    function gatewayDisplayName($rawCallsign) {
        global $GATEWAY_ALIASES;

        $callsign = strtoupper(trim($rawCallsign));

        if (isset($GATEWAY_ALIASES[$callsign])) {
            return htmlspecialchars($GATEWAY_ALIASES[$callsign], ENT_QUOTES, 'UTF-8');
        }

        return str_replace('0', '&Oslash;', htmlspecialchars($callsign, ENT_QUOTES, 'UTF-8'));
    }
}

if (!function_exists('isLinkableCallsign')) {
    /**
     * Is this string worth pointing at QRZ?
     *
     * The log pads every callsign field to a fixed width and uses "??????????"
     * for unknowns, so we filter those out. A real callsign also has at least
     * one letter and one digit, which rejects placeholders like "ALL" and
     * "PARROT" that would otherwise become dead QRZ links.
     */
    function isLinkableCallsign($rawCallsign) {
        $cs = strtoupper(trim($rawCallsign));

        if ($cs === '' || $cs === '??????????' || is_numeric($cs)) {
            return false;
        }
        if (strpos($cs, '?') !== false) {
            return false;
        }
        // letters, digits, stroke and dash only (handles portables like G4KLX/P)
        if (!preg_match('/^[A-Z0-9\/-]{3,}$/', $cs)) {
            return false;
        }
        // must contain both a letter and a digit to be a callsign
        return (bool) (preg_match('/[A-Z]/', $cs) && preg_match('/[0-9]/', $cs));
    }
}

if (!function_exists('qrzBase')) {
    /**
     * QRZ wants the base callsign, not the portable suffix:
     * "G4KLX/P" and "W1AW/4" both look up as the part before the stroke.
     */
    function qrzBase($rawCallsign) {
        $cs    = strtoupper(trim($rawCallsign));
        $parts = explode('/', $cs);

        // pick the longest segment - handles both "G4KLX/P" and "F/G4KLX"
        $base = '';
        foreach ($parts as $p) {
            if (strlen($p) > strlen($base)) {
                $base = $p;
            }
        }
        return $base === '' ? $cs : $base;
    }
}

if (!function_exists('callsignCell')) {
    /**
     * The full HTML for a callsign table cell: alias, slashed zero, and a QRZ
     * link when appropriate. Used by every table so they all behave the same.
     *
     * Honours the dashboard's own SHOWQRZ and GDPR settings:
     *   - GDPR masks the callsign and never links it
     *   - SHOWQRZ absent means no links anywhere (same as upstream)
     */
    function callsignCell($rawCallsign) {
        $raw = trim($rawCallsign);

        if (defined('GDPR')) {
            return str_replace('0', '&Oslash;',
                htmlspecialchars(substr($raw, 0, 3), ENT_QUOTES, 'UTF-8') . '***');
        }

        $label = gatewayDisplayName($raw);

        if (!defined('SHOWQRZ') || !isLinkableCallsign($raw)) {
            return $label;
        }

        return '<a target="_new" rel="noopener noreferrer" href="https://qrz.com/db/'
             . rawurlencode(qrzBase($raw)) . '">' . $label . '</a>';
    }
}
