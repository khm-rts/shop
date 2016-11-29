<?php
// Inkludér fil der etablerer forbindelse til databasen (i variablen $link)
require 'db_config.php';

// Hvis ikke kurv er defineret i vores session, eller kurven er tom sendes man tilbage til kurv
if ( !isset($_SESSION['kurv']) || count($_SESSION['kurv']) == 0 ) header('Location: kurv.php');

// Definer variabler der bruges til formular
$email = $navn = $adresse = $post_nr = $by = '';

// Hvis ordre_id er defineret i URL parametre, er ordren allerede oprettet og de gemte kundeoplysninger hentes fra databasen
if ( isset($_GET['ordre_id']) )
{
	// Hent ordre_id fra adresselinjen og brug intval for at sikre imod SQL injections
	$ordre_id = intval($_GET['ordre_id']);

	// Hent kundes navn, adresse mv. fra databasen
	$query =
	    "SELECT
	        ordre_kunde_email, ordre_kunde_navn, ordre_kunde_adresse, ordre_kunde_post_nr, ordre_kunde_by
	    FROM
	        ordrer 
	    WHERE
	        ordre_id = $ordre_id";

	// Send forespørgsel til databasen med mysqli_query(). Hvis der er fejl heri, stoppes videre indlæsning og fejlbesked vises
	$result = mysqli_query($link, $query) or die( mysqli_error($link) . '<pre>' . $query . '</pre>' . 'Fejl i forespørgsel på linje: ' . __LINE__ . ' i fil: ' . __FILE__);

	// mysqli_fetch_assoc() returner data fra forespørgslen som et assoc array og vi gemmer data i variablen $row.
	$row = mysqli_fetch_assoc($result);

	// Overskriv variabler med værdi fra databasen
	$email		= $row['ordre_kunde_email'];
	$navn		= $row['ordre_kunde_navn'];
	$adresse	= $row['ordre_kunde_adresse'];
	$post_nr	= $row['ordre_kunde_post_nr'];
	$by			= $row['ordre_kunde_by'];
}

