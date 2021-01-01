<?php
//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

class LogoutService{

    //Funkcija koja kao argumente ima tip korisnika i njegov token
    function logout($tip,$token){
        //Generiram trenutno vrijeme
        $trenutniDatum = date("Y-m-d h:i:sa");
        //Pokreće se nova sesija
        session_start();

        //Kreiram prazno polje
        $response = [];
        
        //Importam bazu
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Ako je tip korisnika "Liječnik":
        if($tip == "lijecnik"){

            //Ubacivam datum odjave liječnika u tablicu "session_lijecnik":
            //Kreiram upit za ubacivanje podataka u tablicu "session_lijecnik" :
            $sql = "UPDATE session_lijecnik sl SET sl.datOdjLijecnik = ? 
                    WHERE sl.tokenLijecnik = ?";
            //Kreiram prepared statment
            $stmt = mysqli_stmt_init($conn);
            //Ako je prepared statment neuspješno izvršen
            if(!mysqli_stmt_prepare($stmt,$sql)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement ne valja!";
            }
            else{
                
                //Ako je prepared statment uspješno izvršen
                //Uzima sve parametre i stavlja ih umjesto upitnika
                mysqli_stmt_bind_param($stmt,"ss",$trenutniDatum,$token);
                //Izvršavam statement
                mysqli_stmt_execute($stmt);
                //$_SESSION polje se prazni 
                session_unset();
                //Uništava se trenutna sesija
                session_destroy();

                //Vraćam pozitivan odgovor
                $response["success"] = "true";
                $response["message"] = "Liječnik je uspješno odjavljen!";
            }
        }
        else if($tip == "sestra"){
            //Ubacivam datum odjave med. sestre u tablicu "session_med_ses":
            //Kreiram upit za ubacivanje podataka u tablicu "session_med_ses" :
            $sql = "UPDATE session_med_sestra sms SET sms.datOdjMedSestra = ? 
                    WHERE sms.tokenMedSestra = ?";
            //Kreiram prepared statment
            $stmt = mysqli_stmt_init($conn);
            //Ako je prepared statment neuspješno izvršen
            if(!mysqli_stmt_prepare($stmt,$sql)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement ne valja!";
            }
            else{
                
                //Ako je prepared statment uspješno izvršen
                //Uzima sve parametre i stavlja ih umjesto upitnika
                mysqli_stmt_bind_param($stmt,"ss",$trenutniDatum,$token);
                //Izvršavam statement
                mysqli_stmt_execute($stmt);
                //$_SESSION polje se prazni 
                session_unset();
                //Uništava se trenutna sesija
                session_destroy();

                //Vraćam pozitivan odgovor
                $response["success"] = "true";
                $response["message"] = "Medicinska sestra je uspješno odjavljena!";
            }
        }
        //Vraćam polje odgovora
        return $response;
    }
}

?>