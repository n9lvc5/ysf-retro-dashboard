<?php
include_once __DIR__ . "/../config/aliases.php";
?>
  <div class="card">
  <div class="card-header"><h2><strong>Alltime Heard List</strong></h2></div>
  <div class="table-responsive">
  <table id="oldallHeard" class="table table-condensed">
  <thead>
    <tr>
      <th>Time (<?php echo TIMEZONE;?>)</th>
      <th>Callsign</th>
      <th>Target</th>
      <th>Gateway</th>
    </tr>
  </thead>
  <tbody>
<?php
$rrTotal = count($oldallHeard);
$rrLimit = defined('RR_ALLTIME_LIMIT') ? (int) RR_ALLTIME_LIMIT : 0;
$rrShown = ($rrLimit > 0 && $rrTotal > $rrLimit) ? $rrLimit : $rrTotal;
for ($i = 0; $i < $rrShown; $i++) {
		$listElem = $oldallHeard[$i];
		echo"<tr>";
		echo"<td>$listElem[0]</td>";

		echo"<td nowrap>".callsignCell($listElem[1])."</td>";
		//echo"<td>$listElem[1]</td>";
		echo"<td>$listElem[2]</td>";
		echo"<td nowrap>".callsignCell($listElem[3])."</td>";
		echo"</tr>\n";
	}

?>
  </tbody>
  </table>
  </div>
<?php if ($rrLimit > 0 && $rrTotal > $rrShown) { ?>
  <div class="card-body rr-cap-note">Showing newest <?php echo $rrShown; ?> of <?php echo $rrTotal; ?> callsigns &middot; older entries remain in the logs</div>
<?php } ?>
  <script>
    $(document).ready(function(){
      $('#oldallHeard').dataTable( {
        "aaSorting": [[0,'desc']]
      } );
    });
   </script>
</div>