// Hvis kundeoplysninger er indtastet, så gemmes ordre i databasen
if ( isset($_POST['kunde']) )
{
	// Hent værdier fra formular og sikre imod SQL injections når variabler bruges i SQL-sætning
	$email		= mysqli_real_escape_string($link, $_POST['kunde']['email']);
	$navn		= mysqli_real_escape_string($link, $_POST['kunde']['navn']);
	$adresse	= mysqli_real_escape_string($link, $_POST['kunde']['adresse']);
	$post_nr	= intval($_POST['kunde']['post_nr']);
	$by			= mysqli_real_escape_string($link, $_POST['kunde']['by']);

	// Hvis et af de pøkrævede felter er tomme, gemmes fejlbesked i variablen $status
	if ( empty($_POST['kunde']['email']) || empty($_POST['kunde']['navn']) || empty($_POST['kunde']['adresse']) || empty($_POST['kunde']['post_nr']) || empty($_POST['kunde']['by']) )
	{
		$status = '<p>Fejl! Du har ikke udfyldt alle felter</p>';
	}
	// Hvis alle påkrævede felter er udfyldt, kan ordren oprettes eller opdateres
	else
	{
		// Hvis ordre_id er defineret i URL parametre, er ordren allerede oprettet, så vi opdaterer den
		if ( isset($_GET['ordre_id']) )
		{
			// Hent ordre_id fra adresselinjen og brug intval for at sikre imod SQL injections
			$ordre_id = intval($_GET['ordre_id']);

			// Opdatér kundeoplysninger i databasen
			$query =
				"UPDATE
					ordrer
				SET
					ordre_kunde_email = '$email', ordre_kunde_navn = '$navn', ordre_kunde_adresse = '$adresse', ordre_kunde_post_nr = $post_nr, ordre_kunde_by = '$by'
				WHERE
					ordre_id = $ordre_id";

			// Send forespørgsel til databasen med mysqli_query(). Hvis der er fejl heri, stoppes videre indlæsning og fejlbesked vises
			$result = mysqli_query($link, $query) or die(mysqli_error($link) . '<pre>' . $query . '</pre>' . 'Fejl i forespørgsel på linje: ' . __LINE__ . ' i fil: ' . __FILE__);
		}
		// Hvis ikke ordre_id er defineret i URL parametre, så skal ordren oprettes
		else
		{
			// Opret ordren i databasen
			$query =
				"INSERT INTO
					ordrer (ordre_kunde_email, ordre_kunde_navn, ordre_kunde_adresse, ordre_kunde_post_nr, ordre_kunde_by)
				VALUES
					('$email', '$navn', '$adresse', $post_nr, '$by')";

			// Send forespørgsel til databasen med mysqli_query(). Hvis der er fejl heri, stoppes videre indlæsning og fejlbesked vises
			$result = mysqli_query($link, $query) or die(mysqli_error($link) . '<pre>' . $query . '</pre>' . 'Fejl i forespørgsel på linje: ' . __LINE__ . ' i fil: ' . __FILE__);

			// Hent det oprettede ordre id
			$ordre_id = mysqli_insert_id($link);

			// Brug array_keys() til at gemme alle produkt id'erne fra kurven i et nyt array
			$produkt_id_array	= array_keys($_SESSION['kurv']);

			// Brug array_map() til at løbe igennem alle værdier og køre funktionen intval() herpå, for at sikre de kun indeholder tal, for at sikre imod SQL injections. Overskriv array
			$produkt_id_array	= array_map('intval', $produkt_id_array);

			// Brug implode() til at lave vores array af produkt id'er om til en kommesepareret streng, der kan bruges i SQL-sætning med IN ()
			$produkt_id_csv		= implode(', ', $produkt_id_array);

			// Hent alle aktive produkter fra databasen som matcher produkt id'erne fra $produkt_id_csv
			$query =
				"SELECT 
					produkt_id, produkt_navn, produkt_pris
				FROM 
					produkter
				WHERE 
					produkt_id IN ($produkt_id_csv) 
				AND 
					produkt_status = 1";
			// Send forespørgsel til databasen med mysqli_query(). Hvis der er fejl heri, stoppes videre indlæsning og fejlbesked vises
			$result = mysqli_query($link, $query) or die( mysqli_error($link) . '<pre>' . $query . '</pre>' . 'Fejl i forespørgsel på linje: ' . __LINE__ . ' i fil: ' . __FILE__);

			// Definer variabel der skal bruges til at beregne sum af kurvens produkter i while-løkken.
			$ordre_sum	= 0;
			// mysqli_fetch_assoc() returner data fra forespørgslen som et assoc array og vi gemmer data i variablen $row. Brug while til at løbe igennem alle rækker fra databasen for at indsætte produkter til ordren
			while( $row = mysqli_fetch_assoc($result) )
			{
				// Gem produktet antal fra array produkter i array ordre i session til variablen $produkt_antal
				$produkt_antal = intval($_SESSION['kurv'][ $row['produkt_id'] ]);

				// Beregn produktets sum ved at gange det ønskede antal med produktets pris
				$produkt_sum = $produkt_antal * $row['produkt_pris'];

				// Tilføj det aktuelle produks sum til kurvens sum
				$ordre_sum += $produkt_sum;

				// Opret hver produkt til ordren i databasen
				$query =
					"INSERT INTO
						ordrer_produkter (order_produkter_produkt_antal, order_produkter_produkt_navn, order_produkter_produkt_pris, fk_produkt_id, fk_ordre_id)
					VALUES
						($produkt_antal, '$row[produkt_navn]', $row[produkt_pris], $row[produkt_id], $ordre_id)";

				// Send forespørgsel til databasen med mysqli_query(). Hvis der er fejl heri, stoppes videre indlæsning og fejlbesked vises
				mysqli_query($link, $query) or die( mysqli_error($link) . '<pre>' . $query . '</pre>' . 'Fejl i forespørgsel på linje: ' . __LINE__ . ' i fil: ' . __FILE__);
			}

			// Opdatér ordrens sum da den er beregnet på ny herover
			$query =
				"UPDATE
					ordrer
				SET
					ordre_pris_total = $ordre_sum
				WHERE
					ordre_id = $ordre_id";

			// Send forespørgsel til databasen med mysqli_query(). Hvis der er fejl heri, stoppes videre indlæsning og fejlbesked vises
			$result = mysqli_query($link, $query) or die( mysqli_error($link) . '<pre>' . $query . '</pre>' . 'Fejl i forespørgsel på linje: ' . __LINE__ . ' i fil: ' . __FILE__);
		}

		// Viderestil til kassen og send ordre_id med
		header('Location: kassen.php?ordre_id=' . $ordre_id);
		exit;
	}// Luk else til: if ( empty($_POST['kunde']['navn']) || ...
} // Luk: if ( isset($_POST['kunde']) )
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
	<h1><a href="index.php">Shop</a></h1>

	<h2>Adresse</h2>

	<form method="post">
		<?php if ( isset($status) ) echo $status; // Hvis $status er defineret, udskrives besked heri ?>
		<table cellpadding="5">
			<tr>
				<th align="left">
					<label for="email">E-mailadresse:</label>
				</th>
				<td>
					<input type="email" name="kunde[email]" id="email" required autofocus value="<?php echo $email ?>">
				</td>
			</tr>
			<tr>
				<th align="left">
					<label for="navn">Fulde navn:</label>
				</th>
				<td>
					<input type="text" name="kunde[navn]" id="navn" required value="<?php echo $navn ?>">
				</td>
			</tr>
			<tr>
				<th align="left">
					<label for="adresse">Adresse:</label>
				</th>
				<td>
					<input type="text" name="kunde[adresse]" id="adresse" required value="<?php echo $adresse ?>">
				</td>
			</tr>
			<tr>
				<th align="left">
					<label for="post_nr">Post nr.:</label>
				</th>
				<td>
					<input type="number" name="kunde[post_nr]" id="post_nr" required value="<?php echo $post_nr ?>">
				</td>
			</tr>
			<tr>
				<th align="left">
					<label for="by">By:</label>
				</th>
				<td>
					<input type="text" name="kunde[by]" id="by" required value="<?php echo $by ?>">
				</td>
			</tr>
			<tr>
				<td><a href="kurv.php">Tilbage til kurv</a></td>
				<td colspan="2" align="right">
					<button type="submit">Fortsæt til kassen</button>
				</td>
			</tr>
		</table>
	</form>

</body>
</html>
<?php
// Inkludér fil, der lukker forbindelsen til databasen og tømmer vores output buffer
require 'db_close.php';