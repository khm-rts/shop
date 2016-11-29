<?php
// Inkludér fil der etablerer forbindelse til databasen (i variablen $link)
require 'db_config.php';

// Hvis ikke kurv er defineret i vores session, oprettes den med et tomt array som værdi
if ( !isset($_SESSION['kurv']) ) $_SESSION['kurv'] = [];

// Hvis tilfoej, produkt_id og antal er defineret i vores URL parametre, skal der enten tilføjes eller fjernes et produkt fra kurven
if ( isset($_GET['tilfoej'], $_GET['produkt_id'], $_GET['antal']) )
{
	// Tjek vha. in_array om det aktuelle produkt_id, allerede er i vores kurv, for at tilføje det ønskede antal. Vi bruger array_keys() på vores array, da vi bruger produktets id som key i array
	if ( in_array($_GET['produkt_id'], array_keys($_SESSION['kurv']) ) )
	{
		// Vi bruger produktets id som key i vores array kurv og det ønskede antal som value. += plusser det ønskede antal til det eksisterende antal
		$_SESSION['kurv'][ $_GET['produkt_id'] /* key */ ] += $_GET['antal'];
	}
	// Hvis ikke det aktuelle produkt er i kurven, tilføjes det ønskede antal heraf
	else
	{
		// Vi bruger produktets id som key i vores array kurv og det ønskede antal som value
		$_SESSION['kurv'][ $_GET['produkt_id'] /* key */ ] = $_GET['antal'];
	}
}

// Hvis opdater og antal er posted fra formular, skal ønsket antal opdateres til hvert produkt i kurven
if ( isset($_POST['opdater'], $_POST['antal']) )
{
	// Antal er et array med produktets id som key og ønskede antal som value, derfor løber vi igennem array med en foreach-løkke
	foreach($_POST['antal'] as $produkt_id => $antal)
	{
		// Hvis det ønskede antal er større end 0, opdateres antal til det aktuelle produkt i kurven
		if ($antal > 0)
		{
			$_SESSION['kurv'][$produkt_id] = $antal;
		}
		// Hvis ikke det ønskede antal er større end 0, fjernes det aktuelle produkt fra kurven
		else
		{
			// Fjern det aktuelle produkt_id fra vores array kurv i session
			unset($_SESSION['kurv'][$produkt_id]);
		}
	}
}

// Hvis fjern og produkt_id er defineret i vores URL parametre, skal der fjernes et produkt fra kurven
if ( isset($_GET['fjern'], $_GET['produkt_id']) )
{
	// Fjern det aktuelle produkt_id fra vores array kurv i session
	unset($_SESSION['kurv'][ $_GET['produkt_id'] /* key */ ]);
}

// Hvis toem-kurv er posted fra vores formular, skal array kurv tømmes fra session
if ( isset($_POST['toem-kurv']) ) $_SESSION['kurv'] = [];
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport"
		  content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>Kurv - Shop</title>
</head>
<body>
	<h1><a href="index.php">Shop</a></h1>

	<h2>Kurv</h2>

	<form method="post">
	<?php
	// Tjek vha. count om der er noget i array kurv fra session, for at vise indholdet heraf
	if ( count($_SESSION['kurv']) > 0 )
	{
		// Brug array_keys() til at gemme alle produkt id'erne i et nyt array
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
		?>
		<table cellpadding="5">
			<thead>
				<tr>
					<th>Produkt</th>
					<th colspan="2">Antal</th>
					<th>Pris</th>
					<th>Total</th>
				</tr>
			</thead>

			<tbody>
			<?php
			// Definer variabel der skal bruges til at beregne sum af kurvens produkter i while-løkken.
			$kurv_sum	= 0;
			// mysqli_fetch_assoc() returner data fra forespørgslen som et assoc array og vi gemmer data i variablen $row. Brug while til at løbe igennem alle rækker fra databasen
			while( $row = mysqli_fetch_assoc($result) )
			{
				// Gem produktet antal fra array kurv i session til variablen $produkt_antal
				$produkt_antal	= $_SESSION['kurv'][ $row['produkt_id'] ];

				// Beregn produktets sum ved at gange det ønskede antal med produktets pris
				$produkt_sum	= $produkt_antal * $row['produkt_pris'];
				// Tilføj det aktuelle produks sum til kurvens sum
				$kurv_sum		+= $produkt_sum;
				?>
				<tr>
					<td>
						<a href="#">
							<?php echo $row['produkt_navn'] ?>
						</a>
					</td>

					<td>
						<input type="number" name="antal[<?php echo $row['produkt_id'] ?>]" value="<?php echo $produkt_antal ?>" title="Antal" required min="0" max="999">
					</td>

					<td>
						<a href="kurv.php?fjern&produkt_id=<?php echo $row['produkt_id'] ?>">Fjern</a>
					</td>

					<td align="right">
						<?php
						// Brug number_format() til at formatere produktets pris, med 2 decimaler, komma til at adskille kr og øre samt punktion til at adskille hvert tusinde i prisen
						echo number_format($row['produkt_pris'], 2, ',', '.')
						?> kr.
					</td>

					<td align="right"><?php echo number_format($produkt_sum, 2, ',', '.') ?> kr.</td>
				</tr>
				<?php
			}
			?>
			</tbody>

			<tfoot>
				<tr>
					<td colspan="4"><strong>Total beløb</strong></td>
					<td align="right"><strong><?php echo number_format($kurv_sum, 2, ',', '.') ?> kr.</strong></td>
				</tr>
				<tr>
					<td colspan="4"><small>Heraf moms</small></td>
					<td align="right"><small><?php echo number_format($kurv_sum * 0.2, 2, ',', '.') ?> kr.</small></td>
				</tr>
				<tr>
					<td>
						<a href="index.php">Tilbage til produkter</a>
					</td>

					<td>
						<button type="submit" name="toem-kurv">Tøm kurv</button>
					</td>

					<td>
						<button type="submit" name="opdater">Opdatér kurv</button>
					</td>

					<td align="right" colspan="2">
						<a href="adresse.php">Fortsæt til adresse</a>
					</td>
				</tr>
			</tfoot>
		</table>
		<?php
	}
	// Hvis kurven er tom, vises besked herom
	else
	{
		echo '<p>Din kurv er tom.</p>';
	}
	?>
	</form>
</body>
</html>
<?php
// Inkludér fil, der lukker forbindelsen til databasen og tømmer vores output buffer
require 'db_close.php';