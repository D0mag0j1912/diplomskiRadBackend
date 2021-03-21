<?php
require_once 'C:\wamp64\www\diplomskiBackend\includes\autoloader.inc.php';

$servis = new OpciPodatciService();
//Kreiram prazno polje
$response = [];
//Kreiram objekt tipa "Baza"
$baza = new Baza();
$conn = $baza->spojiSBazom();
//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

$poljeBoja = ['#006400','#7FFFD4','#BDB76B','#40E0D0','#000000','#4B0082','#48D1CC','#D2691E','#FF0000'];
//Na početku inicijaliziram broj pronađenih boja na 0 (u slučaju da ne postoji još ova obrada i boja)
$brojBoja = 0;
//Generiram random boju
$boja = $poljeBoja[array_rand($poljeBoja)];
//Tražim je li se novo generirana boja nalazi u bazi
$sql = "SELECT COUNT(*) AS brojBoja FROM povijestBolesti pb 
        WHERE pb.idObradaLijecnik = 57 AND pb.bojaPregled = '$boja'";
$result = $conn->query($sql);
//Ako ima pronađenih rezultata za navedenu pretragu
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $brojBoja = $row['brojBoja'];
    }
}
//Dok ne pronađem boju koja još ne postoji u bazi za ovu sesiju obrade
while($brojBoja != 0){
    //Generiraj ponovno boju
    $boja = $poljeBoja[array_rand($poljeBoja)];
    //Ponovno traži
    $sql = "SELECT COUNT(*) AS brojBoja FROM povijestBolesti pb 
            WHERE pb.idObradaLijecnik = 57 AND pb.bojaPregled = '$boja'";
    $result = $conn->query($sql);
    //Ako ima pronađenih rezultata za navedenu pretragu
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $brojBoja = $row['brojBoja'];
        }
    }
}

echo $brojBoja."\n";
echo $boja;

?>