<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class CijeneHandler {

    //Funkcija koja dohvaća trenutni iznos cijene pregleda
    function dohvatiTrenutnaCijenaPregleda($idObrada, $tipKorisnik){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        
        //Ako je tip prijavljenog korisnika "lijecnik":
        if($tipKorisnik == 'lijecnik'){
            $sql = "SELECT 
                    CASE 
                        WHEN ol.ukupnaCijenaPregled IS NULL THEN NULL
                        WHEN ol.ukupnaCijenaPregled IS NOT NULL THEN ROUND(ol.ukupnaCijenaPregled,2)
                    END AS ukupnaCijenaPregled FROM obrada_lijecnik ol 
                    WHERE ol.idObrada = '$idObrada'";
            //Rezultat upita spremam u varijablu $result
            $result = mysqli_query($conn,$sql);
            //Ako je baza vratila cijenu  
            if(mysqli_num_rows($result) > 0){
                //Idem redak po redak rezultata upita 
                while($row = mysqli_fetch_assoc($result)){
                    //Vrijednost rezultata spremam u varijablu $ukupnaCijena
                    $ukupnaCijena = $row['ukupnaCijenaPregled'];
                }
            } 
            //Ako baza NIJE vratila cijenu, znači da nema ni ukupne ni doplate
            else{
                $ukupnaCijena = 0.00;
            }
        }
        else if($tipKorisnik == 'sestra'){
            $sql = "SELECT 
                    CASE 
                        WHEN om.ukupnaCijenaPregled IS NULL THEN NULL 
                        WHEN om.ukupnaCijenaPregled IS NOT NULL THEN ROUND(om.ukupnaCijenaPregled,2)
                    END AS ukupnaCijenaPregled FROM obrada_med_sestra om
                    WHERE om.idObrada = '$idObrada'";
            //Rezultat upita spremam u varijablu $result
            $result = mysqli_query($conn,$sql);
            //Ako je baza vratila cijenu  
            if(mysqli_num_rows($result) > 0){
                //Idem redak po redak rezultata upita 
                while($row = mysqli_fetch_assoc($result)){
                    //Vrijednost rezultata spremam u varijablu $ukupnaCijena
                    $ukupnaCijena = $row['ukupnaCijenaPregled'];
                }
            } 
            //Ako baza NIJE vratila cijenu, znači da nema ni ukupne ni doplate
            else{
                $ukupnaCijena = 0.00;
            }
        }
        return $ukupnaCijena;
    }

    //Funkcija koja ažurira ukupnu cijenu pregleda 
    function azurirajUkupnuCijenuPregleda(
        $idObrada, 
        $novaCijena, 
        $tipKorisnik,
        $idRecept,
        $idUputnica,
        $idBMI){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Ako je tip korisnika "lijecnik":
        if($tipKorisnik == 'lijecnik'){
            //Prvo gledam ima li već nekih dodanih cijena za ovu sesiju obrade
            $sqlPrethodnaCijena = "SELECT ol.ukupnaCijenaPregled FROM obrada_lijecnik ol 
                                WHERE ol.idObrada = '$idObrada'";
            //Rezultat upita spremam u varijablu $resultPrethodnaCijena
            $resultPrethodnaCijena = mysqli_query($conn,$sqlPrethodnaCijena);
            //Ako IMA prethodnih dodanih cijena 
            if(mysqli_num_rows($resultPrethodnaCijena) > 0){
                //Idem redak po redak rezultata upita 
                while($rowPrethodnaCijena = mysqli_fetch_assoc($resultPrethodnaCijena)){
                    //Vrijednost rezultata spremam u varijablu $prethodnaCijena
                    $prethodnaCijena = $rowPrethodnaCijena['ukupnaCijenaPregled'];
                }
                //Kao konačnu cijenu stavljam zbroj prethodne i nove cijene
                $konacnaCijenaPregleda = $prethodnaCijena + $novaCijena;
            } 
            //Ako NEMA prethodnih dodanih cijena
            else{
                //Kao konačnu cijenu stavljam prvu dodanu cijenu
                $konacnaCijenaPregleda = $novaCijena;
            }
            //Ako nova cijena nije null
            if(!empty($novaCijena)){
                $sql = "UPDATE obrada_lijecnik ol SET ol.ukupnaCijenaPregled = ? 
                        WHERE ol.idObrada = ?";
                //Kreiranje prepared statementa
                $stmt = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmt,$sql)){
                    return false;
                }
                else{
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmt,"di",$konacnaCijenaPregleda, $idObrada);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmt);

                    //Kreiram upit koji će dodati podatke naplaćene usluge u tablicu "racun"
                    $sqlRacun = "INSERT INTO usluge_lijecnik (idObradaLijecnik,iznosUsluga,idRecept,idUputnica) VALUES (?,?,?,?)";
                    //Kreiranje prepared statementa
                    $stmtRacun = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmtRacun,$sqlRacun)){
                        return false;
                    }
                    else{
                        if(empty($idRecept)){
                            $idRecept = NULL;
                        }
                        if(empty($idUputnica)){
                            $idUputnica = NULL;
                        }
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmtRacun,"idii",$idObrada, $novaCijena, $idRecept, $idUputnica);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmtRacun);
                    } 
                }
            }
            return $konacnaCijenaPregleda;
        }
        //Ako je tip korisnika "sestra":
        else if($tipKorisnik == 'sestra'){
            //Prvo gledam ima li već nekih dodanih cijena za ovu sesiju obrade
            $sqlPrethodnaCijena = "SELECT om.ukupnaCijenaPregled FROM obrada_med_sestra om 
                                WHERE om.idObrada = '$idObrada'";
            //Rezultat upita spremam u varijablu $resultPrethodnaCijena
            $resultPrethodnaCijena = mysqli_query($conn,$sqlPrethodnaCijena);
            //Ako IMA prethodnih dodanih cijena 
            if(mysqli_num_rows($resultPrethodnaCijena) > 0){
                //Idem redak po redak rezultata upita 
                while($rowPrethodnaCijena = mysqli_fetch_assoc($resultPrethodnaCijena)){
                    //Vrijednost rezultata spremam u varijablu $prethodnaCijena
                    $prethodnaCijena = $rowPrethodnaCijena['ukupnaCijenaPregled'];
                }
                //Kao konačnu cijenu stavljam zbroj prethodne i nove cijene
                $konacnaCijenaPregleda = $prethodnaCijena + $novaCijena;
            } 
            //Ako NEMA prethodnih dodanih cijena
            else{
                //Kao konačnu cijenu stavljam prvu dodanu cijenu
                $konacnaCijenaPregleda = $novaCijena;
            }
            //Ako nova cijena nije null
            if(!empty($novaCijena)){
                $sql = "UPDATE obrada_med_sestra om SET om.ukupnaCijenaPregled = ? 
                        WHERE om.idObrada = ?";
                //Kreiranje prepared statementa
                $stmt = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmt,$sql)){
                    return false;
                }
                else{
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmt,"di",$konacnaCijenaPregleda, $idObrada);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmt);

                    //Kreiram upit koji će dodati podatke naplaćene usluge u tablicu "racun"
                    $sqlRacun = "INSERT INTO usluge_med_sestra (idObradaMedSestra,iznosUsluga,idBMI) VALUES (?,?,?)";
                    //Kreiranje prepared statementa
                    $stmtRacun = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmtRacun,$sqlRacun)){
                        return false;
                    }
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmtRacun,"iii",$idObrada, $novaCijena, $idBMI);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmtRacun);
                    } 
                }
            }
            return $konacnaCijenaPregleda;
        }
    }

}
?>