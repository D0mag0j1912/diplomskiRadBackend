<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\diplomskiBackend\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');
class PreglediListService{

    //Funkcija koja provjerava jeli ima dva ili više pregleda iz iste grupacije
    function provjeriIstuGrupaciju($tipKorisnik,$ids){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        //Ako je tip korisnika "lijecnik":
        if($tipKorisnik == "lijecnik"){
            foreach($ids as $idPregled){
                $sql = "SELECT * FROM povijestBolesti pb 
                        WHERE pb.idPovijestBolesti = '$idPregled'";
                //Rezultat upita spremam u varijablu $result
                $result = mysqli_query($conn,$sql);
                //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                if(mysqli_num_rows($result) > 0){
                    //Idem redak po redak rezultata upita 
                    while($row = mysqli_fetch_assoc($result)){
                        $mkbSifraPrimarna = $row['mkbSifraPrimarna'];
                        $idObradaLijecnik = $row['idObradaLijecnik'];
                        $datum = $row['datum'];
                        $vrijeme = $row['vrijeme'];
                        $mboPacijent = $row['mboPacijent'];
                        $tipSlucaj = $row['tipSlucaj'];
                        //Kreiram upit koji dohvaća sve ID-ove pregleda koji se nalaze u grupaciji trenutnog ID-a pregleda, ne uključujući njega npr. [505] ako je 504 trenutni ID
                        $sqlIDS = "SELECT pb.idPovijestBolesti FROM povijestBolesti pb 
                                    WHERE pb.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                                    AND pb.idObradaLijecnik = '$idObradaLijecnik'
                                    AND pb.datum = '$datum' 
                                    AND pb.vrijeme = '$vrijeme' 
                                    AND pb.mboPacijent = '$mboPacijent' 
                                    AND pb.tipSlucaj = '$tipSlucaj' 
                                    AND pb.idPovijestBolesti != '$idPregled'";
                        //Rezultat upita spremam u varijablu $result
                        $resultIDS = mysqli_query($conn,$sqlIDS);
                        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                        if(mysqli_num_rows($resultIDS) > 0){
                            //Idem redak po redak rezultata upita 
                            while($rowIDS = mysqli_fetch_assoc($resultIDS)){
                                //Kreiram upit koji dohvaća MAX ID pregleda grupacije pregleda koji se trenutno gleda
                                $sqlMAX = "SELECT pb.idPovijestBolesti FROM povijestBolesti pb 
                                        WHERE pb.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                                        AND pb.idObradaLijecnik = '$idObradaLijecnik'
                                        AND pb.datum = '$datum' 
                                        AND pb.vrijeme = '$vrijeme' 
                                        AND pb.mboPacijent = '$mboPacijent' 
                                        AND pb.tipSlucaj = '$tipSlucaj' 
                                        AND pb.idPovijestBolesti = 
                                        (SELECT MAX(pb2.idPovijestBolesti) FROM povijestBolesti pb2 
                                        WHERE pb2.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                                        AND pb2.idObradaLijecnik = '$idObradaLijecnik'
                                        AND pb2.datum = '$datum' 
                                        AND pb2.vrijeme = '$vrijeme' 
                                        AND pb2.mboPacijent = '$mboPacijent' 
                                        AND pb2.tipSlucaj = '$tipSlucaj')";
                                //Rezultat upita spremam u varijablu $result
                                $resultMAX = mysqli_query($conn,$sqlMAX);
                                //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                                if(mysqli_num_rows($resultMAX) > 0){
                                    //Idem redak po redak rezultata upita 
                                    while($rowMAX = mysqli_fetch_assoc($resultMAX)){
                                        //Ako se pronađeni pregled iz baze nalazi već u pregledima koji su poslani sa frontenda [504,503,505] te je upravo on maksimalni u toj grupaciji
                                        if(in_array($rowIDS['idPovijestBolesti'],$ids) && $rowIDS['idPovijestBolesti'] == $rowMAX['idPovijestBolesti']){
                                            $response[] = $idPregled;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        //Ako je tip korisnika "sestra":
        else if($tipKorisnik == "sestra"){
            foreach($ids as $idPregled){
                $sql = "SELECT * FROM pregled p 
                        WHERE p.idPregled = '$idPregled'";
                //Rezultat upita spremam u varijablu $result
                $result = mysqli_query($conn,$sql);
                //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                if(mysqli_num_rows($result) > 0){
                    //Idem redak po redak rezultata upita 
                    while($row = mysqli_fetch_assoc($result)){
                        $mkbSifraPrimarna = $row['mkbSifraPrimarna'];
                        $idObradaMedSestra = $row['idObradaMedSestra'];
                        $datum = $row['datumPregled'];
                        $vrijeme = $row['vrijemePregled'];
                        $mboPacijent = $row['mboPacijent'];
                        $tipSlucaj = $row['tipSlucaj'];
                        //Kreiram upit koji dohvaća sve ID-ove pregleda koji se nalaze u grupaciji trenutnog ID-a pregleda, ne uključujući njega npr. [505] ako je 504 trenutni ID
                        $sqlIDS = "SELECT p.idPregled FROM pregled p 
                                    WHERE p.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                                    AND p.idObradaMedSestra = '$idObradaMedSestra'
                                    AND p.datumPregled = '$datum' 
                                    AND p.vrijemePregled = '$vrijeme' 
                                    AND p.mboPacijent = '$mboPacijent' 
                                    AND p.tipSlucaj = '$tipSlucaj' 
                                    AND p.idPregled != '$idPregled'";
                        //Rezultat upita spremam u varijablu $result
                        $resultIDS = mysqli_query($conn,$sqlIDS);
                        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                        if(mysqli_num_rows($resultIDS) > 0){
                            //Idem redak po redak rezultata upita 
                            while($rowIDS = mysqli_fetch_assoc($resultIDS)){
                                //Kreiram upit koji dohvaća MAX ID pregleda grupacije pregleda koji se trenutno gleda
                                $sqlMAX = "SELECT p.idPregled FROM pregled p 
                                        WHERE p.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                                        AND p.idObradaMedSestra = '$idObradaMedSestra'
                                        AND p.datumPregled = '$datum' 
                                        AND p.vrijemePregled = '$vrijeme' 
                                        AND p.mboPacijent = '$mboPacijent' 
                                        AND p.tipSlucaj = '$tipSlucaj' 
                                        AND p.idPregled = 
                                        (SELECT MAX(p2.idPregled) FROM pregled p2 
                                        WHERE p2.mkbSifraPrimarna = '$mkbSifraPrimarna' 
                                        AND p2.idObradaMedSestra = '$idObradaMedSestra'
                                        AND p2.datumPregled = '$datum' 
                                        AND p2.vrijemePregled = '$vrijeme' 
                                        AND p2.mboPacijent = '$mboPacijent' 
                                        AND p2.tipSlucaj = '$tipSlucaj')";
                                //Rezultat upita spremam u varijablu $result
                                $resultMAX = mysqli_query($conn,$sqlMAX);
                                //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                                if(mysqli_num_rows($resultMAX) > 0){
                                    //Idem redak po redak rezultata upita 
                                    while($rowMAX = mysqli_fetch_assoc($resultMAX)){
                                        //Ako se pronađeni pregled iz baze nalazi već u pregledima koji su poslani sa frontenda [504,503,505] te je upravo on maksimalni u toj grupaciji
                                        if(in_array($rowIDS['idPregled'],$ids) && $rowIDS['idPregled'] == $rowMAX['idPregled']){
                                            $response[] = $idPregled;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $response;
    }

    //Funkcija koja dohvaća pregled koji se trenutno NE NALAZI u listi pregleda, ali ga je drugi element zatražio klikom na "Sljedeći pregled"
    function dohvatiTrazeniPregled($tipKorisnik,$idPregled){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];
        //Inicijaliziram varijablu novog slučaja 
        $noviSlucaj = "noviSlucaj";
        //Inicijaliziram varijablu povezanog slučaja
        $povezanSlucaj = "povezanSlucaj";
        //Ako je tip korisnika "lijecnik":
        if($tipKorisnik == "lijecnik"){
            //Kreiram upit koji će dohvatiti sve povijesti bolesti 
            $sql = "(SELECT pb.idPovijestBolesti, DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, 
                    pb.tipSlucaj, pb.vrijeme, pb.bojaPregled, 
                    CASE 
                        WHEN pb.tipSlucaj = '$noviSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                            WHERE pb2.idPovijestBolesti IN 
                                                            (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                            WHERE pb3.prosliPregled = pb.idPovijestBolesti 
                                                            GROUP BY pb3.prosliPregled)
                                                            LIMIT 1) 
                        WHEN pb.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                        WHERE pb2.idPovijestBolesti IN 
                                                        (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                        WHERE pb3.prosliPregled = pb.idPovijestBolesti 
                                                        GROUP BY pb3.prosliPregled)
                                                        LIMIT 1)
                    END AS sljedeciPregled,
                    CASE 
                        WHEN pb.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                                WHERE pb2.tipSlucaj = '$noviSlucaj' 
                                                                AND pb2.idPovijestBolesti IN 
                                                                (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                                WHERE pb3.tipSlucaj = '$noviSlucaj' 
                                                                AND pb3.idPovijestBolesti = pb.prosliPregled 
                                                                GROUP BY pb3.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniNoviSlucaj,
                    CASE 
                        WHEN pb.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                                WHERE pb2.tipSlucaj = '$povezanSlucaj' 
                                                                AND pb2.idPovijestBolesti IN 
                                                                (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                                WHERE pb3.tipSlucaj = '$povezanSlucaj' 
                                                                AND pb3.idPovijestBolesti = pb.prosliPregled 
                                                                GROUP BY pb3.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniPovezanSlucaj FROM povijestBolesti pb 
                    WHERE pb.idPovijestBolesti = '$idPregled') 
                    UNION 
                    (SELECT pb2.idPovijestBolesti, DATE_FORMAT(pb2.datum,'%d.%m.%Y') AS Datum, 
                    pb2.tipSlucaj, pb2.vrijeme, pb2.bojaPregled, 
                    CASE 
                        WHEN pb2.tipSlucaj = '$noviSlucaj' THEN (SELECT pb3.idPovijestBolesti FROM povijestbolesti pb3 
                                                            WHERE pb3.idPovijestBolesti IN 
                                                            (SELECT MAX(pb4.idPovijestBolesti) FROM povijestbolesti pb4 
                                                            WHERE pb4.prosliPregled = pb2.idPovijestBolesti 
                                                            GROUP BY pb4.prosliPregled)
                                                            LIMIT 1)
                        WHEN pb2.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb3.idPovijestBolesti FROM povijestbolesti pb3 
                                                                WHERE pb3.idPovijestBolesti IN 
                                                                (SELECT MAX(pb4.idPovijestBolesti) FROM povijestbolesti pb4 
                                                                WHERE pb4.prosliPregled = pb2.idPovijestBolesti 
                                                                GROUP BY pb4.prosliPregled) 
                                                                LIMIT 1)
                    END AS sljedeciPregled,
                    CASE 
                        WHEN pb2.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb3.idPovijestBolesti FROM povijestbolesti pb3 
                                                                WHERE pb3.tipSlucaj = '$noviSlucaj' 
                                                                AND pb3.idPovijestBolesti IN 
                                                                (SELECT MAX(pb4.idPovijestBolesti) FROM povijestbolesti pb4 
                                                                WHERE pb4.tipSlucaj = '$noviSlucaj' 
                                                                AND pb4.idPovijestBolesti = pb2.prosliPregled 
                                                                GROUP BY pb4.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniNoviSlucaj,
                    CASE 
                        WHEN pb2.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb3.idPovijestBolesti FROM povijestbolesti pb3 
                                                                WHERE pb3.tipSlucaj = '$povezanSlucaj' 
                                                                AND pb3.idPovijestBolesti IN 
                                                                (SELECT MAX(pb4.idPovijestBolesti) FROM povijestbolesti pb4 
                                                                WHERE pb4.tipSlucaj = '$povezanSlucaj' 
                                                                AND pb4.idPovijestBolesti = pb2.prosliPregled 
                                                                GROUP BY pb4.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniPovezanSlucaj FROM povijestBolesti pb2 
                    WHERE pb2.idPovijestBolesti = '$idPregled')";
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
            $sql = "(SELECT p.idPregled, DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, 
                    p.tipSlucaj, p.vrijemePregled, p.bojaPregled, 
                    CASE 
                        WHEN p.tipSlucaj = '$noviSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                            WHERE p2.idPregled IN 
                                                            (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                            WHERE p3.prosliPregled = p.idPregled 
                                                            GROUP BY p3.prosliPregled)
                                                            LIMIT 1) 
                        WHEN p.tipSlucaj = '$povezanSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                                WHERE p2.idPregled IN 
                                                                (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                                WHERE p3.prosliPregled = p.idPregled 
                                                                GROUP BY p3.prosliPregled)
                                                                LIMIT 1)
                    END AS sljedeciPregled,
                    CASE 
                        WHEN p.tipSlucaj = '$povezanSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                                WHERE p2.tipSlucaj = '$noviSlucaj' 
                                                                AND p2.idPregled IN 
                                                                (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                                WHERE p3.tipSlucaj = '$noviSlucaj'
                                                                AND p3.idPregled = p.prosliPregled 
                                                                GROUP BY p3.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniNoviSlucaj,
                    CASE 
                        WHEN p.tipSlucaj = '$povezanSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                                WHERE p2.tipSlucaj = '$povezanSlucaj' 
                                                                AND p2.idPregled IN 
                                                                (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                                WHERE p3.tipSlucaj = '$povezanSlucaj' 
                                                                AND p3.idPregled = p.prosliPregled 
                                                                GROUP BY p3.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniPovezanSlucaj FROM pregled p 
                    WHERE p.idPregled = $idPregled) 
                    UNION 
                    (SELECT p2.idPregled, DATE_FORMAT(p2.datumPregled,'%d.%m.%Y') AS Datum, 
                    p2.tipSlucaj, p2.vrijemePregled, p2.bojaPregled, 
                    CASE 
                        WHEN p2.tipSlucaj = '$noviSlucaj' THEN (SELECT p3.idPregled FROM pregled p3 
                                                            WHERE p3.idPregled IN 
                                                            (SELECT MAX(p4.idPregled) FROM pregled p4 
                                                            WHERE p4.prosliPregled = p2.idPregled 
                                                            GROUP BY p4.prosliPregled)
                                                            LIMIT 1)
                        WHEN p2.tipSlucaj = '$povezanSlucaj' THEN (SELECT p3.idPregled FROM pregled p3 
                                                                WHERE p3.idPregled IN 
                                                                (SELECT MAX(p4.idPregled) FROM pregled p4 
                                                                WHERE p4.prosliPregled = p2.idPregled 
                                                                GROUP BY p4.prosliPregled)
                                                                LIMIT 1)
                    END AS sljedeciPregled,
                    CASE 
                        WHEN p2.tipSlucaj = '$povezanSlucaj' THEN (SELECT p3.idPregled FROM pregled p3 
                                                                WHERE p3.tipSlucaj = '$noviSlucaj' 
                                                                AND p3.idPregled IN 
                                                                (SELECT MAX(p4.idPregled) FROM pregled p4 
                                                                WHERE p4.tipSlucaj = '$noviSlucaj'
                                                                AND p4.idPregled = p2.prosliPregled 
                                                                GROUP BY p4.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniNoviSlucaj,
                    CASE 
                        WHEN p2.tipSlucaj = '$povezanSlucaj' THEN (SELECT p3.idPregled FROM pregled p3
                                                                WHERE p3.tipSlucaj = '$povezanSlucaj' 
                                                                AND p3.idPregled IN 
                                                                (SELECT MAX(p4.idPregled) FROM pregled p4 
                                                                WHERE p4.tipSlucaj = '$povezanSlucaj' 
                                                                AND p4.idPregled = p2.prosliPregled 
                                                                GROUP BY p4.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniPovezanSlucaj FROM pregled p2 
                    WHERE p2.idPregled = $idPregled)";
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

    //Funkcija koja dohvaća sve preglede koji odgovaraju pretrazi korisnika
    function dohvatiSvePregledePretraga($tipKorisnik,$mboPacijent,$pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];
        //Inicijaliziram varijablu novog slučaja 
        $noviSlucaj = "noviSlucaj";
        //Inicijaliziram varijablu povezanog slučaja
        $povezanSlucaj = "povezanSlucaj";
        //Ako je tip korisnika liječnik:
        if($tipKorisnik == "lijecnik"){
            //Ako je empty pretraga
            if(empty($pretraga)){
                //Kreiram upit koji će dohvatiti sve povijesti bolesti 
                $sql = "(SELECT pb.idPovijestBolesti, DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, 
                        pb.tipSlucaj, pb.vrijeme, pb.bojaPregled, 
                        CASE 
                            WHEN pb.tipSlucaj = '$noviSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                                WHERE pb2.idPovijestBolesti IN 
                                                                (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                                WHERE pb3.prosliPregled = pb.idPovijestBolesti 
                                                                GROUP BY pb3.prosliPregled)
                                                                LIMIT 1) 
                            WHEN pb.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                                    WHERE pb2.idPovijestBolesti IN 
                                                                    (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                                    WHERE pb3.prosliPregled = pb.idPovijestBolesti 
                                                                    GROUP BY pb3.prosliPregled)
                                                                    LIMIT 1)
                        END AS sljedeciPregled,
                        CASE 
                            WHEN pb.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                                    WHERE pb2.tipSlucaj = '$noviSlucaj' 
                                                                    AND pb2.idPovijestBolesti IN 
                                                                    (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                                    WHERE pb3.tipSlucaj = '$noviSlucaj' 
                                                                    AND pb3.idPovijestBolesti = pb.prosliPregled 
                                                                    GROUP BY pb3.prosliPregled)
                                                                    LIMIT 1)
                        END AS prethodniNoviSlucaj,
                        CASE 
                            WHEN pb.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                                    WHERE pb2.tipSlucaj = '$povezanSlucaj' 
                                                                    AND pb2.idPovijestBolesti IN 
                                                                    (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                                    WHERE pb3.tipSlucaj = '$povezanSlucaj' 
                                                                    AND pb3.idPovijestBolesti = pb.prosliPregled 
                                                                    GROUP BY pb3.prosliPregled)
                                                                    LIMIT 1)
                        END AS prethodniPovezanSlucaj FROM povijestBolesti pb 
                        WHERE pb.mboPacijent = '$mboPacijent' 
                        AND pb.prosliPregled IS NOT NULL
                        AND pb.idPovijestBolesti IN 
                        (SELECT MAX(pb2.idPovijestBolesti) FROM povijestbolesti pb2 
                        WHERE pb2.mboPacijent = '$mboPacijent' 
                        AND pb2.prosliPregled IS NOT NULL 
                        GROUP BY pb2.prosliPregled)
                        ORDER BY pb.datum DESC, pb.vrijeme DESC
                        LIMIT 7) 
                        UNION 
                        (SELECT pb2.idPovijestBolesti, DATE_FORMAT(pb2.datum,'%d.%m.%Y') AS Datum, 
                        pb2.tipSlucaj, pb2.vrijeme, pb2.bojaPregled, 
                        CASE 
                            WHEN pb2.tipSlucaj = '$noviSlucaj' THEN (SELECT pb3.idPovijestBolesti FROM povijestbolesti pb3 
                                                                WHERE pb3.idPovijestBolesti IN 
                                                                (SELECT MAX(pb4.idPovijestBolesti) FROM povijestbolesti pb4 
                                                                WHERE pb4.prosliPregled = pb2.idPovijestBolesti 
                                                                GROUP BY pb4.prosliPregled)
                                                                LIMIT 1)
                            WHEN pb2.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb3.idPovijestBolesti FROM povijestbolesti pb3 
                                                                    WHERE pb3.idPovijestBolesti IN 
                                                                    (SELECT MAX(pb4.idPovijestBolesti) FROM povijestbolesti pb4 
                                                                    WHERE pb4.prosliPregled = pb2.idPovijestBolesti 
                                                                    GROUP BY pb4.prosliPregled) 
                                                                    LIMIT 1)
                        END AS sljedeciPregled,
                        CASE 
                            WHEN pb2.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb3.idPovijestBolesti FROM povijestbolesti pb3 
                                                                    WHERE pb3.tipSlucaj = '$noviSlucaj' 
                                                                    AND pb3.idPovijestBolesti IN 
                                                                    (SELECT MAX(pb4.idPovijestBolesti) FROM povijestbolesti pb4 
                                                                    WHERE pb4.tipSlucaj = '$noviSlucaj' 
                                                                    AND pb4.idPovijestBolesti = pb2.prosliPregled 
                                                                    GROUP BY pb4.prosliPregled)
                                                                    LIMIT 1)
                        END AS prethodniNoviSlucaj,
                        CASE 
                            WHEN pb2.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb3.idPovijestBolesti FROM povijestbolesti pb3 
                                                                    WHERE pb3.tipSlucaj = '$povezanSlucaj' 
                                                                    AND pb3.idPovijestBolesti IN 
                                                                    (SELECT MAX(pb4.idPovijestBolesti) FROM povijestbolesti pb4 
                                                                    WHERE pb4.tipSlucaj = '$povezanSlucaj' 
                                                                    AND pb4.idPovijestBolesti = pb2.prosliPregled 
                                                                    GROUP BY pb4.prosliPregled)
                                                                    LIMIT 1)
                        END AS prethodniPovezanSlucaj FROM povijestBolesti pb2 
                        WHERE pb2.mboPacijent = '$mboPacijent' 
                        AND pb2.prosliPregled IS NULL 
                        AND pb2.idPovijestBolesti IN 
                        (SELECT MAX(pb3.idPovijestBolesti) FROM povijestBolesti pb3 
                        WHERE pb3.mboPacijent = '$mboPacijent' 
                        AND pb3.prosliPregled IS NULL 
                        GROUP BY pb3.vrijeme)
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
                $sql = "SELECT pb.idPovijestBolesti, DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, 
                        pb.tipSlucaj, pb.vrijeme, pb.bojaPregled, 
                        CASE 
                            WHEN pb.tipSlucaj = '$noviSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                                WHERE pb2.idPovijestBolesti IN 
                                                                (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                                WHERE pb3.prosliPregled = pb.idPovijestBolesti 
                                                                GROUP BY pb3.prosliPregled) 
                                                                LIMIT 1) 
                            WHEN pb.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                                    WHERE pb2.idPovijestBolesti IN 
                                                                    (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                                    WHERE pb3.prosliPregled = pb.idPovijestBolesti 
                                                                    GROUP BY pb3.prosliPregled) 
                                                                    LIMIT 1)
                        END AS sljedeciPregled,
                        CASE 
                            WHEN pb.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                                    WHERE pb2.tipSlucaj = '$noviSlucaj' 
                                                                    AND pb2.idPovijestBolesti IN 
                                                                    (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                                    WHERE pb3.tipSlucaj = '$noviSlucaj' 
                                                                    AND pb3.idPovijestBolesti = pb.prosliPregled 
                                                                    GROUP BY pb3.prosliPregled)
                                                                    LIMIT 1)
                        END AS prethodniNoviSlucaj,
                        CASE 
                            WHEN pb.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                                    WHERE pb2.tipSlucaj = '$povezanSlucaj' 
                                                                    AND pb2.idPovijestBolesti IN 
                                                                    (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                                    WHERE pb3.tipSlucaj = '$povezanSlucaj' 
                                                                    AND pb3.idPovijestBolesti = pb.prosliPregled 
                                                                    GROUP BY pb3.prosliPregled)
                                                                    LIMIT 1)
                        END AS prethodniPovezanSlucaj FROM povijestBolesti pb 
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
                        ORDER BY pb.datum DESC, pb.vrijeme DESC 
                        LIMIT 7";
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
                        p.tipSlucaj, p.vrijemePregled, p.bojaPregled, 
                        CASE 
                            WHEN p.tipSlucaj = '$noviSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                                WHERE p2.idPregled IN 
                                                                (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                                WHERE p3.prosliPregled = p.idPregled 
                                                                GROUP BY p3.prosliPregled)
                                                                LIMIT 1) 
                            WHEN p.tipSlucaj = '$povezanSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                                    WHERE p2.idPregled IN 
                                                                    (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                                    WHERE p3.prosliPregled = p.idPregled 
                                                                    GROUP BY p3.prosliPregled)
                                                                    LIMIT 1)
                        END AS sljedeciPregled,
                        CASE 
                            WHEN p.tipSlucaj = '$povezanSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                                    WHERE p2.tipSlucaj = '$noviSlucaj' 
                                                                    AND p2.idPregled IN 
                                                                    (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                                    WHERE p3.tipSlucaj = '$noviSlucaj'
                                                                    AND p3.idPregled = p.prosliPregled 
                                                                    GROUP BY p3.prosliPregled)
                                                                    LIMIT 1)
                        END AS prethodniNoviSlucaj,
                        CASE 
                            WHEN p.tipSlucaj = '$povezanSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                                    WHERE p2.tipSlucaj = '$povezanSlucaj' 
                                                                    AND p2.idPregled IN 
                                                                    (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                                    WHERE p3.tipSlucaj = '$povezanSlucaj' 
                                                                    AND p3.idPregled = p.prosliPregled 
                                                                    GROUP BY p3.prosliPregled)
                                                                    LIMIT 1)
                        END AS prethodniPovezanSlucaj FROM pregled p 
                        WHERE p.mboPacijent = '$mboPacijent' 
                        AND p.prosliPregled IS NOT NULL
                        AND p.idPregled IN 
                        (SELECT MAX(p2.idPregled) FROM pregled p2 
                        WHERE p2.mboPacijent = '$mboPacijent' 
                        AND p2.prosliPregled IS NOT NULL 
                        GROUP BY p2.prosliPregled)
                        ORDER BY p.datumPregled DESC, p.vrijemePregled DESC
                        LIMIT 7) 
                        UNION 
                        (SELECT p2.idPregled, DATE_FORMAT(p2.datumPregled,'%d.%m.%Y') AS Datum, 
                        p2.tipSlucaj, p2.vrijemePregled, p2.bojaPregled, 
                        CASE 
                            WHEN p2.tipSlucaj = '$noviSlucaj' THEN (SELECT p3.idPregled FROM pregled p3 
                                                                WHERE p3.idPregled IN 
                                                                (SELECT MAX(p4.idPregled) FROM pregled p4 
                                                                WHERE p4.prosliPregled = p2.idPregled 
                                                                GROUP BY p4.prosliPregled)
                                                                LIMIT 1)
                            WHEN p2.tipSlucaj = '$povezanSlucaj' THEN (SELECT p3.idPregled FROM pregled p3 
                                                                    WHERE p3.idPregled IN 
                                                                    (SELECT MAX(p4.idPregled) FROM pregled p4 
                                                                    WHERE p4.prosliPregled = p2.idPregled 
                                                                    GROUP BY p4.prosliPregled)
                                                                    LIMIT 1)
                        END AS sljedeciPregled,
                        CASE 
                            WHEN p2.tipSlucaj = '$povezanSlucaj' THEN (SELECT p3.idPregled FROM pregled p3 
                                                                    WHERE p3.tipSlucaj = '$noviSlucaj' 
                                                                    AND p3.idPregled IN 
                                                                    (SELECT MAX(p4.idPregled) FROM pregled p4 
                                                                    WHERE p4.tipSlucaj = '$noviSlucaj'
                                                                    AND p4.idPregled = p2.prosliPregled 
                                                                    GROUP BY p4.prosliPregled)
                                                                    LIMIT 1)
                        END AS prethodniNoviSlucaj,
                        CASE 
                            WHEN p2.tipSlucaj = '$povezanSlucaj' THEN (SELECT p3.idPregled FROM pregled p3
                                                                    WHERE p3.tipSlucaj = '$povezanSlucaj' 
                                                                    AND p3.idPregled IN 
                                                                    (SELECT MAX(p4.idPregled) FROM pregled p4 
                                                                    WHERE p4.tipSlucaj = '$povezanSlucaj' 
                                                                    AND p4.idPregled = p2.prosliPregled 
                                                                    GROUP BY p4.prosliPregled)
                                                                    LIMIT 1)
                        END AS prethodniPovezanSlucaj FROM pregled p2 
                        WHERE p2.mboPacijent = '$mboPacijent' 
                        AND p2.prosliPregled IS NULL 
                        AND p2.idPregled IN 
                        (SELECT MAX(p3.idPregled) FROM pregled p3 
                        WHERE p3.mboPacijent = '$mboPacijent' 
                        AND p3.prosliPregled IS NULL 
                        GROUP BY p3.vrijemePregled)
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
                $sql = "SELECT p.idPregled, DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, 
                        p.tipSlucaj, p.vrijemePregled, p.bojaPregled, 
                        CASE 
                            WHEN p.tipSlucaj = '$noviSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                                WHERE p2.idPregled IN 
                                                                (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                                WHERE p3.prosliPregled = p.idPregled 
                                                                GROUP BY p3.prosliPregled) 
                                                                LIMIT 1) 
                            WHEN p.tipSlucaj = '$povezanSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                                    WHERE p2.idPregled IN 
                                                                    (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                                    WHERE p3.prosliPregled = p.idPregled 
                                                                    GROUP BY p3.prosliPregled) 
                                                                    LIMIT 1)
                        END AS sljedeciPregled,
                        CASE 
                            WHEN p.tipSlucaj = '$povezanSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                                    WHERE p2.tipSlucaj = '$noviSlucaj' 
                                                                    AND p2.idPregled IN 
                                                                    (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                                    WHERE p3.tipSlucaj = '$noviSlucaj'
                                                                    AND p3.idPregled = p.prosliPregled 
                                                                    GROUP BY p3.prosliPregled)
                                                                    LIMIT 1)
                        END AS prethodniNoviSlucaj,
                        CASE 
                            WHEN p.tipSlucaj = '$povezanSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                                    WHERE p2.tipSlucaj = '$povezanSlucaj' 
                                                                    AND p2.idPregled IN 
                                                                    (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                                    WHERE p3.tipSlucaj = '$povezanSlucaj' 
                                                                    AND p3.idPregled = p.prosliPregled 
                                                                    GROUP BY p3.prosliPregled)
                                                                    LIMIT 1)
                        END AS prethodniPovezanSlucaj FROM pregled p 
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
                        OR UPPER(d.imeDijagnoza) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(d2.imeDijagnoza) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(p.mkbSifraPrimarna) LIKE UPPER('%{$pretraga}%') 
                        OR UPPER(p.mkbSifraSekundarna) LIKE UPPER('%{$pretraga}%')
                        OR UPPER(DATE_FORMAT(p.datumPregled,'%d.%m.%Y')) LIKE UPPER('%{$pretraga}%'))
                        ORDER BY p.datumPregled DESC, p.vrijemePregled DESC 
                        LIMIT 7";
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
        //Inicijaliziram varijablu novog slučaja 
        $noviSlucaj = "noviSlucaj";
        //Inicijaliziram varijablu povezanog slučaja
        $povezanSlucaj = "povezanSlucaj";
        //Ako je tip korisnika liječnik:
        if($tipKorisnik == "lijecnik"){
            //Kreiram upit koji će dohvatiti povijesti bolesti za zadani datum
            $sql = "(SELECT pb.idPovijestBolesti, DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, 
                    pb.tipSlucaj, pb.vrijeme, pb.bojaPregled, 
                    CASE 
                        WHEN pb.tipSlucaj = '$noviSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                            WHERE pb2.idPovijestBolesti IN 
                                                            (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                            WHERE pb3.prosliPregled = pb.idPovijestBolesti 
                                                            GROUP BY pb3.prosliPregled)
                                                            LIMIT 1) 
                        WHEN pb.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                                WHERE pb2.idPovijestBolesti IN 
                                                                (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                                WHERE pb3.prosliPregled = pb.idPovijestBolesti 
                                                                GROUP BY pb3.prosliPregled)
                                                                LIMIT 1)
                    END AS sljedeciPregled,
                    CASE 
                        WHEN pb.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                                WHERE pb2.tipSlucaj = '$noviSlucaj' 
                                                                AND pb2.idPovijestBolesti IN 
                                                                (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                                WHERE pb3.tipSlucaj = '$noviSlucaj' 
                                                                AND pb3.idPovijestBolesti = pb.prosliPregled 
                                                                GROUP BY pb3.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniNoviSlucaj,
                    CASE 
                        WHEN pb.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                                WHERE pb2.tipSlucaj = '$povezanSlucaj' 
                                                                AND pb2.idPovijestBolesti IN 
                                                                (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                                WHERE pb3.tipSlucaj = '$povezanSlucaj' 
                                                                AND pb3.idPovijestBolesti = pb.prosliPregled 
                                                                GROUP BY pb3.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniPovezanSlucaj FROM povijestBolesti pb 
                    WHERE pb.mboPacijent = '$mboPacijent' 
                    AND pb.datum = '$datum'
                    AND pb.prosliPregled IS NOT NULL
                    AND pb.idPovijestBolesti IN 
                    (SELECT MAX(pb2.idPovijestBolesti) FROM povijestbolesti pb2 
                    WHERE pb2.mboPacijent = '$mboPacijent' 
                    AND pb2.datum = '$datum'
                    AND pb2.prosliPregled IS NOT NULL 
                    GROUP BY pb2.prosliPregled)
                    ORDER BY pb.datum DESC, pb.vrijeme DESC
                    LIMIT 7) 
                    UNION 
                    (SELECT pb2.idPovijestBolesti, DATE_FORMAT(pb2.datum,'%d.%m.%Y') AS Datum, 
                    pb2.tipSlucaj, pb2.vrijeme, pb2.bojaPregled, 
                    CASE 
                        WHEN pb2.tipSlucaj = '$noviSlucaj' THEN (SELECT pb3.idPovijestBolesti FROM povijestbolesti pb3 
                                                            WHERE pb3.idPovijestBolesti IN 
                                                            (SELECT MAX(pb4.idPovijestBolesti) FROM povijestbolesti pb4 
                                                            WHERE pb4.prosliPregled = pb2.idPovijestBolesti 
                                                            GROUP BY pb4.prosliPregled)
                                                            LIMIT 1)
                        WHEN pb2.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb3.idPovijestBolesti FROM povijestbolesti pb3 
                                                                WHERE pb3.idPovijestBolesti IN 
                                                                (SELECT MAX(pb4.idPovijestBolesti) FROM povijestbolesti pb4 
                                                                WHERE pb4.prosliPregled = pb2.idPovijestBolesti 
                                                                GROUP BY pb4.prosliPregled) 
                                                                LIMIT 1)
                    END AS sljedeciPregled,
                    CASE 
                        WHEN pb2.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb3.idPovijestBolesti FROM povijestbolesti pb3 
                                                                WHERE pb3.tipSlucaj = '$noviSlucaj' 
                                                                AND pb3.idPovijestBolesti IN 
                                                                (SELECT MAX(pb4.idPovijestBolesti) FROM povijestbolesti pb4 
                                                                WHERE pb4.tipSlucaj = '$noviSlucaj' 
                                                                AND pb4.idPovijestBolesti = pb2.prosliPregled 
                                                                GROUP BY pb4.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniNoviSlucaj,
                    CASE 
                        WHEN pb2.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb3.idPovijestBolesti FROM povijestbolesti pb3 
                                                                WHERE pb3.tipSlucaj = '$povezanSlucaj' 
                                                                AND pb3.idPovijestBolesti IN 
                                                                (SELECT MAX(pb4.idPovijestBolesti) FROM povijestbolesti pb4 
                                                                WHERE pb4.tipSlucaj = '$povezanSlucaj' 
                                                                AND pb4.idPovijestBolesti = pb2.prosliPregled 
                                                                GROUP BY pb4.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniPovezanSlucaj FROM povijestBolesti pb2 
                    WHERE pb2.mboPacijent = '$mboPacijent' 
                    AND pb2.datum = '$datum'
                    AND pb2.prosliPregled IS NULL 
                    AND pb2.idPovijestBolesti IN 
                    (SELECT MAX(pb3.idPovijestBolesti) FROM povijestBolesti pb3 
                    WHERE pb3.mboPacijent = '$mboPacijent' 
                    AND pb3.datum = '$datum'
                    AND pb3.prosliPregled IS NULL 
                    GROUP BY pb3.vrijeme)
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
            //Kreiram upit koji će dohvatiti opće podatke pregleda za zadani datum 
            $sql = "(SELECT p.idPregled, DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum, 
                    p.tipSlucaj, p.vrijemePregled, p.bojaPregled, 
                    CASE 
                        WHEN p.tipSlucaj = '$noviSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                            WHERE p2.idPregled IN 
                                                            (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                            WHERE p3.prosliPregled = p.idPregled 
                                                            GROUP BY p3.prosliPregled)
                                                            LIMIT 1) 
                        WHEN p.tipSlucaj = '$povezanSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                                WHERE p2.idPregled IN 
                                                                (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                                WHERE p3.prosliPregled = p.idPregled 
                                                                GROUP BY p3.prosliPregled)
                                                                LIMIT 1)
                    END AS sljedeciPregled,
                    CASE 
                        WHEN p.tipSlucaj = '$povezanSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                                WHERE p2.tipSlucaj = '$noviSlucaj' 
                                                                AND p2.idPregled IN 
                                                                (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                                WHERE p3.tipSlucaj = '$noviSlucaj'
                                                                AND p3.idPregled = p.prosliPregled 
                                                                GROUP BY p3.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniNoviSlucaj,
                    CASE 
                        WHEN p.tipSlucaj = '$povezanSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                                WHERE p2.tipSlucaj = '$povezanSlucaj' 
                                                                AND p2.idPregled IN 
                                                                (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                                WHERE p3.tipSlucaj = '$povezanSlucaj' 
                                                                AND p3.idPregled = p.prosliPregled 
                                                                GROUP BY p3.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniPovezanSlucaj FROM pregled p 
                    WHERE p.mboPacijent = '$mboPacijent' 
                    AND p.datumPregled = '$datum'
                    AND p.prosliPregled IS NOT NULL
                    AND p.idPregled IN 
                    (SELECT MAX(p2.idPregled) FROM pregled p2 
                    WHERE p2.mboPacijent = '$mboPacijent' 
                    AND p2.datumPregled = '$datum'
                    AND p2.prosliPregled IS NOT NULL 
                    GROUP BY p2.prosliPregled)
                    ORDER BY p.datumPregled DESC, p.vrijemePregled DESC
                    LIMIT 7) 
                    UNION 
                    (SELECT p2.idPregled, DATE_FORMAT(p2.datumPregled,'%d.%m.%Y') AS Datum, 
                    p2.tipSlucaj, p2.vrijemePregled, p2.bojaPregled, 
                    CASE 
                        WHEN p2.tipSlucaj = '$noviSlucaj' THEN (SELECT p3.idPregled FROM pregled p3 
                                                            WHERE p3.idPregled IN 
                                                            (SELECT MAX(p4.idPregled) FROM pregled p4 
                                                            WHERE p4.prosliPregled = p2.idPregled 
                                                            GROUP BY p4.prosliPregled)
                                                            LIMIT 1)
                        WHEN p2.tipSlucaj = '$povezanSlucaj' THEN (SELECT p3.idPregled FROM pregled p3 
                                                                WHERE p3.idPregled IN 
                                                                (SELECT MAX(p4.idPregled) FROM pregled p4 
                                                                WHERE p4.prosliPregled = p2.idPregled 
                                                                GROUP BY p4.prosliPregled)
                                                                LIMIT 1)
                    END AS sljedeciPregled,
                    CASE 
                        WHEN p2.tipSlucaj = '$povezanSlucaj' THEN (SELECT p3.idPregled FROM pregled p3 
                                                                WHERE p3.tipSlucaj = '$noviSlucaj' 
                                                                AND p3.idPregled IN 
                                                                (SELECT MAX(p4.idPregled) FROM pregled p4 
                                                                WHERE p4.tipSlucaj = '$noviSlucaj'
                                                                AND p4.idPregled = p2.prosliPregled 
                                                                GROUP BY p4.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniNoviSlucaj,
                    CASE 
                        WHEN p2.tipSlucaj = '$povezanSlucaj' THEN (SELECT p3.idPregled FROM pregled p3
                                                                WHERE p3.tipSlucaj = '$povezanSlucaj' 
                                                                AND p3.idPregled IN 
                                                                (SELECT MAX(p4.idPregled) FROM pregled p4 
                                                                WHERE p4.tipSlucaj = '$povezanSlucaj' 
                                                                AND p4.idPregled = p2.prosliPregled 
                                                                GROUP BY p4.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniPovezanSlucaj FROM pregled p2 
                    WHERE p2.mboPacijent = '$mboPacijent' 
                    AND p2.datumPregled = '$datum'
                    AND p2.prosliPregled IS NULL 
                    AND p2.idPregled IN 
                    (SELECT MAX(p3.idPregled) FROM pregled p3 
                    WHERE p3.mboPacijent = '$mboPacijent' 
                    AND p3.datumPregled = '$datum'
                    AND p3.prosliPregled IS NULL 
                    GROUP BY p3.vrijemePregled)
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

    //Funkcija koja na osnovu tipa korisnika, ID-a pacijenta dohvaća sve njegove preglede
    function dohvatiSvePreglede($tipKorisnik,$mboPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];
        //Inicijaliziram varijablu novog slučaja 
        $noviSlucaj = "noviSlucaj";
        //Inicijaliziram varijablu povezanog slučaja
        $povezanSlucaj = "povezanSlucaj";

        //Ako je tip korisnika liječnik:
        if($tipKorisnik == "lijecnik"){
            //Kreiram upit koji će dohvatiti sve povijesti bolesti 
            $sql = "(SELECT pb.idPovijestBolesti, DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, 
                    pb.tipSlucaj, pb.vrijeme, pb.bojaPregled, 
                    CASE 
                        WHEN pb.tipSlucaj = '$noviSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                            WHERE pb2.idPovijestBolesti IN 
                                                            (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                            WHERE pb3.prosliPregled = pb.idPovijestBolesti 
                                                            GROUP BY pb3.prosliPregled)
                                                            LIMIT 1) 
                        WHEN pb.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                                WHERE pb2.idPovijestBolesti IN 
                                                                (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                                WHERE pb3.prosliPregled = pb.idPovijestBolesti 
                                                                GROUP BY pb3.prosliPregled)
                                                                LIMIT 1)
                    END AS sljedeciPregled,
                    CASE 
                        WHEN pb.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                                WHERE pb2.tipSlucaj = '$noviSlucaj' 
                                                                AND pb2.idPovijestBolesti IN 
                                                                (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                                WHERE pb3.tipSlucaj = '$noviSlucaj' 
                                                                AND pb3.idPovijestBolesti = pb.prosliPregled 
                                                                GROUP BY pb3.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniNoviSlucaj,
                    CASE 
                        WHEN pb.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                                                WHERE pb2.tipSlucaj = '$povezanSlucaj' 
                                                                AND pb2.idPovijestBolesti IN 
                                                                (SELECT MAX(pb3.idPovijestBolesti) FROM povijestbolesti pb3 
                                                                WHERE pb3.tipSlucaj = '$povezanSlucaj' 
                                                                AND pb3.idPovijestBolesti = pb.prosliPregled 
                                                                GROUP BY pb3.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniPovezanSlucaj FROM povijestBolesti pb 
                    WHERE pb.mboPacijent = '$mboPacijent' 
                    AND pb.prosliPregled IS NOT NULL
                    AND pb.idPovijestBolesti IN 
                    (SELECT MAX(pb2.idPovijestBolesti) FROM povijestbolesti pb2 
                    WHERE pb2.mboPacijent = '$mboPacijent' 
                    AND pb2.prosliPregled IS NOT NULL 
                    GROUP BY pb2.prosliPregled)
                    ORDER BY pb.datum DESC, pb.vrijeme DESC
                    LIMIT 7) 
                    UNION 
                    (SELECT pb2.idPovijestBolesti, DATE_FORMAT(pb2.datum,'%d.%m.%Y') AS Datum, 
                    pb2.tipSlucaj, pb2.vrijeme, pb2.bojaPregled, 
                    CASE 
                        WHEN pb2.tipSlucaj = '$noviSlucaj' THEN (SELECT pb3.idPovijestBolesti FROM povijestbolesti pb3 
                                                            WHERE pb3.idPovijestBolesti IN 
                                                            (SELECT MAX(pb4.idPovijestBolesti) FROM povijestbolesti pb4 
                                                            WHERE pb4.prosliPregled = pb2.idPovijestBolesti 
                                                            GROUP BY pb4.prosliPregled)
                                                            LIMIT 1)
                        WHEN pb2.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb3.idPovijestBolesti FROM povijestbolesti pb3 
                                                                WHERE pb3.idPovijestBolesti IN 
                                                                (SELECT MAX(pb4.idPovijestBolesti) FROM povijestbolesti pb4 
                                                                WHERE pb4.prosliPregled = pb2.idPovijestBolesti 
                                                                GROUP BY pb4.prosliPregled) 
                                                                LIMIT 1)
                    END AS sljedeciPregled,
                    CASE 
                        WHEN pb2.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb3.idPovijestBolesti FROM povijestbolesti pb3 
                                                                WHERE pb3.tipSlucaj = '$noviSlucaj' 
                                                                AND pb3.idPovijestBolesti IN 
                                                                (SELECT MAX(pb4.idPovijestBolesti) FROM povijestbolesti pb4 
                                                                WHERE pb4.tipSlucaj = '$noviSlucaj' 
                                                                AND pb4.idPovijestBolesti = pb2.prosliPregled 
                                                                GROUP BY pb4.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniNoviSlucaj,
                    CASE 
                        WHEN pb2.tipSlucaj = '$povezanSlucaj' THEN (SELECT pb3.idPovijestBolesti FROM povijestbolesti pb3 
                                                                WHERE pb3.tipSlucaj = '$povezanSlucaj' 
                                                                AND pb3.idPovijestBolesti IN 
                                                                (SELECT MAX(pb4.idPovijestBolesti) FROM povijestbolesti pb4 
                                                                WHERE pb4.tipSlucaj = '$povezanSlucaj' 
                                                                AND pb4.idPovijestBolesti = pb2.prosliPregled 
                                                                GROUP BY pb4.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniPovezanSlucaj FROM povijestBolesti pb2 
                    WHERE pb2.mboPacijent = '$mboPacijent' 
                    AND pb2.prosliPregled IS NULL 
                    AND pb2.idPovijestBolesti IN 
                    (SELECT MAX(pb3.idPovijestBolesti) FROM povijestBolesti pb3 
                    WHERE pb3.mboPacijent = '$mboPacijent' 
                    AND pb3.prosliPregled IS NULL 
                    GROUP BY pb3.vrijeme)
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
                    p.tipSlucaj, p.vrijemePregled, p.bojaPregled, 
                    CASE 
                        WHEN p.tipSlucaj = '$noviSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                            WHERE p2.idPregled IN 
                                                            (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                            WHERE p3.prosliPregled = p.idPregled 
                                                            GROUP BY p3.prosliPregled)
                                                            LIMIT 1) 
                        WHEN p.tipSlucaj = '$povezanSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                                WHERE p2.idPregled IN 
                                                                (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                                WHERE p3.prosliPregled = p.idPregled 
                                                                GROUP BY p3.prosliPregled)
                                                                LIMIT 1)
                    END AS sljedeciPregled,
                    CASE 
                        WHEN p.tipSlucaj = '$povezanSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                                WHERE p2.tipSlucaj = '$noviSlucaj' 
                                                                AND p2.idPregled IN 
                                                                (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                                WHERE p3.tipSlucaj = '$noviSlucaj'
                                                                AND p3.idPregled = p.prosliPregled 
                                                                GROUP BY p3.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniNoviSlucaj,
                    CASE 
                        WHEN p.tipSlucaj = '$povezanSlucaj' THEN (SELECT p2.idPregled FROM pregled p2 
                                                                WHERE p2.tipSlucaj = '$povezanSlucaj' 
                                                                AND p2.idPregled IN 
                                                                (SELECT MAX(p3.idPregled) FROM pregled p3 
                                                                WHERE p3.tipSlucaj = '$povezanSlucaj' 
                                                                AND p3.idPregled = p.prosliPregled 
                                                                GROUP BY p3.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniPovezanSlucaj FROM pregled p 
                    WHERE p.mboPacijent = '$mboPacijent' 
                    AND p.prosliPregled IS NOT NULL
                    AND p.idPregled IN 
                    (SELECT MAX(p2.idPregled) FROM pregled p2 
                    WHERE p2.mboPacijent = '$mboPacijent' 
                    AND p2.prosliPregled IS NOT NULL 
                    GROUP BY p2.prosliPregled)
                    ORDER BY p.datumPregled DESC, p.vrijemePregled DESC
                    LIMIT 7) 
                    UNION 
                    (SELECT p2.idPregled, DATE_FORMAT(p2.datumPregled,'%d.%m.%Y') AS Datum, 
                    p2.tipSlucaj, p2.vrijemePregled, p2.bojaPregled, 
                    CASE 
                        WHEN p2.tipSlucaj = '$noviSlucaj' THEN (SELECT p3.idPregled FROM pregled p3 
                                                            WHERE p3.idPregled IN 
                                                            (SELECT MAX(p4.idPregled) FROM pregled p4 
                                                            WHERE p4.prosliPregled = p2.idPregled 
                                                            GROUP BY p4.prosliPregled)
                                                            LIMIT 1)
                        WHEN p2.tipSlucaj = '$povezanSlucaj' THEN (SELECT p3.idPregled FROM pregled p3 
                                                                WHERE p3.idPregled IN 
                                                                (SELECT MAX(p4.idPregled) FROM pregled p4 
                                                                WHERE p4.prosliPregled = p2.idPregled 
                                                                GROUP BY p4.prosliPregled)
                                                                LIMIT 1)
                    END AS sljedeciPregled,
                    CASE 
                        WHEN p2.tipSlucaj = '$povezanSlucaj' THEN (SELECT p3.idPregled FROM pregled p3 
                                                                WHERE p3.tipSlucaj = '$noviSlucaj' 
                                                                AND p3.idPregled IN 
                                                                (SELECT MAX(p4.idPregled) FROM pregled p4 
                                                                WHERE p4.tipSlucaj = '$noviSlucaj'
                                                                AND p4.idPregled = p2.prosliPregled 
                                                                GROUP BY p4.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniNoviSlucaj,
                    CASE 
                        WHEN p2.tipSlucaj = '$povezanSlucaj' THEN (SELECT p3.idPregled FROM pregled p3
                                                                WHERE p3.tipSlucaj = '$povezanSlucaj' 
                                                                AND p3.idPregled IN 
                                                                (SELECT MAX(p4.idPregled) FROM pregled p4 
                                                                WHERE p4.tipSlucaj = '$povezanSlucaj' 
                                                                AND p4.idPregled = p2.prosliPregled 
                                                                GROUP BY p4.prosliPregled)
                                                                LIMIT 1)
                    END AS prethodniPovezanSlucaj FROM pregled p2 
                    WHERE p2.mboPacijent = '$mboPacijent' 
                    AND p2.prosliPregled IS NULL 
                    AND p2.idPregled IN 
                    (SELECT MAX(p3.idPregled) FROM pregled p3 
                    WHERE p3.mboPacijent = '$mboPacijent' 
                    AND p3.prosliPregled IS NULL 
                    GROUP BY p3.vrijemePregled)
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