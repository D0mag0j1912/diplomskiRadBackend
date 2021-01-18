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

//Funkcija koja dohvaća sve registrirane pacijente
function dohvatiSvePacijente(){
    //Dohvaćam bazu 
    $baza = new Baza();
    $conn = $baza->spojiSBazom();

    //Kreiram prazno polje odgovora
    $response = [];

    //Kreiram sql upit koji će provjeriti koliko ima pacijenata u bazi podataka
    $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM pacijent";
    //Rezultat upita spremam u varijablu $resultCountPacijent
    $resultCountPacijent = mysqli_query($conn,$sqlCountPacijent);
    //Ako rezultat upita ima podataka u njemu (znači nije prazan)
    if(mysqli_num_rows($resultCountPacijent) > 0){
        //Idem redak po redak rezultata upita 
        while($rowCountPacijent = mysqli_fetch_assoc($resultCountPacijent)){
            //Vrijednost rezultata spremam u varijablu $brojPacijenata
            $brojPacijenata = $rowCountPacijent['BrojPacijent'];
        }
    }
    //Ako nema pronađenih pacijenata
    if($brojPacijenata == 0){
        $response["success"] = "false";
        $response["message"] = "Nema pronađenih pacijenata!";
    }
    //Ako ima pronađenih pacijenata
    else{
        //Kreiram upit koji dohvaća podatke pacijenta za navedeni ID
        $sql = "SELECT * FROM pacijent 
                ORDER BY pacijent.idPacijent";
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
    }
    //Vraćam odgovor baze
    return $response;   
}

foreach(dohvatiSvePacijente() as $vanjsko){
    foreach($vanjsko as $pacijent){
        echo $pacijent."\n";
    }
}
?>