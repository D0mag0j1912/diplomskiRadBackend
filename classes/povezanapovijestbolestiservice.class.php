<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class PovezanaPovijestBolestiService{

    //Funkcija koja dohvaća sve podatke povijesti bolesti za određeni povezani slučaj
    function dohvatiPovijestBolestiPovezanSlucaj($datum,$razlogDolaska,$mkbSifraPrimarna,$id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];


        $sql = "SELECT DISTINCT(d.imeDijagnoza) AS NazivPrimarna, 
                IF(pb.mkbSifraSekundarna = NULL, NULL, (SELECT d2.imeDijagnoza FROM dijagnoze d2 WHERE d2.mkbSifra = pb.mkbSifraSekundarna)) AS NazivSekundarna,pb.* FROM povijestBolesti pb 
                JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna
                WHERE pb.datum = '$datum' AND pb.razlogDolaska = '$razlogDolaska' 
                AND pb.mkbSifraPrimarna = '$mkbSifraPrimarna' AND pb.mboPacijent IN 
                (SELECT pacijent.mboPacijent FROM pacijent 
                WHERE pacijent.idPacijent = '$id')";
        $result = $conn->query($sql);

        //Ako ima pronađenih rezultata za navedenu pretragu
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }

        return $response;
    }

    //Funkcija koja dohvaća sve podatke povijesti bolesti za određenu PRETRAGU KORISNIKA
    function dohvatiPovijestBolestiPretraga($id,$pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];

        $sql = "SELECT YEAR(pb.datum) AS Godina,DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum,
                pb.razlogDolaska, 
                TRIM(pb.mkbSifraPrimarna) AS mkbSifraPrimarna,
                d.imeDijagnoza AS NazivPrimarna,
                GROUP_CONCAT(DISTINCT pb.mkbSifraSekundarna SEPARATOR ' ') AS mkbSifraSekundarna FROM povijestbolesti pb 
                JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna 
                WHERE (UPPER(pb.datum) LIKE UPPER('%{$pretraga}%') OR UPPER(pb.razlogDolaska) LIKE UPPER('%{$pretraga}%') 
                OR UPPER(pb.mkbSifraPrimarna) LIKE UPPER('%{$pretraga}%') OR UPPER(d.imeDijagnoza) LIKE UPPER('%{$pretraga}%') 
                OR UPPER(pb.mkbSifraSekundarna) LIKE UPPER('%{$pretraga}%')) AND pb.mboPacijent IN 
                (SELECT pacijent.mboPacijent FROM pacijent 
                WHERE pacijent.idPacijent = '$id') 
                GROUP BY pb.mkbSifraPrimarna,pb.datum 
                ORDER BY pb.datum DESC;";
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
        return $response;
    }

    //Funkcija koja dohvaća naziv sekundarne dijagnoze na osnovu njezine MKB šifre
    function dohvatiNazivSekundarna($polje){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];
        //Za svaku pojedinu šifru sekundarne dijagnoze iz polja, pronađi joj šifru i naziv iz baze
        foreach($polje as $mkbSifra){
            
            $sql = "SELECT DISTINCT(TRIM(pb.mkbSifraPrimarna)) AS mkbSifraPrimarna,d.mkbSifra,d.imeDijagnoza FROM dijagnoze d 
                    JOIN povijestbolesti pb ON pb.mkbSifraSekundarna = d.mkbSifra
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

    //Funkcija koja dohvaća sve podatke povijesti bolesti (BEZ SEKUNDARNIH DIJAGNOZA)
    function dohvatiPovijestBolesti($id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];

        $sqlCount = "SELECT COUNT(*) AS BrojPovijestBolesti FROM povijestbolesti pb 
                    WHERE pb.mboPacijent IN 
                    (SELECT pacijent.mboPacijent FROM pacijent 
                    WHERE pacijent.idPacijent = '$id');";
        //Rezultat upita spremam u varijablu $resultCount
        $resultCount= mysqli_query($conn,$sqlCount);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCount) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCount = mysqli_fetch_assoc($resultCount)){
                //Vrijednost rezultata spremam u varijablu $brojPovijestBolesti
                $brojPovijestBolesti = $rowCount['BrojPovijestBolesti'];
            }
        }

        //Ako ovaj pacijent nema zabilježen nijedan povijest bolesti
        if($brojPovijestBolesti == 0){
            //Vraćam neuspješnu poruku
            $response["success"] = "false";
            $response["message"] = "Pacijent nema evidentiranih povijesti bolesti!";
        }
        //Ako ovaj pacijent ima zabilježene povijesti bolesti
        else{
            $sql = "SELECT YEAR(pb.datum) AS Godina,DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum,
                    pb.razlogDolaska, 
                    TRIM(pb.mkbSifraPrimarna) AS mkbSifraPrimarna,
                    d.imeDijagnoza AS NazivPrimarna,
                    GROUP_CONCAT(DISTINCT pb.mkbSifraSekundarna SEPARATOR ' ') AS mkbSifraSekundarna FROM povijestbolesti pb 
                    JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna 
                    WHERE pb.mboPacijent IN 
                    (SELECT pacijent.mboPacijent FROM pacijent 
                    WHERE pacijent.idPacijent = '$id') 
                    GROUP BY pb.mkbSifraPrimarna,pb.datum 
                    ORDER BY pb.datum DESC;";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }
        return $response;
    }

}
?>