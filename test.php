<?php
include('./backend-path.php');
require_once BASE_PATH.'\includes\autoloader.inc.php';

$servis = new OpciPodatciService();
//Kreiram prazno polje
$response = [];
//Kreiram objekt tipa "Baza"
$baza = new Baza();
$conn = $baza->spojiSBazom();
//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');


//Označavam da slučajno generirana oznaka već postoji u bazi
$ispravan = false;
while($ispravan != true){
    //Generiram slučajni oznaku po kojom grupiram
    $oznaka = uniqid();
    //Kreiram upit koji provjerava postoji li već ova random generirana oznaka u bazi
    $sqlProvjeraOznaka = "SELECT u.oznaka FROM uputnica u 
                        WHERE u.oznaka = '$oznaka';";
    //Rezultat upita spremam u varijablu $resultProvjeraOznaka
    $resultProvjeraOznaka = mysqli_query($conn,$sqlProvjeraOznaka);
    //Ako se novo generirana oznaka NE NALAZI u bazi
    if(mysqli_num_rows($resultProvjeraOznaka) == 0){
        //Izlazim iz petlje
        $ispravan = true;
    } 
}
echo $oznaka;
?>