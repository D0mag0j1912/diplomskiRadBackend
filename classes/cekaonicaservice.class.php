<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';
//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class CekaonicaService{

    //Funkcija koja dohvaća naziv i šifru sekundarnih dijagnoza na osnovu šifre sek. dijagnoze
    function dohvatiNazivSifraPovijestBolesti($polje,$idObrada){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
        
        //Za svaku pojedinu šifru sekundarne dijagnoze iz polja, pronađi joj šifru i naziv iz baze
        foreach($polje as $mkbSifra){
            $sql = "SELECT DISTINCT(TRIM(pb.mkbSifraPrimarna)) AS mkbSifraPrimarna,d.mkbSifra,d.imeDijagnoza,pb.idPovijestBolesti FROM dijagnoze d 
                    JOIN povijestBolesti pb ON pb.mkbSifraSekundarna = d.mkbSifra
                    WHERE d.mkbSifra = '$mkbSifra' AND pb.idObradaLijecnik = '$idObrada'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            } 
        }   
        return $response;
    }

    //Funkcija koja dohvaća naziv i šifru sekundarnih dijagnoza na osnovu šifre sek. dijagnoze
    function dohvatiNazivSifraOpciPodatci($polje,$idObrada){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
        
        //Za svaku pojedinu šifru sekundarne dijagnoze iz polja, pronađi joj šifru i naziv iz baze
        foreach($polje as $mkbSifra){
            $sql = "SELECT DISTINCT(TRIM(pr.mkbSifraPrimarna)) AS mkbSifraPrimarna,d.mkbSifra,d.imeDijagnoza,pr.idPregled FROM dijagnoze d 
                    JOIN pregled pr ON pr.mkbSifraSekundarna = d.mkbSifra
                    WHERE d.mkbSifra = '$mkbSifra' AND pr.idObradaMedSestra = '$idObrada'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            } 
        }

        return $response;
    }

    //Funkcija koja dohvaća povijest bolesti za određeni ID obrade liječnika
    function dohvatiPovijestBolesti($idObrada){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 
        
        $sql = "SELECT pb.anamneza,pb.terapija,TRIM(pb.mkbSifraPrimarna) AS mkbSifraPrimarna,d.imeDijagnoza AS NazivPrimarna, 
                GROUP_CONCAT(DISTINCT pb.mkbSifraSekundarna SEPARATOR ' ') AS mkbSifraSekundarna FROM povijestbolesti pb 
                JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna 
                WHERE pb.idObradaLijecnik = '$idObrada'
                GROUP BY pb.mkbSifraPrimarna";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        
        return $response;
    }

    //Funkcija koja dohvaća opće podatke pregleda za određeni ID obrade medicinske sestre
    function dohvatiOpcePodatke($idObrada){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 
        
        $sql = "SELECT TRIM(pr.mkbSifraPrimarna) AS mkbSifraPrimarna,d.imeDijagnoza AS NazivPrimarna, 
                GROUP_CONCAT(DISTINCT pr.mkbSifraSekundarna SEPARATOR ' ') AS mkbSifraSekundarna FROM pregled pr 
                JOIN dijagnoze d ON d.mkbSifra = pr.mkbSifraPrimarna 
                WHERE pr.idObradaMedSestra = '$idObrada' 
                GROUP BY pr.mkbSifraPrimarna";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        
        return $response;
    }

    //Funkcija koja dohvaća ime, prezime i datum pregleda pacijenta
    function dohvatiImePrezimeDatum($tip,$idObrada){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 
        //Ako je tip korisnika "lijecnik":
        if($tip == "lijecnik"){
            $sql = "SELECT p.imePacijent,p.prezPacijent,DATE_FORMAT(o.datumDodavanja,'%d.%m.%Y') AS Datum,o.idObrada FROM pacijent p 
                    JOIN obrada_lijecnik o ON o.idPacijent = p.idPacijent 
                    WHERE o.idObrada = '$idObrada'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }
        //Ako je tip korisnika "sestra":
        else if($tip == "sestra"){
            $sql = "SELECT p.imePacijent,p.prezPacijent,DATE_FORMAT(o.datumDodavanja,'%d.%m.%Y') AS Datum,o.idObrada FROM pacijent p 
                    JOIN obrada_med_sestra o ON o.idPacijent = p.idPacijent 
                    WHERE o.idObrada = '$idObrada'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }
        return $response;
    }

    //Funkcija koja briše pacijenta iz čekaonice
    function izbrisiPacijentaCekaonica($tip,$idObrada,$idCekaonica){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 
        //Ako je ID obrade prazan
        if(empty($idObrada)){
            $sql = "DELETE FROM cekaonica 
                WHERE idCekaonica = ?";
        
            //Kreiranje prepared statementa
            $stmt = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmt,$sql)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement brisanja čekaonice ne valja!";
            }
            //Ako je prepared statement u redu
            else{
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmt,"i",$idCekaonica);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmt);

                $response["success"] = "true";
                $response["message"] = "Pacijent uspješno izbrisan!";
            }  
        }
        //Ako ID obrade nije prazan
        else{
            //Ako je tip korisnika koji je dodao ovaj redak u čekaonicu "lijecnik":
            if($tip == "lijecnik"){
                $sqlObrada = "DELETE FROM obrada_lijecnik 
                            WHERE idObrada = ?;";

                //Kreiranje prepared statementa
                $stmtObrada = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmtObrada,$sqlObrada)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement brisanja obrade ne valja!";
                }
                //Ako je prepared statement u redu
                else{
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmtObrada,"i",$idObrada);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmtObrada);

                    $sql = "DELETE FROM cekaonica 
                            WHERE idObradaLijecnik = ?";
                
                    //Kreiranje prepared statementa
                    $stmt = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmt,$sql)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement brisanja čekaonice ne valja!";
                    }
                    //Ako je prepared statement u redu
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmt,"i",$idObrada);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmt);

                        $response["success"] = "true";
                        $response["message"] = "Pacijent uspješno izbrisan!";
                    }
                }
            }
            //Ako je tip korisnika koji je dodao ovaj redak u čekaonicu "sestra":
            else if($tip == "sestra"){
                $sqlObrada = "DELETE FROM obrada_med_sestra 
                            WHERE idObrada = ?;";

                //Kreiranje prepared statementa
                $stmtObrada = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmtObrada,$sqlObrada)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement brisanja obrade ne valja!";
                }
                //Ako je prepared statement u redu
                else{
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmtObrada,"i",$idObrada);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmtObrada);

                    $sql = "DELETE FROM cekaonica 
                        WHERE idObradaMedSestra = ?";
                
                    //Kreiranje prepared statementa
                    $stmt = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmt,$sql)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement brisanja čekaonice ne valja!";
                    }
                    //Ako je prepared statement u redu
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmt,"i",$idObrada);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmt);

                        $response["success"] = "true";
                        $response["message"] = "Pacijent uspješno izbrisan!";
                    }
                }
            }
        }

        return $response;
    }

    //Funkcija koja provjerava koliko još ima pacijenata u čekaonici
    function provjeriBrojCekaonica(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        //Kreiram sql upit koji će provjeriti koliko ima pacijenata u bazi podataka
        $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM cekaonica";
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
        //Vrati broj pacijenata
        return $brojPacijenata;
    }

    //Funkcija koja dodava pacijenta u čekaonicu
    function dodajUCekaonicu($tip,$id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Trenutni datum
        $datum = date('Y-m-d');
        //Trenutno vrijeme
        $vrijeme = date('H:i:s');
        //Početni status čekaonice
        $status = "Čeka na pregled";
        //Ako je prijavljeni korisnik "lijecnik"
        if($tip == "lijecnik"){
            $sqlID = "SELECT l.idLijecnik FROM lijecnik l;";
            $result = $conn->query($sqlID);

            if ($result->num_rows > 0) {
                // output data of each row
                while($row = $result->fetch_assoc()) {
                    //Dohvaćam ime liječnika
                    $idLijecnik = $row["idLijecnik"];
                }
            }

            $sql = "INSERT INTO cekaonica(idPacijent,datumDodavanja,vrijemeDodavanja,statusCekaonica,idLijecnik) VALUES (?,?,?,?,?)";
            //Kreiranje prepared statementa
            $stmt = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmt,$sql)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement ne valja!";
            }
            //Ako je prepared statement u redu
            else{
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmt,"isssi",$id,$datum,$vrijeme,$status,$idLijecnik);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmt);

                $response["success"] = "true";
                $response["message"] = "Pacijent je uspješno dodan u čekaonicu!";
            }
        }
        //Ako je prijavljeni korisnik "sestra":
        else if($tip == "sestra"){
            $sqlID = "SELECT m.idMedSestra FROM med_sestra m;";
            $result = $conn->query($sqlID);

            if ($result->num_rows > 0) {
                // output data of each row
                while($row = $result->fetch_assoc()) {
                    //Dohvaćam ime liječnika
                    $idMedSestra = $row["idMedSestra"];
                }
            }
            $sql = "INSERT INTO cekaonica(idPacijent,datumDodavanja,vrijemeDodavanja,statusCekaonica,idMedSestra) VALUES (?,?,?,?,?)";
            //Kreiranje prepared statementa
            $stmt = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmt,$sql)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement ne valja!";
            }
            //Ako je prepared statement u redu
            else{
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmt,"isssi",$id,$datum,$vrijeme,$status,$idMedSestra);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmt);

                $response["success"] = "true";
                $response["message"] = "Pacijent je uspješno dodan u čekaonicu!";
            }
        }
        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja dohvaća pacijente iz čekaonice
    function dohvatiPacijenteCekaonica(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Kreiram sql upit koji će provjeriti postoji li pacijenata u čekaonici
        $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM cekaonica";
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
        //Ako nema pronađenih pacijenata u čekaonici
        if($brojPacijenata == 0){
            $response["success"] = "false";
            $response["message"] = "Čekaonica je prazna!";
        }
        //Ako ima pacijenata u čekaonici
        else{
            //Kreiram upit koji dohvaća sve pacijente iz čekaonice
            $sql = "SELECT CASE 
                    WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT m.imeMedSestra) FROM med_sestra m JOIN cekaonica c ON c.idMedSestra = m.idMedSestra)
                    WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT l.imeLijecnik) FROM lijecnik l JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                    END AS OdgovornaOsoba,
                    p.idPacijent,p.imePacijent,p.prezPacijent,c.idCekaonica,
                    DATE_FORMAT(c.datumDodavanja,'%d.%m.%Y') AS DatumDodavanja,c.vrijemeDodavanja,
                    c.statusCekaonica,
                    CASE 
                        WHEN c.idObradaLijecnik IS NULL AND c.idObradaMedSestra IS NULL THEN NULL
                        WHEN c.idObradaLijecnik IS NULL THEN c.idObradaMedSestra 
                        WHEN c.idObradaMedSestra IS NULL THEN c.idObradaLijecnik
                    END AS idObrada,
                    CASE 	
                        WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                        JOIN med_sestra m ON k.idKorisnik = m.idKorisnik 
                                                        JOIN cekaonica c ON c.idMedSestra = m.idMedSestra) 
                        WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                        JOIN lijecnik l ON l.idKorisnik = k.idKorisnik 
                                                        JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                    END AS tip FROM pacijent p 
                    JOIN cekaonica c ON p.idPacijent = c.idPacijent 
                    ORDER BY c.statusCekaonica,c.datumDodavanja,c.vrijemeDodavanja DESC";

            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }
        
        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja provjerava status u čekaonici
    function provjeraCekaonica($tip,$id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Status pacijenta u čekaonici
        $status = "Čeka na pregled";

        //Ako je prijavljeni korisnik "lijecnik"
        if($tip == "lijecnik"){
            $sqlID = "SELECT l.idLijecnik FROM lijecnik l;";
            $resultID = $conn->query($sqlID);

            if ($resultID->num_rows > 0) {
                // output data of each row
                while($rowID = $resultID->fetch_assoc()) {
                    //Dohvaćam ID liječnika
                    $idLijecnik = $rowID["idLijecnik"];
                }
            }
            //Provjeravam je li postoji već taj pacijent u čekaonici da ga je dodao liječnik (da čeka na pregled) 
            $sql="SELECT c.idPacijent FROM cekaonica c 
                WHERE c.idPacijent = '$id' AND c.statusCekaonica = '$status' AND c.idLijecnik = '$idLijecnik'";

            $result = $conn->query($sql);

            if($result->num_rows > 0){
                $response["success"] = "false";
                $response["message"] = "Pacijent trenutno čeka na pregled!";
                return $response;
            }
            else{
                //Vraćam null
                return null;
            }
        }
        //Ako je prijavljeni korisnik "sestra":
        else if($tip == "sestra"){
            $sqlID = "SELECT m.idMedSestra FROM med_sestra m;";
            $resultID = $conn->query($sqlID);

            if ($resultID->num_rows > 0) {
                // output data of each row
                while($rowID = $resultID->fetch_assoc()) {
                    //Dohvaćam ID liječnika
                    $idMedSestra = $rowID["idMedSestra"];
                }
            }
            //Provjeravam je li postoji već taj pacijent u čekaonici da ga je dodala medicinska sestra (da čeka na pregled) 
            $sql="SELECT c.idPacijent FROM cekaonica c 
                WHERE c.idPacijent = '$id' AND c.statusCekaonica = '$status' AND c.idMedSestra = '$idMedSestra'";

            $result = $conn->query($sql);

            if($result->num_rows > 0){
                $response["success"] = "false";
                $response["message"] = "Pacijent trenutno čeka na pregled!";
                return $response;
            }
            else{
                //Vraćam null
                return null;
            }
        }
    }

    //Funkcija koja dohvaća 10 zadnjih pacijenata u čekaonici
    function dohvati10zadnjih(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Kreiram sql upit koji će provjeriti postoji li pacijenata u čekaonici
        $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM cekaonica";
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
        //Ako nema pronađenih pacijenata u čekaonici
        if($brojPacijenata == 0){
            $response["success"] = "false";
            $response["message"] = "Čekaonica je prazna!";
        }
        //Ako ima pacijenata u čekaonici
        else{

            $sql = "SELECT CASE 
                    WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT m.imeMedSestra) FROM med_sestra m JOIN cekaonica c ON c.idMedSestra = m.idMedSestra)
                    WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT l.imeLijecnik) FROM lijecnik l JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                    END AS OdgovornaOsoba,
                    p.idPacijent,p.imePacijent,p.prezPacijent,c.idCekaonica,
                    DATE_FORMAT(c.datumDodavanja,'%d.%m.%Y') AS DatumDodavanja,c.vrijemeDodavanja,
                    c.statusCekaonica,
                    CASE 
                        WHEN c.idObradaLijecnik IS NULL AND c.idObradaMedSestra IS NULL THEN NULL
                        WHEN c.idObradaLijecnik IS NULL THEN c.idObradaMedSestra 
                        WHEN c.idObradaMedSestra IS NULL THEN c.idObradaLijecnik
                    END AS idObrada,
                    CASE 	
                        WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                        JOIN med_sestra m ON k.idKorisnik = m.idKorisnik 
                                                        JOIN cekaonica c ON c.idMedSestra = m.idMedSestra) 
                        WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                        JOIN lijecnik l ON l.idKorisnik = k.idKorisnik 
                                                        JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                    END AS tip FROM pacijent p 
                    JOIN cekaonica c ON p.idPacijent = c.idPacijent 
                    ORDER BY c.statusCekaonica,c.datumDodavanja,c.vrijemeDodavanja DESC 
                    LIMIT 10";

            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }
        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja dohvaća pacijente iz čekaonice po njihovom statusu u čekaonici
    function dohvatiPacijentaPoStatusu($statusi){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Ako polje nije prazno
        if(!empty($statusi)){
            foreach($statusi as $status){
        
                $sql = "SELECT CASE 
                        WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT m.imeMedSestra) FROM med_sestra m JOIN cekaonica c ON c.idMedSestra = m.idMedSestra)
                        WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT l.imeLijecnik) FROM lijecnik l JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                        END AS OdgovornaOsoba,
                        p.idPacijent,p.imePacijent,p.prezPacijent,c.idCekaonica,
                        DATE_FORMAT(c.datumDodavanja,'%d.%m.%Y') AS DatumDodavanja,c.vrijemeDodavanja,
                        c.statusCekaonica,
                        CASE 
                            WHEN c.idObradaLijecnik IS NULL AND c.idObradaMedSestra IS NULL THEN NULL
                            WHEN c.idObradaLijecnik IS NULL THEN c.idObradaMedSestra 
                            WHEN c.idObradaMedSestra IS NULL THEN c.idObradaLijecnik
                        END AS idObrada,
                        CASE 	
                            WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                            JOIN med_sestra m ON k.idKorisnik = m.idKorisnik 
                                                            JOIN cekaonica c ON c.idMedSestra = m.idMedSestra) 
                            WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                            JOIN lijecnik l ON l.idKorisnik = k.idKorisnik 
                                                            JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                        END AS tip FROM pacijent p 
                        JOIN cekaonica c ON p.idPacijent = c.idPacijent 
                        WHERE c.statusCekaonica = '$status'
                        ORDER BY c.statusCekaonica,c.datumDodavanja,c.vrijemeDodavanja DESC";
        
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
}
?>