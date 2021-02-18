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
//Označavam da trenutno nisam našao lijek u osnovnoj ili dopunskoj listi lijekova
$pronasao = false;
//Definiram zaštićeno ime lijeka i postavljam ga na NULL trenutno
$zasticenoImeLijek = NULL;
//Definiram oblik, jačinu i pakiranje lijeka i postavljam ga na NULL trenutno
$oblikJacinaPakiranjeLijek = NULL;
//Postavljam brojač inicijalno na 2
$brojac = 2;

$dostatnost = 30;
$proizvod = "Rp. Afloderm krema 12,0 Belobaza ad 30,0 M.D.S. krema";
$datumRecept = "2021-02-15";
$vrijemeRecept = "15:44:00";
$idPacijent = 1;
$mkbSifraPrimarna = "A07.2";
//Funkcija koja dohvaća podatke PACIJENTA I RECEPTA za prikaz recepta
function dohvatiPacijentRecept($dostatnost,$datumRecept, 
                                $idPacijent,$mkbSifraPrimarna, 
                                $proizvod,$vrijemeRecept){
    //Dohvaćam bazu 
    $baza = new Baza();
    $conn = $baza->spojiSBazom();

    //Kreiram prazno polje odgovora
    $response = []; 
    //Označavam da trenutno nisam našao lijek u osnovnoj ili dopunskoj listi lijekova
    $pronasao = false;
    //Definiram zaštićeno ime lijeka i postavljam ga na NULL trenutno
    $zasticenoImeLijek = NULL;
    //Definiram oblik, jačinu i pakiranje lijeka i postavljam ga na NULL trenutno
    $oblikJacinaPakiranjeLijek = NULL;
    //Postavljam brojač inicijalno na 2
    $brojac = 2;
    //Dohvaćam OJP u OSNOVNOJ LISTI ako ga ima
    while($pronasao !== true){
        //Splitam string da mu uzmem ime i oblik-jačinu-pakiranje (KREĆEM OD 2)
        $polje = explode(" ",$proizvod,$brojac);
        //Dohvaćam oblik,jačinu i pakiranje lijeka
        $ojpLijek = array_pop($polje);
        //Dohvaćam ime lijeka
        $imeLijek = implode(" ", $polje);

        //Kreiram sql upit kojim provjeravam postoji li LIJEK u osnovnoj listi lijekova
        $sqlOsnovnaLista = "SELECT o.zasticenoImeLijek,o.oblikJacinaPakiranjeLijek FROM osnovnalistalijekova o 
                            WHERE o.oblikJacinaPakiranjeLijek = '$ojpLijek' AND o.zasticenoImeLijek = '$imeLijek'";

        $resultOsnovnaLista = $conn->query($sqlOsnovnaLista);
        //Ako je lijek pronađen u OSNOVNOJ LISTI LIJEKOVA
        if ($resultOsnovnaLista->num_rows > 0) {
            while($rowOsnovnaLista = $resultOsnovnaLista->fetch_assoc()) {
                //Dohvaćam oblik, jačinu i pakiranje unesenog lijeka
                $oblikJacinaPakiranjeLijek = $rowOsnovnaLista['oblikJacinaPakiranjeLijek'];
                $zasticenoImeLijek = $rowOsnovnaLista['zasticenoImeLijek'];
            }
            //Izlazim iz petlje
            $pronasao = true;
        }
        //Povećavam brojač za 1
        $brojac++;
        if($brojac === 20){
            break;
        }
    }
    //Ako je proizvod pronađen u OSNOVNOJ LISTI
    if($pronasao == true){
        //Kreiram sql upit koji će dohvatiti podatke pacijenta i recepta
        $sql = "SELECT r.*,CONCAT(p.imePacijent,' ',p.prezPacijent) AS imePrezimePacijent, 
                DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS DatumRodenja, p.adresaPacijent FROM recept r 
                JOIN pacijent p ON p.idPacijent = r.idPacijent 
                WHERE r.dostatnost = '$dostatnost' AND r.datumRecept = '$datumRecept' 
                AND r.idPacijent = '$idPacijent' AND r.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                AND r.proizvod = '$zasticenoImeLijek' AND r.oblikJacinaPakiranjeLijek = '$oblikJacinaPakiranjeLijek' AND r.vrijemeRecept = '$vrijemeRecept'";
        $result = $conn->query($sql);

        //Ako pacijent IMA evidentiranih recepata:
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
            return $response;
        }
    }
    //Ako su OJP i zaštićeno ime lijeka JOŠ NULL, sljedeće provjeravam u dopunskoj listi lijekova
    if($oblikJacinaPakiranjeLijek == NULL && $zasticenoImeLijek == NULL){
        $pronasao = false;
        //Postavljam brojač inicijalno na 2
        $brojac = 2;
        //Dohvaćam OJP ako ga ima
        while($pronasao !== true){
            //Splitam string da mu uzmem ime i oblik-jačinu-pakiranje (KREĆEM OD 2)
            $polje = explode(" ",$proizvod,$brojac);
            //Dohvaćam oblik,jačinu i pakiranje lijeka
            $ojpLijek = array_pop($polje);
            //Dohvaćam ime lijeka
            $imeLijek = implode(" ", $polje);

            //Kreiram sql upit kojim provjeravam postoji li LIJEK u osnovnoj listi lijekova
            $sqlDopunskaLista = "SELECT d.zasticenoImeLijek,d.oblikJacinaPakiranjeLijek FROM dopunskalistalijekova d 
                                WHERE d.oblikJacinaPakiranjeLijek = '$ojpLijek' AND d.zasticenoImeLijek = '$imeLijek'";

            $resultDopunskaLista = $conn->query($sqlDopunskaLista);
            //Ako je lijek pronađen u DOPUNSKOJ LISTI LIJEKOVA
            if ($resultDopunskaLista->num_rows > 0) {
                while($rowDopunskaLista = $resultDopunskaLista->fetch_assoc()) {
                    //Dohvaćam oblik, jačinu i pakiranje unesenog lijeka
                    $oblikJacinaPakiranjeLijek = $rowDopunskaLista['oblikJacinaPakiranjeLijek'];
                    $zasticenoImeLijek = $rowDopunskaLista['zasticenoImeLijek'];
                }
                //Izlazim iz petlje
                $pronasao = true;
            }
            //Povećavam brojač za 1
            $brojac++;
            if($brojac === 20){
                break;
            }
        }
        //Kreiram sql upit koji će dohvatiti podatke pacijenta i recepta
        $sql = "SELECT r.*,CONCAT(p.imePacijent,' ',p.prezPacijent) AS imePrezimePacijent, 
                DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS DatumRodenja, p.adresaPacijent FROM recept r 
                JOIN pacijent p ON p.idPacijent = r.idPacijent 
                WHERE r.dostatnost = '$dostatnost' AND r.datumRecept = '$datumRecept' 
                AND r.idPacijent = '$idPacijent' AND r.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                AND r.proizvod = '$zasticenoImeLijek' AND r.oblikJacinaPakiranjeLijek = '$oblikJacinaPakiranjeLijek' AND r.vrijemeRecept = '$vrijemeRecept'";
        $result = $conn->query($sql);

        //Ako pacijent IMA evidentiranih recepata:
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
            return $response;
        }
    }
    //Ako su OJP i zaštićeno ime lijeka I DALJE NULL
    //Kreiram sql upit koji će dohvatiti podatke pacijenta i recepta
    $sql = "SELECT r.*,CONCAT(p.imePacijent,' ',p.prezPacijent) AS imePrezimePacijent, 
            DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS DatumRodenja, p.adresaPacijent FROM recept r 
            JOIN pacijent p ON p.idPacijent = r.idPacijent 
            WHERE r.dostatnost = '$dostatnost' AND r.datumRecept = '$datumRecept' 
            AND r.idPacijent = '$idPacijent' AND r.mkbSifraPrimarna = '$mkbSifraPrimarna' 
            AND r.proizvod = '$proizvod' AND r.vrijemeRecept = '$vrijemeRecept'";
    $result = $conn->query($sql);

    //Ako pacijent IMA evidentiranih recepata:
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
    }

    //Vraćam odgovor 
    return $response;
}
foreach($response as $recept){
    foreach($recept as $r){
        echo $r;
    }
}

?>