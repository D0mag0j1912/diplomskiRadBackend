<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class SharedService {
    //Funkcija koja dohvaća zadnje generirani ID obrade povijesti bolesti
    function dohvatiSlucajniIDObrada(){
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        $sql = "SELECT pb.idObradaLijecnik FROM povijestBolesti pb 
                WHERE pb.idPovijestBolesti = 
                (SELECT MAX(pb2.idPovijestBolesti) FROM povijestBolesti pb2)";
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