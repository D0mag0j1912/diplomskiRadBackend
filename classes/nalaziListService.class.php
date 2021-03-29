<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');
class NalaziListService {

    //Funkcija koja dohvaća nalaz preko njegovog ID-a
    function dohvatiNalaz($idNalaz){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        //Kreiram upit koji dohvaća sve podatke traženog nalaza
        $sql = "SELECT CONCAT(s.imeSpecijalist,' ',s.prezSpecijalist) AS specijalist,
                CONCAT(TRIM(zr.tipSpecijalist),' [',TRIM(zr.sifraSpecijalist),']') AS sifraTipSpecijalist,
                zu.idZdrUst, TRIM(zu.nazivZdrUst) AS nazivZdrUst, TRIM(zu.adresaZdrUst) AS adresaZdrUst, zu.pbrZdrUst,
                CONCAT(TRIM(zd.nazivDjel),' [',TRIM(zd.sifDjel),']') AS zdravstvenaDjelatnost,
                CONCAT(TRIM(d.imeDijagnoza),' [',TRIM(d.mkbSifra),']') AS primarnaDijagnoza,
                n.misljenjeSpecijalist, DATE_FORMAT(n.datumNalaz,'%d.%m.%Y') AS datumNalaz
                FROM nalaz n 
                JOIN specijalist s ON s.idSpecijalist = n.idSpecijalist 
                JOIN zdr_radnici zr ON zr.sifraSpecijalist = n.sifraSpecijalist 
                JOIN zdr_ustanova zu ON zu.idZdrUst = n.idZdrUst 
                JOIN zdr_djel zd ON zd.sifDjel = n.sifDjel 
                JOIN dijagnoze d ON d.mkbSifra = n.mkbSifraPrimarna
                WHERE n.idNalaz = '$idNalaz'";
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
                ORDER BY n.datumNalaz DESC";
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