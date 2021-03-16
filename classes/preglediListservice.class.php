<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');
class PreglediListService{

    //Funkcija koja dohvaća sve preglede koji odgovaraju pretrazi korisnika
    function dohvatiSvePregledePretraga($tipKorisnik,$mboPacijent,$pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        //Ako je tip korisnika liječnik:
        if($tipKorisnik == "lijecnik"){
            //Ako je empty pretraga
            if(empty($pretraga)){
                //Kreiram upit koji će dohvatiti sve povijesti bolesti 
                $sql = "SELECT pb.idPovijestBolesti, DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, 
                        pb.tipSlucaj, pb.vrijeme, pb.bojaPregled FROM povijestBolesti pb 
                        WHERE pb.mboPacijent = '$mboPacijent'
                        GROUP BY pb.prosliPregled
                        ORDER BY pb.datum DESC, pb.vrijeme DESC
                        LIMIT 7";
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
            //Ako pretraga nije prazna
            else{
                $sql = "SELECT pb.idPovijestBolesti, DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, 
                    pb.tipSlucaj, pb.vrijeme, pb.bojaPregled FROM povijestBolesti pb 
                    JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna 
                    WHERE pb.mboPacijent = '$mboPacijent' 
                    AND (UPPER(pb.razlogDolaska) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb.anamneza) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb.statusPacijent) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb.nalaz) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb.mkbSifraPrimarna) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(d.imeDijagnoza) LIKE UPPER('%{$pretraga}%')
                    OR UPPER(pb.mkbSifraSekundarna) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb.terapija) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb.preporukaLijecnik) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb.napomena) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(DATE_FORMAT(pb.datum,'%d.%m.%Y')) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb.mboPacijent) LIKE UPPER('%{$pretraga}%')) 
                    GROUP BY pb.prosliPregled
                    ORDER BY pb.datum DESC, pb.vrijeme DESC 
                    LIMIT 7;";
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
        }
        //Ako je tip korisnika "sestra":
        else if($tipKorisnik == "sestra"){
            //Ako je prazna pretraga
            if(empty($pretraga)){
                //Kreiram upit koji će dohvatiti sve povijesti bolesti 
                $sql = "SELECT p.idPregled, DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, 
                        p.tipSlucaj, p.vrijemePregled, p.bojaPregled FROM pregled p 
                        WHERE p.mboPacijent = '$mboPacijent' 
                        GROUP BY p.prosliPregled 
                        ORDER BY p.datumPregled DESC, p.vrijemePregled DESC 
                        LIMIT 7";
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
            //Ako nije prazna pretraga
            else{
                $sql = "SELECT p.idPregled, DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, 
                    p.tipSlucaj, p.vrijemePregled, p.bojaPregled FROM pregled p 
                    WHERE p.mboPacijent = '$mboPacijent' 
                    AND (UPPER(p.nazivPoduzeca) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(p.nazivDrzave) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(p.mboPacijent) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(p.brIskDopunsko) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(p.mkbSifraPrimarna) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(p.mkbSifraSekundarna) LIKE UPPER('%{$pretraga}%')
                    OR UPPER(DATE_FORMAT(p.datumPregled,'%d.%m.%Y')) LIKE UPPER('%{$pretraga}%')) 
                    GROUP BY p.prosliPregled
                    ORDER BY p.datumPregled DESC, p.vrijemePregled DESC 
                    LIMIT 7;";
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
        }
        return $response;
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
                    pb.tipSlucaj, pb.vrijeme, pb.bojaPregled FROM povijestBolesti pb 
                    WHERE pb.mboPacijent = '$mboPacijent' 
                    AND pb.datum = '$datum'
                    GROUP BY pb.prosliPregled
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
                    p.tipSlucaj, p.vrijemePregled, p.bojaPregled FROM pregled p 
                    WHERE p.mboPacijent = '$mboPacijent' 
                    AND p.datumPregled = '$datum' 
                    GROUP BY p.prosliPregled 
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
    function dohvatiSvePreglede($tipKorisnik,$mboPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        //Ako je tip korisnika liječnik:
        if($tipKorisnik == "lijecnik"){
            //Kreiram upit koji će dohvatiti sve povijesti bolesti 
            $sql = "SELECT pb.idPovijestBolesti, DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, 
                    pb.tipSlucaj, pb.vrijeme, pb.bojaPregled FROM povijestBolesti pb 
                    WHERE pb.mboPacijent = '$mboPacijent'
                    GROUP BY pb.prosliPregled
                    ORDER BY pb.datum DESC, pb.vrijeme DESC
                    LIMIT 7";
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
                    p.tipSlucaj, p.vrijemePregled, p.bojaPregled FROM pregled p 
                    WHERE p.mboPacijent = '$mboPacijent' 
                    GROUP BY p.prosliPregled 
                    ORDER BY p.datumPregled DESC, p.vrijemePregled DESC 
                    LIMIT 7";
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