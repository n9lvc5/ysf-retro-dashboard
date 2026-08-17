<?php
include_once __DIR__ . "/../config/aliases.php";
?>
  <div class="card">
  <div class="card-header"><h2><strong>Connected YSFGateways</strong></h2></div>
  <div class="table-responsive">
  <table id="gateways" class="table table-condensed">
  	<thead>
    <tr>
      <th>Reporting Time (<?php echo TIMEZONE;?>)</th>
      <th>Callsign</th>
    </tr>
    </thead>
    <tbody>
<?php
	//$gateways = getConnectedGateways($logLines);
	$gateways = getLinkedGateways($logLines);
	foreach ($gateways as $gateway) {

		echo "<tr>";
		echo "<td>".convertTimezone($gateway['timestamp'])."</td>";

		echo"<td nowrap>".callsignCell($gateway["callsign"])."</td>";
		echo "</tr>";
	}
?>
  </tbody>
  </table>
  </div>
  <script>
    $(document).ready(function(){ 
      $('#gateways').dataTable( {
        "aaSorting": [[1,'asc']]
      } );
    });
   </script>
</div>
