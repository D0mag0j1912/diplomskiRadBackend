<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class PrikazReceptService {

    //Funkcija koja dohvaća tip specijalista na osnovu njegove šifre
    function dohvatiTipSpecijalist($sifraSpecijalist){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Deklariram tip specijalista
        $tipSpecijalist = "";
        //Kreiram upit koji dohvaća tip specijalista 
        $sql = "SELECT tipSpecijalist FROM zdr_radnici 
                WHERE sifraSpecijalist = '$sifraSpecijalist'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $tipSpecijalist = $row['tipSpecijalist'];
            }
        } 
        return $tipSpecijalist;
    }

    //Funkcija koja dohvaća naziv i šifru sekundarne dijagnoze na osnovu njezine MKB šifre
    function dohvatiSekundarneDijagnoza($polje){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];
        //Za svaku pojedinu šifru sekundarne dijagnoze iz polja, pronađi joj šifru i naziv iz baze
        foreach($polje as $mkbSifra){
            
            $sql = "SELECT d.mkbSifra,d.imeDijagnoza FROM dijagnoze d
                    WHERE d.mkbSifra = '$mkbSifra'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            } 
        }
        return $response;
    }

    //Funkcija koja dohvaća podatke zdravstvene ustanove 
    function dohvatiZdrUst(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 
        //Kreiram SQL upit koji će dohvatiti recepte za ID pacijenta
        $sql = "SELECT CONCAT(l.imeLijecnik,' ',l.prezLijecnik) AS Lijecnik, zu.*,m.nazivMjesto FROM lijecnik l 
                JOIN zdr_ustanova zu ON zu.idZdrUst = l.idZdrUst 
                JOIN mjesto m ON m.pbrMjesto = zu.pbrZdrUst";

        $result = $conn->query($sql);

        //Ako pacijent IMA evidentiranih recepata:
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        return $response;
    }
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
            if($brojac == 20){
                break;
            }
        }
        //Ako je proizvod pronađen u OSNOVNOJ LISTI
        if($pronasao == true){
            //Kreiram sql upit koji će dohvatiti podatke pacijenta i recepta
            $sql = "SELECT CONCAT(r.mkbSifraPrimarna,' | ',d.imeDijagnoza) AS mkbSifraPrimarna, 
                    GROUP_CONCAT(DISTINCT r.mkbSifraSekundarna SEPARATOR ' ') AS mkbSifraSekundarna, 
                    r.ponovljiv, DATE_FORMAT(r.datumRecept,'%d.%m.%Y') AS Datum, 
                    IF(r.oblikJacinaPakiranjeLijek IS NULL, 
                    r.proizvod, CONCAT(r.proizvod,' ',r.oblikJacinaPakiranjeLijek)) AS proizvod,
                    CONCAT(p.imePacijent,' ',p.prezPacijent) AS imePrezimePacijent, 
                    r.kolicina,r.doziranje,r.dostatnost,r.brojPonavljanja,r.sifraSpecijalist,r.oblikJacinaPakiranjeLijek,
                    DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS DatumRodenja, p.adresaPacijent, r.hitnost FROM recept r 
                    JOIN pacijent p ON p.idPacijent = r.idPacijent 
                    JOIN dijagnoze d ON d.mkbSifra = r.mkbSifraPrimarna
                    WHERE r.dostatnost = '$dostatnost' AND DATE_FORMAT(r.datumRecept,'%d.%m.%Y') = '$datumRecept' 
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
                $sql = "SELECT CONCAT(r.mkbSifraPrimarna,' | ',d.imeDijagnoza) AS mkbSifraPrimarna, 
                        GROUP_CONCAT(DISTINCT r.mkbSifraSekundarna SEPARATOR ' ') AS mkbSifraSekundarna, 
                        r.ponovljiv, DATE_FORMAT(r.datumRecept,'%d.%m.%Y') AS Datum, 
                        IF(r.oblikJacinaPakiranjeLijek IS NULL, 
                        r.proizvod, CONCAT(r.proizvod,' ',r.oblikJacinaPakiranjeLijek)) AS proizvod,
                        CONCAT(p.imePacijent,' ',p.prezPacijent) AS imePrezimePacijent, 
                        r.kolicina,r.doziranje,r.dostatnost,r.brojPonavljanja,r.sifraSpecijalist,r.oblikJacinaPakiranjeLijek,
                        DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS DatumRodenja, p.adresaPacijent, r.hitnost FROM recept r 
                        JOIN pacijent p ON p.idPacijent = r.idPacijent 
                        JOIN dijagnoze d ON d.mkbSifra = r.mkbSifraPrimarna
                        WHERE r.dostatnost = '$dostatnost' AND DATE_FORMAT(r.datumRecept,'%d.%m.%Y') = '$datumRecept' 
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
            //Ako OJP i zaštićeno ime NISU pronađeni u DOPUNSKOJ LISTI lijekova
            else if($pronasao == false){
                //Kreiram sql upit koji će dohvatiti podatke pacijenta i recepta
                $sql = "SELECT CONCAT(r.mkbSifraPrimarna,' | ',d.imeDijagnoza) AS mkbSifraPrimarna, 
                        GROUP_CONCAT(DISTINCT r.mkbSifraSekundarna SEPARATOR ' ') AS mkbSifraSekundarna, 
                        r.ponovljiv, DATE_FORMAT(r.datumRecept,'%d.%m.%Y') AS Datum, 
                        IF(r.oblikJacinaPakiranjeLijek IS NULL, 
                        r.proizvod, CONCAT(r.proizvod,' ',r.oblikJacinaPakiranjeLijek)) AS proizvod,
                        CONCAT(p.imePacijent,' ',p.prezPacijent) AS imePrezimePacijent, 
                        r.kolicina,r.doziranje,r.dostatnost,r.brojPonavljanja,r.sifraSpecijalist,r.oblikJacinaPakiranjeLijek,
                        DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS DatumRodenja, p.adresaPacijent, r.hitnost FROM recept r 
                        JOIN pacijent p ON p.idPacijent = r.idPacijent 
                        JOIN dijagnoze d ON d.mkbSifra = r.mkbSifraPrimarna
                        WHERE r.dostatnost = '$dostatnost' AND DATE_FORMAT(r.datumRecept,'%d.%m.%Y') = '$datumRecept' 
                        AND r.idPacijent = '$idPacijent' AND r.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                        AND r.proizvod = '$proizvod' AND r.vrijemeRecept = '$vrijemeRecept'";
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
}
?>