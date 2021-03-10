<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class PreglediService{

    //Funkcija koja dohvaća sve sekundarne dijagnoze na osnovu ID-a pregleda ili ID-a povijesti bolesti
    function dohvatiSekundarneDijagnoze($datum,$vrijeme,$mkbSifraPrimarna,$tipSlucaj,$idPacijent,$tipKorisnik){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        //Kreiram upit za dohvaćanjem MBO-a pacijenta kojemu se upisiva povijest bolesti
        $sqlMBO = "SELECT p.mboPacijent AS MBO FROM pacijent p 
                WHERE p.idPacijent = '$idPacijent'";
        //Rezultat upita spremam u varijablu $resultMBO
        $resultMBO = mysqli_query($conn,$sqlMBO);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultMBO) > 0){
            //Idem redak po redak rezultata upita 
            while($rowMBO = mysqli_fetch_assoc($resultMBO)){
                //Vrijednost rezultata spremam u varijablu $mboPacijent
                $mboPacijent = $rowMBO['MBO'];
            }
        } 
        //Ako je tip korisnika "liječnik":
        if($tipKorisnik == "lijecnik"){
            //Kreiram upit koji dohvaća sve sekundarne dijagnoze
            $sql = "SELECT IF(pb.mkbSifraSekundarna IS NULL, NULL, 
                    CONCAT(pb.mkbSifraSekundarna,' | ', (SELECT d.imeDijagnoza FROM dijagnoze d 
                                                        WHERE d.mkbSifra = pb.mkbSifraSekundarna))) AS sekundarneDijagnoze FROM povijestbolesti pb 
                    WHERE pb.datum = '$datum' 
                    AND pb.vrijeme = '$vrijeme' 
                    AND pb.tipSlucaj = '$tipSlucaj' 
                    AND pb.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                    AND pb.mboPacijent = '$mboPacijent';";
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
            //Kreiram upit koji dohvaća sve sekundarne dijagnoze
            $sql = "SELECT IF(p.mkbSifraSekundarna IS NULL, NULL, 
                    CONCAT(p.mkbSifraSekundarna,' | ', (SELECT d.imeDijagnoza FROM dijagnoze d 
                                                        WHERE d.mkbSifra = p.mkbSifraSekundarna))) AS sekundarneDijagnoze FROM pregled p 
                    WHERE p.datumPregled = '$datum' 
                    AND p.vrijemePregled = '$vrijeme' 
                    AND p.tipSlucaj = '$tipSlucaj' 
                    AND p.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                    AND p.mboPacijent = '$mboPacijent';";
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
                    pb.statusPacijent, pb.nalaz, 
                    CONCAT(d.imeDijagnoza,' [',pb.mkbSifraPrimarna,']') AS primarnaDijagnoza,
                    pb.terapija, pb.preporukaLijecnik, pb.napomena, kor.tip,
                    pb.datum, pb.vrijeme, pb.tipSlucaj, pb.mkbSifraPrimarna FROM povijestBolesti pb 
                    JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna
                    JOIN ambulanta a ON a.idPovijestBolesti = pb.idPovijestBolesti 
                    JOIN lijecnik l ON l.idLijecnik = a.idLijecnik 
                    JOIN korisnik kor ON kor.idKorisnik = l.idKorisnik
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
            $sql = "SELECT p.idPregled, 
                    CASE 
                        WHEN p.nacinPlacanja = 'hzzo' THEN (SELECT CONCAT('HZZO (',pu.nazivSluzbe,' [',p2.podrucniUredHZZO,'])') FROM pregled p2 
                                                        JOIN podrucni_ured pu ON pu.sifUred = p2.podrucniUredHZZO 
                                                        WHERE p2.idPregled = '$id') 
                        WHEN p.nacinPlacanja = 'ozljeda' THEN (SELECT CONCAT('Ozljeda (',pu.nazivSluzbe,' [',p2.podrucniUredOzljeda,'])') FROM pregled p2 
                                                            JOIN podrucni_ured pu ON pu.sifUred = p2.podrucniUredOzljeda 
                                                            WHERE p2.idPregled = '$id')
                        WHEN p.nacinPlacanja = 'poduzece' THEN CONCAT('Naziv poduzeća: ',p.nazivPoduzeca)
                        WHEN p.nacinPlacanja = 'osobno' THEN p.nacinPlacanja
                    END AS nacinPlacanja,
                    CONCAT(k.opisOsiguranika,' [',p.oznakaOsiguranika,']') AS oznakaOsiguranika,
                    p.nazivDrzave,
                    CONCAT(d.imeDijagnoza,' [',p.mkbSifraPrimarna,']') AS primarnaDijagnoza, kor.tip,
                    p.datumPregled, p.vrijemePregled, p.tipSlucaj, p.mkbSifraPrimarna
                    FROM pregled p 
                    JOIN kategorije_osiguranje k ON k.oznakaOsiguranika = p.oznakaOsiguranika 
                    JOIN dijagnoze d ON d.mkbSifra = p.mkbSifraPrimarna
                    JOIN ambulanta a ON a.idPregled = p.idPregled 
                    JOIN med_sestra m ON m.idMedSestra = a.idMedSestra 
                    JOIN korisnik kor ON kor.idKorisnik = m.idKorisnik 
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