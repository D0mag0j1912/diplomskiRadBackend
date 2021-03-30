<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');
class NalaziDetailService {
    //Funkcija koja dohvaća sve sekundarne dijagnoze koje se prikazivaju u detaljima nalaza
    function dohvatiSekundarneDijagnoze($idNalaz){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        $sql = "SELECT n.idPacijent, n.idSpecijalist, n.idZdrUst, 
                n.sifDjel, n.mkbSifraPrimarna, n.misljenjeSpecijalist, 
                n.datumNalaz, n.vrijemeNalaz 
                FROM nalaz n 
                WHERE n.idNalaz = '$idNalaz'";
        //Rezultat upita spremam u varijablu $result
        $result = mysqli_query($conn,$sql);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($result) > 0){
            //Idem redak po redak rezultata upita 
            while($row = mysqli_fetch_assoc($result)){
                $idPacijent = $row['idPacijent'];
                $idSpecijalist = $row['idSpecijalist'];
                $idZdrUst = $row['idZdrUst'];
                $sifDjel = $row['sifDjel'];
                $mkbSifraPrimarna = $row['mkbSifraPrimarna'];
                $misljenjeSpecijalist = $row['misljenjeSpecijalist'];
                $datumNalaz = $row['datumNalaz'];
                $vrijemeNalaz = $row['vrijemeNalaz'];

                //Kreiram upit koji dohvaća sve sek. dijagnoze na osnovu ovih gore parametara
                $sqlSek = "SELECT CONCAT(TRIM(d.imeDijagnoza),' [',TRIM(n.mkbSifraSekundarna),']') AS sekundarneDijagnoze 
                        FROM nalaz n 
                        JOIN dijagnoze d ON d.mkbSifra = n.mkbSifraSekundarna 
                        WHERE n.idPacijent = '$idPacijent' 
                        AND n.idSpecijalist = '$idSpecijalist' 
                        AND n.idZdrUst = '$idZdrUst' 
                        AND n.sifDjel = '$sifDjel' 
                        AND n.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                        AND n.misljenjeSpecijalist = '$misljenjeSpecijalist' 
                        AND n.datumNalaz = '$datumNalaz' 
                        AND n.vrijemeNalaz = '$vrijemeNalaz'";
                //Rezultat upita spremam u varijablu $result
                $resultSek = mysqli_query($conn,$sqlSek);
                //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                if(mysqli_num_rows($resultSek) > 0){
                    //Idem redak po redak rezultata upita 
                    while($rowSek = mysqli_fetch_assoc($resultSek)){
                        $response[] = $rowSek;
                    }
                }
                else{
                    return null;
                }
            }
        } 
        return $response;
    }

    //Funkcija koja dohvaća nalaz preko njegovog ID-a
    function dohvatiNalaz($idNalaz){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        //Kreiram upit koji dohvaća sve podatke traženog nalaza
        $sql = "SELECT CONCAT(TRIM(s.imeSpecijalist),' ',TRIM(s.prezSpecijalist)) AS specijalist,
                zu.idZdrUst, TRIM(zu.nazivZdrUst) AS nazivZdrUst, TRIM(zu.adresaZdrUst) AS adresaZdrUst, zu.pbrZdrUst,
                CONCAT(TRIM(zd.nazivDjel),' [',TRIM(zd.sifDjel),']') AS zdravstvenaDjelatnost,
                CONCAT(TRIM(d.imeDijagnoza),' [',TRIM(d.mkbSifra),']') AS primarnaDijagnoza,
                n.misljenjeSpecijalist, DATE_FORMAT(n.datumNalaz,'%d.%m.%Y') AS datumNalaz
                FROM nalaz n 
                JOIN specijalist s ON s.idSpecijalist = n.idSpecijalist
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
}