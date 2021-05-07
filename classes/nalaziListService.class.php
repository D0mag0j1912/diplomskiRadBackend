<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');
class NalaziListService {

    //Funkcija koja dohvaća sve nalaze koji odgovaraju tekstualnoj pretrazi
    function dohvatiNalazePoTekstu($pretraga,$idPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        //Ako je pretraga prazna
        if(empty($pretraga)){
            //Kreiram upit koji dohvaća sve nalaze za listu
            $sql = "SELECT n.idNalaz, DATE_FORMAT(n.datumNalaz,'%d.%m.%Y') AS datumNalaz, 
                    TRIM(zu.idZdrUst) AS idZdrUst, TRIM(zu.nazivZdrUst) AS nazivZdrUst, 
                    TRIM(n.mkbSifraPrimarna) AS mkbSifraPrimarna, TRIM(d.imeDijagnoza) AS imeDijagnoza,
                    n.misljenjeSpecijalist 
                    FROM nalaz n 
                    JOIN zdr_ustanova zu ON zu.idZdrUst = n.idZdrUst 
                    JOIN dijagnoze d ON d.mkbSifra = n.mkbSifraPrimarna 
                    WHERE n.idPacijent = '$idPacijent'
                    GROUP BY n.oznaka 
                    ORDER BY n.datumNalaz DESC, n.vrijemeNalaz DESC 
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
        }
        //Ako pretraga nije prazna
        else{
            $sql = "SELECT n.idNalaz, DATE_FORMAT(n.datumNalaz,'%d.%m.%Y') AS datumNalaz, 
                    TRIM(zu.idZdrUst) AS idZdrUst, TRIM(zu.nazivZdrUst) AS nazivZdrUst, 
                    TRIM(n.mkbSifraPrimarna) AS mkbSifraPrimarna, TRIM(d.imeDijagnoza) AS imeDijagnoza,
                    n.misljenjeSpecijalist 
                    FROM nalaz n 
                    LEFT JOIN zdr_ustanova zu ON zu.idZdrUst = n.idZdrUst 
                    LEFT JOIN dijagnoze d ON d.mkbSifra = n.mkbSifraPrimarna 
                    LEFT JOIN specijalist s ON s.idSpecijalist = n.idSpecijalist 
                    LEFT JOIN zdr_djel zd ON zd.sifDjel = n.sifDjel 
                    LEFT JOIN dijagnoze d2 ON d2.mkbSifra = n.mkbSifraSekundarna
                    WHERE n.idPacijent = '$idPacijent' 
                    AND (UPPER(s.imeSpecijalist) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(s.prezSpecijalist) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(zu.nazivZdrUst) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(zd.nazivDjel) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(TRIM(n.mkbSifraPrimarna)) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(TRIM(n.mkbSifraSekundarna)) LIKE UPPER('%{$pretraga}%')
                    OR UPPER(TRIM(d.imeDijagnoza)) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(TRIM(d2.imeDijagnoza)) LIKE UPPER('%{$pretraga}%')) 
                    GROUP BY n.oznaka 
                    ORDER BY n.datumNalaz DESC, n.vrijemeNalaz DESC 
                    LIMIT 8";
            $result = $conn->query($sql);

            //Ako ima pronađenih rezultata za navedenu pretragu
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
            //Ako nema pronađenih rezultata za ovu pretragu
            else{
                $response["success"] = "false";
                $response["message"] = "Nema pronađenih rezultata za ključnu riječ: ".$pretraga;
            }
        }

        return $response;
    }

    //Funkcija koja dohvaća sve nalaze za određeni datum
    function dohvatiNalazePoDatumu($datum,$idPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        $sql = "SELECT n.idNalaz, DATE_FORMAT(n.datumNalaz,'%d.%m.%Y') AS datumNalaz, zu.idZdrUst, 
                TRIM(zu.nazivZdrUst) AS nazivZdrUst, 
                TRIM(n.mkbSifraPrimarna) AS mkbSifraPrimarna, TRIM(d.imeDijagnoza) AS imeDijagnoza,
                n.misljenjeSpecijalist 
                FROM nalaz n 
                JOIN zdr_ustanova zu ON zu.idZdrUst = n.idZdrUst 
                JOIN dijagnoze d ON d.mkbSifra = n.mkbSifraPrimarna 
                WHERE n.idPacijent = '$idPacijent' 
                AND n.datumNalaz = '$datum' 
                GROUP BY n.oznaka 
                ORDER BY n.idNalaz DESC";
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
        $sql = "SELECT n.idNalaz, DATE_FORMAT(n.datumNalaz,'%d.%m.%Y') AS datumNalaz, 
                TRIM(zu.idZdrUst) AS idZdrUst, TRIM(zu.nazivZdrUst) AS nazivZdrUst, 
                TRIM(n.mkbSifraPrimarna) AS mkbSifraPrimarna, TRIM(d.imeDijagnoza) AS imeDijagnoza,
                n.misljenjeSpecijalist 
                FROM nalaz n 
                JOIN zdr_ustanova zu ON zu.idZdrUst = n.idZdrUst 
                JOIN dijagnoze d ON d.mkbSifra = n.mkbSifraPrimarna 
                WHERE n.idPacijent = '$idPacijent' 
                GROUP BY n.oznaka 
                ORDER BY n.datumNalaz DESC, n.vrijemeNalaz DESC 
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