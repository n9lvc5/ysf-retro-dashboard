<?php
include_once __DIR__ . "/../config/aliases.php";
?>
  <div class="card">
  <div class="card-header"><h2><strong>Last Heard List</strong></h2></div>
  <div class="table-responsive">
  <table id="lh" class="table table-condensed">
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
for ($i = 0; $i < count($lastHeard); $i++) {
		$listElem = $lastHeard[$i];
		echo"<tr>";
		echo"<td>$listElem[0]</td>";
		echo"<td nowrap>".callsignCell($listElem[1])."</td>";
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
      $('#lh').dataTable( {
        "aaSorting": [[0,'desc']]
      } );
    });
   </script>
</div>
