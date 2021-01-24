<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class ReceptService{

    //Funkcija koja dohvaća inforaciju je li izabrani LIJEK ima oznaku RS
    function dohvatiOznakaLijek($imeLijek,$ojpLijek,$lijek){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Definiram oznaku koju tražim
        $oznaka = "RS";
        //Kreiram prazno polje odgovora
        $response = [];
    
        $sql = "SELECT * FROM osnovnalistalijekova o 
                WHERE o.zasticenoImeLijek = '$imeLijek' 
                AND o.oblikJacinaPakiranjeLijek = '$ojpLijek'";
        $result = $conn->query($sql);
    
        //Ako ima pronađenih rezultata za navedenu pretragu
        if ($result->num_rows > 0) {
            //Kreiram upit koji će provjeriti je li izabrani LIJEK ima oznaku RS
            $sqlCount = "SELECT COUNT(*) AS BrojRS FROM osnovnalistalijekova 
                    WHERE zasticenoImeLijek = '$imeLijek' AND oblikJacinaPakiranje = '$ojpLijek' 
                    AND oznakaOsnovniLijek = '$oznaka'";
            //Rezultat upita spremam u varijablu $resultCount
            $resultCount = mysqli_query($conn,$sqlCount);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($resultCount) > 0){
                //Idem redak po redak rezultata upita 
                while($rowCount= mysqli_fetch_assoc($resultCount)){
                    //Vrijednost rezultata spremam u varijablu $brojRS
                    $brojRS = $rowCount['BrojRS'];
                }
            }
            //Ako lijek ima oznaku RS
            if($brojRS > 0){
                $response["brojRS"] = $brojRS;
            }
        }
        //Ako nema pronađenih rezultata za navedenu pretragu, splittam ga na drugoj praznini
        else{
            //Razdvajam string na drugoj praznini
            $polje = preg_split ('/ /', $lijek, 3);
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
                //Kreiram upit koji će provjeriti je li izabrani LIJEK ima oznaku RS
                $sqlCount = "SELECT COUNT(*) AS BrojRS FROM osnovnalistalijekova 
                            WHERE zasticenoImeLijek = '$nazivLijek' AND oblikJacinaPakiranje = '$ojpLijek' 
                            AND oznakaOsnovniLijek = '$oznaka'";
                //Rezultat upita spremam u varijablu $resultCount
                $resultCount = mysqli_query($conn,$sqlCount);
                //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                if(mysqli_num_rows($resultCount) > 0){
                    //Idem redak po redak rezultata upita 
                    while($rowCount= mysqli_fetch_assoc($resultCount)){
                        //Vrijednost rezultata spremam u varijablu $brojRS
                        $brojRS = $rowCount['BrojRS'];
                    }
                }
                //Ako lijek ima oznaku RS
                if($brojRS > 0){
                    $response["brojRS"] = $brojRS;
                }
            }
        }
        return $response;
    }

    //Funkcija koja dohvaća cijene za MAGISTRALNI PRIPRAVAK sa DOPUNSKE LISTE
    function dohvatiCijenaMagPripravakDL($magPripravak){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        $sql = "SELECT d.cijenaMagPripravak,d.cijenaZavod,d.doplataMagPripravak FROM dopunskalistamagistralnihpripravaka d 
                WHERE d.nazivMagPripravak = '$magPripravak'";
        $result = $conn->query($sql);

        //Ako ima pronađenih rezultata za navedenu pretragu
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        return $response;
    }
    //Funkcija koja dohvaća cijene sa osnovu izabranog LIJEKA sa DOPUNSKE LISTE
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

    //Funkcija koja dohvaća MAGISTRALNE PRIPRAVKE sa DOPUNSKE LISTE na osnovu liječničke pretrage
    function dohvatiMagPripravciDopunskaListaPretraga($pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        $sql = "SELECT DISTINCT(m.nazivMagPripravak) AS nazivMagPripravak FROM dopunskalistamagistralnihpripravaka m  
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