<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

class PacijentService{

    //Funkcija koja dohvaća sve dijagnoze
    function dohvatiSveDijagnoze(){

        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
        //Kreiram upit koji dohvaća sve dijagnoze
        $sql = "SELECT * FROM dijagnoze";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        } 
        return $response;   
    }
}
?>