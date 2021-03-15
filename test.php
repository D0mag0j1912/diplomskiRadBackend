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

$dostatnost = 30;
$datumRecept = "2021-03-15";
$idPacijent = 4;
$mkbSifraPrimarna = "A20.1";
$proizvod = "Glypvilo tbl. 60x50 mg";
$vrijemeRecept = "12:14:00";
//Funkcija koja dohvaća sve podatke recepta u svrhu njihovog prikazivanja u formi (AŽURIRANJE RECEPTA)
function dohvatiRecept($dostatnost,$datumRecept, 
                    $idPacijent,$mkbSifraPrimarna, 
                    $proizvod,$vrijemeRecept){
    //Dohvaćam bazu 
    $baza = new Baza();
    $conn = $baza->spojiSBazom();
    //Kreiram prazno polje odgovora
    $response = []; 
    //Inicijalno definiram da nisam pronašao recept
    $pronasao = false;
    //Inicijalno postavljam brojač na 2
    $brojac = 2;
    //Definiram zaštićeno ime lijeka i postavljam ga na NULL trenutno
    $zasticenoImeLijek = NULL;
    //Definiram oblik, jačinu i pakiranje lijeka i postavljam ga na NULL trenutno
    $oblikJacinaPakiranjeLijek = NULL;
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
        if($brojac == 20){
            break;
        }
    }
    //Ako je proizvod pronađen u OSNOVNOJ LISTI
    if($pronasao == true){
        //Kreiram sql upit koji će dohvatiti podatke pacijenta i recepta
        $sql = "SELECT r.mkbSifraPrimarna, d.imeDijagnoza AS nazivPrimarna, GROUP_CONCAT(DISTINCT r.mkbSifraSekundarna SEPARATOR ' ') AS mkbSifraSekundarna,
                r.proizvod, r.oblikJacinaPakiranjeLijek, r.kolicina, r.doziranje, 
                r.dostatnost, r.hitnost, r.ponovljiv, r.brojPonavljanja, r.sifraSpecijalist, 
                r.idPacijent, r.datumRecept, r.vrijemeRecept FROM recept r 
                JOIN dijagnoze d ON d.mkbSifra = r.mkbSifraPrimarna
                WHERE r.dostatnost = '$dostatnost' AND r.datumRecept = '$datumRecept' 
                AND r.idPacijent = '$idPacijent' AND r.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                AND r.proizvod = '$zasticenoImeLijek' AND r.oblikJacinaPakiranjeLijek = '$oblikJacinaPakiranjeLijek' 
                AND r.vrijemeRecept = '$vrijemeRecept'";
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
    else if($pronasao == false){
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

            //Kreiram sql upit kojim provjeravam postoji li LIJEK u DOPUNSKOJ listi lijekova
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
            if($brojac == 20){
                break;
            }
        }
        //Ako je lijek PRONAĐEN u DOPUNSKOJ LISTI LIJEKOVA
        if($pronasao == true){
            //Kreiram sql upit koji će dohvatiti podatke pacijenta i recepta
            $sql = "SELECT r.mkbSifraPrimarna,d.imeDijagnoza AS nazivPrimarna, GROUP_CONCAT(DISTINCT r.mkbSifraSekundarna SEPARATOR ' ') AS mkbSifraSekundarna,
                    r.proizvod, r.oblikJacinaPakiranjeLijek, r.kolicina, r.doziranje, 
                    r.dostatnost, r.hitnost, r.ponovljiv, r.brojPonavljanja, r.sifraSpecijalist, 
                    r.idPacijent, r.datumRecept, r.vrijemeRecept FROM recept r 
                    JOIN dijagnoze d ON d.mkbSifra = r.mkbSifraPrimarna
                    WHERE r.dostatnost = '$dostatnost' AND r.datumRecept = '$datumRecept' 
                    AND r.idPacijent = '$idPacijent' AND r.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                    AND r.proizvod = '$zasticenoImeLijek' AND r.oblikJacinaPakiranjeLijek = '$oblikJacinaPakiranjeLijek' 
                    AND r.vrijemeRecept = '$vrijemeRecept'";
            $result = $conn->query($sql);

            //Ako pacijent IMA evidentiranih recepata:
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
                return $pronasao;
            }
        }
        //Ako OJP i zaštićeno ime NISU pronađeni u DOPUNSKOJ LISTI lijekova
        else if($pronasao == false){
            //Kreiram sql upit koji će dohvatiti podatke pacijenta i recepta
            $sql = "SELECT r.mkbSifraPrimarna, d.imeDijagnoza AS nazivPrimarna, GROUP_CONCAT(DISTINCT r.mkbSifraSekundarna SEPARATOR ' ') AS mkbSifraSekundarna,
                    r.proizvod, r.oblikJacinaPakiranjeLijek, r.kolicina, r.doziranje, 
                    r.dostatnost, r.hitnost, r.ponovljiv, r.brojPonavljanja, r.sifraSpecijalist, 
                    r.idPacijent, r.datumRecept, r.vrijemeRecept FROM recept r 
                    JOIN dijagnoze d ON d.mkbSifra = r.mkbSifraPrimarna
                    WHERE r.dostatnost = '$dostatnost' AND DATE_FORMAT(r.datumRecept,'%d.%m.%Y') = '$datumRecept' 
                    AND r.idPacijent = '$idPacijent' AND r.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                    AND r.proizvod = '$proizvod' 
                    AND r.vrijemeRecept = '$vrijemeRecept'";
            $result = $conn->query($sql);

            //Ako pacijent IMA evidentiranih recepata:
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
                return $response;
            }
        }
    }
}

foreach(dohvatiRecept($dostatnost,$datumRecept, 
                        $idPacijent,$mkbSifraPrimarna, 
                        $proizvod,$vrijemeRecept) as $vanjski){
    foreach($vanjski as $el){
        echo $el;
    }
}
?>