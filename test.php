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

//Funkcija koja dohvaća inicijalne dijagnoze u unosu novog recepta
function dohvatiInicijalneDijagnoze($idPacijent){
    //Dohvaćam bazu 
    $baza = new Baza();
    $conn = $baza->spojiSBazom();
    //Kreiram prazno polje
    $response = [];

    //Kreiram upit kojim dohvaćam ZADNJE UNESENU primarnu dijagnozu povijesti bolesti za određenog pacijenta
    $sqlZadnjaPrimarna = "SELECT pb.mkbSifraPrimarna FROM povijestbolesti pb
                        WHERE pb.idPovijestBolesti = 
                        (SELECT MAX(pb2.idPovijestBolesti) FROM povijestbolesti pb2
                        WHERE pb.mboPacijent = pb2.mboPacijent)
                        AND pb.mboPacijent IN 
                        (SELECT pacijent.mboPacijent FROM pacijent 
                        WHERE pacijent.idPacijent = '$idPacijent')
                        GROUP BY pb.mboPacijent;";
    $resultZadnjaPrimarna = $conn->query($sqlZadnjaPrimarna);
    //Ako postoji primarna dijagnoza zabilježena u povijesti bolesti za OVOG PACIJENTA
    if($resultZadnjaPrimarna->num_rows > 0){
        while($rowZadnjaPrimarna = $resultZadnjaPrimarna->fetch_assoc()) {
            //Dohvaćam tu primarnu dijagnozu
            $mkbPrimarnaDijagnoza = $rowZadnjaPrimarna['mkbSifraPrimarna'];
        }
    }
    //Ako NE POSTOJI primarna dijagnoza zabilježena u povijesti bolesti za OVOG PACIJENTA
    else{
        //Vrati null
        return null;
    } 

    //Ako POSTOJI primarna dijagnoza, kreiram upit koji dohvaća sve njezine sekundarne dijagoze
    $sql = "SELECT DISTINCT(d.imeDijagnoza) AS NazivPrimarna, 
            IF(pb.mkbSifraSekundarna = NULL, NULL, (SELECT d2.imeDijagnoza FROM dijagnoze d2 WHERE d2.mkbSifra = pb.mkbSifraSekundarna)) AS NazivSekundarna,pb.* FROM povijestBolesti pb 
            JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna
            WHERE pb.mkbSifraPrimarna = '$mkbPrimarnaDijagnoza' 
            AND pb.mboPacijent IN 
            (SELECT pacijent.mboPacijent FROM pacijent 
            WHERE pacijent.idPacijent = '$idPacijent')";
    $result = $conn->query($sql);
    //Ako ima rezultata
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $response = $row;
        }
    }
    return $response;
}

foreach(dohvatiInicijalneDijagnoze(4) as $vanjsko){
    echo $vanjsko;
} 
?>