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
$tip = "lijecnik";
$id = 7;
//Funkcija koja dohvaća sve otvorene slučajeve za trenutno aktivnog pacijenta
function dohvatiSveOtvoreneSlucajeve($tip,$id){
    //Dohvaćam bazu 
    $baza = new Baza();
    $conn = $baza->spojiSBazom();
    //Kreiram prazno polje odgovora
    $response = [];

    //Kreiram upit koji će provjeriti postoje li primarne dijagnoze (jer ako nema primarne, nema ni sekundarnih) za trenutno aktivnog pacijenta ZA TABLICU POVIJEST BOLESTI
    $sqlCountDijagnoza = "SELECT COUNT(DISTINCT(pb.mkbSifraPrimarna)) AS BrojDijagnoza FROM  povijestbolesti pb 
                        JOIN ambulanta a ON a.idPovijestBolesti = pb.idPovijestBolesti 
                        WHERE a.idPacijent = '$id';";
    //Rezultat upita spremam u varijablu $resultCountDijagnoza
    $resultCountDijagnoza = mysqli_query($conn,$sqlCountDijagnoza);
    //Ako rezultat upita ima podataka u njemu (znači nije prazan)
    if(mysqli_num_rows($resultCountDijagnoza) > 0){
        //Idem redak po redak rezultata upita 
        while($rowCountDijagnoza = mysqli_fetch_assoc($resultCountDijagnoza)){
            //Vrijednost rezultata spremam u varijablu $BrojDijagnoza
            $brojDijagnozaPovijestBolesti = $rowCountDijagnoza['BrojDijagnoza'];
        }
    }
    //Kreiram upit koji će provjeriti postoje li primarne dijagnoze (jer ako nema primarne, nema ni sekundarnih) za trenutno aktivnog pacijenta ZA TABLICU OPĆIH PODATAKA
    $sqlCountDijagnozaOpci = "SELECT COUNT(DISTINCT(p.mkbSifraPrimarna)) AS BrojDijagnoza FROM  pregled p 
                            JOIN ambulanta a ON a.idPregled = p.idPregled 
                            WHERE a.idPacijent = '$id';";
    //Rezultat upita spremam u varijablu $resultCountDijagnozaOpci
    $resultCountDijagnozaOpci = mysqli_query($conn,$sqlCountDijagnozaOpci);
    //Ako rezultat upita ima podataka u njemu (znači nije prazan)
    if(mysqli_num_rows($resultCountDijagnozaOpci) > 0){
        //Idem redak po redak rezultata upita 
        while($rowCountDijagnozaOpci = mysqli_fetch_assoc($resultCountDijagnozaOpci)){
            //Vrijednost rezultata spremam u varijablu $BrojDijagnoza
            $brojDijagnozaOpci = $rowCountDijagnozaOpci['BrojDijagnoza'];
        }
    }
    //Zbrajam primarne dijagnoze povijesti bolesti i primarne dijagnoze općih podataka pregleda
    $brojDijagnoza = $brojDijagnozaPovijestBolesti + $brojDijagnozaOpci;
    //Ako NEMA pronađenih dijagnoza za trenutno aktivnog pacijenta
    if($brojDijagnoza == 0){
        $response["success"] = "false";
        $response["message"] = "Nema aktivnih dijagnoza za pacijenta!";
    }
    //Ako IMA pronađenih dijagnoza za trenutno aktivnog pacijenta
    else{
        //Ako je tip prijavljenog korisnika "lijecnik":
        if($tip == "lijecnik"){
            $sql = "SELECT DISTINCT(pb.mkbSifraPrimarna), DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, d.imeDijagnoza AS NazivPrimarna,l.imeLijecnik AS OdgovornaOsoba FROM 	                         povijestbolesti pb
                    JOIN dijagnoze d ON pb.mkbSifraPrimarna = d.mkbSifra 
                    JOIN ambulanta a ON a.idPovijestBolesti = pb.idPovijestBolesti 
                    JOIN lijecnik l ON l.idLijecnik = a.idLijecnik
                    WHERE a.idPacijent = '$id' 
                    ORDER BY Datum DESC;";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }
        //Ako je tip prijavljenog korisnika "sestra":
        else if($tip == "sestra"){
            $sql = "SELECT DISTINCT(p.mkbSifraPrimarna), DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, d.imeDijagnoza AS NazivPrimarna,m.imeMedSestra AS OdgovornaOsoba FROM pregled p
                    JOIN dijagnoze d ON p.mkbSifraPrimarna = d.mkbSifra 
                    JOIN ambulanta a ON a.idPregled = p.idPregled 
                    JOIN med_sestra m ON m.idMedSestra = a.idMedSestra
                    WHERE a.idPacijent = '$id'
                    ORDER BY Datum DESC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }
    }
    return $response;
}

foreach(dohvatiSveOtvoreneSlucajeve($tip,$id) as $vanjsko){
    foreach($vanjsko as $podatak){
        echo $podatak;
    }
}
?>