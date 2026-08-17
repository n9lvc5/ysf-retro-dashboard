<?php
include_once __DIR__ . "/../config/aliases.php";
?>
  <div class="card">
  <div class="card-header"> <h2><strong>Todays Heard List</strong></h2></div>
  <div class="table-responsive">
  <table id="allHeard" class="table table-condensed">
  <thead>
    <tr>
      <th>Time (<?php echo TIMEZONE;?>)</th>
      <th>Callsign</th>
      <th>Target</th>
      <th>Gateway</th>
      <th>Dur (s)</th>
    </tr>
  </thead>
  <tbody>
<?php
for ($i = 0; $i < count($allHeard); $i++) {
		$listElem = $allHeard[$i];
		echo"<tr>";
		echo"<td>$listElem[0]</td>";

		echo"<td nowrap>".callsignCell($listElem[1])."</td>";
		//echo"<td>$listElem[1]</td>";
		echo"<td>$listElem[2]</td>";
		echo"<td nowrap>".callsignCell($listElem[3])."</td>";
		echo"<td>$listElem[4]</td>";
		echo"</tr>\n";
	}

?>
  </tbody>
  </table>
  </div>
  <script>
    $(document).ready(function(){
      $('#allHeard').dataTable( {
        "aaSorting": [[0,'desc']]
      } );
    });
   </script>
</div>
