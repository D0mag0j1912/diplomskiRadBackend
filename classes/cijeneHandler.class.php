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
            //Kada ID uputnice ili ID recepta u vanjskoj petlji nije NULL, a sigurno će barem jedno biti !== NULL, ulazim u unutarnju petlju
            //U unutarnjoj petlji tražim sumu naplaćenog iznosa SAMO ZA AKTIVNOG PACIJENTA u toj sesiji obrade
            $sql = "SELECT 
                    CASE
                        WHEN ul.idUputnica IS NOT NULL THEN (SELECT ROUND(SUM(ul2.iznosUsluga),2) FROM usluge_lijecnik ul2 
                                                            WHERE ul2.idObradaLijecnik = '$idObrada' 
                                                            AND ul2.idUputnica = ul.idUputnica 
                                                            AND ul2.idUputnica IN 
                                                            (SELECT pb.idUputnica FROM povijestbolesti pb 
                                                            WHERE pb.mboPacijent IN 
                                                            (SELECT p.mboPacijent FROM pacijent p 
                                                            WHERE p.idPacijent = ol.idPacijent)))
                        WHEN ul.idRecept IS NOT NULL THEN (SELECT ROUND(SUM(ul2.iznosUsluga),2) FROM usluge_lijecnik ul2 
                                                        WHERE ul2.idObradaLijecnik = '$idObrada' 
                                                        AND ul2.idRecept = ul.idRecept 
                                                        AND ul2.idRecept IN 
                                                        (SELECT pb.idRecept FROM povijestbolesti pb 
                                                        WHERE pb.mboPacijent IN 
                                                        (SELECT p.mboPacijent FROM pacijent p 
                                                        WHERE p.idPacijent = ol.idPacijent)))
                    END AS ukupnaCijenaPregled FROM usluge_lijecnik ul 
                    JOIN obrada_lijecnik ol ON ol.idObrada = ul.idObradaLijecnik
                    WHERE ul.idObradaLijecnik = '$idObrada'";
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
            //Ako baza NIJE vratila cijenu, a trebala bi, znači da nema redaka
            else{
                $ukupnaCijena = 0.00;
            }
        }
        else if($tipKorisnik == 'sestra'){
            $sql = "SELECT ROUND(SUM(ums.iznosUsluga),2) AS ukupnaCijenaPregled FROM usluge_med_sestra ums
                    WHERE ums.idObradaMedSestra = '$idObrada'";
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
            //Ako baza NIJE vratila cijenu, a trebala bi, znači da nema redaka
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
            //Prvo zbrajam do sada iznose svih naplaćenih usluga za ovu sesiju obrade
            $sqlPrethodnaSuma = "SELECT SUM(ul.iznosUsluga) AS prethodnaSuma FROM usluge_lijecnik ul 
                                WHERE ul.idObradaLijecnik = '$idObrada'";
            //Rezultat upita spremam u varijablu $resultPrethodnaSuma
            $resultPrethodnaSuma = mysqli_query($conn,$sqlPrethodnaSuma);
            //Ako IMA prethodno naplaćenih pregleda
            if(mysqli_num_rows($resultPrethodnaSuma) > 0){
                //Idem redak po redak rezultata upita 
                while($rowPrethodnaSuma = mysqli_fetch_assoc($resultPrethodnaSuma)){
                    //Vrijednost rezultata spremam u varijablu $prethodnaSuma
                    $prethodnaSuma = $rowPrethodnaSuma['prethodnaSuma'];
                }
                //Kao konačnu cijenu stavljam zbroj prethodne i nove cijene
                $noviIznosPregleda = $prethodnaSuma + $novaCijena;
            }
            //Ako je ovo prvi naplaćeni pregled
            else{
                $noviIznosPregleda = $novaCijena;
            }
            //Ako nova cijena nije null
            if(!empty($novaCijena)){
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
            return $noviIznosPregleda;
        }
        //Ako je tip korisnika "sestra":
        else if($tipKorisnik == 'sestra'){
            //Zbrajam sve prethodne iznose pregleda
            $sqlPrethodnaSuma = "SELECT SUM(ums.iznosUsluga) AS prethodnaSuma FROM usluge_med_sestra ums 
                                WHERE ums.idObradaMedSestra = '$idObrada'";
            //Rezultat upita spremam u varijablu $resultPrethodnaSuma
            $resultPrethodnaSuma = mysqli_query($conn,$sqlPrethodnaSuma);
            //Ako IMA prethodnih naplaćenih pregleda
            if(mysqli_num_rows($resultPrethodnaSuma) > 0){
                //Idem redak po redak rezultata upita 
                while($rowPrethodnaSuma = mysqli_fetch_assoc($resultPrethodnaSuma)){
                    //Vrijednost rezultata spremam u varijablu $prethodnaSuma
                    $prethodnaSuma = $rowPrethodnaSuma['prethodnaSuma'];
                }
                //Kao konačnu cijenu stavljam zbroj prethodne i nove cijene
                $noviIznosPregleda = $prethodnaSuma + $novaCijena;
            } 
            //Ako je ovo prvi naplaćeni pregled
            else{
                //Kao konačnu cijenu stavljam prvu dodanu cijenu
                $noviIznosPregleda = $novaCijena;
            }
            //Ako nova cijena nije null
            if(!empty($novaCijena)){
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
            return $noviIznosPregleda;
        }
    }

}
?>