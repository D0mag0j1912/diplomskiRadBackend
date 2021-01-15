<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class OtvoreniSlucajService{
    //Funkcija koja dohvaća sve otvorene slučajeve za trenutno aktivnog pacijenta
    function dohvatiSveOtvoreneSlucajeve($tip,$id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];

        //Kreiram upit koji će provjeriti postoje li primarne dijagnoze (jer ako nema primarne, nema ni sekundarnih) za trenutno aktivnog pacijenta ZA TABLICU POVIJEST BOLESTI
        $sqlCountDijagnoza = "SELECT COUNT(DISTINCT(pb.mkbSifraPrimarna)) AS BrojDijagnoza FROM  povijestbolesti pb 
                            JOIN ambulanta a ON a.idPovijestBolesti = pb.idPovijestBolesti 
                            WHERE a.idPacijent = '$id';";
        //Rezultat upita spremam u varijablu $resultCountDijagnoza
        $resultCountDijagnoza = mysqli_query($conn,$sqlCountDijagnoza);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCountDijagnoza) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCountDijagnoza = mysqli_fetch_assoc($resultCountDijagnoza)){
                //Vrijednost rezultata spremam u varijablu $BrojDijagnoza
                $brojDijagnozaPovijestBolesti = $rowCountDijagnoza['BrojDijagnoza'];
            }
        }
        //Kreiram upit koji će provjeriti postoje li primarne dijagnoze (jer ako nema primarne, nema ni sekundarnih) za trenutno aktivnog pacijenta ZA TABLICU OPĆIH PODATAKA
        $sqlCountDijagnozaOpci = "SELECT COUNT(DISTINCT(p.mkbSifraPrimarna)) AS BrojDijagnoza FROM  pregled p 
                                JOIN ambulanta a ON a.idPregled = p.idPregled 
                                WHERE a.idPacijent = '$id';";
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
        //Zbrajam primarne dijagnoze povijesti bolesti i primarne dijagnoze općih podataka pregleda
        $brojDijagnoza = $brojDijagnozaPovijestBolesti + $brojDijagnozaOpci;
        //Ako NEMA pronađenih dijagnoza za trenutno aktivnog pacijenta
        if($brojDijagnoza == 0){
            $response["success"] = "false";
            $response["message"] = "Nema aktivnih dijagnoza za pacijenta!";
        }
        //Ako IMA pronađenih dijagnoza za trenutno aktivnog pacijenta
        else{
            //Ako je tip prijavljenog korisnika "lijecnik":
            if($tip == "lijecnik"){
                $sql = "SELECT DISTINCT(pb.mkbSifraPrimarna), DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, d.imeDijagnoza AS NazivPrimarna,l.imeLijecnik AS OdgovornaOsoba FROM 	                         povijestbolesti pb
                        JOIN dijagnoze d ON pb.mkbSifraPrimarna = d.mkbSifra 
                        JOIN ambulanta a ON a.idPovijestBolesti = pb.idPovijestBolesti 
                        JOIN lijecnik l ON l.idLijecnik = a.idLijecnik
                        WHERE a.idPacijent = '$id' 
                        ORDER BY Datum DESC;";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }
            }
            //Ako je tip prijavljenog korisnika "sestra":
            else if($tip == "sestra"){
                $sql = "SELECT DISTINCT(p.mkbSifraPrimarna), DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, d.imeDijagnoza AS NazivPrimarna,m.imeMedSestra AS OdgovornaOsoba FROM pregled p
                        JOIN dijagnoze d ON p.mkbSifraPrimarna = d.mkbSifra 
                        JOIN ambulanta a ON a.idPregled = p.idPregled 
                        JOIN med_sestra m ON m.idMedSestra = a.idMedSestra
                        WHERE a.idPacijent = '$id'
                        ORDER BY Datum DESC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }
            }
        }
        return $response;
    }

    function svePrimarneDijagnoze($id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];
    
        $sql = "SELECT p.mkbSifraPrimarna FROM pregled p
                WHERE p.mboPacijent IN 
                (SELECT pacijent.mboPacijent FROM pacijent 
                WHERE pacijent.idPacijent = '$id')
                UNION 
                SELECT pb.mkbSifraPrimarna FROM povijestbolesti pb 
                WHERE pb.mboPacijent IN 
                (SELECT pacijent.mboPacijent FROM pacijent 
                WHERE pacijent.idPacijent = '$id');";
        
        $result = $conn->query($sql);
    
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
    
        return $response;
    }

    //Funkcija koja dohvaća sve sekundarne dijagnoze ZA NEKU PRIMARNU DIJAGNOZU trenutno aktivnog pacijenta
    function dohvatiSveSekundarneDijagnoze($tip,$vanjsko,$id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];
        //Ako je tip prijavljenog korisnika "lijecnik":
        if($tip == "lijecnik"){
            //ZA SVAKU PRIMARNU DIJAGNOZU, PRONAĐI SVE SEKUNDARNE DIJAGNOZE
            foreach($vanjsko as $unutarnje){
                foreach($unutarnje as $imeAtributa=>$sifraPrimarna){
                    $sql = "SELECT DISTINCT(pb.mkbSifraSekundarna),l.imeLijecnik AS OdgovornaOsoba, pb.mkbSifraPrimarna,
                            IF(pb.mkbSifraSekundarna = NULL, NULL, (SELECT d.imeDijagnoza FROM dijagnoze d WHERE d.mkbSifra = pb.mkbSifraSekundarna)) AS NazivSekundarna,DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum FROM povijestbolesti pb 
                            JOIN ambulanta a ON a.idPovijestBolesti = pb.idPovijestBolesti
                            JOIN lijecnik l ON l.idLijecnik = a.idLijecnik
                            WHERE a.idPacijent = '$id' AND pb.mkbSifraPrimarna = '$sifraPrimarna'
                            ORDER BY pb.datum DESC";
                    $result = $conn->query($sql);

                    //Ako ima pronađenih sekundarnih dijagnoza za ovu primarnu dijagnozu
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $response[] = $row;
                        }
                    }
                }
            }
        }
        //Ako je tip prijavljenog korisnika "sestra":
        else if($tip == "sestra"){
            //ZA SVAKU PRIMARNU DIJAGNOZU, PRONAĐI SVE SEKUNDARNE DIJAGNOZE
            foreach($vanjsko as $unutarnje){
                foreach($unutarnje as $imeAtributa=>$sifraPrimarna){
                    $sql = "SELECT DISTINCT(p.mkbSifraSekundarna),m.imeMedSestra AS OdgovornaOsoba, p.mkbSifraPrimarna,
                            IF(p.mkbSifraSekundarna = NULL, NULL, (SELECT d.imeDijagnoza FROM dijagnoze d WHERE d.mkbSifra = p.mkbSifraSekundarna)) AS NazivSekundarna,DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum FROM pregled p 
                            JOIN ambulanta a ON a.idPregled = p.idPregled
                            JOIN med_sestra m ON m.idMedSestra = a.idMedSestra
                            WHERE a.idPacijent = '$id' AND p.mkbSifraPrimarna = '$sifraPrimarna'
                            ORDER BY p.datumPregled DESC";
                    $result = $conn->query($sql);

                    //Ako ima pronađenih sekundarnih dijagnoza za ovu primarnu dijagnozu
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $response[] = $row;
                        }
                    }
                }
            }
        }
        return $response;
    }

    //Funkcija koja dohvaća NAZIV PRIMARNE DIJAGNOZE te NAZIVE SEKUNDARNIH DIJAGNOZA na osnovu ŠIFRE PRIMARNE DIJAGNOZE i ID-a pacijenta
    function dohvatiDijagnozePovezanSlucaj($mkbSifra, $idPacijent,$datumPregled,$odgovornaOsoba){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];

        $sql = "SELECT DISTINCT(d.imeDijagnoza) AS NazivPrimarna, 
                IF(p.mkbSifraSekundarna = NULL, NULL, (SELECT d.imeDijagnoza FROM dijagnoze d WHERE d.mkbSifra = p.mkbSifraSekundarna)) AS NazivSekundarna FROM dijagnoze d 
                JOIN pregled p ON d.mkbSifra = p.mkbSifraPrimarna
                JOIN ambulanta a ON p.idPregled = a.idPregled
                JOIN med_sestra m ON m.idMedSestra = a.idMedSestra
                WHERE a.idPacijent = '$idPacijent' AND p.mkbSifraPrimarna = '$mkbSifra' AND p.datumPregled = '$datumPregled' AND m.imeMedSestra = '$odgovornaOsoba'
                UNION 
                SELECT DISTINCT(d2.imeDijagnoza) AS NazivPrimarna, 
                IF(pb.mkbSifraSekundarna = NULL, NULL, (SELECT d.imeDijagnoza FROM dijagnoze d WHERE d.mkbSifra = pb.mkbSifraSekundarna)) AS NazivSekundarna FROM dijagnoze d2 
                JOIN povijestbolesti pb ON d2.mkbSifra = pb.mkbSifraPrimarna
                JOIN ambulanta a ON pb.idPovijestBolesti = a.idPovijestBolesti
                JOIN lijecnik l ON l.idLijecnik = a.idLijecnik
                WHERE a.idPacijent = '$idPacijent' AND pb.mkbSifraPrimarna = '$mkbSifra' AND pb.datum = '$datumPregled' AND l.imeLijecnik = '$odgovornaOsoba';";
        
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
    function dohvatiOtvoreniSlucajPretraga($tip,$pretraga,$id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];
        //Ako je tip prijavljenog korisnika "lijecnik":
        if($tip == "lijecnik"){
            $sql = "SELECT DISTINCT(pb.mkbSifraPrimarna), DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, d.imeDijagnoza AS NazivPrimarna,
                    l.imeLijecnik AS OdgovornaOsoba FROM povijestbolesti pb
                    JOIN dijagnoze d ON pb.mkbSifraPrimarna = d.mkbSifra
                    JOIN ambulanta a ON a.idPovijestBolesti = pb.idPovijestBolesti 
                    JOIN lijecnik l ON l.idLijecnik = a.idLijecnik
                    WHERE a.idPacijent = '$id' AND (UPPER(d.imeDijagnoza) LIKE UPPER('%{$pretraga}%')  
                                                OR UPPER(l.imeLijecnik) LIKE UPPER('%{$pretraga}%') OR UPPER(pb.mkbSifraPrimarna) LIKE UPPER('%{$pretraga}%') 
                                                OR UPPER(pb.mkbSifraSekundarna) LIKE UPPER('%{$pretraga}%') OR UPPER(pb.datum) LIKE UPPER('%{$pretraga}%'))
                    GROUP BY pb.mkbSifraPrimarna
                    ORDER BY pb.datum DESC";
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
        //Ako je tip prijavljenog korisnika "sestra":
        else if($tip == "sestra"){
            $sql = "SELECT DISTINCT(p.mkbSifraPrimarna), DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, d.imeDijagnoza AS NazivPrimarna,
                    m.imeMedSestra AS OdgovornaOsoba FROM pregled p
                    JOIN dijagnoze d ON p.mkbSifraPrimarna = d.mkbSifra
                    JOIN ambulanta a ON a.idPregled = p.idPregled 
                    JOIN med_sestra m ON m.idMedSestra = a.idMedSestra
                    WHERE a.idPacijent = '$id' AND (UPPER(d.imeDijagnoza) LIKE UPPER('%{$pretraga}%')  
                                                    OR UPPER(m.imeMedSestra) LIKE UPPER('%{$pretraga}%') OR UPPER(p.mkbSifraPrimarna) LIKE UPPER('%{$pretraga}%') 
                                                    OR UPPER(p.mkbSifraSekundarna) LIKE UPPER('%{$pretraga}%') OR UPPER(p.datumPregled) LIKE UPPER('%{$pretraga}%'))
                    GROUP BY p.mkbSifraPrimarna 
                    ORDER BY p.datumPregled DESC";
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