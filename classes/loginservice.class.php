<?php
/********************************* 
OVDJE SE NALAZE SVE BACKEND METODE ZA PRIJAVU
*/
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');
class LoginService{

    //Funkcija koja dohvaća lozinku za upisani email logina
    function dohvatiLozinku($email,$lozinka){
        //Kreiram objekt tipa "Baza"
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];
        $sql = "SELECT k.pass FROM korisnik k 
            WHERE k.email = ?";
        //Kreiranje prepared statementa
        $stmt = mysqli_stmt_init($conn);
        if(!mysqli_stmt_prepare($stmt,$sql)){
            $response["success"] = "false";
        }
        //Ako je prepared statement u redu
        else{
            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
            mysqli_stmt_bind_param($stmt,"s",$email);
            //Izvršavanje statementa
            mysqli_stmt_execute($stmt);
            //Vraćam rezultat statementa u varijablu $result
            $result = mysqli_stmt_get_result($stmt);
            
            //Ako smo dobili nešto u $result, tj. ako je baza podataka našla nešto
            if($row = mysqli_fetch_assoc($result)){
                //Provjera passworda korisnika (uzima password koji je korisnik upisao i password koji odgovara upisanom username-u u bazi podataka
                //i provjerava je li se ti passwordi poklapaju) -> vraća true ili false
                $passwordCheck = password_verify($lozinka,$row['pass']);
                //Ako password koji je korisnik upisao NE ODGOVARA passwordu iz baze podataka za taj username
                if($passwordCheck == false){
                    $response["success"] = "false";   
                }
                //Ako password koji je korisnik upisao ODGOVARA passwordu iz baze podataka za taj username
                else if($passwordCheck == true){
                    $response["success"] = "true";
                }
            }
            else{
                $response["success"] = "false";
            }
        }
        return $response;
    }

    //Funkcija koja dohvaća sve emailove u svrhu validacije
    function dohvatiSveEmailove(){
        //Kreiram prazno polje
        $response = [];
        //Kreiram objekt tipa "Baza"
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        $sql = "SELECT k.email FROM korisnik k";
        $result = $conn->query($sql);

        //Ako ima pronađenih sekundarnih dijagnoza za ovu primarnu dijagnozu
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row['email'];
            }
        }
        return $response;
    }

    //Funkcija koja prijavljuje korisnika
    function prijavaKorisnik($email,$lozinka){
        //Kreiram prazno polje
        $response = [];
        //Kreiram objekt tipa "Baza"
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Trenutno vrijeme
        $vrijeme = date('H:i:s');
        //Kreiram upit koji provjerava postoji li uneseni email u bazi
        $sql = "SELECT * FROM korisnik k 
                WHERE k.email = ?";

        //Kreiranje prepared statementa
        $stmt = mysqli_stmt_init($conn);
        if(!mysqli_stmt_prepare($stmt,$sql)){
            $response["success"] = "false";
            $response["message"] = "Došlo je do pogreške!";
            
        }
        //Ako je prepared statement u redu
        else{
            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
            mysqli_stmt_bind_param($stmt,"s",$email);
            //Izvršavanje statementa
            mysqli_stmt_execute($stmt);
            //Vraćam rezultat statementa u varijablu $result
            $result = mysqli_stmt_get_result($stmt);
            
            //Ako smo dobili nešto u $result, tj. ako je baza podataka našla nešto
            if($row = mysqli_fetch_assoc($result)){
                //Provjera passworda korisnika (uzima password koji je korisnik upisao i password koji odgovara upisanom username-u u bazi podataka
                //i provjerava je li se ti passwordi poklapaju) -> vraća true ili false
                $passwordCheck = password_verify($lozinka,$row['pass']);
                //Ako password koji je korisnik upisao NE ODGOVARA passwordu iz baze podataka za taj username
                if($passwordCheck == false){
                    $response["success"] = "false";
                    $response["message"] = "Unijeli ste krivu lozinku";    
                }
                //Ako password koji je korisnik upisao ODGOVARA passwordu iz baze podataka za taj username
                else if($passwordCheck == true){
                    //Ako je tip korisnika liječnik:
                    if($row['tip'] == "lijecnik"){
                        
                        $sqlLijecnik = "SELECT * FROM lijecnik l 
                                        JOIN korisnik k ON l.idKorisnik = k.idKorisnik 
                                        WHERE k.email = '$email'";
                        $resultLijecnik = $conn->query($sqlLijecnik);
                        if ($resultLijecnik->num_rows > 0) {
                            // output data of each row
                            while($rowLijecnik = $resultLijecnik->fetch_assoc()) {
                                //Pokrećem novu sesiju
                                session_start();
                                //Kreiranje globalne varijable $_SESSION u koju se stavljaju svi podatci prijavljenog korisnika (Sve podatke iz baze za korisnika
                                //preslikavam u array $_SESSION)
                                $_SESSION['idLijecnik'] = $rowLijecnik['idLijecnik'];
                                $_SESSION['imeLijecnik'] = $rowLijecnik['imeLijecnik'];
                                $_SESSION['prezLijecnik'] = $rowLijecnik['prezLijecnik'];
                                $_SESSION['idKorisnik'] = $rowLijecnik['idKorisnik'];
                                $_SESSION['email'] = $rowLijecnik['email'];
                                //Kreiram token za liječnika
                                $token = sha1(uniqid($_SESSION['email'], true));
                                $_SESSION['datPrijLijecnik']=date("Y-m-d");
                                $_SESSION['tokenLijecnik'] = $token;
                                //Kreiram upit za ubacivanje podataka u tablicu "session_lijecnik" :
                                $sqlSessionLijecnik = "INSERT INTO session_lijecnik (idLijecnik,datPrijLijecnik,tokenLijecnik,vrijemePrijLijecnik) VALUES (?,?,?,?)";
                                //Kreiram prepared statment
                                $stmtSessionLijecnik = mysqli_stmt_init($conn);
                                //Ako je prepared statment neuspješno izvršen
                                if(!mysqli_stmt_prepare($stmtSessionLijecnik,$sqlSessionLijecnik)){
                                    $response["success"] = "false";
                                    $response["message"] = "Došlo je do pogreške!";
                                }
                                else{
                                    //Ako je prepared statment uspješno izvršen
                                    //Uzima sve parametre što je liječnik unio i stavlja ih umjesto upitnika
                                    mysqli_stmt_bind_param($stmtSessionLijecnik,"isss",$_SESSION['idLijecnik'],$_SESSION['datPrijLijecnik'],$_SESSION['tokenLijecnik'],$vrijeme);
                                    //Izvršavam statement
                                    mysqli_stmt_execute($stmtSessionLijecnik);

                                    //Vrati uspješno polje
                                    $response["success"] = "true";
                                    $response["message"] = "Liječnik je uspješno prijavljen!";
                                    $response["token"] = $token;
                                    $response["tip"] = $rowLijecnik["tip"];
                                    $response["expiresIn"] = 14400;
                                }
                            }
                        }
                    }
                    //Ako je tip korisnika "Medicinska sestra
                    if($row['tip'] == "sestra"){

                        $sqlSestra = "SELECT * FROM med_sestra m 
                                        JOIN korisnik k ON m.idKorisnik = k.idKorisnik 
                                        WHERE k.email = '$email'";
                        $resultSestra = $conn->query($sqlSestra);
                        if ($resultSestra->num_rows > 0) {
                            // output data of each row
                            while($rowSestra = $resultSestra->fetch_assoc()) {
                                //Pokrećem novu sesiju
                                session_start();
                                //Kreiranje globalne varijable $_SESSION u koju se stavljaju svi podatci prijavljenog korisnika (Sve podatke iz baze za korisnika
                                //preslikavam u array $_SESSION)
                                $_SESSION['idMedSestra'] = $rowSestra['idMedSestra'];
                                $_SESSION['imeMedSestra'] = $rowSestra['imeMedSestra'];
                                $_SESSION['prezMedSestra'] = $rowSestra['prezMedSestra'];
                                $_SESSION['idKorisnik'] = $rowSestra['idKorisnik'];
                                $_SESSION['email'] = $rowSestra['email'];
                                //Kreiram token za medicinsku sestru
                                $token = sha1(uniqid($_SESSION['email'], true));
                                $_SESSION['datPrijMedSestra']=date("Y-m-d");
                                $_SESSION['tokenMedSestra'] = $token;
                                //Kreiram upit za ubacivanje podataka u tablicu "session_med_ses" :
                                $sqlSessionMedSestra = "INSERT INTO session_med_sestra (idMedSestra,datPrijMedSestra,tokenMedSestra,vrijemePrijMedSestra) VALUES (?,?,?,?)";
                                //Kreiram prepared statment
                                $stmtSessionMedSestra = mysqli_stmt_init($conn);
                                //Ako je prepared statment neuspješno izvršen
                                if(!mysqli_stmt_prepare($stmtSessionMedSestra,$sqlSessionMedSestra)){
                                    $response["success"] = "false";
                                    $response["message"] = "Došlo je do pogreške!";
                                }
                                else{
                                    //Ako je prepared statment uspješno izvršen
                                    //Uzima sve parametre što je medicinska sestra unijela i stavlja ih umjesto upitnika
                                    mysqli_stmt_bind_param($stmtSessionMedSestra,"isss",$_SESSION['idMedSestra'],$_SESSION['datPrijMedSestra'],$_SESSION['tokenMedSestra'],$vrijeme);
                                    //Izvršavam statement
                                    mysqli_stmt_execute($stmtSessionMedSestra);

                                    $response["success"] = "true";
                                    $response["message"] = "Medicinska sestra je uspješno prijavljena!";
                                    $response["token"] = $token;
                                    $response["tip"] = $rowSestra["tip"];
                                    $response["expiresIn"] = 14400;
                                }
                            }
                        }
                    }
                }
                //Ako se nešto čudno dogodi
                else{
                    $response["success"] = "false";
                    $response["message"] = "Nešto se čudno dogodilo!";
                }
            }
            //Ako je $result prazan, tj. ako nismo ništa dobili iz baze podataka
            else{
                $response["success"] = "false";
                $response["message"] = "Korisnik ne postoji!";   
            }
        }
        //Vraćam response polje
        return $response;
    }
}
?>