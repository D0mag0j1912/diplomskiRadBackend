<?php

//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');
class PosjetService{

    //Funkcija koja dodava novi posjet
    function dodajPosjet($datumPosjet,$dijagnoza,$razlog,$anamneza,$status,$preporuka){

        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        //Kreiram sql upit koji provjerava je li taj posjet postoji već u bazi podataka
        $sql = "SELECT * FROM posjet p
                JOIN dijagnoza d ON p.idPosjet = d.idPosjet 
                WHERE p.datumPosjet = ? AND p.razlogDolaskaPosjet = ? 
                AND p.anamnezaPosjet = ? AND p.statusPosjet = ? 
                AND p.preporukaLijecnikPosjet = ? AND d.nazivDijagnoza = ?";
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
            mysqli_stmt_bind_param($stmt,"ssssss",$datumPosjet,$razlog,$anamneza,$status,$preporuka,$dijagnoza);
            //Izvršavam statement
            mysqli_stmt_execute($stmt);
            //Rezultat koji smo dobili iz baze podataka pohranjuje u varijablu $stmt
            mysqli_stmt_store_result($stmt);
            //Vraća broj redaka što je baza podataka vratila
            $resultCheck = mysqli_stmt_num_rows($stmt);
            //Ako pacijent već postoji u bazi podataka
            if($resultCheck > 0){
                $response["success"] = "false";
                $response["message"] = "Posjet već postoji u bazi podataka!";
            }
            //Ako je sve u redu do sada
            else{
                //Kreiram upit za spremanje pacijentovih podataka u tablicu "posjet" :
                $sqlPosjet= "INSERT INTO posjet (datumPosjet,anamnezaPosjet,statusPosjet,preporukaLijecnikPosjet,razlogDolaskaPosjet)
                                VALUES(?,?,?,?,?)";
                //Kreiram prepared statment
                $stmtPosjet = mysqli_stmt_init($conn);
                //Ako je prepared statment neuspješno izvršen
                if(!mysqli_stmt_prepare($stmtPosjet,$sqlPosjet)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement ne valja!";    
                }
                //Ako je prepared statment uspješno izvršen
                else{
                    //Uzima sve parametre što je liječnik unio i stavlja ih umjesto upitnika
                    mysqli_stmt_bind_param($stmtPosjet,"sssss",$datumPosjet,$anamneza,$status,$preporuka,$razlog);
                    //Izvršavam statement
                    mysqli_stmt_execute($stmtPosjet);

                    //Izvršavam upit koji dohvaća ID posjeta
                    $resultPosjet= mysqli_query($conn,"SELECT p.idPosjet FROM posjet p WHERE p.datumPosjet = '" . mysqli_real_escape_string($conn, $datumPosjet) . "' 
                                                    AND p.anamnezaPosjet = '" . mysqli_real_escape_string($conn, $anamneza) . "' 
                                                    AND p.statusPosjet = '" . mysqli_real_escape_string($conn, $status) . "' 
                                                    AND p.preporukaLijecnikPosjet = '" . mysqli_real_escape_string($conn, $preporuka) . "' 
                                                    AND p.razlogDolaskaPosjet = '" . mysqli_real_escape_string($conn, $razlog) . "'"); 
                    while($rowPosjet = mysqli_fetch_array($resultPosjet))
                    {
                        $idPosjet = $rowPosjet['idPosjet'];
                    }

                    //Ubacivam podatke u tablicu "dijagnoza"
                    $sqlDijagnoza = "INSERT INTO dijagnoza (idPosjet,nazivDijagnoza) VALUES (?,?)";
                    //Kreiram prepared statment
                    $stmtDijagnoza = mysqli_stmt_init($conn);
                    
                    //Ako je prepared statment neuspješno izvršen
                    if(!mysqli_stmt_prepare($stmtDijagnoza,$sqlDijagnoza)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement osiguranja ne valja!";    
                    }
                    //Ako je sve u redu
                    else{
                        //Ako je prepared statment uspješno izvršen
                        //Uzima sve parametre i stavlja ih umjesto upitnika
                        mysqli_stmt_bind_param($stmtDijagnoza,"is",$idPosjet,$dijagnoza);
                        //Izvršavam statement
                        mysqli_stmt_execute($stmtDijagnoza);
                        //Zatvaram statement
                        mysqli_stmt_close($stmtDijagnoza);

                        //Vraćam uspješni response
                        $response["success"] = "true";
                        $response["message"] = "Dodavanje posjeta uspješno!";
                    }
                }
            }
        }
        return $response;
    }
}
?>