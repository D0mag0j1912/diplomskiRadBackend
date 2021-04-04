<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class OtvoreniSlucajService{
    //Funkcija koja dohvaća sve otvorene slučajeve za trenutno aktivnog pacijenta
    function dohvatiSveOtvoreneSlucajeve($mboPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];

        //Kreiram upit koji će provjeriti postoje li primarne dijagnoze (jer ako nema primarne, nema ni sekundarnih) za trenutno aktivnog pacijenta ZA TABLICU OPĆIH PODATAKA
        $sqlCountDijagnozaOpci = "SELECT COUNT(DISTINCT(p.mkbSifraPrimarna)) AS BrojDijagnoza FROM  pregled p 
                                WHERE p.mboPacijent = '$mboPacijent'";
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
        //Ako nema primarnih dijagnoza
        if($brojDijagnozaOpci == 0){
            $response["success"] = "false";
            $response["message"] = "Nema aktivnih dijagnoza za pacijenta!";
        }
        //Ako ima primarnih dijagnoza:
        else{
            $sql = "SELECT * FROM 
                    (SELECT 
                    CASE 
                        WHEN p.mkbSifraPrimarna IS NOT NULL THEN TRIM(p.mkbSifraPrimarna)
                        WHEN p.mkbSifraPrimarna IS NULL THEN NULL
                    END AS mkbSifraPrimarna, DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, p.vrijemePregled, 
                    p.tipSlucaj,
                    CASE 
                        WHEN p.mkbSifraPrimarna IS NOT NULL THEN (SELECT TRIM(d.imeDijagnoza) FROM dijagnoze d 
                                                                WHERE d.mkbSifra = p.mkbSifraPrimarna)
                        WHEN p.mkbSifraPrimarna IS NULL THEN NULL
                    END AS NazivPrimarna FROM pregled p 
                    WHERE p.mboPacijent = '$mboPacijent' 
                    AND p.prosliPregled IS NOT NULL 
                    GROUP BY p.prosliPregled
                    UNION ALL
                    SELECT 
                    CASE 
                        WHEN p.mkbSifraPrimarna IS NOT NULL THEN TRIM(p.mkbSifraPrimarna)
                        WHEN p.mkbSifraPrimarna IS NULL THEN NULL
                    END AS mkbSifraPrimarna, DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, p.vrijemePregled, 
                    p.tipSlucaj,
                    CASE 
                        WHEN p.mkbSifraPrimarna IS NOT NULL THEN (SELECT TRIM(d.imeDijagnoza) FROM dijagnoze d 
                                                                WHERE d.mkbSifra = p.mkbSifraPrimarna)
                        WHEN p.mkbSifraPrimarna IS NULL THEN NULL
                    END AS NazivPrimarna  FROM pregled p 
                    WHERE p.mboPacijent = '$mboPacijent' 
                    AND p.prosliPregled IS NULL 
                    GROUP BY p.vrijemePregled) AS Pretraga
                    ORDER BY Datum DESC, vrijemePregled DESC
                    LIMIT 7";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }
        return $response;
    }
    //Funkcija koja dohvaća sve primarne dijagnoze evidentirane na pregledima nekog pacijenta
    function svePrimarneDijagnoze($mboPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];
    
        $sql = "SELECT DISTINCT(p.mkbSifraPrimarna) AS mkbSifraPrimarna FROM pregled p
                WHERE p.mboPacijent = '$mboPacijent'";
        
        $result = $conn->query($sql);
    
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
    
        return $response;
    }

    //Funkcija koja dohvaća sve sekundarne dijagnoze ZA NEKU PRIMARNU DIJAGNOZU trenutno aktivnog pacijenta
    function dohvatiSveSekundarneDijagnoze($vanjsko,$mboPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];
        //ZA SVAKU PRIMARNU DIJAGNOZU, PRONAĐI SVE SEKUNDARNE DIJAGNOZE
        foreach($vanjsko as $unutarnje){
            foreach($unutarnje as $imeAtributa=>$sifraPrimarna){
                $sql = "SELECT DISTINCT(TRIM(p.mkbSifraSekundarna)) AS mkbSifraSekundarna, 
                        TRIM(p.mkbSifraPrimarna) AS mkbSifraPrimarna,
                        IF(p.mkbSifraSekundarna = NULL, NULL, (SELECT TRIM(d.imeDijagnoza) FROM dijagnoze d 
                                                            WHERE d.mkbSifra = p.mkbSifraSekundarna)) AS NazivSekundarna,
                        DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, p.vrijemePregled, p.tipSlucaj FROM pregled p
                        WHERE p.mboPacijent = '$mboPacijent' 
                        AND p.mkbSifraPrimarna = '$sifraPrimarna'
                        ORDER BY p.datumPregled DESC, p.vrijemePregled DESC";
                $result = $conn->query($sql);

                //Ako ima pronađenih sekundarnih dijagnoza za ovu primarnu dijagnozu
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }
            }
        }
        return $response;
    }

    //Funkcija koja dohvaća sve podatke vezane za povezan slučaj
    function dohvatiDijagnozePovezanSlucaj($mkbSifraPrimarna, $mboPacijent ,$datumPregled, $vrijemePregled, $tipSlucaj){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];

        $sql = "SELECT DISTINCT(TRIM(d.imeDijagnoza)) AS NazivPrimarna, 
                IF(p.mkbSifraSekundarna = NULL, NULL, (SELECT TRIM(d.imeDijagnoza) FROM dijagnoze d 
                                                    WHERE d.mkbSifra = p.mkbSifraSekundarna)) AS NazivSekundarna, 
                p.idObradaMedSestra,TRIM(p.mkbSifraPrimarna) AS mkbSifraPrimarna, p.idPregled, p.bojaPregled FROM dijagnoze d 
                JOIN pregled p ON d.mkbSifra = p.mkbSifraPrimarna
                WHERE p.mboPacijent = '$mboPacijent' 
                AND p.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                AND p.datumPregled = '$datumPregled' 
                AND p.vrijemePregled = '$vrijemePregled' 
                AND p.tipSlucaj = '$tipSlucaj'";
        
        $result = $conn->query($sql);

        //Ako ima pronađenih sekundarnih dijagnoza za ovu primarnu dijagnozu
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja dohvaća DATUM PREGLEDA, ODGOVORNU OSOBU, ŠIFRU PRIMARNE DIJAGNOZE I NAZIV PRIMARNE DIJAGNOZE NA OSNOVU PRETRAGE
    function dohvatiOtvoreniSlucajPretraga($pretraga,$mboPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];

        //Kada je empty pretraga
        if(empty($pretraga)){
            $sql = "SELECT * FROM 
                    (SELECT 
                    CASE 
                        WHEN p.mkbSifraPrimarna IS NOT NULL THEN TRIM(p.mkbSifraPrimarna)
                        WHEN p.mkbSifraPrimarna IS NULL THEN NULL
                    END AS mkbSifraPrimarna, DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, p.vrijemePregled, 
                    p.tipSlucaj,
                    CASE 
                        WHEN p.mkbSifraPrimarna IS NOT NULL THEN (SELECT TRIM(d.imeDijagnoza) FROM dijagnoze d 
                                                                WHERE d.mkbSifra = p.mkbSifraPrimarna)
                        WHEN p.mkbSifraPrimarna IS NULL THEN NULL
                    END AS NazivPrimarna FROM pregled p 
                    WHERE p.mboPacijent = '$mboPacijent' 
                    AND p.prosliPregled IS NOT NULL 
                    GROUP BY p.prosliPregled
                    UNION ALL
                    SELECT 
                    CASE 
                        WHEN p.mkbSifraPrimarna IS NOT NULL THEN TRIM(p.mkbSifraPrimarna)
                        WHEN p.mkbSifraPrimarna IS NULL THEN NULL
                    END AS mkbSifraPrimarna, DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, p.vrijemePregled, 
                    p.tipSlucaj,
                    CASE 
                        WHEN p.mkbSifraPrimarna IS NOT NULL THEN (SELECT TRIM(d.imeDijagnoza) FROM dijagnoze d 
                                                                WHERE d.mkbSifra = p.mkbSifraPrimarna)
                        WHEN p.mkbSifraPrimarna IS NULL THEN NULL
                    END AS NazivPrimarna  FROM pregled p 
                    WHERE p.mboPacijent = '$mboPacijent' 
                    AND p.prosliPregled IS NULL 
                    GROUP BY p.vrijemePregled) AS Pretraga
                    ORDER BY Datum DESC, vrijemePregled DESC
                    LIMIT 7";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }
        //Kada ima nešto u pretrazi:
        else{
            $sql = "SELECT DISTINCT(TRIM(p.mkbSifraPrimarna)) AS mkbSifraPrimarna, 
                DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, 
                TRIM(d.imeDijagnoza) AS NazivPrimarna, p.vrijemePregled, p.tipSlucaj FROM pregled p
                LEFT JOIN dijagnoze d ON p.mkbSifraPrimarna = d.mkbSifra 
                LEFT JOIN dijagnoze d2 ON p.mkbSifraSekundarna = d2.mkbSifra
                WHERE p.mboPacijent = '$mboPacijent'
                AND (UPPER(TRIM(d.imeDijagnoza)) LIKE UPPER('%{$pretraga}%')  
                OR UPPER(TRIM(d2.imeDijagnoza)) LIKE UPPER('%{$pretraga}%')
                OR UPPER(p.mkbSifraPrimarna) LIKE UPPER('%{$pretraga}%') 
                OR UPPER(p.mkbSifraSekundarna) LIKE UPPER('%{$pretraga}%') 
                OR UPPER(p.datumPregled) LIKE UPPER('%{$pretraga}%')) 
                ORDER BY p.datumPregled DESC, p.vrijemePregled DESC
                LIMIT 7";
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
}
?>