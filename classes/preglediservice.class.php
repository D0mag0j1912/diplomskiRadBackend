<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class PreglediService{

    //Funkcija koja dohvaća DATUM najnovijeg pregleda da ga mogu uskladiti filter sa najvišim elementom liste pregleda
    function dohvatiNajnovijiDatum($tipKorisnik,$mboPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Ako je tip korisnika "lijecnik":
        if($tipKorisnik == "lijecnik"){
            //Kreiram upit koji dohvaća najnoviji datum povijesti bolesti
            $sql = "SELECT pb.datum FROM povijestBolesti pb 
                    WHERE pb.mboPacijent = '$mboPacijent' 
                    AND pb.idPovijestBolesti = 
                    (SELECT MAX(pb2.idPovijestBolesti) FROM povijestBolesti pb2 
                    WHERE pb2.mboPacijent = '$mboPacijent')"; 
            //Rezultat upita spremam u varijablu $result
            $result = mysqli_query($conn,$sql);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($result) > 0){
                //Idem redak po redak rezultata upita 
                while($row = mysqli_fetch_assoc($result)){
                    //Dohvaćam najnoviji datum
                    $datum = $row['datum'];
                }
            }
            //Ako ovaj pacijent NEMA evidentiranih povijesti bolesti
            else{
                //Vraćam današnji datum
                $datum = date('Y-m-d');
            }
        }  
        //Ako je tip korisnika "sestra":
        else if($tipKorisnik == "sestra"){
            //Kreiram upit koji dohvaća najnoviji datum povijesti bolesti
            $sql = "SELECT p.datumPregled FROM pregled p 
                    WHERE p.mboPacijent = '$mboPacijent' 
                    AND p.idPregled = 
                    (SELECT MAX(p2.idPregled) FROM pregled p2 
                    WHERE p2.mboPacijent = '$mboPacijent')"; 
            //Rezultat upita spremam u varijablu $result
            $result = mysqli_query($conn,$sql);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($result) > 0){
                //Idem redak po redak rezultata upita 
                while($row = mysqli_fetch_assoc($result)){
                    //Dohvaćam najnoviji datum
                    $datum = $row['datumPregled'];
                }
            }
            //Ako ovaj pacijent NEMA evidentiranih povijesti bolesti
            else{
                //Vraćam današnji datum
                $datum = date('Y-m-d');
            }
        }
        return $datum; 
    }

    //Funkcija koja dohvaća ID najnovijeg pregleda ovisno o tipu korisnika
    function dohvatiNajnovijiIDPregled($tipKorisnik,$mboPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Ako je prijavljena medicinska sestra:
        if($tipKorisnik == "sestra"){
            $sql = "SELECT * FROM pregled p
                    WHERE p.mboPacijent = '$mboPacijent'
                    AND p.idPregled = 
                    (SELECT MAX(p2.idPregled) FROM pregled p2
                    WHERE p2.mboPacijent = '$mboPacijent')";
            //Rezultat upita spremam u varijablu $result
            $result = mysqli_query($conn,$sql);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($result) > 0){
                //Idem redak po redak rezultata upita 
                while($row = mysqli_fetch_assoc($result)){
                    //Spremam podatke koji mi trebaju za dohvat MINIMALNOG ID-a zadnjeg pregleda
                    $mkbSifraPrimarna = $row['mkbSifraPrimarna'];
                    $tipSlucaj = $row['tipSlucaj'];
                    $datumPregled = $row['datumPregled'];
                    $idObradaMedSestra = $row['idObradaMedSestra'];
                    $vrijemePregled = $row['vrijemePregled'];
                    //Ako je upisana MKB šifra primarne dijagnoze
                    if(!empty($mkbSifraPrimarna)){
                        //Kreiram upit kojim dohvaćam MINIMALNI ID zadnjeg evidentiranog pregleda
                        $sqlMinID = "SELECT p.idPregled FROM pregled p 
                                    WHERE p.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                                    AND p.tipSlucaj = '$tipSlucaj' 
                                    AND p.datumPregled = '$datumPregled' 
                                    AND p.idObradaMedSestra = '$idObradaMedSestra' 
                                    AND p.vrijemePregled = '$vrijemePregled' 
                                    AND p.idPregled = 
                                    (SELECT MIN(p2.idPregled) FROM pregled p2 
                                    WHERE p2.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                                    AND p2.tipSlucaj = '$tipSlucaj' 
                                    AND p2.datumPregled = '$datumPregled' 
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
                                    AND p.datumPregled = '$datumPregled' 
                                    AND p.idObradaMedSestra = '$idObradaMedSestra' 
                                    AND p.vrijemePregled = '$vrijemePregled' 
                                    AND p.idPregled = 
                                    (SELECT MIN(p2.idPregled) FROM pregled p2 
                                    WHERE p2.mkbSifraPrimarna IS NULL 
                                    AND p2.tipSlucaj = '$tipSlucaj' 
                                    AND p2.datumPregled = '$datumPregled' 
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
            //Ako nema evidentiranih pregleda za ovog pacijenta
            else{
                return null;
            }
        }
        //Ako je prijavljen liječnik:
        else if($tipKorisnik == "lijecnik"){
            $sql = "SELECT * FROM povijestBolesti pb
                    WHERE pb.mboPacijent = '$mboPacijent'
                    AND pb.idPovijestBolesti = 
                    (SELECT MAX(pb2.idPovijestBolesti) FROM povijestBolesti pb2
                    WHERE pb2.mboPacijent = '$mboPacijent')";
            //Rezultat upita spremam u varijablu $result
            $result = mysqli_query($conn,$sql);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($result) > 0){
                //Idem redak po redak rezultata upita 
                while($row = mysqli_fetch_assoc($result)){
                    //Spremam podatke koji mi trebaju za dohvat MINIMALNOG ID-a zadnjeg pregleda
                    $mkbSifraPrimarna = $row['mkbSifraPrimarna'];
                    $tipSlucaj = $row['tipSlucaj'];
                    $datum = $row['datum'];
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
        return $idPregled;
    }

    //Funkcija koja dohvaća sve sekundarne dijagnoze na osnovu ID-a pregleda ili ID-a povijesti bolesti
    function dohvatiSekundarneDijagnoze($datum,$vrijeme,$mkbSifraPrimarna,$tipSlucaj,$mboPacijent,$tipKorisnik){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        //Ako je tip korisnika "liječnik":
        if($tipKorisnik == "lijecnik"){
            //Kreiram upit koji dohvaća sve sekundarne dijagnoze
            $sql = "SELECT IF(pb.mkbSifraSekundarna IS NULL, NULL, 
                    CONCAT(pb.mkbSifraSekundarna,' | ', (SELECT TRIM(d.imeDijagnoza) FROM dijagnoze d 
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
                    CONCAT(p.mkbSifraSekundarna,' | ', (SELECT TRIM(d.imeDijagnoza) FROM dijagnoze d 
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
                    CONCAT(TRIM(d.imeDijagnoza),' [',pb.mkbSifraPrimarna,']') AS primarnaDijagnoza,
                    pb.terapija, pb.preporukaLijecnik, pb.napomena, kor.tip,
                    pb.datum, pb.vrijeme, pb.tipSlucaj, pb.mkbSifraPrimarna,
                    IF(pb.idRecept IS NULL, NULL, (SELECT r.proizvod FROM recept r 
                                                JOIN povijestbolesti pb ON pb.idRecept = r.idRecept 
                                                WHERE pb.idPovijestBolesti = '$id')) AS proizvod,
                    IF(pb.idRecept IS NULL, NULL, (SELECT r.oblikJacinaPakiranjeLijek FROM recept r 
                                                JOIN povijestbolesti pb ON pb.idRecept = r.idRecept 
                                                WHERE pb.idPovijestBolesti = '$id')) AS oblikJacinaPakiranjeLijek,
                    IF(pb.idRecept IS NULL, NULL, (SELECT r.kolicina FROM recept r 
                                                JOIN povijestbolesti pb ON pb.idRecept = r.idRecept 
                                                WHERE pb.idPovijestBolesti = '$id')) AS kolicina,
                    IF(pb.idRecept IS NULL, NULL, (SELECT r.doziranje FROM recept r 
                                                JOIN povijestbolesti pb ON pb.idRecept = r.idRecept 
                                                WHERE pb.idPovijestBolesti = '$id')) AS doziranje,
                    IF(pb.idRecept IS NULL, NULL, (SELECT r.dostatnost FROM recept r 
                                                JOIN povijestbolesti pb ON pb.idRecept = r.idRecept 
                                                WHERE pb.idPovijestBolesti = '$id')) AS dostatnost,
                    IF(pb.idRecept IS NULL, NULL, (SELECT r.hitnost FROM recept r 
                                                JOIN povijestbolesti pb ON pb.idRecept = r.idRecept 
                                                WHERE pb.idPovijestBolesti = '$id')) AS hitnost,
                    IF(pb.idRecept IS NULL, NULL, (SELECT r.ponovljiv FROM recept r 
                                                JOIN povijestbolesti pb ON pb.idRecept = r.idRecept 
                                                WHERE pb.idPovijestBolesti = '$id')) AS ponovljiv,
                    IF(pb.idRecept IS NULL, NULL, (SELECT r.brojPonavljanja FROM recept r 
                                                JOIN povijestbolesti pb ON pb.idRecept = r.idRecept 
                                                WHERE pb.idPovijestBolesti = '$id')) AS brojPonavljanja,
                    IF(pb.idRecept IS NULL, NULL, (SELECT CONCAT((SELECT DISTINCT(zr.tipSpecijalist) FROM zdr_radnici zr 
                                                                JOIN recept r ON r.sifraSpecijalist = zr.sifraSpecijalist),' [',r.sifraSpecijalist,']') FROM recept r 
                                                JOIN povijestbolesti pb ON pb.idRecept = r.idRecept 
                                                WHERE pb.idPovijestBolesti = '$id')) AS specijalist FROM povijestBolesti pb 
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
                    IF(p.mkbSifraPrimarna IS NULL, NULL, CONCAT((SELECT TRIM(d.imeDijagnoza) FROM dijagnoze d 
                                                                WHERE d.mkbSifra = p.mkbSifraPrimarna),' [',p.mkbSifraPrimarna,']')) AS primarnaDijagnoza, kor.tip,
                    p.datumPregled, p.vrijemePregled, p.tipSlucaj, p.mkbSifraPrimarna
                    FROM pregled p 
                    JOIN kategorije_osiguranje k ON k.oznakaOsiguranika = p.oznakaOsiguranika 
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
    
    //Funkcija koja vraća MBO pacijenta na osnovu njegovog ID-a
    function getMBO($idPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
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
        return $mboPacijent;
    }
}
?>