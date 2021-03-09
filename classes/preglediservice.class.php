<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class PreglediService{

    //Funkcija koja dohvaća podatke cijelog pregleda na osnovu tipa korisnika
    function dohvatiCijeliPregled($tipKorisnik,$id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        //Ako je tip "lijecnik":
        if($tipKorisnik == "lijecnik"){
            //Kreiram upit koji će dohvatiti sve povijesti bolesti 
            $sql = "SELECT pb.idPovijestBolesti, pb.razlogDolaska, pb.anamneza, 
                    pb.statusPacijent, pb.nalaz, pb.mkbSifraPrimarna,
                    pb.terapija, pb.preporukaLijecnik, pb.napomena, 
                    DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, 
                    pb.tipSlucaj, pb.vrijeme FROM povijestBolesti pb 
                    WHERE pb.idPovijestBolesti = '$id';";
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
        }
        //Ako je tip korisnika "sestra":
        else if($tipKorisnik == "sestra"){
            //Kreiram upit koji će dohvatiti sve povijesti bolesti 
            $sql = "SELECT p.idPregled, p.nacinPlacanja, p.podrucniUredHZZO, 
                    IF(p.podrucniUredHZZO IS NULL, NULL, (SELECT pu.nazivSluzbe FROM podrucni_ured pu
                                                    JOIN pregled p ON p.podrucniUredHZZO = pu.sifUred 
                                                    WHERE p.idPregled = '$id')) AS nazivSluzbeHZZO,
                    p.podrucniUredOzljeda, 
                    IF(p.podrucniUredOzljeda IS NULL, NULL, (SELECT pu.nazivSluzbe FROM podrucni_ured pu
                                                    JOIN pregled p ON p.podrucniUredOzljeda = pu.sifUred 
                                                    WHERE p.idPregled = '$id')) AS nazivSluzbeOzljeda,
                    p.nazivPoduzeca,p.oznakaOsiguranika, k.opisOsiguranika, p.nazivDrzave, p.mkbSifraPrimarna,
                    DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, 
                    p.tipSlucaj, p.vrijemePregled FROM pregled p 
                    JOIN kategorije_osiguranje k ON k.oznakaOsiguranika = p.oznakaOsiguranika
                    WHERE p.idPregled = '$id';";
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
        }
        return $response;
    }

    //Funkcija koja na osnovu tipa korisnika, ID-a pacijenta te datuma dohvaća sve njegove preglede
    function dohvatiSvePregledePoDatumu($tipKorisnik,$idPacijent,$datum){
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
                    AND pb.datum = '$datum' 
                    GROUP BY pb.mkbSifraPrimarna 
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
        }
        //Ako je tip korisnika "sestra":
        else if($tipKorisnik == "sestra"){
            //Kreiram upit koji će dohvatiti sve povijesti bolesti 
            $sql = "SELECT p.idPregled, DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, 
                    p.tipSlucaj, p.vrijemePregled FROM pregled p 
                    WHERE p.mboPacijent IN 
                    (SELECT pacijent.mboPacijent FROM pacijent 
                    WHERE pacijent.idPacijent = '$idPacijent') 
                    AND p.datumPregled = '$datum' 
                    GROUP BY p.mkbSifraPrimarna 
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
        }
        return $response;
    } 
}
?>