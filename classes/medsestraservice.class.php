<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';
//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class MedSestraService{
    //Funkcija koja dohvaća ID medicinske sestre
    function getIDMedSestra(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
        
        //Kreiram sql upit koji će provjeriti koliko ima medicinskih sestara u bazi podataka
        $sqlCountMedSestra = "SELECT COUNT(*) AS BrojMedSestra FROM med_sestra";
        //Rezultat upita spremam u varijablu $resultCountMedSestra
        $resultCountMedSestra = mysqli_query($conn,$sqlCountMedSestra);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCountMedSestra) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCountMedSestra = mysqli_fetch_assoc($resultCountMedSestra)){
                //Vrijednost rezultata spremam u varijablu $brojMedSestra
                $brojMedSestra = $rowCountMedSestra['BrojMedSestra'];
            }
        } 
        //Ako je broj medicinskih sestara 0
        if($brojMedSestra == 0){
            $response["success"] = "false";
            $response["message"] = "Nema evidentiranih medicinskih sestara!";
        }
        //Ako ima medicinskih sestara
        else{
            //Kreiram upit koji dohvaća ID medicinske sestre
            $sql = "SELECT idMedSestra FROM med_sestra";
            
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

    //Funkcija koja dohvaća osobne podatke medicinske sestre
    function dohvatiOsobnePodatke(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        //Kreiram upit koji dohvaća osobne podatke medicinske sestre
        $sql = "SELECT m.idMedSestra, m.imeMedSestra, m.prezMedSestra, 
                m.adrMedSestra, DATE_FORMAT(m.datKreirMedSestra,'%d.%m.%Y') AS datKreirMedSestra, 
                zr.tipSpecijalist, k.tip, k.email FROM med_sestra m 
                JOIN korisnik k ON m.idKorisnik = k.idKorisnik 
                JOIN zdr_radnici zr ON zr.sifraSpecijalist = m.sifraSpecijalist";
        
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

        //Dohvaćam šifru specijalista 
        $sqlSpecijalist = "SELECT zr.sifraSpecijalist FROM zdr_radnici zr 
                        WHERE zr.tipSpecijalist = '$specijalizacija'";
        $resultSpecijalist = $conn->query($sqlSpecijalist);

        if ($resultSpecijalist->num_rows > 0) {
            while($rowSpecijalist = $resultSpecijalist->fetch_assoc()) {
                $sifraSpecijalist = $rowSpecijalist['sifraSpecijalist'];
            }
        }

        //Kreiram upit za bazu podataka koji će ažurirati vrijednosti medicinske sestre iz baze na nove vrijednosti koje je medicinska sestra ažurirala za svoj profil za tablicu "MED_SESTRA"
        $sqlMedSestra = "UPDATE med_sestra m SET m.imeMedSestra = ?,m.prezMedSestra = ?, 
                        m.adrMedSestra = ?, m.sifraSpecijalist = ?  
                        WHERE m.idMedSestra = ?";
        //Kreiram prepared statement
        $stmtMedSestra = mysqli_stmt_init($conn);
        //Ako je prepared statment neuspješno izvršen
        if(!mysqli_stmt_prepare($stmtMedSestra,$sqlMedSestra)){
            $response["success"] = "false";
            $response["message"] = "Došlo je do pogreške!";
        }
        //Ako je prepared statement uspješno izvršen
        else{
            //Uzima sve parametre što je medicinska sestra ažurirala i stavlja ih umjesto upitnika u upitu
            mysqli_stmt_bind_param($stmtMedSestra,"sssii",$ime,$prezime,$adresa,$sifraSpecijalist,$id);
            //Izvršavam statement
            mysqli_stmt_execute($stmtMedSestra);

            //Izvršavam upit koji dohvaća ID korisnika koji odgovara unesenom email-u
            $resultKorisnik = mysqli_query($conn,"SELECT m.idKorisnik FROM med_sestra m WHERE m.idMedSestra = '" . mysqli_real_escape_string($conn, $id) . "'"); 
            while($rowKorisnik = mysqli_fetch_array($resultKorisnik))
            {
                $idKorisnik = $rowKorisnik['idKorisnik'];
            }

            //Kreiram upit za bazu podataka koji će ažurirati vrijednosti medicinske sestre iz baze na nove vrijednosti koje je medicinska sestra ažurirala za svoj profil za tablicu "MED_SESTRA"
            $sqlKorisnik = "UPDATE korisnik k SET k.email = ? WHERE k.idKorisnik = ?";
            //Kreiram prepared statement
            $stmtKorisnik = mysqli_stmt_init($conn);
            //Ako je prepared statment neuspješno izvršen
            if(!mysqli_stmt_prepare($stmtKorisnik,$sqlKorisnik)){
                $response["success"] = "false";
                $response["message"] = "Došlo je do pogreške!";
            }
            //Ako je prepared statement uspješno izvršen
            else{
                //Uzima sve parametre što je medicinska sestra ažurirala i stavlja ih umjesto upitnika u upitu
                mysqli_stmt_bind_param($stmtKorisnik,"si",$email,$idKorisnik);
                //Izvršavam statement
                mysqli_stmt_execute($stmtKorisnik);

                $response["success"] = "true";
                $response["message"] = "Ažuriranje osobnih podataka uspješno!";
            }
        }
        return $response;
    }

    //Funkcija koja provjerava je li unesena ispravna trenutna lozinka za registriranu medicinsku sestru
    function provjeraLozinka($id,$trenutna){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        //Moram napravit provjeru je li unesena trenutna lozinka odgovara liječniku
        $sql = "SELECT k.pass FROM korisnik k 
                JOIN med_sestra m ON k.idKorisnik = m.idKorisnik 
                WHERE m.idMedSestra = ?";
        //Kreiranje prepared statementa
        $stmt = mysqli_stmt_init($conn);
        if(!mysqli_stmt_prepare($stmt,$sql)){
            $response["success"] = "false";
            $response["message"] = "Došlo je do pogreške!";
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
                //Provjera passworda korisnika (uzima password koji je korisnik upisao i password koji odgovara ID-u medicinske sestre u bazi podataka
                //i provjerava je li se ti passwordi poklapaju) -> vraća true ili false
                $passwordCheck = password_verify($trenutna,$row['pass']);
                //Ako password koji je korisnik upisao NE ODGOVARA passwordu iz baze podataka za taj ID medicinske sestre
                if($passwordCheck == false){
                    $response["success"] = "false";
                    $response["message"] = "Upisana trenutna lozinka ne odgovara lozinki trenutno registrirane medicinske sestre!";
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

        //Kreiram upit koji će ažurirati lozinku medicinske sestre u tablici "korisnik"
        $sql = "UPDATE korisnik k SET k.pass = ? 
                WHERE k.idKorisnik IN 
                (SELECT m.idKorisnik FROM med_sestra m 
                WHERE m.idMedSestra = ?)";
        //Kreiram prepared statment
        $stmt= mysqli_stmt_init($conn);
        //Ako je prepared statment neuspješno izvršen
        if(!mysqli_stmt_prepare($stmt,$sql)){
            $response["success"] = "false";
            $response["message"] = "Došlo je do pogreške!";
        }
        //Ako je prepared statment uspješno izvršen
        else{
            //Hashiram password 
            $passwordHash = password_hash($nova,PASSWORD_DEFAULT);
            //Uzima password što je korisnik unio i stavlja ga umjesto upitnika
            mysqli_stmt_bind_param($stmt,"si",$passwordHash,$id);
            //Izvršavam statement
            mysqli_stmt_execute($stmt);

            //Vraćam uspješni odgovor
            $response["success"] = "true";
            $response["message"] = "Lozinka medicinske sestre je uspješno ažurirana!";
        }
        return $response;
    
    }

    //Funkcija koja dohvaća pacijente na osnovu pretrage u dijelu obrade
    function dohvatiPacijente($ime,$prezime,$trenutnaStranica){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Ako su uneseni ime ILI prezime (znači samo jedan unos)
        if(empty($ime) || empty($prezime)){
            //Kreiram sql upit koji će provjeriti postoji li pacijent u bazi podataka kojega je medicinska sestra tražila
            $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM pacijent p 
                                WHERE p.imePacijent = '$ime' 
                                OR p.prezPacijent = '$prezime'";
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
                $response["message"] = "Nema pronađenih pacijenata za navedeno ime ili prezime!";
            }
            //Ako ima pronađenih pacijenata za navedenu pretragu
            else{
                //Definiram koliko pacijenata ima po svakoj stranici tablice
                $brojPacijenataStranica = 5;
                //Definiram početni broj LIMIT-a (s koje n-torke se počinje)
                $pocetak = ($trenutnaStranica-1)*$brojPacijenataStranica;
                //Kreiram upit koji dohvaća osobne podatke pacijenta
                $sql = "SELECT p.idPacijent,p.imePacijent, 
                        p.prezPacijent,DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS Datum,
                        p.adresaPacijent FROM pacijent p 
                        WHERE p.imePacijent = '$ime' 
                        OR p.prezPacijent = '$prezime' 
                        LIMIT $pocetak, $brojPacijenataStranica";

                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }
            }
        }
        else if(!empty($ime) && !empty($prezime)){
            //Kreiram sql upit koji će provjeriti postoji li pacijent u bazi podataka kojega je medicinska sestra tražila
            $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM pacijent p 
                                WHERE p.imePacijent = '$ime' 
                                AND p.prezPacijent = '$prezime'";
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
            }
            //Ako ima pronađenih pacijenata za navedenu pretragu
            else{
                //Definiram koliko pacijenata ima po svakoj stranici tablice
                $brojPacijenataStranica = 5;
                //Definiram početni broj LIMIT-a (s koje n-torke se počinje)
                $pocetak = ($trenutnaStranica-1)*$brojPacijenataStranica;
                //Kreiram upit koji dohvaća osobne podatke pacijenta
                $sql = "SELECT p.idPacijent,p.imePacijent, 
                        p.prezPacijent,DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS Datum,
                        p.adresaPacijent FROM pacijent p 
                        WHERE p.imePacijent = '$ime' 
                        AND p.prezPacijent = '$prezime' 
                        LIMIT $pocetak, $brojPacijenataStranica";

                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }
            } 
        }
        //Vraćam pacijente
        return $response;
    }

    //Funkcija koja vraća potrebne podatke koji se tiču pacijenata
    function pacijentiDodatno($ime,$prezime){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Kreiram sql upit koji će provjeriti postoji li pacijent u bazi podataka kojega je medicinska sestra tražila
        $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM pacijent p 
                            WHERE p.imePacijent = '$ime' 
                            OR p.prezPacijent = '$prezime'";
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
        
        //Definiram koliko pacijenata ima po svakoj stranici tablice
        $brojPacijenataStranica = 5;
        //Definiram broj početne stranice
        $stranica = 1;
        //Definiram početni broj LIMIT-a (s koje n-torke se počinje)
        $pocetak = ($stranica-1)*$brojPacijenataStranica;
        //Definiram koliko će biti broj stranica 
        $brojStranica = ceil($brojPacijenata/$brojPacijenataStranica);

        //U polje ubacivam potrebne podatke
        $response["brojPacijenata"] = $brojPacijenata;
        $response["brojStranica"] = $brojStranica;
        $response["stranica"] = $stranica;
        
        return $response;
    }
     
}
?>