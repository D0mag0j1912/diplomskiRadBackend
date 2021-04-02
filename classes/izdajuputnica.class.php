<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');
class IzdajUputnica {

    //Funkcija koja dohvaća zadnje postavljene dijagnoze u povijesti bolesti
    function dohvatiInicijalneDijagnoze($idObrada,$mboPacijent){
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        //Kreiram upit koji dohvaća sporedne podatke povijest bolesti ZADNJEG RETKA (jer ako ovo ne napravim, vraćati će mi samo zadnju sek. dijagnozu)
        $sqlZadnjiRedak = "SELECT * FROM povijestBolesti pb
                        WHERE pb.idUputnica IS NULL 
                        AND pb.mboPacijent = '$mboPacijent' 
                        AND pb.idObradaLijecnik = '$idObrada'
                        AND pb.idPovijestBolesti = 
                        (SELECT MAX(pb2.idPovijestBolesti) FROM povijestbolesti pb2 
                        WHERE pb2.idUputnica IS NULL 
                        AND pb2.mboPacijent = '$mboPacijent' 
                        AND pb2.idObradaLijecnik = '$idObrada')";
        $resultZadnjiRedak = $conn->query($sqlZadnjiRedak);
        //Ako ima rezultata
        if($resultZadnjiRedak->num_rows > 0){
            while($rowZadnjiRedak = $resultZadnjiRedak->fetch_assoc()){
                $mkbSifraPrimarna = $rowZadnjiRedak['mkbSifraPrimarna'];
                $tipSlucaj = $rowZadnjiRedak['tipSlucaj'];
                $datum = $rowZadnjiRedak['datum'];
                $vrijeme = $rowZadnjiRedak['vrijeme'];
                $idObradaLijecnik = $rowZadnjiRedak['idObradaLijecnik'];
            }
        }

        //Dohvaćam primarnu i sve sekundarne dijagnoze 
        $sql = "SELECT DISTINCT(TRIM(d.imeDijagnoza)) AS NazivPrimarna, 
                IF(pb.mkbSifraSekundarna = NULL, NULL, (SELECT TRIM(d2.imeDijagnoza) FROM dijagnoze d2 WHERE d2.mkbSifra = pb.mkbSifraSekundarna)) AS NazivSekundarna 
                ,pb.idObradaLijecnik,pb.tipSlucaj,pb.vrijeme,pb.datum FROM povijestBolesti pb 
                JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna
                WHERE TRIM(pb.mkbSifraPrimarna) = '$mkbSifraPrimarna' 
                AND pb.tipSlucaj = '$tipSlucaj' 
                AND pb.datum = '$datum' 
                AND pb.vrijeme = '$vrijeme' 
                AND pb.idObradaLijecnik = '$idObradaLijecnik'";
        $result = $conn->query($sql);
        //Ako ima rezultata
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $response[] = $row;
            }
        }
        return $response;
    }
    //Funkcija koja provjerava je li unesena povijest bolesti za ovu sesiju obrade
    function isUnesenaPovijestBolesti($idObrada, $mboPacijent){
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        $sql = "SELECT COUNT(pb.idPovijestBolesti) AS BrojPovijestBolesti FROM povijestBolesti pb 
                WHERE pb.idObradaLijecnik = '$idObrada' 
                AND pb.mboPacijent = '$mboPacijent' 
                AND pb.idUputnica IS NULL";
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