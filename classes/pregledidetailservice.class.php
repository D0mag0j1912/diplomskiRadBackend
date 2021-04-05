<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');
class PreglediDetailService{

    //Funkcija koja dohvaća maksimalni ID pregleda za zadnji pregled za zadanu pretragu
    function dohvatiNajnovijiIDPregledPoPretrazi($tipKorisnik,$mboPacijent,$pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        //Ako je tip korisnika "lijecnik":
        if($tipKorisnik == "lijecnik"){
            $sql = "SELECT pb.idPovijestBolesti FROM povijestBolesti pb 
                    LEFT JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna 
                    LEFT JOIN dijagnoze d2 ON d2.mkbSifra = pb.mkbSifraSekundarna
                    WHERE pb.mboPacijent = '$mboPacijent' 
                    AND (UPPER(pb.razlogDolaska) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb.anamneza) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb.statusPacijent) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb.nalaz) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(TRIM(pb.mkbSifraPrimarna)) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(TRIM(d.imeDijagnoza)) LIKE UPPER('%{$pretraga}%')
                    OR UPPER(TRIM(d2.imeDijagnoza)) LIKE UPPER('%{$pretraga}%')
                    OR UPPER(TRIM(pb.mkbSifraSekundarna)) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb.terapija) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb.preporukaLijecnik) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb.napomena) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(DATE_FORMAT(pb.datum,'%d.%m.%Y')) LIKE UPPER('%{$pretraga}%')) 
                    AND pb.idPovijestBolesti = 
                    (SELECT MAX(pb2.idPovijestBolesti) FROM povijestbolesti pb2 
                    LEFT JOIN dijagnoze d3 ON d3.mkbSifra = pb2.mkbSifraPrimarna 
                    LEFT JOIN dijagnoze d4 ON d4.mkbSifra = pb2.mkbSifraSekundarna
                    WHERE pb2.mboPacijent = '$mboPacijent' 
                    AND (UPPER(pb2.razlogDolaska) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb2.anamneza) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb2.statusPacijent) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb2.nalaz) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(TRIM(pb2.mkbSifraPrimarna)) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(TRIM(d3.imeDijagnoza)) LIKE UPPER('%{$pretraga}%')
                    OR UPPER(TRIM(d4.imeDijagnoza)) LIKE UPPER('%{$pretraga}%')
                    OR UPPER(TRIM(pb2.mkbSifraSekundarna)) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb2.terapija) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb2.preporukaLijecnik) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pb2.napomena) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(DATE_FORMAT(pb2.datum,'%d.%m.%Y')) LIKE UPPER('%{$pretraga}%')))";
            $result = $conn->query($sql);

            //Ako ima pronađenih rezultata za navedenu pretragu
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $idPregled = $row['idPovijestBolesti'];
                }
            }
            else{
                return null;
            }
        }
        //Ako je tip korisnika "sestra":
        else if($tipKorisnik == "sestra"){
            $sql = "SELECT * FROM pregled p 
                    LEFT JOIN dijagnoze d ON d.mkbSifra = p.mkbSifraPrimarna 
                    LEFT JOIN dijagnoze d2 ON d2.mkbSifra = p.mkbSifraSekundarna 
                    LEFT JOIN podrucni_ured pu ON pu.sifUred = p.podrucniUredHZZO 
                    LEFT JOIN podrucni_ured pu2 ON pu2.sifUred = p.podrucniUredOzljeda 
                    LEFT JOIN kategorije_osiguranje ko ON ko.oznakaOsiguranika = p.oznakaOsiguranika
                    WHERE p.mboPacijent = '$mboPacijent' 
                    AND (UPPER(pu.nazivSluzbe) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(pu2.nazivSluzbe) LIKE UPPER('%{$pretraga}%')
                    OR UPPER(p.nazivPoduzeca) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(ko.opisOsiguranika) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(p.nazivDrzave) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(TRIM(p.mkbSifraPrimarna)) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(TRIM(d.imeDijagnoza)) LIKE UPPER('%{$pretraga}%')
                    OR UPPER(TRIM(p.mkbSifraSekundarna)) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(TRIM(d2.imeDijagnoza)) LIKE UPPER('%{$pretraga}%')
                    OR UPPER(DATE_FORMAT(p.datumPregled,'%d.%m.%Y')) LIKE UPPER('%{$pretraga}%')) 
                    AND p.idPregled = 
                    (SELECT MAX(p2.idPregled) FROM pregled p2 
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
                    OR UPPER(TRIM(p2.mkbSifraPrimarna)) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(TRIM(d3.imeDijagnoza)) LIKE UPPER('%{$pretraga}%')
                    OR UPPER(TRIM(p2.mkbSifraSekundarna)) LIKE UPPER('%{$pretraga}%') 
                    OR UPPER(TRIM(d4.imeDijagnoza)) LIKE UPPER('%{$pretraga}%')
                    OR UPPER(DATE_FORMAT(p2.datumPregled,'%d.%m.%Y')) LIKE UPPER('%{$pretraga}%')))"; 
            $result = $conn->query($sql);

            //Ako ima pronađenih rezultata za navedenu pretragu
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $idPregled = $row['idPregled'];
                }
            }
            else{
                return null;
            }
        }
        return $idPregled;
    } 

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
                    //Spremam podatke koji mi trebaju za dohvat MAKSIMALNOG ID-a zadnjeg pregleda
                    $mkbSifraPrimarna = $row['mkbSifraPrimarna'];
                    $tipSlucaj = $row['tipSlucaj'];
                    $idObradaLijecnik = $row['idObradaLijecnik'];
                    $vrijeme = $row['vrijeme'];

                    //Kreiram upit kojim dohvaćam MAKSIMALNI ID zadnjeg evidentiranog pregleda
                    $sqlMinID = "SELECT pb.idPovijestBolesti FROM povijestBolesti pb 
                                WHERE pb.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                                AND pb.tipSlucaj = '$tipSlucaj' 
                                AND pb.datum = '$datum' 
                                AND pb.idObradaLijecnik = '$idObradaLijecnik' 
                                AND pb.vrijeme = '$vrijeme' 
                                AND pb.idPovijestBolesti = 
                                (SELECT MAX(pb2.idPovijestBolesti) FROM povijestBolesti pb2 
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
                            //Spremam MAKSIMALNI ID povijesti bolesti
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
                    //Spremam podatke koji mi trebaju za dohvat MAKSIMALNOG ID-a zadnjeg pregleda
                    $mkbSifraPrimarna = $row['mkbSifraPrimarna'];
                    $tipSlucaj = $row['tipSlucaj'];
                    $idObradaMedSestra = $row['idObradaMedSestra'];
                    $vrijemePregled = $row['vrijemePregled'];
                    //Ako je upisana MKB šifra primarne dijagnoze
                    if(!empty($mkbSifraPrimarna)){
                        //Kreiram upit kojim dohvaćam MAKSIMALNI ID zadnjeg evidentiranog pregleda
                        $sqlMinID = "SELECT p.idPregled FROM pregled p 
                                    WHERE p.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                                    AND p.tipSlucaj = '$tipSlucaj' 
                                    AND p.datumPregled = '$datum' 
                                    AND p.idObradaMedSestra = '$idObradaMedSestra' 
                                    AND p.vrijemePregled = '$vrijemePregled' 
                                    AND p.idPregled = 
                                    (SELECT MAX(p2.idPregled) FROM pregled p2 
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
                                //Spremam MAKSIMALNI ID pregleda
                                $idPregled = $rowMinID['idPregled'];
                            }
                        }
                    }
                    //Ako nije upisana MKB šifra primarne dijagnoze
                    else{
                        //Kreiram upit kojim dohvaćam MAKSIMALNI ID zadnjeg evidentiranog pregleda
                        $sqlMinID = "SELECT p.idPregled FROM pregled p 
                                    WHERE p.mkbSifraPrimarna IS NULL 
                                    AND p.tipSlucaj = '$tipSlucaj' 
                                    AND p.datumPregled = '$datum' 
                                    AND p.idObradaMedSestra = '$idObradaMedSestra' 
                                    AND p.vrijemePregled = '$vrijemePregled' 
                                    AND p.idPregled = 
                                    (SELECT MAX(p2.idPregled) FROM pregled p2 
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
                                //Spremam MAKSIMALNI ID pregleda
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
                    //Spremam podatke koji mi trebaju za dohvat MAKSIMALNOG ID-a zadnjeg pregleda
                    $mkbSifraPrimarna = $row['mkbSifraPrimarna'];
                    $tipSlucaj = $row['tipSlucaj'];
                    $datumPregled = $row['datumPregled'];
                    $idObradaMedSestra = $row['idObradaMedSestra'];
                    $vrijemePregled = $row['vrijemePregled'];
                    //Ako je upisana MKB šifra primarne dijagnoze
                    if(!empty($mkbSifraPrimarna)){
                        //Kreiram upit kojim dohvaćam MAKSIMALNI ID zadnjeg evidentiranog pregleda
                        $sqlMinID = "SELECT p.idPregled FROM pregled p 
                                    WHERE p.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                                    AND p.tipSlucaj = '$tipSlucaj' 
                                    AND p.datumPregled = '$datumPregled' 
                                    AND p.idObradaMedSestra = '$idObradaMedSestra' 
                                    AND p.vrijemePregled = '$vrijemePregled' 
                                    AND p.idPregled = 
                                    (SELECT MAX(p2.idPregled) FROM pregled p2 
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
                                //Spremam MAKSIMALNI ID pregleda
                                $idPregled = $rowMinID['idPregled'];
                            }
                        }
                    }
                    //Ako nije upisana MKB šifra primarne dijagnoze
                    else{
                        //Kreiram upit kojim dohvaćam MAKSIMALNI ID zadnjeg evidentiranog pregleda
                        $sqlMinID = "SELECT p.idPregled FROM pregled p 
                                    WHERE p.mkbSifraPrimarna IS NULL 
                                    AND p.tipSlucaj = '$tipSlucaj' 
                                    AND p.datumPregled = '$datumPregled' 
                                    AND p.idObradaMedSestra = '$idObradaMedSestra' 
                                    AND p.vrijemePregled = '$vrijemePregled' 
                                    AND p.idPregled = 
                                    (SELECT MAX(p2.idPregled) FROM pregled p2 
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
                                //Spremam MAKSIMALNI ID pregleda
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
                    //Spremam podatke koji mi trebaju za dohvat MAKSIMALNOG ID-a zadnjeg pregleda
                    $mkbSifraPrimarna = $row['mkbSifraPrimarna'];
                    $tipSlucaj = $row['tipSlucaj'];
                    $datum = $row['datum'];
                    $idObradaLijecnik = $row['idObradaLijecnik'];
                    $vrijeme = $row['vrijeme'];

                    //Kreiram upit kojim dohvaćam MAKSIMALNI ID zadnjeg evidentiranog pregleda
                    $sqlMinID = "SELECT pb.idPovijestBolesti FROM povijestBolesti pb 
                                WHERE pb.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                                AND pb.tipSlucaj = '$tipSlucaj' 
                                AND pb.datum = '$datum' 
                                AND pb.idObradaLijecnik = '$idObradaLijecnik' 
                                AND pb.vrijeme = '$vrijeme' 
                                AND pb.idPovijestBolesti = 
                                (SELECT MAX(pb2.idPovijestBolesti) FROM povijestBolesti pb2 
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
                            //Spremam MAKSIMALNI ID povijesti bolesti
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
                    CONCAT((SELECT TRIM(d.imeDijagnoza) FROM dijagnoze d 
                            WHERE d.mkbSifra = pb.mkbSifraSekundarna),' [',TRIM(pb.mkbSifraSekundarna),']')) AS sekundarneDijagnoze FROM povijestbolesti pb 
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
                    CONCAT((SELECT TRIM(d.imeDijagnoza) FROM dijagnoze d 
                            WHERE d.mkbSifra = p.mkbSifraSekundarna),' [',TRIM(p.mkbSifraSekundarna),']')) AS sekundarneDijagnoze FROM pregled p 
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
                    CONCAT(TRIM(d.imeDijagnoza),' [',TRIM(pb.mkbSifraPrimarna),']') AS primarnaDijagnoza,
                    pb.terapija, pb.preporukaLijecnik, pb.napomena, kor.tip,
                    pb.datum, pb.vrijeme, pb.tipSlucaj, TRIM(pb.mkbSifraPrimarna) AS mkbSifraPrimarna,
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
                                                                JOIN recept r ON r.sifraSpecijalist = zr.sifraSpecijalist 
                                                                JOIN povijestbolesti pb ON pb.idRecept = r.idRecept 
                                                                WHERE pb.idPovijestBolesti = '$id'),' [',r.sifraSpecijalist,']') FROM recept r 
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
                                                                WHERE d.mkbSifra = p.mkbSifraPrimarna),' [',TRIM(p.mkbSifraPrimarna),']')) AS primarnaDijagnoza, kor.tip,
                    p.datumPregled, p.vrijemePregled, p.tipSlucaj, TRIM(p.mkbSifraPrimarna) AS mkbSifraPrimarna
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
}
?>