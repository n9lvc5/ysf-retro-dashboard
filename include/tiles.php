<?php
/*
 * Retro Radio theme - stat tile row.
 * Additive: rendered above the existing panels, changes no existing markup.
 * IDs here are the update targets for js/live.js.
 */
include_once __DIR__ . '/rrstats.php';
include_once __DIR__ . '/../config/aliases.php';   // RR_TEMP_CRITICAL lives here

$rrGateways = is_array($gateways ?? null) ? count($gateways) : count(getLinkedGateways($logLines));
$rrTemp     = rr_cputemp();     // Celsius - for the threshold comparison
$rrTempF    = rr_cputemp_f();   // Fahrenheit - for display
$rrOnAir    = rr_onair($lastHeard ?? array());
$rrWarnAt   = defined('TEMPERATUREHIGHLEVEL') ? (int) TEMPERATUREHIGHLEVEL : 60;
$rrCritAt   = defined('RR_TEMP_CRITICAL') ? (int) RR_TEMP_CRITICAL : 80;
$rrHot      = ($rrTemp !== null && $rrTemp >= $rrCritAt);   // red, blinking
$rrWarm     = ($rrTemp !== null && !$rrHot && $rrTemp >= $rrWarnAt);  // amber, pulsing
?>
<div class="rr-tiles">

  <div class="rr-tile">
    <div class="rr-tile-value" id="rr-gateways"><?php echo str_pad((string) $rrGateways, 2, '0', STR_PAD_LEFT); ?></div>
    <div class="rr-tile-label">Gateways</div>
  </div>

  <div class="rr-tile">
    <div class="rr-tile-value" id="rr-lastcall"><?php
        echo htmlspecialchars(rr_lastcall($lastHeard ?? array()), ENT_QUOTES, 'UTF-8');
    ?></div>
    <div class="rr-tile-label">Last Heard</div>
  </div>

  <div class="rr-tile<?php echo $rrHot ? ' is-hot' : ($rrWarm ? ' is-warm' : ''); ?>" id="rr-temp-tile">
    <div class="rr-tile-value" id="rr-temp"><?php echo $rrTempF === null ? '--' : $rrTempF . '&deg;F'; ?></div>
    <div class="rr-tile-label">CPU Temp</div>
  </div>

  <div class="rr-tile">
    <div class="rr-tile-value" id="rr-uptime"><?php echo htmlspecialchars(rr_uptime(), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="rr-tile-label">Uptime</div>
  </div>

  <div class="rr-tile">
    <div class="rr-tile-value" id="rr-port"><?php echo htmlspecialchars(getConfigItem('Network', 'Port', $configs), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="rr-tile-sub" id="rr-extip"><?php echo htmlspecialchars(rr_extip(), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="rr-tile-label">UDP Port / Public IP</div>
  </div>

</div>
