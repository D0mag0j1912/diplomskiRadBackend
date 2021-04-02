<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');
class IzdajUputnica {
    //Funkcija koja provjerava je li unesena povijest bolesti za ovu sesiju obrade
    function isUnesenaPovijestBolesti($idObrada, $mboPacijent){
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        $sql = "SELECT COUNT(pb.idPovijestBolesti) AS BrojPovijestBolesti FROM povijestBolesti pb 
                WHERE pb.idObradaLijecnik = '$idObrada' 
                AND pb.mboPacijent = '$mboPacijent'";
        //Rezultat upita spremam u varijablu $result
        $result = mysqli_query($conn,$sql);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($result) > 0){
            //Idem redak po redak rezultata upita 
            while($row = mysqli_fetch_assoc($result)){
                //Vrijednost rezultata spremam u varijablu $brojPovijestBolesti
                $brojPovijestBolesti = $row['BrojPovijestBolesti'];
            }
        }
        return $brojPovijestBolesti; 
    }
}   
?>