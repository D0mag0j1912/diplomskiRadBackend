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
$idPacijent = 3;
$idObrada = 41;
$mkbSifraPrimarna = 'A20';
//Funkcija koja dohvaća zadnje uneseni ID povijesti bolesti
function getIDPovijestBolesti($idPacijent,$idObrada,$mkbSifraPrimarna){
    //Dohvaćam bazu 
    $baza = new Baza();
    $conn = $baza->spojiSBazom();

    //Kreiram upit za dohvaćanjem MBO-a pacijenta kojemu se upisiva povijest bolesti
    $sqlMBO = "SELECT p.mboPacijent AS MBO FROM pacijent p 
            WHERE p.idPacijent = '$idPacijent'";
    //Rezultat upita spremam u varijablu $resultMBO
    $resultMBO = mysqli_query($conn,$sqlMBO);
    //Ako rezultat upita ima podataka u njemu (znači nije prazan)
    if(mysqli_num_rows($resultMBO) > 0){
        //Idem redak po redak rezultata upita 
        while($rowMBO = mysqli_fetch_assoc($resultMBO)){
            //Vrijednost rezultata spremam u varijablu $mboPacijent
            $mboPacijent = $rowMBO['MBO'];
        }
    }
      
    $sql = "SELECT pb.idPovijestBolesti FROM povijestBolesti pb 
        WHERE pb.mboPacijent = '$mboPacijent' 
        AND pb.idObradaLijecnik = '$idObrada' 
        AND pb.mkbSifraPrimarna = '$mkbSifraPrimarna' 
        AND pb.idPovijestBolesti = 
        (SELECT MAX(pb2.idPovijestBolesti) FROM povijestBolesti pb2 
        WHERE pb2.mboPacijent = '$mboPacijent' 
        AND pb2.idObradaLijecnik = '$idObrada' 
        AND pb2.mkbSifraPrimarna = '$mkbSifraPrimarna')";
    //Rezultat upita spremam u varijablu $resultMBO
    $result = mysqli_query($conn,$sql);
    //Ako rezultat upita ima podataka u njemu (znači nije prazan)
    if(mysqli_num_rows($result) > 0){
        //Idem redak po redak rezultata upita 
        while($row = mysqli_fetch_assoc($result)){
            //Vrijednost rezultata spremam u varijablu $idPovijestBolesti
            $idPovijestBolesti = $row['idPovijestBolesti'];
        }
    } 
    return $idPovijestBolesti;
}
echo getIDPovijestBolesti($idPacijent,$idObrada,$mkbSifraPrimarna);
?>