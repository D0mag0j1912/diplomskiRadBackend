<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class PovezanaPovijestBolestiService{

    //Funkcija koja dohvaća sve podatke povijesti bolesti za određeni povezani slučaj
    function dohvatiPovijestBolestiPovezanSlucaj($datum,$razlogDolaska, $anamneza,
                                                $mkbSifraPrimarna,$vrijeme,$tipSlucaj,$id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];


        $sql = "SELECT DISTINCT(TRIM(d.imeDijagnoza)) AS NazivPrimarna, 
                IF(pb.mkbSifraSekundarna = NULL, NULL, (SELECT TRIM(d2.imeDijagnoza) FROM dijagnoze d2 
                                                        WHERE d2.mkbSifra = pb.mkbSifraSekundarna)) AS NazivSekundarna
                ,pb.* FROM povijestBolesti pb 
                JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna
                WHERE pb.datum = '$datum' 
                AND pb.razlogDolaska = '$razlogDolaska' 
                AND pb.anamneza = '$anamneza'
                AND TRIM(pb.mkbSifraPrimarna) = '$mkbSifraPrimarna' 
                AND pb.vrijeme = '$vrijeme' 
                AND pb.tipSlucaj = '$tipSlucaj'
                AND pb.mboPacijent IN 
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
    function dohvatiPovijestBolestiPretraga($mboPacijent,$pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];
        //Ako je pretraga ""
        if(empty($pretraga)){
            $sql = "SELECT * FROM 
                    (SELECT YEAR(pb.datum) AS Godina,pb.idPovijestBolesti,DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum,
                    pb.razlogDolaska,
                    CONCAT(TRIM(d.imeDijagnoza),' | ',TRIM(pb.mkbSifraPrimarna)) AS primarnaDijagnoza, 
                    pb.tipSlucaj,pb.vrijeme,pb.anamneza,
                    (SELECT COUNT(*) FROM povijestbolesti pb2 
                    WHERE pb2.prosliPregled = pb.idPovijestBolesti) AS prosliPregled FROM povijestbolesti pb 
                    JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna 
                    WHERE pb.mboPacijent = '$mboPacijent' 
                    AND pb.prosliPregled IS NOT NULL 
                    AND pb.idPovijestBolesti IN 
                    (SELECT MAX(pb2.idPovijestBolesti) FROM povijestbolesti pb2 
                    WHERE pb2.mboPacijent = '$mboPacijent' 
                    AND pb2.prosliPregled IS NOT NULL 
                    GROUP BY pb2.prosliPregled)
                    UNION ALL 
                    SELECT YEAR(pb.datum) AS Godina,pb.idPovijestBolesti,DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum,
                    pb.razlogDolaska,
                    CONCAT(TRIM(d.imeDijagnoza),' | ',TRIM(pb.mkbSifraPrimarna)) AS primarnaDijagnoza, 
                    pb.tipSlucaj,pb.vrijeme,pb.anamneza, 
                    (SELECT COUNT(*) FROM povijestbolesti pb2 
                    WHERE pb2.prosliPregled = pb.idPovijestBolesti) AS prosliPregled FROM povijestbolesti pb 
                    JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna 
                    WHERE pb.mboPacijent = '$mboPacijent' 
                    AND pb.prosliPregled IS NULL 
                    AND pb.idPovijestBolesti IN 
                    (SELECT MAX(pb2.idPovijestBolesti) FROM povijestbolesti pb2 
                    WHERE pb2.mboPacijent = '$mboPacijent' 
                    AND pb2.prosliPregled IS NULL 
                    GROUP BY pb2.vrijeme)) AS povezanaPovijestBolesti 
                    ORDER BY Datum DESC, vrijeme DESC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }
        //Ako pretraga NIJE ""
        else{
            $sql = "SELECT YEAR(pb.datum) AS Godina,DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum,
                    pb.razlogDolaska,
                    CONCAT(TRIM(d.imeDijagnoza),' | ',TRIM(pb.mkbSifraPrimarna)) AS primarnaDijagnoza,
                    pb.tipSlucaj,pb.vrijeme,pb.anamneza,
                    (SELECT COUNT(*) FROM povijestBolesti pb2 
                    WHERE pb2.prosliPregled = 
                    (SELECT MAX(pb3.idPovijestBolesti) FROM povijestBolesti pb3 
                    WHERE pb3.vrijeme = pb.vrijeme 
                    AND pb3.datum = pb.datum)) AS prosliPregled FROM povijestbolesti pb 
                    LEFT JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna 
                    LEFT JOIN dijagnoze d2 ON d2.mkbSifra = pb.mkbSifraSekundarna
                    WHERE (UPPER(pb.datum) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb.razlogDolaska) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(TRIM(pb.mkbSifraPrimarna)) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(TRIM(pb.mkbSifraSekundarna)) LIKE UPPER('%{$pretraga}%')
                    OR UPPER(TRIM(d.imeDijagnoza)) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(TRIM(d2.imeDijagnoza)) LIKE UPPER('%{$pretraga}%')) 
                    AND pb.mboPacijent = '$mboPacijent' 
                    GROUP BY pb.vrijeme
                    ORDER BY Datum DESC, vrijeme DESC";
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
        }
        return $response;
    }

    //Funkcija koja dohvaća sve sek. dijagnoze za navedeni pregled
    function dohvatiSekundarneDijagnoze($datum,$vrijeme,$tipSlucaj,$mkbSifraPrimarna,$mboPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];

        $sql = "SELECT IF(pb.mkbSifraSekundarna IS NULL, NULL, 
                CONCAT((SELECT TRIM(d.imeDijagnoza) FROM dijagnoze d 
                        WHERE d.mkbSifra = pb.mkbSifraSekundarna),' | ',TRIM(pb.mkbSifraSekundarna))) AS sekundarneDijagnoze, 
                DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum,pb.vrijeme,pb.tipSlucaj FROM povijestbolesti pb 
                WHERE pb.datum = '$datum' 
                AND pb.vrijeme = '$vrijeme' 
                AND pb.tipSlucaj = '$tipSlucaj' 
                AND TRIM(pb.mkbSifraPrimarna) = '$mkbSifraPrimarna' 
                AND pb.mboPacijent = '$mboPacijent';";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        } 
        return $response;
    }

    //Funkcija koja dohvaća sve podatke povijesti bolesti (BEZ SEKUNDARNIH DIJAGNOZA)
    function dohvatiPovijestBolesti($mboPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];

        $sqlCount = "SELECT COUNT(*) AS BrojPovijestBolesti FROM povijestbolesti pb 
                    WHERE pb.mboPacijent = '$mboPacijent';";
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
            $sql = "SELECT * FROM 
                    (SELECT YEAR(pb.datum) AS Godina,pb.idPovijestBolesti,DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum,
                    pb.razlogDolaska,
                    CONCAT(TRIM(d.imeDijagnoza),' | ',TRIM(pb.mkbSifraPrimarna)) AS primarnaDijagnoza, 
                    pb.tipSlucaj,pb.vrijeme,pb.anamneza,
                    (SELECT COUNT(*) FROM povijestbolesti pb2 
                    WHERE pb2.prosliPregled = pb.idPovijestBolesti) AS prosliPregled FROM povijestbolesti pb 
                    JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna 
                    WHERE pb.mboPacijent = '$mboPacijent' 
                    AND pb.prosliPregled IS NOT NULL 
                    AND pb.idPovijestBolesti IN 
                    (SELECT MAX(pb2.idPovijestBolesti) FROM povijestbolesti pb2 
                    WHERE pb2.mboPacijent = '$mboPacijent' 
                    AND pb2.prosliPregled IS NOT NULL 
                    GROUP BY pb2.prosliPregled)
                    UNION ALL 
                    SELECT YEAR(pb.datum) AS Godina,pb.idPovijestBolesti,DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum,
                    pb.razlogDolaska,
                    CONCAT(TRIM(d.imeDijagnoza),' | ',TRIM(pb.mkbSifraPrimarna)) AS primarnaDijagnoza, 
                    pb.tipSlucaj,pb.vrijeme,pb.anamneza, 
                    (SELECT COUNT(*) FROM povijestbolesti pb2 
                    WHERE pb2.prosliPregled = pb.idPovijestBolesti) AS prosliPregled FROM povijestbolesti pb 
                    JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna 
                    WHERE pb.mboPacijent = '$mboPacijent' 
                    AND pb.prosliPregled IS NULL 
                    AND pb.idPovijestBolesti IN 
                    (SELECT MAX(pb2.idPovijestBolesti) FROM povijestbolesti pb2 
                    WHERE pb2.mboPacijent = '$mboPacijent' 
                    AND pb2.prosliPregled IS NULL 
                    GROUP BY pb2.vrijeme)) AS povezanaPovijestBolesti 
                    ORDER BY Datum DESC, vrijeme DESC";
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