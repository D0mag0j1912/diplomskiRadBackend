<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');
class UputnicaService {

    //Funkcija koja dohvaća sve uputnice koje odgovaraju pretrazi
    function dohvatiSveUputnicePretraga($pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        $sql = "SELECT TRIM(zd.sifDjel) AS sifDjel,TRIM(zd.nazivDjel) AS nazivDjel, 
                p.idPacijent, p.imePacijent, p.prezPacijent, 
                p.mboPacijent, TRIM(u.mkbSifraPrimarna) AS mkbSifraPrimarna, TRIM(d.imeDijagnoza) AS nazivPrimarna, 
                u.vrstaPregleda, u.molimTraziSe, u.napomena, DATE_FORMAT(u.datum,'%d.%m.%Y') AS Datum, 
                TRIM(zr.sifraSpecijalist) AS sifraSpecijalist,TRIM(zr.tipSpecijalist) AS tipSpecijalist, 
                TRIM(zu.idZdrUst) AS idZdrUst, TRIM(zu.nazivZdrUst) AS nazivZdrUst,
                TRIM(zu.adresaZdrUst) AS adresaZdrUst, TRIM(zu.pbrZdrUst) AS pbrZdrUst FROM uputnica u 
                LEFT JOIN dijagnoze d ON d.mkbSifra = u.mkbSifraPrimarna 
                LEFT JOIN pacijent p ON p.idPacijent = u.idPacijent 
                LEFT JOIN zdr_djel zd ON zd.sifDjel = u.sifDjel 
                LEFT JOIN zdr_radnici zr ON zr.sifraSpecijalist = u.sifraSpecijalist 
                LEFT JOIN zdr_ustanova zu ON zu.idZdrUst = u.idZdrUst 
                WHERE (UPPER(TRIM(zd.sifDjel)) LIKE '%{$pretraga}%' 
                OR UPPER(TRIM(zd.nazivDjel)) LIKE '%{$pretraga}%' 
                OR UPPER(p.imePacijent) LIKE '%{$pretraga}%' 
                OR UPPER(p.prezPacijent) LIKE '%{$pretraga}%' 
                OR UPPER(p.mboPacijent) LIKE '%{$pretraga}%' 
                OR UPPER(TRIM(u.mkbSifraPrimarna)) LIKE '%{$pretraga}%' 
                OR UPPER(TRIM(d.imeDijagnoza)) LIKE '%{$pretraga}%' 
                OR UPPER(u.vrstaPregleda) LIKE '%{$pretraga}%' 
                OR UPPER(DATE_FORMAT(u.datum,'%d.%m.%Y')) LIKE '%{$pretraga}%' 
                OR UPPER(TRIM(zr.sifraSpecijalist)) LIKE '%{$pretraga}%' 
                OR UPPER(TRIM(zr.tipSpecijalist)) LIKE '%{$pretraga}%' 
                OR UPPER(TRIM(zu.idZdrUst)) LIKE '%{$pretraga}%' 
                OR UPPER(TRIM(zu.nazivZdrUst)) LIKE '%{$pretraga}%' 
                OR UPPER(TRIM(zu.adresaZdrUst)) LIKE '%{$pretraga}%' 
                OR UPPER(TRIM(zu.pbrZdrUst)) LIKE '%{$pretraga}%') 
                GROUP BY u.oznaka
                ORDER BY u.datum DESC, u.vrijeme DESC";
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

        return $response;
    }
    //Funkcija koja dohvaća sve uputnice
    function dohvatiSveUputnice(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        $sql = "SELECT TRIM(zd.sifDjel) AS sifDjel,TRIM(zd.nazivDjel) AS nazivDjel, 
                p.idPacijent, p.imePacijent, p.prezPacijent, 
                p.mboPacijent, TRIM(u.mkbSifraPrimarna) AS mkbSifraPrimarna, TRIM(d.imeDijagnoza) AS nazivPrimarna, 
                u.vrstaPregleda, u.molimTraziSe, u.napomena, DATE_FORMAT(u.datum,'%d.%m.%Y') AS Datum, 
                TRIM(zr.sifraSpecijalist) AS sifraSpecijalist,TRIM(zr.tipSpecijalist) AS tipSpecijalist, 
                TRIM(zu.idZdrUst) AS idZdrUst, TRIM(zu.nazivZdrUst) AS nazivZdrUst,
                TRIM(zu.adresaZdrUst) AS adresaZdrUst, TRIM(zu.pbrZdrUst) AS pbrZdrUst FROM uputnica u 
                LEFT JOIN dijagnoze d ON d.mkbSifra = u.mkbSifraPrimarna 
                LEFT JOIN pacijent p ON p.idPacijent = u.idPacijent 
                LEFT JOIN zdr_djel zd ON zd.sifDjel = u.sifDjel 
                LEFT JOIN zdr_radnici zr ON zr.sifraSpecijalist = u.sifraSpecijalist 
                LEFT JOIN zdr_ustanova zu ON zu.idZdrUst = u.idZdrUst 
                GROUP BY u.oznaka
                ORDER BY u.datum DESC, u.vrijeme DESC";
        //Rezultat upita spremam u varijablu $result
        $result = mysqli_query($conn,$sql);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($result) > 0){
            //Idem redak po redak rezultata upita 
            while($row = mysqli_fetch_assoc($result)){
                //Vrijednost rezultata spremam u varijablu $mboPacijent
                $response[] = $row;
            }
        }
        //Ako nema rezultata
        else{
            return null;
        }
        return $response;
    }
}
?>