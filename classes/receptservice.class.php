<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class ReceptService{

    //Funkcija koja dohvaća cijene za LIJEK sa OSNOVNE LISTE
    function dohvatiCijenaLijekOL($lijek,$ojp){
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
        return $response;
    }

    //Funkcija koja dohvaća MAGISTRALNE PRIPRAVKE sa DOPUNSKE LISTE na osnovu liječničke pretrage
    function dohvatiMagPripravciDopunskaListaPretraga($pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        $sql = "SELECT m.nazivMagPripravak FROM dopunskalistamagistralnihpripravaka m  
                WHERE UPPER(m.nazivMagPripravak) LIKE UPPER('%{$pretraga}%') 
                LIMIT 8";
        $result = $conn->query($sql);

        //Ako ima pronađenih rezultata za navedenu pretragu
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Ako nema pronađenih rezultata za ovu pretragu
        else{
            $response["success"] = "false";
            $response["message"] = "Nema pronađenih rezultata za ključnu riječ: ".$pretraga;
        } 
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća MAGISTRALNE PRIPRAVKE sa OSNOVNE LISTE na osnovu liječničke pretrage
    function dohvatiMagPripravciOsnovnaListaPretraga($pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        $sql = "SELECT m.nazivMagPripravak FROM osnovnalistamagistralnihpripravaka m  
                WHERE UPPER(m.nazivMagPripravak) LIKE UPPER('%{$pretraga}%') 
                LIMIT 8";
        $result = $conn->query($sql);

        //Ako ima pronađenih rezultata za navedenu pretragu
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Ako nema pronađenih rezultata za ovu pretragu
        else{
            $response["success"] = "false";
            $response["message"] = "Nema pronađenih rezultata za ključnu riječ: ".$pretraga;
        } 
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća lijekove sa dopunske liste na osnovu liječničke pretrage
    function dohvatiLijekoviDopunskaListaPretraga($pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        $sql = "SELECT CONCAT(l.zasticenoImeLijek,' ',l.oblikJacinaPakiranjeLijek) AS zasticenoImeLijek FROM dopunskalistalijekova l 
                WHERE UPPER(l.zasticenoImeLijek) LIKE UPPER('%{$pretraga}%') 
                LIMIT 8";
        $result = $conn->query($sql);

        //Ako ima pronađenih rezultata za navedenu pretragu
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Ako nema pronađenih rezultata za ovu pretragu
        else{
            $response["success"] = "false";
            $response["message"] = "Nema pronađenih rezultata za ključnu riječ: ".$pretraga;
        } 
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća lijekove sa osnovne liste na osnovu liječničke pretrage
    function dohvatiLijekoviOsnovnaListaPretraga($pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        $sql = "SELECT CONCAT(l.zasticenoImeLijek,' ',l.oblikJacinaPakiranjeLijek) AS zasticenoImeLijek FROM osnovnalistalijekova l 
                WHERE UPPER(l.zasticenoImeLijek) LIKE UPPER('%{$pretraga}%') 
                LIMIT 8";
        $result = $conn->query($sql);

        //Ako ima pronađenih rezultata za navedenu pretragu
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Ako nema pronađenih rezultata za ovu pretragu
        else{
            $response["success"] = "false";
            $response["message"] = "Nema pronađenih rezultata za ključnu riječ: ".$pretraga;
        } 
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća pacijente na osnovu liječničke pretrage
    function dohvatiPacijentiPretraga($pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
 
        $sql = "SELECT p.imePacijent,p.prezPacijent, 
                DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS Datum,
                p.mboPacijent FROM pacijent p 
                WHERE UPPER(p.imePacijent) LIKE UPPER('%{$pretraga}%') 
                OR UPPER(p.prezPacijent) LIKE UPPER('%{$pretraga}%') OR UPPER(p.datRodPacijent) LIKE UPPER('%{$pretraga}%') 
                OR UPPER(p.mboPacijent) LIKE UPPER('%{$pretraga}%')
                ORDER BY p.prezPacijent ASC";
        $result = $conn->query($sql);

        //Ako ima pronađenih rezultata za navedenu pretragu
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Ako nema pronađenih rezultata za ovu pretragu
        else{
            $response["success"] = "false";
            $response["message"] = "Nema pronađenih rezultata za ključnu riječ: ".$pretraga;
        } 
        //Vraćam odgovor baze
        return $response;
    }
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
            //Kreiram upit koji dohvaća sve pacijente
            $sql = "SELECT p.imePacijent,p.prezPacijent, 
                    DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS Datum,
                    p.mboPacijent FROM pacijent p
                    ORDER BY p.prezPacijent ASC";
            
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
}
?>