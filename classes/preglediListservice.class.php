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
                $sql = "(SELECT pb.idPovijestBolesti, DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, 
                        pb.tipSlucaj, pb.vrijeme, pb.bojaPregled FROM povijestBolesti pb 
                        WHERE pb.mboPacijent = '$mboPacijent' AND pb.prosliPregled IS NOT NULL
                        GROUP BY pb.prosliPregled
                        ORDER BY pb.datum DESC, pb.vrijeme DESC
                        LIMIT 7)
                        UNION 
                        (SELECT pb2.idPovijestBolesti, DATE_FORMAT(pb2.datum,'%d.%m.%Y') AS Datum, 
                        pb2.tipSlucaj, pb2.vrijeme, pb2.bojaPregled FROM povijestbolesti pb2 
                        WHERE pb2.mboPacijent = '$mboPacijent' AND pb2.prosliPregled IS NULL
                        ORDER BY pb2.datum DESC, pb2.vrijeme DESC 
                        LIMIT 7)";
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
                $sql = "(SELECT pb.idPovijestBolesti, DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, 
                        pb.tipSlucaj, pb.vrijeme, pb.bojaPregled FROM povijestBolesti pb 
                        LEFT JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna 
                        LEFT JOIN dijagnoze d2 ON d2.mkbSifra = pb.mkbSifraSekundarna
                        WHERE pb.mboPacijent = '$mboPacijent' 
                        AND (UPPER(pb.razlogDolaska) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pb.anamneza) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pb.statusPacijent) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pb.nalaz) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pb.mkbSifraPrimarna) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(d.imeDijagnoza) LIKE UPPER('%{$pretraga}%')
                        OR UPPER(d2.imeDijagnoza) LIKE UPPER('%{$pretraga}%')
                        OR UPPER(pb.mkbSifraSekundarna) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pb.terapija) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pb.preporukaLijecnik) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pb.napomena) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(DATE_FORMAT(pb.datum,'%d.%m.%Y')) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pb.mboPacijent) LIKE UPPER('%{$pretraga}%')) 
                        AND pb.prosliPregled IS NOT NULL 
                        GROUP BY pb.prosliPregled
                        ORDER BY pb.datum DESC, pb.vrijeme DESC 
                        LIMIT 7)
                        UNION 
                        (SELECT pb2.idPovijestBolesti, DATE_FORMAT(pb2.datum,'%d.%m.%Y') AS Datum, 
                        pb2.tipSlucaj, pb2.vrijeme, pb2.bojaPregled FROM povijestBolesti pb2 
                        LEFT JOIN dijagnoze d3 ON d3.mkbSifra = pb2.mkbSifraPrimarna 
                        LEFT JOIN dijagnoze d4 ON d4.mkbSifra = pb2.mkbSifraSekundarna
                        WHERE pb2.mboPacijent = '$mboPacijent' 
                        AND (UPPER(pb2.razlogDolaska) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pb2.anamneza) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pb2.statusPacijent) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pb2.nalaz) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pb2.mkbSifraPrimarna) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(d3.imeDijagnoza) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(d4.imeDijagnoza) LIKE UPPER('%{$pretraga}%')
                        OR UPPER(pb2.mkbSifraSekundarna) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pb2.terapija) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pb2.preporukaLijecnik) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pb2.napomena) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(DATE_FORMAT(pb2.datum,'%d.%m.%Y')) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pb2.mboPacijent) LIKE UPPER('%{$pretraga}%')) 
                        AND pb2.prosliPregled IS NULL
                        ORDER BY pb2.datum DESC, pb2.vrijeme DESC 
                        LIMIT 7)";
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
                $sql = "(SELECT p.idPregled, DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, 
                        p.tipSlucaj, p.vrijemePregled, p.bojaPregled FROM pregled p 
                        WHERE p.mboPacijent = '$mboPacijent' 
                        AND p.prosliPregled IS NOT NULL
                        GROUP BY p.prosliPregled 
                        ORDER BY p.datumPregled DESC, p.vrijemePregled DESC 
                        LIMIT 7)
                        UNION 
                        (SELECT p2.idPregled, DATE_FORMAT(p2.datumPregled,'%d.%m.%Y') AS Datum, 
                        p2.tipSlucaj, p2.vrijemePregled, p2.bojaPregled FROM pregled p2 
                        WHERE p2.mboPacijent = '$mboPacijent' 
                        AND p2.prosliPregled IS NULL 
                        ORDER BY p2.datumPregled DESC, p2.vrijemePregled DESC 
                        LIMIT 7)";
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
                $sql = "(SELECT p.idPregled, DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, 
                        p.tipSlucaj, p.vrijemePregled, p.bojaPregled FROM pregled p 
                        LEFT JOIN dijagnoze d ON d.mkbSifra = p.mkbSifraPrimarna
                        LEFT JOIN dijagnoze d2 ON d2.mkbSifra = p.mkbSifraSekundarna 
                        LEFT JOIN podrucni_ured pu ON pu.sifUred = p.podrucniUredHZZO 
                        LEFT JOIN podrucni_ured pu2 ON pu2.sifUred = p.podrucniUredOzljeda 
                        LEFT JOIN kategorije_osiguranje ko ON ko.oznakaOsiguranika = p.oznakaOsiguranika
                        WHERE p.mboPacijent = '$mboPacijent' 
                        AND (UPPER(pu.nazivSluzbe) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pu2.nazivSluzbe) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(ko.opisOsiguranika) LIKE UPPER('%{$pretraga}%')
                        OR UPPER(p.nazivPoduzeca) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(p.nazivDrzave) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(p.mboPacijent) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(d.imeDijagnoza) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(d2.imeDijagnoza) LIKE UPPER('%{$pretraga}%')
                        OR UPPER(p.brIskDopunsko) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(p.mkbSifraPrimarna) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(p.mkbSifraSekundarna) LIKE UPPER('%{$pretraga}%')
                        OR UPPER(DATE_FORMAT(p.datumPregled,'%d.%m.%Y')) LIKE UPPER('%{$pretraga}%')) 
                        AND p.prosliPregled IS NOT NULL
                        GROUP BY p.prosliPregled
                        ORDER BY p.datumPregled DESC, p.vrijemePregled DESC 
                        LIMIT 7) 
                        UNION 
                        (SELECT p2.idPregled, DATE_FORMAT(p2.datumPregled,'%d.%m.%Y') AS Datum, 
                        p2.tipSlucaj, p2.vrijemePregled, p2.bojaPregled FROM pregled p2 
                        LEFT JOIN dijagnoze d3 ON d3.mkbSifra = p2.mkbSifraPrimarna 
                        LEFT JOIN dijagnoze d4 ON d4.mkbSifra = p2.mkbSifraSekundarna 
                        LEFT JOIN podrucni_ured pu3 ON pu3.sifUred = p2.podrucniUredHZZO 
                        LEFT JOIN podrucni_ured pu4 ON pu4.sifUred = p2.podrucniUredOzljeda 
                        LEFT JOIN kategorije_osiguranje ko2 ON ko2.oznakaOsiguranika = p2.oznakaOsiguranika
                        WHERE p2.mboPacijent = '$mboPacijent' 
                        AND (UPPER(pu3.nazivSluzbe) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(pu4.nazivSluzbe) LIKE UPPER('%{$pretraga}%')
                        OR UPPER(p2.nazivPoduzeca) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(p2.nazivDrzave) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(ko2.opisOsiguranika) LIKE UPPER('%{$pretraga}%')
                        OR UPPER(p2.mboPacijent) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(p2.brIskDopunsko) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(d3.imeDijagnoza) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(d4.imeDijagnoza) LIKE UPPER('%{$pretraga}%')
                        OR UPPER(p2.mkbSifraPrimarna) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(p2.mkbSifraSekundarna) LIKE UPPER('%{$pretraga}%')
                        OR UPPER(DATE_FORMAT(p2.datumPregled,'%d.%m.%Y')) LIKE UPPER('%{$pretraga}%')) 
                        AND p2.prosliPregled IS NULL
                        ORDER BY p2.datumPregled DESC, p2.vrijemePregled DESC 
                        LIMIT 7)";
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
            $sql = "(SELECT pb.idPovijestBolesti, DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, 
                    pb.tipSlucaj, pb.vrijeme, pb.bojaPregled FROM povijestBolesti pb 
                    WHERE pb.mboPacijent = '$mboPacijent' 
                    AND pb.datum = '$datum' 
                    AND pb.prosliPregled IS NOT NULL
                    GROUP BY pb.prosliPregled
                    ORDER BY pb.vrijeme DESC)
                    UNION 
                    (SELECT pb2.idPovijestBolesti, DATE_FORMAT(pb2.datum,'%d.%m.%Y') AS Datum, 
                    pb2.tipSlucaj, pb2.vrijeme, pb2.bojaPregled FROM povijestBolesti pb2 
                    WHERE pb2.mboPacijent = '$mboPacijent' 
                    AND pb2.datum = '$datum' 
                    AND pb2.prosliPregled IS NULL
                    ORDER BY pb2.vrijeme DESC)";
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
            $sql = "(SELECT p.idPregled, DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, 
                    p.tipSlucaj, p.vrijemePregled, p.bojaPregled FROM pregled p 
                    WHERE p.mboPacijent = '$mboPacijent' 
                    AND p.datumPregled = '$datum' 
                    AND p.prosliPregled IS NOT NULL
                    GROUP BY p.prosliPregled 
                    ORDER BY p.vrijemePregled DESC) 
                    UNION 
                    (SELECT p2.idPregled, DATE_FORMAT(p2.datumPregled,'%d.%m.%Y') AS Datum, 
                    p2.tipSlucaj, p2.vrijemePregled, p2.bojaPregled FROM pregled p2 
                    WHERE p2.mboPacijent = '$mboPacijent' 
                    AND p2.datumPregled = '$datum' 
                    AND p2.prosliPregled IS NULL
                    ORDER BY p2.vrijemePregled DESC)";
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
            $sql = "(SELECT pb.idPovijestBolesti, DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, 
                    pb.tipSlucaj, pb.vrijeme, pb.bojaPregled FROM povijestBolesti pb 
                    WHERE pb.mboPacijent = '$mboPacijent' 
                    AND pb.prosliPregled IS NOT NULL
                    GROUP BY pb.prosliPregled
                    ORDER BY pb.datum DESC, pb.vrijeme DESC
                    LIMIT 7) 
                    UNION 
                    (SELECT pb2.idPovijestBolesti, DATE_FORMAT(pb2.datum,'%d.%m.%Y') AS Datum, 
                    pb2.tipSlucaj, pb2.vrijeme, pb2.bojaPregled FROM povijestBolesti pb2 
                    WHERE pb2.mboPacijent = '$mboPacijent' 
                    AND pb2.prosliPregled IS NULL
                    ORDER BY pb2.datum DESC, pb2.vrijeme DESC
                    LIMIT 7)";
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
            $sql = "(SELECT p.idPregled, DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, 
                    p.tipSlucaj, p.vrijemePregled, p.bojaPregled FROM pregled p 
                    WHERE p.mboPacijent = '$mboPacijent' 
                    AND p.prosliPregled IS NOT NULL
                    GROUP BY p.prosliPregled 
                    ORDER BY p.datumPregled DESC, p.vrijemePregled DESC 
                    LIMIT 7) 
                    UNION 
                    (SELECT p2.idPregled, DATE_FORMAT(p2.datumPregled,'%d.%m.%Y') AS Datum, 
                    p2.tipSlucaj, p2.vrijemePregled, p2.bojaPregled FROM pregled p2 
                    WHERE p2.mboPacijent = '$mboPacijent' 
                    AND p2.prosliPregled IS NULL
                    ORDER BY p2.datumPregled DESC, p2.vrijemePregled DESC 
                    LIMIT 7)";
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