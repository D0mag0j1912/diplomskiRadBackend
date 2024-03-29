<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

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
        $sql = "SELECT l.idLijecnik, l.imeLijecnik, l.prezLijecnik, 
                l.adrLijecnik, DATE_FORMAT(l.datKreirLijecnik,'%d.%m.%Y') AS datKreirLijecnik, 
                zr.tipSpecijalist, k.tip, k.email FROM lijecnik l 
                JOIN korisnik k ON l.idKorisnik = k.idKorisnik 
                JOIN zdr_radnici zr ON zr.sifraSpecijalist = l.sifraSpecijalist";
        
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

        //Kreiram upit za bazu podataka koji će ažurirati vrijednosti liječnika iz baze na nove vrijednosti koje je liječnik ažurirao za svoj profil za tablicu "LIJECNIK"
        $sqlLijecnik = "UPDATE lijecnik l SET l.imeLijecnik = ?,l.prezLijecnik = ?, 
                        l.adrLijecnik = ?, l.sifraSpecijalist = ? 
                        WHERE l.idLijecnik = ?";
        //Kreiram prepared statement
        $stmtLijecnik = mysqli_stmt_init($conn);
        //Ako je prepared statment neuspješno izvršen
        if(!mysqli_stmt_prepare($stmtLijecnik,$sqlLijecnik)){
            $response["success"] = "false";
            $response["message"] = "Došlo je do pogreške!";
        }
        //Ako je prepared statement uspješno izvršen
        else{
            //Uzima sve parametre što je liječnik ažurirao i stavlja ih umjesto upitnika u upitu
            mysqli_stmt_bind_param($stmtLijecnik,"sssii",$ime,$prezime,$adresa,$sifraSpecijalist,$id);
            //Izvršavam statement
            mysqli_stmt_execute($stmtLijecnik);

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
                $response["message"] = "Došlo je do pogreške!";
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
    //Funkcija koja ažurira liječnikovu lozinku
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
            $response["message"] = "Lozinka liječnika je uspješno ažurirana!";
        }
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