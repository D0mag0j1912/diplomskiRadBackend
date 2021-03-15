<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');
class PreglediListService{

    //Funkcija koja dohvaća najnoviji ID pregleda ZA ZADANI DATUM (kada se filtrira po datumu)
    function dohvatiNajnovijiIDPregledPoDatumu($tipKorisnik,$mboPacijent,$datum){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        //Ako je tip korisnika "lijecnik":
        if($tipKorisnik == "lijecnik"){
            //Kreiram upit koji će dohvatiti povijesti bolesti za zadani datum
            $sql = "SELECT * FROM povijestBolesti pb 
                    WHERE pb.mboPacijent = '$mboPacijent' 
                    AND pb.datum = '$datum'
                    AND pb.idPovijestBolesti = 
                    (SELECT MAX(pb2.idPovijestBolesti) FROM povijestBolesti pb2 
                    WHERE pb2.mboPacijent = '$mboPacijent' 
                    AND pb2.datum = '$datum')";
            //Rezultat upita spremam u varijablu $result
            $result = mysqli_query($conn,$sql);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($result) > 0){
                //Idem redak po redak rezultata upita 
                while($row = mysqli_fetch_assoc($result)){
                    //Spremam podatke koji mi trebaju za dohvat MINIMALNOG ID-a zadnjeg pregleda
                    $mkbSifraPrimarna = $row['mkbSifraPrimarna'];
                    $tipSlucaj = $row['tipSlucaj'];
                    $idObradaLijecnik = $row['idObradaLijecnik'];
                    $vrijeme = $row['vrijeme'];

                    //Kreiram upit kojim dohvaćam MINIMALNI ID zadnjeg evidentiranog pregleda
                    $sqlMinID = "SELECT pb.idPovijestBolesti FROM povijestBolesti pb 
                                WHERE pb.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                                AND pb.tipSlucaj = '$tipSlucaj' 
                                AND pb.datum = '$datum' 
                                AND pb.idObradaLijecnik = '$idObradaLijecnik' 
                                AND pb.vrijeme = '$vrijeme' 
                                AND pb.idPovijestBolesti = 
                                (SELECT MIN(pb2.idPovijestBolesti) FROM povijestBolesti pb2 
                                WHERE pb2.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                                AND pb2.tipSlucaj = '$tipSlucaj' 
                                AND pb2.datum = '$datum' 
                                AND pb2.idObradaLijecnik = '$idObradaLijecnik' 
                                AND pb2.vrijeme = '$vrijeme')";
                    //Rezultat upita spremam u varijablu $result
                    $resultMinID = mysqli_query($conn,$sqlMinID);
                    //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                    if(mysqli_num_rows($resultMinID) > 0){
                        //Idem redak po redak rezultata upita 
                        while($rowMinID = mysqli_fetch_assoc($resultMinID)){
                            //Spremam MINIMALNI ID povijesti bolesti
                            $idPregled = $rowMinID['idPovijestBolesti'];
                        }
                    }
                }
            }
            //Ako nema evidentiranih pregleda za ovog pacijenta
            else{
                return null;
            }
        }
        //Ako je tip korisnika "sestra":
        else if($tipKorisnik == "sestra"){
            $sql = "SELECT * FROM pregled p
                    WHERE p.mboPacijent = '$mboPacijent' 
                    AND p.datumPregled = '$datum'
                    AND p.idPregled = 
                    (SELECT MAX(p2.idPregled) FROM pregled p2
                    WHERE p2.mboPacijent = '$mboPacijent' 
                    AND p2.datumPregled = '$datum')";
            //Rezultat upita spremam u varijablu $result
            $result = mysqli_query($conn,$sql);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($result) > 0){
                //Idem redak po redak rezultata upita 
                while($row = mysqli_fetch_assoc($result)){
                    //Spremam podatke koji mi trebaju za dohvat MINIMALNOG ID-a zadnjeg pregleda
                    $mkbSifraPrimarna = $row['mkbSifraPrimarna'];
                    $tipSlucaj = $row['tipSlucaj'];
                    $idObradaMedSestra = $row['idObradaMedSestra'];
                    $vrijemePregled = $row['vrijemePregled'];
                    //Ako je upisana MKB šifra primarne dijagnoze
                    if(!empty($mkbSifraPrimarna)){
                        //Kreiram upit kojim dohvaćam MINIMALNI ID zadnjeg evidentiranog pregleda
                        $sqlMinID = "SELECT p.idPregled FROM pregled p 
                                    WHERE p.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                                    AND p.tipSlucaj = '$tipSlucaj' 
                                    AND p.datumPregled = '$datum' 
                                    AND p.idObradaMedSestra = '$idObradaMedSestra' 
                                    AND p.vrijemePregled = '$vrijemePregled' 
                                    AND p.idPregled = 
                                    (SELECT MIN(p2.idPregled) FROM pregled p2 
                                    WHERE p2.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                                    AND p2.tipSlucaj = '$tipSlucaj' 
                                    AND p2.datumPregled = '$datum' 
                                    AND p2.idObradaMedSestra = '$idObradaMedSestra' 
                                    AND p2.vrijemePregled = '$vrijemePregled')";
                        //Rezultat upita spremam u varijablu $result
                        $resultMinID = mysqli_query($conn,$sqlMinID);
                        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                        if(mysqli_num_rows($resultMinID) > 0){
                            //Idem redak po redak rezultata upita 
                            while($rowMinID = mysqli_fetch_assoc($resultMinID)){
                                //Spremam MINIMALNI ID pregleda
                                $idPregled = $rowMinID['idPregled'];
                            }
                        }
                    }
                    //Ako nije upisana MKB šifra primarne dijagnoze
                    else{
                        //Kreiram upit kojim dohvaćam MINIMALNI ID zadnjeg evidentiranog pregleda
                        $sqlMinID = "SELECT p.idPregled FROM pregled p 
                                    WHERE p.mkbSifraPrimarna IS NULL 
                                    AND p.tipSlucaj = '$tipSlucaj' 
                                    AND p.datumPregled = '$datum' 
                                    AND p.idObradaMedSestra = '$idObradaMedSestra' 
                                    AND p.vrijemePregled = '$vrijemePregled' 
                                    AND p.idPregled = 
                                    (SELECT MIN(p2.idPregled) FROM pregled p2 
                                    WHERE p2.mkbSifraPrimarna IS NULL 
                                    AND p2.tipSlucaj = '$tipSlucaj' 
                                    AND p2.datumPregled = '$datum' 
                                    AND p2.idObradaMedSestra = '$idObradaMedSestra' 
                                    AND p2.vrijemePregled = '$vrijemePregled')";
                        //Rezultat upita spremam u varijablu $result
                        $resultMinID = mysqli_query($conn,$sqlMinID);
                        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                        if(mysqli_num_rows($resultMinID) > 0){
                            //Idem redak po redak rezultata upita 
                            while($rowMinID = mysqli_fetch_assoc($resultMinID)){
                                //Spremam MINIMALNI ID pregleda
                                $idPregled = $rowMinID['idPregled'];
                            }
                        }
                    }
                }
            }
            //Ako pacijent NEMA evidentiranih pregleda
            else{
                return null;
            }
        }
        return $idPregled;
    }
    //Funkcija koja dohvaća SVE PREGLEDE aktivnog pacijenta ZA ZADANI DATUM
    function dohvatiPregledePoDatumu($tipKorisnik,$mboPacijent,$datum){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        //Ako je tip korisnika liječnik:
        if($tipKorisnik == "lijecnik"){
            //Kreiram upit koji će dohvatiti povijesti bolesti za zadani datum
            $sql = "SELECT pb.idPovijestBolesti, DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, 
                    pb.tipSlucaj, pb.vrijeme FROM povijestBolesti pb 
                    WHERE pb.mboPacijent = '$mboPacijent' 
                    AND pb.datum = '$datum'
                    GROUP BY pb.tipSlucaj,pb.mkbSifraPrimarna
                    ORDER BY pb.vrijeme DESC;";
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
            //Ako pacijent nema evidentiranih pregleda
            else{
                return null;
            }
        }
        //Ako je tip korisnika "sestra":
        else if($tipKorisnik == "sestra"){
            //Kreiram upit koji će dohvatiti opće podatke pregleda za zadani datum 
            $sql = "SELECT p.idPregled, DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, 
                    p.tipSlucaj, p.vrijemePregled FROM pregled p 
                    WHERE p.mboPacijent = '$mboPacijent' 
                    AND p.datumPregled = '$datum' 
                    GROUP BY p.tipSlucaj, p.mkbSifraPrimarna 
                    ORDER BY p.vrijemePregled DESC;";
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
            //Ako pacijent nema evidentiranih pregleda
            else{
                return null;
            }
        }

        return $response;
    }

    //Funkcija koja na osnovu tipa korisnika, ID-a pacijenta dohvaća sve njegove preglede
    function dohvatiSvePreglede($tipKorisnik,$idPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        //Ako je tip korisnika liječnik:
        if($tipKorisnik == "lijecnik"){
            //Kreiram upit koji će dohvatiti sve povijesti bolesti 
            $sql = "SELECT pb.idPovijestBolesti, DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, 
                    pb.tipSlucaj, pb.vrijeme FROM povijestBolesti pb 
                    WHERE pb.mboPacijent IN 
                    (SELECT pacijent.mboPacijent FROM pacijent 
                    WHERE pacijent.idPacijent = '$idPacijent')
                    GROUP BY pb.tipSlucaj,pb.mkbSifraPrimarna
                    ORDER BY pb.datum DESC, pb.vrijeme DESC;";
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
            //Ako pacijent nema evidentiranih pregleda
            else{
                return null;
            }
        }
        //Ako je tip korisnika "sestra":
        else if($tipKorisnik == "sestra"){
            //Kreiram upit koji će dohvatiti sve povijesti bolesti 
            $sql = "SELECT p.idPregled, DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, 
                    p.tipSlucaj, p.vrijemePregled FROM pregled p 
                    WHERE p.mboPacijent IN 
                    (SELECT pacijent.mboPacijent FROM pacijent 
                    WHERE pacijent.idPacijent = '$idPacijent') 
                    GROUP BY p.tipSlucaj, p.mkbSifraPrimarna 
                    ORDER BY p.datumPregled DESC, p.vrijemePregled DESC;";
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
            //Ako pacijent nema evidentiranih pregleda
            else{
                return null;
            }
        }
        return $response;
    }
}
?>