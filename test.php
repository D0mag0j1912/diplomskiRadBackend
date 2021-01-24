<?php
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

$servis = new OpciPodatciService();
//Kreiram prazno polje
$response = [];
//Kreiram objekt tipa "Baza"
$baza = new Baza();
$conn = $baza->spojiSBazom();
//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

/* $vrijeme = date("H:i");

//Ako su minute vremena == 0, ostavi kako jest
if((int)(date('i',strtotime($vrijeme))) === 0){
    $vrijeme = $vrijeme;
}
//Ako su minute vremena == 30, ostavi kako jest
else if( (int)(date('i',strtotime($vrijeme))) === 30){
    $vrijeme = $vrijeme;
}
//Ako su minute vremena > 0 && minute < 15, zaokruži na manji puni sat
else if( (int)(date('i',strtotime($vrijeme))) > 0 && (int)(date('i',strtotime($vrijeme))) < 15){
    $vrijeme = date("H:i", strtotime("-".(int)(date('i',strtotime($vrijeme)))." minutes", strtotime($vrijeme) ) );  
}
//Ako su minute vremena >= 15 && minute < 30, zaokruži na pola sata 
else if( (int)(date('i',strtotime($vrijeme))) >= 15 && (int)(date('i',strtotime($vrijeme))) < 30){
    $vrijeme = date("H:i", strtotime("+".(30-(int)(date('i',strtotime($vrijeme))))." minutes",strtotime($vrijeme) ) );
}
//Ako su minute vremena > 30 && minute < 45, zaokruži na pola sata
else if( (int)(date('i',strtotime($vrijeme))) > 30 && (int)(date('i',strtotime($vrijeme))) < 45){
    $vrijeme = date("H:i", strtotime("-".((int)(date('i',strtotime($vrijeme)))-30)." minutes", strtotime($vrijeme) ) );
}
//Ako su minute vremena >=45 && minute < 60, zaokruži na veći puni sat
else if( (int)(date('i',strtotime($vrijeme))) >= 45 && (int)(date('i',strtotime($vrijeme))) < 60){
    $vrijeme = date("H:i", strtotime("+".(60-(int)(date('i',strtotime($vrijeme))))." minutes",strtotime($vrijeme) ) );
}

echo $vrijeme; */

$cijeliLijek = "Xultophy%20otop.%20za%20inj.%2C%20brizg.%20napunj.%203x3%20mL%20(100%20U%2B3%2C6%20mg%2FmL)";
echo urldecode($cijeliLijek);
/* $pr = "otop. za inj., brizg. napunj. 3x3 mL (100 U+3,6 mg/mL)";
//Dohvaćam vrijednost izabranog lijeka
$lijek = mysqli_real_escape_string($conn, trim($cijeliLijek));
//Splitam string da mu uzmem ime i oblik-jačinu-pakiranje
$polje = explode(" ",$lijek,2);
//Dohvaćam ime lijeka
$imeLijek = $polje[0];
//Dohvaćam oblik,jačinu i pakiranje lijeka
$ojpLijek = $polje[1]; */
/* //Funkcija koja dohvaća cijene za LIJEK sa DOPUNSKE LISTE
function dohvatiCijenaLijekDL($lijek,$ojp,$cijeliLijek){
    //Dohvaćam bazu 
    $baza = new Baza();
    $conn = $baza->spojiSBazom();

    //Kreiram prazno polje odgovora
    $response = [];

    $sql = "SELECT d.cijenaLijek,d.cijenaZavod,d.doplataLijek FROM dopunskalistalijekova d 
            WHERE d.zasticenoImeLijek = '$lijek' 
            AND d.oblikJacinaPakiranjeLijek = '$ojp'";
    $result = $conn->query($sql);

    //Ako ima pronađenih rezultata za navedenu pretragu
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
    }
    //Ako nema pronađenih rezultata za navedenu pretragu, splittam ga na drugoj praznini
    else{
        //Razdvajam string na drugoj praznini
        $polje = preg_split ('/ /', $cijeliLijek, 3);
        //Dohvaćam oblik, jačinu i pakiranje lijeka
        $ojpLijek=array_pop($polje);
        //Dohvaćam naziv lijeka
        $nazivLijek=implode(" ", $polje);
        //Kreiram upit za dohvaćanje cijena
        $sqlDrugiSpace = "SELECT d.cijenaLijek,d.cijenaZavod,d.doplataLijek FROM dopunskalistalijekova d 
            WHERE d.zasticenoImeLijek = '$nazivLijek'
            AND d.oblikJacinaPakiranjeLijek = '$ojpLijek'";
        $resultDrugiSpace = $conn->query($sqlDrugiSpace);

        //Ako ima pronađenih rezultata za navedenu pretragu
        if ($resultDrugiSpace->num_rows > 0) {
            while($row = $resultDrugiSpace->fetch_assoc()) {
                $response[] = $row;
            }
        }
    }
    return $response;
}

foreach(dohvatiCijenaLijekDL($imeLijek,$ojpLijek,$lijek) as $vanjski){
    foreach($vanjski as $element){
        echo $element;
    }
}   */
?>