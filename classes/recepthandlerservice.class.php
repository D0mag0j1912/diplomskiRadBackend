<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class ReceptHandlerService{

    //Funkcija koja dohvaća pacijente na temelju ID-ova (pretraga recepata u listi)
    function dohvatiPacijentPoIDu($ids){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Ako polje nije prazno
        if(!empty($ids)){
            //Prolazim poljem ID-ova pacijenata
            foreach($ids as $id){
                //Kreiram SQL upit koji će dohvatiti recepte za ID pacijenta
                $sql = "SELECT p.idPacijent,p.imePacijent,p.prezPacijent, 
                        DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS Datum,
                        p.mboPacijent FROM pacijent p
                        WHERE p.idPacijent = '$id'
                        ORDER BY p.prezPacijent ASC
                        LIMIT 7;";

                $result = $conn->query($sql);
                
                //Ako postoji pacijent s ovim ID-om
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }
            }
            //Vraća recepte frontendu
            return $response;
        }
    }

    //Funkcija koja dohvaća recepte na temelju ID-ova pacijenata koji se nalaze u lijevoj tablici
    function dohvatiReceptPoIDu($ids){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Ako polje nije prazno
        if(!empty($ids)){
            //Brojim koliko ID-eva pacijenata ima u ovom polju
            $brojac = count($ids);
            //Prolazim poljem ID-ova pacijenata
            foreach($ids as $id){
                //Kreiram SQL upit koji će dohvatiti recepte za ID pacijenta
                $sql = "SELECT DISTINCT(r.mkbSifraPrimarna) AS mkbSifraPrimarna,CONCAT(p.imePacijent,' ',p.prezPacijent) AS Pacijent, 
                        DATE_FORMAT(r.datumRecept,'%d.%m.%Y') AS Datum, 
                        IF(r.oblikJacinaPakiranjeLijek IS NULL, 
                        r.proizvod, CONCAT(r.proizvod,' ',r.oblikJacinaPakiranjeLijek)) AS proizvod, 
                        r.dostatnost,r.idPacijent,r.vrijemeRecept 
                        FROM recept r 
                        JOIN pacijent p ON p.idPacijent = r.idPacijent
                        WHERE r.idPacijent = '$id' 
                        ORDER BY r.datumRecept DESC,r.vrijemeRecept DESC";

                $result = $conn->query($sql);
                
                //Ako pacijent IMA evidentiranih recepata:
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }
                //Ako pacijent NEMA evidentiranih recepata:
                else{
                    //Ako ima samo JEDAN ID pacijenta:
                    if($brojac == 1){
                        //Kreiram sql koji će dohvatiti ime i prezime pacijenta koji odgovara ID-u
                        $sqlPacijent = "SELECT p.imePacijent,p.prezPacijent FROM pacijent p 
                                        WHERE p.idPacijent = '$id'";
                        $resultPacijent = $conn->query($sqlPacijent);
                        //Ako je baza vratila neke redove
                        if ($resultPacijent->num_rows > 0) {
                            while($rowPacijent = $resultPacijent->fetch_assoc()) {
                                //Dohvaćam ime i prezime pacijenta koji nema evidentiranih recepata
                                $imePacijent = $rowPacijent['imePacijent'];
                                $prezimePacijent = $rowPacijent['prezPacijent'];
                            }
                        }
                        $response["success"] = "false";
                        $response["message"] = "Pacijent ".$imePacijent." ".$prezimePacijent." nema evidentiranih recepata!";
                    }
                }
            }
            //Vraća recepte frontendu
            return $response;
        }
    }

    //Funkcija koja dohvaća recepte na temelju liječničke pretrage
    function dohvatiReceptePretraga($pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Definiram inicijalno stanje obrade
        $status = "Aktivan";
        //Kreiram prazno polje odgovora
        $response = [];

        //Ako je $pretraga prazan string
        if(empty($pretraga)){
            //Kreiram upit koji će provjeriti je li postoji aktivan pacijent u obradi
            $sqlObrada = "SELECT COUNT(*) AS BrojAktivnihPacijenata FROM obrada_lijecnik o 
                        WHERE o.statusObrada = '$status'";
            //Rezultat upita spremam u varijablu $resultObrada
            $resultObrada = mysqli_query($conn,$sqlObrada);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($resultObrada) > 0){
                //Idem redak po redak rezultata upita 
                while($rowObrada = mysqli_fetch_assoc($resultObrada)){
                    //Vrijednost rezultata spremam u varijablu $brojAktivnihPacijenata
                    $brojAktivnihPacijenata = $rowObrada['BrojAktivnihPacijenata'];
                }
            }
            //Ako NEMA pronađenih pacijenata u obradi
            if($brojAktivnihPacijenata == 0){
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
                    //Kreiram upit koji dohvaća podatke recepata zadnjih 7 pacijenata koje je liječnik stavio u obradu
                    $sql = "SELECT DISTINCT(r.mkbSifraPrimarna) AS mkbSifraPrimarna,CONCAT(p.imePacijent,' ',p.prezPacijent) AS Pacijent, 
                            DATE_FORMAT(r.datumRecept,'%d.%m.%Y') AS Datum, 
                            IF(r.oblikJacinaPakiranjeLijek IS NULL, 
                            r.proizvod, CONCAT(r.proizvod,' ',r.oblikJacinaPakiranjeLijek)) AS proizvod, 
                            r.dostatnost,r.idPacijent,r.vrijemeRecept  FROM recept r 
                            JOIN pacijent p ON p.idPacijent = r.idPacijent
                            WHERE r.idPacijent IN 
                            (SELECT o.idPacijent FROM obrada_lijecnik o)
                            ORDER BY r.datumRecept DESC,r.vrijemeRecept DESC
                            LIMIT 7;";

                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $response[] = $row;
                        }
                    }
                }
            }
            //Ako IMA pronađenih pacijenata u obradi
            else{
                //Prvo gledam je li pacijent u obradi ima evidentiranih recepata
                $sqlCountRecept = "SELECT COUNT(*) AS BrojRecept FROM recept r
                                    WHERE r.idPacijent IN 
                                    (SELECT o.idPacijent FROM obrada_lijecnik o 
                                    WHERE o.statusObrada = '$status');";
                //Rezultat upita spremam u varijablu $resultCountPacijent
                $resultCountRecept = mysqli_query($conn,$sqlCountRecept);
                //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                if(mysqli_num_rows($resultCountRecept) > 0){
                    //Idem redak po redak rezultata upita 
                    while($rowCountRecept = mysqli_fetch_assoc($resultCountRecept)){
                        //Vrijednost rezultata spremam u varijablu $brojRecept
                        $brojRecept = $rowCountRecept['BrojRecept'];
                    }
                }
                //Ako nema pronađenih recepata
                if($brojRecept == 0){
                    $response["success"] = "false";
                    $response["message"] = "Aktivni pacijent nema evidentiranih recepata!";
                }
                //Ako ima pronađenih recepata za trenutno aktivnog pacijenta
                else{
                    //Kreiram upit koji će dohvatiti recepte aktivnog pacijenta u obradi
                    $sql = "SELECT DISTINCT(r.mkbSifraPrimarna) AS mkbSifraPrimarna,CONCAT(p.imePacijent,' ',p.prezPacijent) AS Pacijent, 
                            DATE_FORMAT(r.datumRecept,'%d.%m.%Y') AS Datum, 
                            IF(r.oblikJacinaPakiranjeLijek IS NULL, 
                            r.proizvod, CONCAT(r.proizvod,' ',r.oblikJacinaPakiranjeLijek)) AS proizvod, 
                            r.dostatnost,r.idPacijent,r.vrijemeRecept  FROM recept r 
                            JOIN pacijent p ON p.idPacijent = r.idPacijent
                            WHERE r.idPacijent IN 
                            (SELECT o.idPacijent FROM obrada_lijecnik o 
                            WHERE o.statusObrada = '$status'); 
                            ORDER BY r.datumRecept DESC, r.vrijemeRecept DESC";

                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $response[] = $row;
                        }
                    }
                }
            }
        }
        //Ako $pretraga nije prazna
        else{
            $sql = "SELECT DISTINCT(r.mkbSifraPrimarna) AS mkbSifraPrimarna,CONCAT(p.imePacijent,' ',p.prezPacijent) AS Pacijent, 
                    DATE_FORMAT(r.datumRecept,'%d.%m.%Y') AS Datum, 
                    IF(r.oblikJacinaPakiranjeLijek IS NULL, 
                    r.proizvod, CONCAT(r.proizvod,' ',r.oblikJacinaPakiranjeLijek)) AS proizvod, 
                    r.dostatnost,r.idPacijent,r.vrijemeRecept  FROM recept r 
                    JOIN pacijent p ON p.idPacijent = r.idPacijent
                    WHERE UPPER(r.mkbSifraPrimarna) LIKE '%{$pretraga}%' OR UPPER(CONCAT(p.imePacijent,' ',p.prezPacijent)) LIKE '%{$pretraga}%' 
                    OR UPPER(DATE_FORMAT(r.datumRecept,'%d.%m.%Y')) LIKE '%a%' 
                    OR UPPER(IF(r.oblikJacinaPakiranjeLijek IS NULL,r.proizvod,CONCAT(r.proizvod,' ',r.oblikJacinaPakiranjeLijek))) LIKE '%{$pretraga}%' 
                    OR UPPER(r.dostatnost) LIKE '%{$pretraga}%' 
                    ORDER BY r.datumRecept DESC, r.vrijemeRecept DESC;";
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
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća recepte koji odgovoraju INICIJALNOM STANJU baze
    function dohvatiInicijalneRecepte(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Definiram inicijalni status obrade
        $status = 'Aktivan';
        //Kreiram prazno polje odgovora
        $response = [];

        //Kreiram upit koji će provjeriti je li postoji aktivan pacijent u obradi
        $sqlObrada = "SELECT COUNT(*) AS BrojAktivnihPacijenata FROM obrada_lijecnik o 
                    WHERE o.statusObrada = '$status'";
        //Rezultat upita spremam u varijablu $resultObrada
        $resultObrada = mysqli_query($conn,$sqlObrada);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultObrada) > 0){
            //Idem redak po redak rezultata upita 
            while($rowObrada = mysqli_fetch_assoc($resultObrada)){
                //Vrijednost rezultata spremam u varijablu $brojAktivnihPacijenata
                $brojAktivnihPacijenata = $rowObrada['BrojAktivnihPacijenata'];
            }
        }
        //Ako NEMA pronađenih pacijenata u obradi
        if($brojAktivnihPacijenata == 0){
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
                //Kreiram upit koji dohvaća recepte za zadnjih 7 pacijenata koje je liječnik stavio u obradu
                $sql = "SELECT DISTINCT(r.mkbSifraPrimarna) AS mkbSifraPrimarna,CONCAT(p.imePacijent,' ',p.prezPacijent) AS Pacijent, 
                        DATE_FORMAT(r.datumRecept,'%d.%m.%Y') AS Datum, 
                        IF(r.oblikJacinaPakiranjeLijek IS NULL, 
                        r.proizvod, CONCAT(r.proizvod,' ',r.oblikJacinaPakiranjeLijek)) AS proizvod, 
                        r.dostatnost,r.idPacijent,r.vrijemeRecept  FROM recept r 
                        JOIN pacijent p ON p.idPacijent = r.idPacijent
                        WHERE r.idPacijent IN 
                        (SELECT o.idPacijent FROM obrada_lijecnik o)
                        ORDER BY r.datumRecept DESC,r.vrijemeRecept DESC
                        LIMIT 7;";
    
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }
            }
        }
        //Ako IMA pronađenih pacijenata u obradi
        else{
            //Prvo gledam je li pacijent u obradi ima evidentiranih recepata
            $sqlCountRecept = "SELECT COUNT(*) AS BrojRecept FROM recept r
                                WHERE r.idPacijent IN 
                                (SELECT o.idPacijent FROM obrada_lijecnik o 
                                WHERE o.statusObrada = '$status');";
            //Rezultat upita spremam u varijablu $resultCountPacijent
            $resultCountRecept = mysqli_query($conn,$sqlCountRecept);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($resultCountRecept) > 0){
                //Idem redak po redak rezultata upita 
                while($rowCountRecept = mysqli_fetch_assoc($resultCountRecept)){
                    //Vrijednost rezultata spremam u varijablu $brojRecept
                    $brojRecept = $rowCountRecept['BrojRecept'];
                }
            }
            //Ako nema pronađenih recepata
            if($brojRecept == 0){
                $response["success"] = "false";
                $response["message"] = "Aktivni pacijent nema evidentiranih recepata!";
            }
            //Ako ima pronađenih recepata:
            else{
                //Kreiram upit koji će dohvatiti recepte aktivnog pacijenta u obradi
                $sql = "SELECT DISTINCT(r.mkbSifraPrimarna) AS mkbSifraPrimarna, 
                        CONCAT(p.imePacijent,' ',p.prezPacijent) AS Pacijent, 
                        DATE_FORMAT(r.datumRecept,'%d.%m.%Y') AS Datum, 
                        IF(r.oblikJacinaPakiranjeLijek IS NULL, 
                        r.proizvod, CONCAT(r.proizvod,' ',r.oblikJacinaPakiranjeLijek)) AS proizvod, 
                        r.dostatnost,r.idPacijent,r.vrijemeRecept  FROM recept r 
                        JOIN pacijent p ON p.idPacijent = r.idPacijent
                        WHERE r.idPacijent IN 
                        (SELECT o.idPacijent FROM obrada_lijecnik o 
                        WHERE o.statusObrada = '$status') 
                        ORDER BY r.datumRecept, r.vrijemeRecept DESC";

                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }   
            }
        }
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća pacijente na osnovu liječničke pretrage
    function dohvatiPacijentiPretraga($pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Definiram inicijalno stanje obrade
        $status = "Aktivan";
        //Kreiram prazno polje odgovora
        $response = [];

        //Ako je $pretraga prazan string
        if(empty($pretraga)){
            //Kreiram upit koji će provjeriti je li postoji aktivan pacijent u obradi
            $sqlObrada = "SELECT COUNT(*) AS BrojAktivnihPacijenata FROM obrada_lijecnik o 
                        WHERE o.statusObrada = '$status'";
            //Rezultat upita spremam u varijablu $resultObrada
            $resultObrada = mysqli_query($conn,$sqlObrada);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($resultObrada) > 0){
                //Idem redak po redak rezultata upita 
                while($rowObrada = mysqli_fetch_assoc($resultObrada)){
                    //Vrijednost rezultata spremam u varijablu $brojAktivnihPacijenata
                    $brojAktivnihPacijenata = $rowObrada['BrojAktivnihPacijenata'];
                }
            }
            //Ako NEMA pronađenih pacijenata u obradi
            if($brojAktivnihPacijenata == 0){
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
                    //Kreiram upit koji dohvaća zadnjih 7 pacijenata koje je liječnik stavio u obradu
                    $sql = "SELECT p.idPacijent,p.imePacijent,p.prezPacijent, 
                            DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS Datum,
                            p.mboPacijent FROM pacijent p
                            WHERE p.idPacijent IN 
                            (SELECT o.idPacijent FROM obrada_lijecnik o)
                            ORDER BY p.prezPacijent ASC
                            LIMIT 7;";

                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $response[] = $row;
                        }
                    }
                }
            }
            //Ako IMA pronađenih pacijenata u obradi
            else{
                //Kreiram upit koji će dohvatiti podatke aktivnog pacijenta u obradi
                $sql = "SELECT p.idPacijent,p.imePacijent,p.prezPacijent, 
                        DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS Datum, p.mboPacijent FROM pacijent p 
                        JOIN obrada_lijecnik o ON o.idPacijent = p.idPacijent 
                        WHERE o.statusObrada = '$status'";

                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }
            }
        }
        //Ako $pretraga nije prazna
        else{
            //Kreiram upit koji će dohvatiti pacijente ovisno o pretrazi liječnika
            $sql = "SELECT p.idPacijent,p.imePacijent,p.prezPacijent, 
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
        }
        //Vraćam odgovor baze
        return $response;
    }
    //Funkcija koja dohvaća trenutno aktivnog pacijenta ili sve pacijente
    function dohvatiInicijalnoAktivanPacijent(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Definiram inicijalno stanje obrade
        $status = "Aktivan";
        //Kreiram prazno polje odgovora
        $response = [];

        //Kreiram upit koji će provjeriti je li postoji aktivan pacijent u obradi
        $sqlObrada = "SELECT COUNT(*) AS BrojAktivnihPacijenata FROM obrada_lijecnik o 
                    WHERE o.statusObrada = '$status'";
        //Rezultat upita spremam u varijablu $resultObrada
        $resultObrada = mysqli_query($conn,$sqlObrada);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultObrada) > 0){
            //Idem redak po redak rezultata upita 
            while($rowObrada = mysqli_fetch_assoc($resultObrada)){
                //Vrijednost rezultata spremam u varijablu $brojAktivnihPacijenata
                $brojAktivnihPacijenata = $rowObrada['BrojAktivnihPacijenata'];
            }
        }
        //Ako NEMA pronađenih pacijenata u obradi
        if($brojAktivnihPacijenata == 0){
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
                //Kreiram upit koji dohvaća zadnjih 7 pacijenata koje je liječnik stavio u obradu
                $sql = "SELECT p.idPacijent,p.imePacijent,p.prezPacijent, 
                        DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS Datum,
                        p.mboPacijent FROM pacijent p
                        WHERE p.idPacijent IN 
                        (SELECT o.idPacijent FROM obrada_lijecnik o)
                        ORDER BY p.prezPacijent ASC
                        LIMIT 7;";
    
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }
            }
        }
        //Ako IMA pronađenih pacijenata u obradi
        else{
            //Kreiram upit koji će dohvatiti podatke aktivnog pacijenta u obradi
            $sql = "SELECT p.idPacijent,p.imePacijent,p.prezPacijent, 
                    DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS Datum, p.mboPacijent FROM pacijent p 
                    JOIN obrada_lijecnik o ON o.idPacijent = p.idPacijent 
                    WHERE o.statusObrada = '$status'";

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