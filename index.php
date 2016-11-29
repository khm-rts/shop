<?php
// Inkludér fil der etablerer forbindelse til databasen (i variablen $link)
require 'db_config.php';

// Hvis ikke kurv er defineret i vores session, oprettes den med et tomt array som værdi
if ( !isset($_SESSION['kurv']) )	$_SESSION['kurv'] = [];
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
// Forespørgsel til at hente alle aktive produkter fra databasen, sorteret efter kategori og dernæst pris
$query =
	"SELECT
		produkt_id, produkt_varenr, produkt_navn, produkt_beskrivelse, produkt_pris, kategori_navn, producent_navn
	FROM
		produkter
	LEFT JOIN
		kategorier ON produkter.fk_kategori_id = kategorier.kategori_id
	LEFT JOIN
		producenter ON produkter.fk_producent_id = producenter.producent_id
	WHERE
		produkt_status = 1
	ORDER BY
		kategori_navn, produkt_pris";

// Send forespørgsel af produkter til databasen med mysqli_query(). Hvis der er fejl heri, stoppes videre indlæsning og fejlbesked vises
$result = mysqli_query($link, $query) or die( mysqli_error($link) . '<pre>' . $query . '</pre>' . 'Fejl i forespørgsel på linje: ' . __LINE__ . ' i fil: ' . __FILE__);

// Vis antallet af produkter med funktionen mysqli_num_rows() der returnerer antaller af rækker fra resultat ($result)
echo '<h2>Produkter (' . mysqli_num_rows($result) . ')';

// mysqli_fetch_assoc() returner data fra forespørgslen som et assoc array og vi gemmer data i variablen $row. Brug while til at løbe igennem alle rækker med produkter fra databasen
while( $row = mysqli_fetch_assoc($result) )
{
	?>
	<hr>
	<h3><?php echo $row['produkt_navn'] ?></h3>
	Varenr. <?php echo $row['produkt_varenr'] ?>
	<br><?php echo substr($row['produkt_beskrivelse'], 0, 100) . '...' // Brug substr() til kun at vise de første 100 karakterer af produktets beskrivelse ?>
	<br><strong><?php echo number_format($row['produkt_pris'], 2, ',', '.') // Brug number_format() til at formatere prisen med 2 decimaler, komma til adskillelse af decimaler og punktum for hvert tusinde i beløb. F.eks. 123.456,78 ?> kr.</strong>
	<br>
	<form action="kurv.php">
		<input type="hidden" name="tilfoej">
		<input type="hidden" name="produkt_id" value="<?php echo $row['produkt_id'] ?>" required>
		<label>
			Antal
			<input type="number" name="antal" value="1" required min="1" max="999">
		</label>
		<button type="submit">Tilføj til kurv</button>
	</form>
	<?php
}
?>
</body>
</html>
<?php
// Inkludér fil, der lukker forbindelsen til databasen og tømmer vores output buffer
require 'db_close.php';