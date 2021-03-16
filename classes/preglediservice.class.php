<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

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
            $sql = "SELECT pb.datum FROM povijestBolesti pb 
                    WHERE pb.mboPacijent = '$mboPacijent' 
                    AND pb.idPovijestBolesti = 
                    (SELECT MAX(pb2.idPovijestBolesti) FROM povijestBolesti pb2 
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
    
    //Funkcija koja vraća MBO pacijenta na osnovu njegovog ID-a
    function getMBO($idPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram upit za dohvaćanjem MBO-a pacijenta kojemu se upisiva povijest bolesti
        $sqlMBO = "SELECT p.mboPacijent AS MBO FROM pacijent p 
                WHERE p.idPacijent = '$idPacijent'";
        //Rezultat upita spremam u varijablu $resultMBO
        $resultMBO = mysqli_query($conn,$sqlMBO);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultMBO) > 0){
            //Idem redak po redak rezultata upita 
            while($rowMBO = mysqli_fetch_assoc($resultMBO)){
                //Vrijednost rezultata spremam u varijablu $mboPacijent
                $mboPacijent = $rowMBO['MBO'];
            }
        }
        return $mboPacijent;
    }
}
?>