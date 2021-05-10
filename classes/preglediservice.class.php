<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class PreglediService{

    //Funkcija koja dohvaća DATUM najnovijeg pregleda da ga mogu uskladiti filter sa najvišim elementom liste pregleda
    function dohvatiNajnovijiDatum($tipKorisnik,$mboPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Ako je tip korisnika "lijecnik":
        if($tipKorisnik == "lijecnik"){
            //Kreiram upit koji dohvaća najnoviji datum povijesti bolesti
            $sql = "SELECT pb.datum FROM povijest_bolesti pb 
                    WHERE pb.mboPacijent = '$mboPacijent' 
                    AND pb.idPovijestBolesti = 
                    (SELECT MAX(pb2.idPovijestBolesti) FROM povijest_bolesti pb2 
                    WHERE pb2.mboPacijent = '$mboPacijent')"; 
            //Rezultat upita spremam u varijablu $result
            $result = mysqli_query($conn,$sql);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($result) > 0){
                //Idem redak po redak rezultata upita 
                while($row = mysqli_fetch_assoc($result)){
                    //Dohvaćam najnoviji datum
                    $datum = $row['datum'];
                }
            }
            //Ako ovaj pacijent NEMA evidentiranih povijesti bolesti
            else{
                //Vraćam današnji datum
                $datum = date('Y-m-d');
            }
        }  
        //Ako je tip korisnika "sestra":
        else if($tipKorisnik == "sestra"){
            //Kreiram upit koji dohvaća najnoviji datum povijesti bolesti
            $sql = "SELECT p.datumPregled FROM pregled p 
                    WHERE p.mboPacijent = '$mboPacijent' 
                    AND p.idPregled = 
                    (SELECT MAX(p2.idPregled) FROM pregled p2 
                    WHERE p2.mboPacijent = '$mboPacijent')"; 
            //Rezultat upita spremam u varijablu $result
            $result = mysqli_query($conn,$sql);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($result) > 0){
                //Idem redak po redak rezultata upita 
                while($row = mysqli_fetch_assoc($result)){
                    //Dohvaćam najnoviji datum
                    $datum = $row['datumPregled'];
                }
            }
            //Ako ovaj pacijent NEMA evidentiranih povijesti bolesti
            else{
                //Vraćam današnji datum
                $datum = date('Y-m-d');
            }
        }
        return $datum; 
    }
}
?>