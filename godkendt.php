<?php
// Inkludér fil der etablerer forbindelse til databasen (i variablen $link)
require 'db_config.php';

// Hvis ikke ordre_id er defineret i URL parametre smides man tilbahe til forsiden med produkter
if ( !isset($_GET['ordre_id']) )
{
	header('Location: index.php');
	exit;
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport"
		  content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>Produkter - Shop</title>
</head>
<body>
	<a href="kurv.php" style="float: right;">Kurv (<?php echo count($_SESSION['kurv']) ?>)</a>

	<h1>Shop</h1>

	<?php
	// Hent ordre_id fra adresselinjen og brug intval for at sikre imod SQL injections
	$ordre_id = intval($_GET['ordre_id']);

	// Hent oplysninger om ordre fra databasen for at kunne sende en mail. Hent kun med status 2 for at sikre der kun bliver vist en ordre der er gennemført
	$query =
		"SELECT
			ordre_pris_total, ordre_kunde_email, ordre_kunde_navn, ordre_kunde_adresse, ordre_kunde_post_nr, ordre_kunde_by
		FROM
			ordrer 
		WHERE
			ordre_id = $ordre_id
		AND
			fk_ordre_status_id = 2";

	// Send forespørgsel til databasen med mysqli_query(). Hvis der er fejl heri, stoppes videre indlæsning og fejlbesked vises
	$result = mysqli_query($link, $query) or die( mysqli_error($link) . '<pre>' . $query . '</pre>' . 'Fejl i forespørgsel på linje: ' . __LINE__ . ' i fil: ' . __FILE__);

	// Hvis ikke ordren blev fundet, vises denne besked
	if ( mysqli_num_rows($result) == 0 )
	{
		echo '<p>Fejl! Ordren blev ikke fundet. Kontakt os venligst for at høre om ordren er registreret.</p>';
	}
	// Hvis ordren blev fundet i databasen, sendes e-mail
	else
	{
		// mysqli_fetch_assoc() returner data fra forespørgslen som et assoc array og vi gemmer data i variablen $row.
		$row = mysqli_fetch_assoc($result);

		// For at sende HTML e-mail, skal bl.a. Content-Type og charset defineres i header
		$headers	 =	"MIME-Version: 1.0 \r\n";
		$headers	.=	"Content-Type: text/html; charset=UTF-8 \r\n";

		// Yderligere headers, hvor bl.a. afsender af e-mail angives
		$header1	 =	$headers . "From: Shoppens Navn <shop@eksempel.dk> \r\n";

		$besked		 = 	"Hej $row[ordre_kunde_navn],<br><br>
						Tak for din ordre på i alt " . number_format($row['ordre_pris_total'], 2, ',', '.') . " kr.<br><br>
						Vi har registreret følgende adresse:<br>
						$row[ordre_kunde_adresse], $row[ordre_kunde_post_nr] $row[ordre_kunde_by]<br><br>
						Med venlig hilsen<br>
						Shoppens signatur her";

		// Send mail til kunde vha. funktionen mail()
		mail($row['ordre_kunde_email'], 'Ordrebekræftelse på ordre nr. ' . $ordre_id, $besked, $header1);

		$besked		 = 'Kopi af kundens ordrebekræftelse<br><br>' . $besked;

		// Yderligere headers, hvor bl.a. afsender af e-mail angives
		$header2	 =	$headers . "From: $row[ordre_kunde_navn] <$row[ordre_kunde_email]> \r\n";

		// Send kopi til shop
		mail('shop@eksempel.dk', 'Ny ordre fra ' . $row['ordre_kunde_navn'], $besked, $header2);

		echo '<p>Tak for din ordre. Vi har sendt dig en ordrebekræftelse på din email ' . $row['ordre_kunde_email'];
	}
	?>
</body>
</html>
<?php
// Inkludér fil, der lukker forbindelsen til databasen og tømmer vores output buffer
require 'db_close.php';