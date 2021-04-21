<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class SharedService {

    //Funkcija koja dohvaća dopunsko osiguranje na osnovu ID-a pacijenta
    function getDopunsko($mboPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram upit za dohvaćanjem MBO-a pacijenta kojemu se upisiva povijest bolesti
        $sql = "SELECT zp.brojIskazniceDopunsko FROM zdr_podatci zp 
                WHERE zp.mboPacijent = '$mboPacijent'";
        //Rezultat upita spremam u varijablu $resultMBO
        $result = mysqli_query($conn,$sql);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($result) > 0){
            //Idem redak po redak rezultata upita 
            while($row = mysqli_fetch_assoc($result)){
                //Vrijednost rezultata spremam u varijablu $dopunsko
                $dopunsko = $row['brojIskazniceDopunsko'];
            }
        }
        return $dopunsko;
    }
    
    //Funkcija koja dohvaća zadnje generirani ID obrade povijesti bolesti
    function dohvatiSlucajniIDObrada($mboPacijent){
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        $sql = "SELECT pb.idObradaLijecnik FROM povijestBolesti pb 
                WHERE pb.mboPacijent = '$mboPacijent'
                AND pb.idPovijestBolesti = 
                (SELECT MAX(pb2.idPovijestBolesti) FROM povijestBolesti pb2 
                WHERE pb2.mboPacijent = '$mboPacijent')";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $idObrada = $row['idObradaLijecnik'];
            }
        }
        return $idObrada; 
    }
}
?>