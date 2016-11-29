<?php
// Inkludér fil der etablerer forbindelse til databasen (i variablen $link)
require 'db_config.php';

// Hvis ikke kurv er defineret i vores session, eller kurven er tom sendes man tilbage til kurv
if ( !isset($_SESSION['kurv']) || count($_SESSION['kurv']) == 0 )
{
	header('Location: kurv.php');
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
	<title>Kurv - Shop</title>
</head>
<body>
	<h1><a href="index.php">Shop</a></h1>

	<h2>Kassen</h2>

	<?php
	// Hvis ikke ordre_id er defineret i URL parametre, eller den ikke har nogen værdi, vises denne besked
	if ( !isset($_GET['ordre_id']) || empty($_GET['ordre_id']) )
	{
		echo '<p>Fejl! Ordren blev ikke oprettet. Gå tilbage til <a href="adresse.php">adresse</a> og prøv igen, eller kontakt os hvis problemet fortsætter.</p>';
	}
	else
	{
		// Hent ordre_id fra adresselinjen og brug intval for at sikre imod SQL injections
		$ordre_id = intval($_GET['ordre_id']);

		// Hvis koeb er posted fra formular, køres dette for at opdatére status på ordren
		if ( isset($_POST['koeb']) )
		{
			// Hvis ikke betingelser er afkrydset gemmes denne besked i variablen $status
			if ( !isset($_POST['accepter_betingelser']) )
			{
				echo '<p>Du har ikke accepteret vores betingelser.</p>';
			}
			else
			{
				// Opdatér ordrestatussens id til 2, der svarer til at ordren er gennemført
				$query =
					"UPDATE
						ordrer
					SET
						fk_ordre_status_id = 2
					WHERE
						ordre_id = $ordre_id";

				// Send forespørgsel til databasen med mysqli_query(). Hvis der er fejl heri, stoppes videre indlæsning og fejlbesked vises
				$result = mysqli_query($link, $query) or die( mysqli_error($link) . '<pre>' . $query . '</pre>' . 'Fejl i forespørgsel på linje: ' . __LINE__ . ' i fil: ' . __FILE__);

				// Køb gennemført, så tøm kurven
				$_SESSION['kurv'] = [];

				// Viderestil bruger til godkendt-side
				header('Location: godkendt.php?ordre_id=' . $ordre_id);
				exit;
			}
		}

		// Hent oplysninger om ordre fra databasen for at vise den. Hent kun med status 1 for at sikre der kun bliver vist en ordre der endnu ikke er gennemført
		$query =
			"SELECT
				ordre_pris_total, ordre_kunde_email, ordre_kunde_navn, ordre_kunde_adresse, ordre_kunde_post_nr, ordre_kunde_by
			FROM
				ordrer 
			WHERE
				ordre_id = $ordre_id
			AND
				fk_ordre_status_id = 1";

		// Send forespørgsel til databasen med mysqli_query(). Hvis der er fejl heri, stoppes videre indlæsning og fejlbesked vises
		$result = mysqli_query($link, $query) or die( mysqli_error($link) . '<pre>' . $query . '</pre>' . 'Fejl i forespørgsel på linje: ' . __LINE__ . ' i fil: ' . __FILE__);

		// Hvis ikke ordren blev fundet, vises denne besked
		if ( mysqli_num_rows($result) == 0 )
		{
			echo '<p>Fejl! Ordren blev ikke fundet. Gå tilbage til <a href="adresse.php">adresse</a> og prøv igen, eller kontakt os hvis problemet fortsætter.</p>';
		}
		// Hvis ordren blev fundet i databasen, vises den
		else
		{
			// mysqli_fetch_assoc() returner data fra forespørgslen som et assoc array og vi gemmer data i variablen $ordre.
			$ordre = mysqli_fetch_assoc($result);
			?>
			<h3>Adresse</h3>
			<p>
				<?php
				// Vis indtastet adresse
				echo $ordre['ordre_kunde_navn'];
				echo '<br>' . $ordre['ordre_kunde_adresse'];
				echo '<br>' . $ordre['ordre_kunde_post_nr'] . ' ' .$ordre['ordre_kunde_by'];
				echo '<br>E-mail: ' . $ordre['ordre_kunde_by'];
				?>
			</p>

			<h3>Produkter</h3>
			<form method="post">
				<?php
				// Hent alle produkter til ordren fra databasen
				$query =
					"SELECT 
						order_produkter_produkt_antal, order_produkter_produkt_navn, order_produkter_produkt_pris
					FROM 
						ordrer_produkter
					WHERE 
						fk_ordre_id = $ordre_id";

				// Send forespørgsel til databasen med mysqli_query(). Hvis der er fejl heri, stoppes videre indlæsning og fejlbesked vises
				$result = mysqli_query($link, $query) or die( mysqli_error($link) . '<pre>' . $query . '</pre>' . 'Fejl i forespørgsel på linje: ' . __LINE__ . ' i fil: ' . __FILE__);
				?>
				<table cellpadding="5">
					<thead>
					<tr>
						<th>Produkt</th>
						<th>Antal</th>
						<th>Pris</th>
						<th>Total</th>
					</tr>
					</thead>

					<tbody>
					<?php
					// mysqli_fetch_assoc() returner data fra forespørgslen som et assoc array og vi gemmer data i variablen $row. Brug while til at løbe igennem alle rækker fra databasen
					while( $row = mysqli_fetch_assoc($result) )
					{
						// Beregn produktets sum ved at gange det ønskede antal med produktets pris
						$produkt_sum	= $row['order_produkter_produkt_antal'] * $row['order_produkter_produkt_pris'];
						?>
						<tr>
							<td>
								<a href="#">
									<?php echo $row['order_produkter_produkt_navn'] ?>
								</a>
							</td>

							<td align="right">
								<?php echo $row['order_produkter_produkt_antal'] ?>
							</td>

							<td>
								<?php
								// Brug number_format() til at formatere produktets pris, med 2 decimaler, komma til at adskille kr og øre samt punktion til at adskille hvert tusinde i prisen
								echo number_format($row['order_produkter_produkt_pris'], 2, ',', '.')
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
						<td colspan="3"><strong>Total beløb</strong></td>
						<td align="right"><strong><?php echo number_format($ordre['ordre_pris_total'], 2, ',', '.') ?> kr.</strong></td>
					</tr>
					<tr>
						<td colspan="3"><small>Heraf moms</small></td>
						<td align="right"><small><?php echo number_format($ordre['ordre_pris_total'] * 0.2, 2, ',', '.') ?> kr.</small></td>
					</tr>
					<tr>
						<td colspan="4">
							<label>
								<input type="checkbox" name="accepter_betingelser" required>
								Accepter <a href="#">betingelser</a>
							</label>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<a href="adresse.php?ordre_id=<?php echo $ordre_id ?>">Tilbage til adresse</a>
						</td>

						<td align="right" colspan="2">
							<button type="submit" name="koeb">Bekræft køb</button>
						</td>
					</tr>
					</tfoot>
				</table>
			</form>
			<?php
		} // Close else to: if ( mysqli_num_rows($result) == 0 )
	} // Close else to: if ( !isset($_GET['ordre_id']) || empty($_GET['ordre_id']) )
	?>
</body>
</html>
<?php
// Inkludér fil, der lukker forbindelsen til databasen og tømmer vores output buffer
require 'db_close.php';