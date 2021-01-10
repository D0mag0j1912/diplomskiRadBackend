<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class LijecnikService{

    //Za header.php dohvaćanje ID registriranog liječnika
    function getIDLijecnik(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
        
        //Kreiram sql upit koji će provjeriti koliko ima liječnika u bazi podataka
        $sqlCountLijecnik = "SELECT COUNT(*) AS BrojLijecnik FROM lijecnik";
        //Rezultat upita spremam u varijablu $resultCountLijecnik
        $resultCountLijecnik = mysqli_query($conn,$sqlCountLijecnik);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCountLijecnik) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCountLijecnik = mysqli_fetch_assoc($resultCountLijecnik)){
                //Vrijednost rezultata spremam u varijablu $brojLijecnika
                $brojLijecnika = $rowCountLijecnik['BrojLijecnik'];
            }
        } 
        //Ako je broj liječnika 0
        if($brojLijecnika == 0){
            $response["success"] = "false";
            $response["message"] = "Nema evidentiranih liječnika!";
        }
        //Ako ima liječnika
        else{
            //Kreiram upit koji dohvaća ID liječnika
            $sql = "SELECT idLijecnik FROM lijecnik";
            
            $result = $conn->query($sql);

            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                    $response[] = $row;
                }
            }
        }
        //Vraćam odgovor baze 
        return $response;
    }
    
    //Funkcija koja dohvaća osobne podatke liječnika
    function dohvatiOsobnePodatke(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        //Kreiram upit koji dohvaća osobne podatke liječnika
        $sql = "SELECT * FROM lijecnik l 
                JOIN korisnik k ON l.idKorisnik = k.idKorisnik";
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response;
    }

    function azurirajOsobnePodatke($id,$email,$ime,$prezime,$adresa,$specijalizacija){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        //Provjeravam postoji li neki liječnik u bazi koji ima iste podatke kao ovaj koji ažurira profil (ima različiti ID)
        $sql = "SELECT * FROM lijecnik l
                WHERE l.nazSpecLijecnik = ? AND l.imeLijecnik = ? AND l.prezLijecnik = ? 
                AND l.adrLijecnik = ? AND l.idLijecnik != ?";
        //Kreiram prepared statement
        $stmt = mysqli_stmt_init($conn);
        //Ako je prepared statment neuspješno izvršen
        if(!mysqli_stmt_prepare($stmt,$sql)){
            $response["success"] = "false";
            $response["message"] = "Prepared statement lijecnika ne valja!";
        }
        //Ako je prepared statment uspješno izvršen
        else{
            //Uzima sve parametre i stavlja ih umjesto upitnika u upitu
            mysqli_stmt_bind_param($stmt,"ssssi",$specijalizacija,$ime,$prezime,$adresa,$id);
            //Izvršavam statement
            mysqli_stmt_execute($stmt);
            //Rezultat koji smo dobili iz baze podataka pohranjuje u varijablu $stmt
            mysqli_stmt_store_result($stmt);
            //Vraća broj redaka što je baza podataka vratila
            $resultCheck = mysqli_stmt_num_rows($stmt);
            //Ako liječnik već postoji u bazi podataka
            if($resultCheck > 0){
                $response["success"] = "false";
                $response["message"] = "Liječnik već postoji u bazi podataka!";
            }
            //Ako je sve u redu do sada
            else{
                //Kreiram upit za bazu podataka koji će ažurirati vrijednosti liječnika iz baze na nove vrijednosti koje je liječnik ažurirao za svoj profil za tablicu "LIJECNIK"
                $sqlLijecnik = "UPDATE lijecnik l SET l.imeLijecnik = ?,l.prezLijecnik = ?, l.adrLijecnik = ?, l.nazSpecLijecnik = ? 
                                WHERE l.idLijecnik = ?";
                //Kreiram prepared statement
                $stmtLijecnik = mysqli_stmt_init($conn);
                //Ako je prepared statment neuspješno izvršen
                if(!mysqli_stmt_prepare($stmtLijecnik,$sqlLijecnik)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement liječnika ne valja!";
                }
                //Ako je prepared statement uspješno izvršen
                else{
                    //Uzima sve parametre što je liječnik ažurirao i stavlja ih umjesto upitnika u upitu
                    mysqli_stmt_bind_param($stmtLijecnik,"ssssi",$ime,$prezime,$adresa,$specijalizacija,$id);
                    //Izvršavam statement
                    mysqli_stmt_execute($stmtLijecnik);

                    //Kreiram upit za spremanje pacijentovih podataka u tablicu "pacijent_dodatno" :
                    $sqlLijecnikDodatno = "INSERT INTO lijecnik_dodatno (idLijecnik,datAzurLijecnik,tipAzurLijecnik) VALUES (?,?,?)";
                    //Kreiram prepared statment
                    $stmtLijecnikDodatno = mysqli_stmt_init($conn);
                    //Ako je prepared statment neuspješno izvršen
                    if(!mysqli_stmt_prepare($stmtLijecnikDodatno,$sqlLijecnikDodatno)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement liječnika dod ne valja!";    
                    }
                    //Ako je prepared statment uspješno izvršen
                    else{
                        $trenutniDatum = date("Y-m-d h:i:sa");
                        $tip = "osobniPodatci";
                        //Uzima sve parametre što je liječnik unio i stavlja ih umjesto upitnika
                        mysqli_stmt_bind_param($stmtLijecnikDodatno,"iss",$id,$trenutniDatum,$tip);
                        //Izvršavam statement
                        mysqli_stmt_execute($stmtLijecnikDodatno);

                        //Izvršavam upit koji dohvaća ID korisnika koji odgovara unesenom email-u
                        $resultKorisnik = mysqli_query($conn,"SELECT l.idKorisnik FROM lijecnik l WHERE l.idLijecnik = '" . mysqli_real_escape_string($conn, $id) . "'"); 
                        while($rowKorisnik = mysqli_fetch_array($resultKorisnik))
                        {
                            $idKorisnik = $rowKorisnik['idKorisnik'];
                        }
                        
                        //Kreiram upit za bazu podataka koji će ažurirati vrijednosti liječnika iz baze na nove vrijednosti koje je liječnik ažurirao za svoj profil za tablicu "LIJECNIK"
                        $sqlKorisnik = "UPDATE korisnik k SET k.email = ? WHERE k.idKorisnik = ?";
                        //Kreiram prepared statement
                        $stmtKorisnik = mysqli_stmt_init($conn);
                        //Ako je prepared statment neuspješno izvršen
                        if(!mysqli_stmt_prepare($stmtKorisnik,$sqlKorisnik)){
                            $response["success"] = "false";
                            $response["message"] = "Prepared statement korisnika ne valja!";
                        }
                        //Ako je prepared statement uspješno izvršen
                        else{
                            //Uzima sve parametre što je liječnik ažurirao i stavlja ih umjesto upitnika u upitu
                            mysqli_stmt_bind_param($stmtKorisnik,"si",$email,$idKorisnik);
                            //Izvršavam statement
                            mysqli_stmt_execute($stmtKorisnik);

                            $response["success"] = "true";
                            $response["message"] = "Ažuriranje osobnih podataka uspješno!";
                        }
                    }
                }
            }
        }
        return $response;
    }

    //Funkcija koja provjerava je li unesena ispravna trenutna lozinka za registriranog liječnika
    function provjeraLozinka($id,$trenutna){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        //Moram napravit provjeru je li unesena trenutna lozinka odgovara liječniku
        $sql = "SELECT k.pass FROM korisnik k 
                JOIN lijecnik l ON k.idKorisnik = l.idKorisnik 
                WHERE l.idLijecnik = ?";
        //Kreiranje prepared statementa
        $stmt = mysqli_stmt_init($conn);
        if(!mysqli_stmt_prepare($stmt,$sql)){
            $response["success"] = "false";
            $response["message"] = "Prepared statement ne valja!";
        }
        //Ako je prepared statement u redu
        else{
            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
            mysqli_stmt_bind_param($stmt,"i",$id);
            //Izvršavanje statementa
            mysqli_stmt_execute($stmt);
            //Vraćam rezultat statementa u varijablu $result
            $result = mysqli_stmt_get_result($stmt);
            //Ako smo dobili nešto u $result, tj. ako je baza podataka našla nešto
            if($row = mysqli_fetch_assoc($result)){
                //Provjera passworda korisnika (uzima password koji je korisnik upisao i password koji odgovara ID-u liječnika u bazi podataka
                //i provjerava je li se ti passwordi poklapaju) -> vraća true ili false
                $passwordCheck = password_verify($trenutna,$row['pass']);
                //Ako password koji je korisnik upisao NE ODGOVARA passwordu iz baze podataka za taj ID liječnika
                if($passwordCheck == false){
                    $response["success"] = "false";
                    $response["message"] = "Upisana trenutna lozinka ne odgovara lozinki trenutno registriranog liječnika!";
                    return $response;   
                }
                else{
                    return null;
                }
            }
        }
    }
    //Funkcija koja provjerava jesu li nova lozinka i potvrda nove lozinke identične
    function jednakeLozinke($nova,$potvrdaNova){
        $response = [];
        if($nova !== $potvrdaNova){
            $response["success"] = "false";
            $response["message"] = "Nova lozinka i potvrda lozinke moraju biti identične!";
            return $response;
        }
        else{
            return null;
        }
    }

    function azurirajLozinka($id,$nova){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        //Kreiram upit koji će ažurirati lozinku liječniku u tablici "korisnik"
        $sql = "UPDATE korisnik k SET k.pass = ? 
                        WHERE k.idKorisnik IN 
                        (SELECT l.idKorisnik FROM lijecnik l 
                        WHERE l.idLijecnik = ?)";
        //Kreiram prepared statment
        $stmt= mysqli_stmt_init($conn);
        //Ako je prepared statment neuspješno izvršen
        if(!mysqli_stmt_prepare($stmt,$sql)){
            $response["success"] = "false";
            $response["message"] = "Prepared statement ne valja!";
        }
        //Ako je prepared statment uspješno izvršen
        else{
            //Hashiram password 
            $passwordHash = password_hash($nova,PASSWORD_DEFAULT);
            //Uzima password što je korisnik unio i stavlja ga umjesto upitnika
            mysqli_stmt_bind_param($stmt,"si",$passwordHash,$id);
            //Izvršavam statement
            mysqli_stmt_execute($stmt);

            //Kreiram upit za spremanje pacijentovih podataka u tablicu "pacijent_dodatno" :
            $sqlLijecnikDodatno = "INSERT INTO lijecnik_dodatno (idLijecnik,datAzurLijecnik,tipAzurLijecnik) VALUES (?,?,?)";
            //Kreiram prepared statment
            $stmtLijecnikDodatno = mysqli_stmt_init($conn);
            //Ako je prepared statment neuspješno izvršen
            if(!mysqli_stmt_prepare($stmtLijecnikDodatno,$sqlLijecnikDodatno)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement liječnika dod ne valja!";    
            }
            //Ako je prepared statment uspješno izvršen
            else{
                $trenutniDatum = date("Y-m-d h:i:sa");
                $tip = "lozinka";
                //Uzima sve parametre što je liječnik unio i stavlja ih umjesto upitnika
                mysqli_stmt_bind_param($stmtLijecnikDodatno,"iss",$id,$trenutniDatum,$tip);
                //Izvršavam statement
                mysqli_stmt_execute($stmtLijecnikDodatno);

                //Vraćam uspješni odgovor
                $response["success"] = "true";
                $response["message"] = "Lozinka liječnika je uspješno ažurirana!";
            }
        }
        return $response;
    }
    /******************************************
    * DODAVANJE PACIJENTA */
    function dodajPacijenta($ime,$prezime,$email,$spol,$starost){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
        
        //Kreiram sql upit koji provjerava je li taj pacijent postoji već u bazi podataka
        $sql = "SELECT * FROM pacijent p 
                WHERE p.imePacijent = ? AND p.prezPacijent = ? 
                AND p.spolPacijent = ? AND p.emailPacijent = ? 
                AND p.starostPacijent = ?";
        //Kreiram prepared statment
        $stmt= mysqli_stmt_init($conn);
        //Ako je prepared statment neuspješno izvršen
        if(!mysqli_stmt_prepare($stmt,$sql)){
            $response["success"] = "false";
            $response["message"] = "Prepared statement ne valja!";
        }
        //Ako je prepared statement uspješno izvršen
        else{
            //Uzima sve parametre što je liječnik unio i stavlja ga umjesto upitnika
            mysqli_stmt_bind_param($stmt,"ssssi",$ime,$prezime,$spol,$email,$starost);
            //Izvršavam statement
            mysqli_stmt_execute($stmt);
            //Rezultat koji smo dobili iz baze podataka pohranjuje u varijablu $stmt
            mysqli_stmt_store_result($stmt);
            //Vraća broj redaka što je baza podataka vratila
            $resultCheck = mysqli_stmt_num_rows($stmt);
            //Ako pacijent već postoji u bazi podataka
            if($resultCheck > 0){
                $response["success"] = "false";
                $response["message"] = "Pacijent već postoji u bazi podataka!";
            }
            //Ako je sve u redu do sada
            else{
                //Kreiram upit za spremanje pacijentovih podataka u tablicu "pacijent" :
                $sqlPacijent = "INSERT INTO pacijent (imePacijent,prezPacijent,spolPacijent,starostPacijent,datKreirPacijent,mboPacijent,emailPacijent)
                                VALUES(?,?,?,?,?,?,?)";
                //Kreiram prepared statment
                $stmtPacijent = mysqli_stmt_init($conn);
                //Ako je prepared statment neuspješno izvršen
                if(!mysqli_stmt_prepare($stmtPacijent,$sqlPacijent)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement ne valja!";    
                }
                //Ako je prepared statment uspješno izvršen
                else{
                    $trenutniDatum = date("Y-m-d h:i:sa");
                    //Generiram random mbo pacijenta
                    $mbo = rand(100000000,999999999);
                    //Uzima sve parametre što je liječnik unio i stavlja ih umjesto upitnika
                    mysqli_stmt_bind_param($stmtPacijent,"sssisis",$ime,$prezime,$spol,$starost,$trenutniDatum,$mbo,$email);
                    //Izvršavam statement
                    mysqli_stmt_execute($stmtPacijent);
                    
                    //Automatsko slučajno generiranje osiguranja za pacijenta
                    //Kreiram asocijativno polje
                    $osiguranjePolje = array("tipPolice"=>array("obavezno","dopunsko","dodatno"),"pravo"=>array("active","suspended"),"periodPoliceOd"=>array("2020-03-28 04:05:34pm"),"periodPoliceDo"=>array("2020-04-10 04:05:34pm"));
                    $brojacTip = 0;
                    $brojacPravo = 0;
                    //Uzimam random po jednu vrijednost za svaki ključ (tip,pravo,peroidOd,periodDo)
                    foreach($osiguranjePolje as $poljeKljuc=>$vrijednostKljuc){
                        foreach($vrijednostKljuc as $element){
                            $brojacTip++;
                            if($brojacTip == 2){
                                break;
                            }
                            $brojacPravo++;
                            if($brojacPravo == 3){
                                break;
                            }
                            $k = array_rand($vrijednostKljuc,1);
                            $polje[$poljeKljuc] = $vrijednostKljuc[$k];
                        }
                    }
                    //Elemente polja stavljam u zasebne varijable zbog prepared statement-a
                    $tipPolice = $polje['tipPolice'];
                    $pravo = $polje['pravo'];
                    $periodPoliceOd = $polje['periodPoliceOd'];
                    $periodPoliceDo = $polje['periodPoliceDo'];

                    //Ubacivam podatke u tablicu "osiguranje"
                    $sqlOsiguranje = "INSERT INTO osiguranje (mboPacijent,tipPolice,pravo,periodPoliceOd,periodPoliceDo) VALUES (?,?,?,?,?)";
                    //Kreiram prepared statment
                    $stmtOsiguranje = mysqli_stmt_init($conn);
                    
                    //Ako je prepared statment neuspješno izvršen
                    if(!mysqli_stmt_prepare($stmtOsiguranje,$sqlOsiguranje)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement osiguranja ne valja!";    
                    }
                    //Ako je sve u redu
                    else{
                        //Ako je prepared statment uspješno izvršen
                        //Uzima sve parametre i stavlja ih umjesto upitnika
                        mysqli_stmt_bind_param($stmtOsiguranje,"issss",$mbo,$tipPolice,$pravo,$periodPoliceOd,$periodPoliceDo);
                        //Izvršavam statement
                        mysqli_stmt_execute($stmtOsiguranje);
                        //Zatvaram statement
                        mysqli_stmt_close($stmtOsiguranje);

                        //Vraćam uspješni response
                        $response["success"] = "true";
                        $response["message"] = "Dodavanje pacijenta uspješno!";
                    }
                }
            }
        }
        return $response;
    }

    //Funkcija koja dohvaća pacijente
    function dohvatiPacijente($mbo,$ime,$prezime){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        
        //Ako polja imena i prezimena nisu prazna, pretraživa se po imenu i prezimenu
        if($ime != null && $prezime != null && $mbo == null){
            //Kreiram sql upit koji će provjeriti postoji li pacijent u bazi podataka kojega je liječnik tražio
            $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM pacijent p 
                                WHERE p.imePacijent LIKE '%{$ime}%' 
                                AND p.prezPacijent LIKE '%{$prezime}%'";
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
            //Ako nema pronađenih pacijenata za navedenu pretragu
            if($brojPacijenata == 0){
                $response["success"] = "false";
                $response["message"] = "Nema pronađenih pacijenata za navedeno ime i prezime!";
                $response["test"] = "Kada ime nije prazno i prezime nije prazno";
            }
            //Ako ima pronađenih pacijenata za navedenu pretragu
            else{
                //Kreiram upit koji dohvaća osobne podatke liječnika
                $sql = "SELECT * FROM pacijent p 
                        WHERE p.imePacijent LIKE '%{$ime}%' 
                        AND p.prezPacijent LIKE '%{$prezime}%'";
                
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }
            }    
        //Ako $mbo nije prazan, pretraživa se po MBO
        }else if($mbo != null && $ime == null && $prezime == null){
            //Kreiram sql upit koji će provjeriti postoji li pacijent u bazi podataka kojega je liječnik tražio
            $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM pacijent p 
                                WHERE p.mboPacijent = '$mbo'";
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
            //Ako nema pronađenih pacijenata za navedenu pretragu
            if($brojPacijenata == 0){
                $response["success"] = "false";
                $response["message"] = "Nema pronađenih pacijenata za navedeni MBO!";
                $response["test"] = "tu sam";
            }
            //Ako ima pronađenih pacijenata za navedenu pretragu
            else{
                //Kreiram upit koji dohvaća osobne podatke liječnika
                $sql = "SELECT * FROM pacijent p 
                        WHERE p.mboPacijent = '$mbo'";
                
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

    //Funkcija koja provjerava je li MBO ispravno unesen
    function provjeriMBO($mbo){
        $response = [];
        //Regularni izraz koji provjerava je li mbo ispravan
        $regex = "/^\d{9}$/";
        //Ako je mbo ispravan, vrati null
        if(preg_match($regex, $mbo)){
            return null;
        }
        //Ako mbo nije ispravan, vrati neuspješnu poruku
        else{
            $response["success"] = "false";
            $response["message"] = "Neispravan format MBO-a!";
            return $response;
        }
    }

    //Funkcija koja ažurira pacijenta na osnovu njegovog ID-a
    function azurirajPacijenta($id,$ime,$prezime,$email,$spol,$starost){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
        
        //Provjeravam je li ažurirani pacijent već postoji u bazi podataka, ali da se ne gleda ovaj pacijent koji se ažurira
        $sql = "SELECT * FROM pacijent p 
                WHERE p.imePacijent = ? 
                AND p.prezPacijent = ? AND p.emailPacijent = ? 
                AND p.spolPacijent = ? AND p.starostPacijent = ? 
                AND p.idPacijent != ?";
        //Kreiram prepared statement
        $stmt = mysqli_stmt_init($conn);
        //Ako je prepared statment neuspješno izvršen
        if(!mysqli_stmt_prepare($stmt,$sql)){
            $response["success"] = "false";
            $response["message"] = "Prepared statement ne valja!";
        }
        //Ako je prepared statment uspješno izvršen
        else{
            //Uzima sve parametre što je liječnik ažurirao i stavlja ih umjesto upitnika u upitu
            mysqli_stmt_bind_param($stmt,"ssssii",$ime,$prezime,$email,$spol,$starost,$id);
            //Izvršavam statement
            mysqli_stmt_execute($stmt);
            //Rezultat koji smo dobili iz baze podataka pohranjuje u varijablu $stmt
            mysqli_stmt_store_result($stmt);
            //Vraća broj redaka što je baza podataka vratila
            $resultCheck = mysqli_stmt_num_rows($stmt);
            //Ako korisnik već postoji u bazi podataka
            if($resultCheck > 0){
                $response["success"] = "false";
                $response["message"] = "Pacijent već postoji u bazi podataka!";
            }
            //Ako je sve u redu do sada
            else{
                //Kreiram upit za bazu podataka koji će ažurirati vrijednosti pacijenta iz baze na nove vrijednosti koje je liječnik ažurirao
                $sqlPacijent = "UPDATE pacijent p SET p.imePacijent = ?,p.prezPacijent = ?, p.emailPacijent = ?,p.spolPacijent = ?,p.starostPacijent = ? 
                                WHERE p.idPacijent = ?";
                //Kreiram prepared statement
                $stmtPacijent = mysqli_stmt_init($conn);
                //Ako je prepared statment neuspješno izvršen
                if(!mysqli_stmt_prepare($stmtPacijent,$sqlPacijent)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement ne valja!";    
                }
                //Ako je prepared statement uspješno izvršen
                else{
                    //Uzima sve parametre što je liječnik ažurirao i stavlja ih umjesto upitnika u upitu
                    mysqli_stmt_bind_param($stmtPacijent,"ssssii",$ime,$prezime,$email,$spol,$starost,$id);
                    //Izvršavam statement
                    mysqli_stmt_execute($stmtPacijent);
                    
                    
                    //Kreiram upit za spremanje pacijentovih podataka u tablicu "pacijent_dodatno" :
                    $sqlPacijentDodatno = "INSERT INTO pacijent_dodatno (idPacijent,datAzurPacijent) VALUES (?,?)";
                    //Kreiram prepared statment
                    $stmtPacijentDodatno = mysqli_stmt_init($conn);
                    //Ako je prepared statment neuspješno izvršen
                    if(!mysqli_stmt_prepare($stmtPacijentDodatno,$sqlPacijentDodatno)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement ne valja!";     
                    }
                    //Ako je prepared statment uspješno izvršen
                    else{
                        $trenutniDatum = date("Y-m-d h:i:sa");
                        //Uzima sve parametre što je liječnik unio i stavlja ih umjesto upitnika
                        mysqli_stmt_bind_param($stmtPacijentDodatno,"is",$id,$trenutniDatum);
                        //Izvršavam statement
                        mysqli_stmt_execute($stmtPacijentDodatno);

                        //Vraćam uspješnu poruku liječniku
                        $response["success"] = "true";
                        $response["message"] = "Pacijent je uspješno ažuriran!";
                    }
                }
            }
        }
        return $response;
    }

    //Funkcija koja dohvaća podatke pacijenta za navedeni ID pacijenta
    function dohvatiPodatkePacijenta($id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        //Kreiram upit koji dohvaća podatke pacijenta za navedeni ID
        $sql = "SELECT * FROM pacijent p 
                WHERE p.idPacijent = '$id'";
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response;   
    }

    //Funkcija koja briše pacijenta koji ima navedeni ID
    function obrisiPacijenta($id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
        
        //Brišem podatke pacijenta iz tablice "osiguranje"
        $sqlOsiguranje = "DELETE FROM osiguranje 
                        WHERE mboPacijent IN 
                        (SELECT mboPacijent FROM pacijent 
                        WHERE idPacijent = ?)";
        //Kreiram prepared statement
        $stmtOsiguranje = mysqli_stmt_init($conn);
        //Ako je prepared statment neuspješno izvršen
        if(!mysqli_stmt_prepare($stmtOsiguranje,$sqlOsiguranje)){
            $response["success"] = "false";
            $response["message"] = "Prepared statement ne valja!";
        }
        //Ako je prepared statement uspješno izvršen
        else{
            //Uzima sve parametre i stavlja ih umjesto upitnika u upitu
            mysqli_stmt_bind_param($stmtOsiguranje,"i",$id);
            //Izvršavam statement
            mysqli_stmt_execute($stmtOsiguranje);
                
            //Kreiram sql koji će izbrisati pacijenta iz tablice "pacijent"
            $sqlPacijent = "DELETE FROM pacijent WHERE idPacijent = ?";
            //Kreiram prepared statement
            $stmtPacijent = mysqli_stmt_init($conn);
            //Ako je prepared statment neuspješno izvršen
            if(!mysqli_stmt_prepare($stmtPacijent,$sqlPacijent)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement ne valja!";    
            }
            //Ako je prepared statement uspješno izvršen
            else{
                //Uzima sve parametre i stavlja ih umjesto upitnika u upitu
                mysqli_stmt_bind_param($stmtPacijent,"i",$id);
                //Izvršavam statement
                mysqli_stmt_execute($stmtPacijent);

                //Kreiram sql koji će izbrisati logove pacijenta kojega želimo izbrisati iz tablice "pacijent_dodatno" 
                $sqlPacijentDodatno = "DELETE FROM pacijent_dodatno WHERE idPacijent = ?";
                //Kreiram prepared statement
                $stmtPacijentDodatno = mysqli_stmt_init($conn);
                //Ako je prepared statment neuspješno izvršen
                if(!mysqli_stmt_prepare($stmtPacijentDodatno,$sqlPacijentDodatno)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement ne valja!";   
                }
                //Ako je prepared statement uspješno izvršen
                else{
                    //Uzima sve parametre i stavlja ih umjesto upitnika u upitu
                    mysqli_stmt_bind_param($stmtPacijentDodatno,"i",$id);
                    //Izvršavam statement
                    mysqli_stmt_execute($stmtPacijentDodatno);

                    //Vraćam uspješnu poruku frontendu
                    $response["success"] = "true";
                    $response["message"] = "Pacijent je uspješno obrisan!";
                }
            }
        }
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
            $response["test"] = "tu sam";
        }
        //Ako ima pronađenih pacijenata
        else{
            //Kreiram upit koji dohvaća podatke pacijenta za navedeni ID
            $sql = "SELECT * FROM pacijent 
                    ORDER BY pacijent.idPacijent";
            
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
    //Funkcija koja dohvaća trenutni broj registriranih pacijenata
    function dohvatiBrojPacijenata(){
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
        //Vrati broj pacijenata
        return $brojPacijenata;   
    }
}
?>