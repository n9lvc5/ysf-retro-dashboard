<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

require_once "config/config.php";
require_once "include/tools.php";
require_once "include/functions.php";

$configs = getYSFReflectorConfig();
$logLines = getShortYSFReflectorLog();

// Sort log lines in reverse (latest first)
$reverseLogLines = $logLines;
array_multisort($reverseLogLines, SORT_DESC);

// Get last heard entry
$lastHeard = getLastHeard($reverseLogLines, true);
$listElem = $lastHeard[0] ?? null;

if ($listElem && isset($listElem[1]) && strlen($listElem[1]) !== 0) {
    echo "<tr>";
    echo "<td nowrap>" . htmlspecialchars($listElem[0]) . "</td>";

    // Callsign display logic
    $callsign = htmlspecialchars($listElem[1]);
    $callsignDisplay = str_replace("0", "&Oslash;", $callsign);

    if (defined("SHOWQRZ") && $callsign !== "??????????" && !is_numeric($callsign)) {
        echo "<td nowrap><a target=\"_new\" href=\"https://qrz.com/db/$callsign\">$callsignDisplay</a></td>";
    } else {
        if (defined("GDPR")) {
            echo "<td nowrap>" . str_replace("0", "&Oslash;", substr($callsign, 0, 3) . "***") . "</td>";
        } else {
            echo "<td nowrap>$callsignDisplay</td>";
        }
    }

    // Target
    echo "<td nowrap>" . htmlspecialchars($listElem[2]) . "</td>";

    // Source
    $source = htmlspecialchars($listElem[3]);
    if (defined("GDPR")) {
        echo "<td nowrap>" . str_replace("0", "&Oslash;", substr($source, 0, 3) . "***") . "</td>";
    } else {
        echo "<td nowrap>" . str_replace("0", "&Oslash;", $source) . "</td>";
    }

    // Time difference
    try {
        $d1 = new DateTime($listElem[0], new DateTimeZone(TIMEZONE));
        $d2 = new DateTime('now', new DateTimeZone(TIMEZONE));
        $diff = $d2->getTimestamp() - $d1->getTimestamp();
        echo "<td nowrap>{$diff} s</td>";
    } catch (Exception $e) {
        echo "<td nowrap>time error</td>";
    }

    echo "</tr>";
} else {
    echo "<tr><td colspan=\"5\"></td></tr>";
}
?>
