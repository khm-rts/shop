<pre>SESSION <?php print_r($_SESSION) // Vis hvad der er gemt i SESSION ?></pre>
<pre>POST <?php print_r($_POST) // Vis hvad der bliver sendt via POST ?></pre>
<?php
// Luk forbindelsen til databasen, for at at undgå for mange åbne forbindelser
mysqli_close($link);

// Tøm output bufferen, når al html er genereret, for at forhindre performance problemer på server
ob_end_flush();