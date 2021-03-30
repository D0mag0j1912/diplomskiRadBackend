<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');
class NalaziListService {

    //Funkcija koja dohvaća sve nalaze za određeni datum
    function dohvatiNalazePoDatumu($datum,$idPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        $sql = "SELECT n.idNalaz, DATE_FORMAT(n.datumNalaz,'%d.%m.%Y') AS datumNalaz, zu.idZdrUst, zu.nazivZdrUst, 
                n.mkbSifraPrimarna, d.imeDijagnoza 
                FROM nalaz n 
                JOIN zdr_ustanova zu ON zu.idZdrUst = n.idZdrUst 
                JOIN dijagnoze d ON d.mkbSifra = n.mkbSifraPrimarna 
                WHERE n.idPacijent = '$idPacijent' 
                AND n.datumNalaz = '$datum' 
                GROUP BY n.mkbSifraPrimarna";
        //Rezultat upita spremam u varijablu $result
        $result = mysqli_query($conn,$sql);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($result) > 0){
            //Idem redak po redak rezultata upita 
            while($row = mysqli_fetch_assoc($result)){
                $response[] = $row;
            }
        } 
        //Ako nema nalaza za traženi datum
        else{
            return null;
        }
        return $response;
    }

    //Funkcija koja dohvaća sve nalaze za listu
    function dohvatiSveNalaze($idPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        //Kreiram upit koji dohvaća sve nalaze za listu
        $sql = "SELECT n.idNalaz, DATE_FORMAT(n.datumNalaz,'%d.%m.%Y') AS datumNalaz, zu.idZdrUst, zu.nazivZdrUst, 
                n.mkbSifraPrimarna, d.imeDijagnoza 
                FROM nalaz n 
                JOIN zdr_ustanova zu ON zu.idZdrUst = n.idZdrUst 
                JOIN dijagnoze d ON d.mkbSifra = n.mkbSifraPrimarna 
                WHERE n.idPacijent = '$idPacijent' 
                GROUP BY n.mkbSifraPrimarna 
                ORDER BY n.datumNalaz DESC 
                LIMIT 8";
        //Rezultat upita spremam u varijablu $result
        $result = mysqli_query($conn,$sql);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($result) > 0){
            //Idem redak po redak rezultata upita 
            while($row = mysqli_fetch_assoc($result)){
                $response[] = $row;
            }
        }  
        return $response;
    }
}
?>