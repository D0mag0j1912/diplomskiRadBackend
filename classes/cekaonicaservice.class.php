<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';
//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class CekaonicaService{

    //Funkcija koja dohvaća naziv i šifru sekundarnih dijagnoza na osnovu šifre sek. dijagnoze
    function dohvatiNazivSifraPovijestBolesti($datum,$vrijeme,$tipSlucaj,$mkbSifraPrimarna,$idObrada){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
        
        $sql = "SELECT IF(pb.mkbSifraSekundarna IS NULL, NULL, 
                CONCAT((SELECT TRIM(d.imeDijagnoza) FROM dijagnoze d 
                        WHERE d.mkbSifra = pb.mkbSifraSekundarna),' [',TRIM(pb.mkbSifraSekundarna),']')) AS sekundarneDijagnoze, 
                DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum,pb.vrijeme,pb.tipSlucaj FROM povijestbolesti pb 
                WHERE pb.datum = '$datum' 
                AND pb.vrijeme = '$vrijeme' 
                AND pb.tipSlucaj = '$tipSlucaj' 
                AND TRIM(pb.mkbSifraPrimarna) = '$mkbSifraPrimarna' 
                AND pb.idObradaLijecnik = '$idObrada';";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        } 
        return $response;
    }

    //Funkcija koja dohvaća naziv i šifru sekundarnih dijagnoza na osnovu šifre sek. dijagnoze
    function dohvatiNazivSifraOpciPodatci($datum,$vrijeme,$tipSlucaj,$mkbSifraPrimarna,$idObrada){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
        

        $sql = "SELECT IF(p.mkbSifraSekundarna IS NULL, NULL, 
                CONCAT((SELECT TRIM(d.imeDijagnoza) FROM dijagnoze d 
                        WHERE d.mkbSifra = p.mkbSifraSekundarna),' [',TRIM(p.mkbSifraSekundarna),']')) AS sekundarneDijagnoze, 
                DATE_FORMAT(p.datumPregled,'%d.%m.%Y') AS Datum,p.vrijemePregled,p.tipSlucaj FROM pregled p 
                WHERE p.datumPregled = '$datum' 
                AND p.vrijemePregled = '$vrijeme' 
                AND p.tipSlucaj = '$tipSlucaj' 
                AND TRIM(p.mkbSifraPrimarna) = '$mkbSifraPrimarna' 
                AND p.idObradaMedSestra = '$idObrada';";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        } 

        return $response;
    }

    //Funkcija koja dohvaća povijest bolesti za određeni ID obrade liječnika
    function dohvatiPovijestBolesti($idObrada){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 
        
        $sql = "SELECT pb.idPovijestBolesti,pb.anamneza,pb.razlogDolaska,TRIM(pb.mkbSifraPrimarna) AS mkbSifraPrimarna, 
                TRIM(d.imeDijagnoza) AS NazivPrimarna, pb.vrijeme, pb.tipSlucaj,
                DATE_FORMAT(pb.datum,'%d.%m.%Y') AS Datum, p.imePacijent, p.prezPacijent, p.mboPacijent, 
                p2.mboPacijent AS mboAktivniPacijent,
                CASE
                    WHEN pb.idUputnica IS NOT NULL THEN (SELECT ROUND(ul.iznosUsluga,2) FROM usluge_lijecnik ul
                                                    WHERE ul.idUputnica IN 
                                                    (SELECT MIN(pb2.idUputnica) FROM povijestbolesti pb2 
                                                    WHERE pb2.oznaka = (SELECT pb3.oznaka FROM povijestbolesti pb3 
                                                                    WHERE pb3.idUputnica = pb.idUputnica)))
                    WHEN pb.idUputnica IS NULL THEN NULL
                END AS iznosUputnica,
                CASE 
                    WHEN pb.idRecept IS NOT NULL THEN (SELECT ROUND(ul.iznosUsluga,2) FROM usluge_lijecnik ul 
                                                    WHERE ul.idRecept IN 
                                                    (SELECT MIN(pb2.idRecept) FROM povijestbolesti pb2 
                                                    WHERE pb2.oznaka = (SELECT pb3.oznaka FROM povijestbolesti pb3 
                                                                    WHERE pb3.idRecept = pb.idRecept)))
                    WHEN pb.idRecept IS NULL THEN NULL
                END AS iznosRecept,
                CASE 
                    WHEN r.oblikJacinaPakiranjeLijek IS NULL THEN r.proizvod 
                    WHEN r.oblikJacinaPakiranjeLijek IS NOT NULL THEN CONCAT(r.proizvod,' ',r.oblikJacinaPakiranjeLijek)
                END AS proizvod, r.kolicina, r.doziranje, r.dostatnost, r.hitnost, r.ponovljiv, 
                r.brojPonavljanja, 
                CASE 
                    WHEN r.sifraSpecijalist IS NOT NULL THEN (SELECT CONCAT((SELECT TRIM(zr.tipSpecijalist) FROM zdr_radnici zr 
                                                                            WHERE zr.sifraSpecijalist = r.sifraSpecijalist),' [',TRIM(r.sifraSpecijalist),']'))
                    WHEN r.sifraSpecijalist IS NULL THEN NULL
                END AS specijalist,
                CASE 
                    WHEN u.idZdrUst IS NOT NULL THEN (SELECT CONCAT((SELECT TRIM(zu.nazivZdrUst) FROM zdr_ustanova zu 
                                                                    WHERE zu.idZdrUst = u.idZdrUst),' [',TRIM(u.idZdrUst),']'))
                    WHEN u.idZdrUst IS NULL THEN NULL
                END AS zdravstvenaUstanova,
                CASE 
                    WHEN u.sifDjel IS NOT NULL THEN (SELECT CONCAT((SELECT TRIM(zd.nazivDjel) FROM zdr_djel zd 
                                                                    WHERE zd.sifDjel = u.sifDjel),' [',TRIM(u.sifDjel),']'))
                    WHEN u.sifDjel IS NULL THEN NULL
                END AS zdravstvenaDjelatnost,
                CASE 
                    WHEN u.sifraSpecijalist IS NOT NULL THEN (SELECT CONCAT((SELECT TRIM(zr.tipSpecijalist) FROM zdr_radnici zr 
                                                                            WHERE zr.sifraSpecijalist = u.sifraSpecijalist),' [',TRIM(u.sifraSpecijalist),']'))
                    WHEN u.sifraSpecijalist IS NULL THEN NULL
                END AS specijalistUputnica,
                u.vrstaPregleda AS vrstaPregled,u.molimTraziSe,
                CASE 
                    WHEN u.napomena IS NOT NULL THEN u.napomena
                    WHEN u.napomena IS NULL THEN NULL
                END AS napomena
                FROM povijestbolesti pb 
                LEFT JOIN recept r ON r.idRecept = pb.idRecept 
                LEFT JOIN uputnica u ON u.idUputnica = pb.idUputnica
                LEFT JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna 
                LEFT JOIN pacijent p ON p.mboPacijent = pb.mboPacijent 
                LEFT JOIN obrada_lijecnik o ON o.idObrada = pb.idObradaLijecnik 
                LEFT JOIN pacijent p2 ON p2.idPacijent = o.idPacijent
                WHERE pb.idObradaLijecnik = '$idObrada'
                GROUP BY pb.oznaka 
                ORDER BY pb.datum DESC, pb.vrijeme DESC";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        
        return $response;
    }

    //Funkcija koja dohvaća opće podatke pregleda za određeni ID obrade medicinske sestre
    function dohvatiOpcePodatke($idObrada){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 
        
        $sql = "SELECT
                CASE 
                    WHEN pr.nacinPlacanja = 'hzzo' THEN (SELECT CONCAT('HZZO (',pu.nazivSluzbe,' [',p2.podrucniUredHZZO,'])') FROM pregled p2 
                                                        JOIN podrucni_ured pu ON pu.sifUred = p2.podrucniUredHZZO 
                                                        WHERE p2.idPregled = pr.idPregled)
                    WHEN pr.nacinPlacanja = 'ozljeda' THEN (SELECT CONCAT('Ozljeda (',pu.nazivSluzbe,' [',p2.podrucniUredOzljeda,'])') FROM pregled p2 
                                                            JOIN podrucni_ured pu ON pu.sifUred = p2.podrucniUredOzljeda 
                                                            WHERE p2.idPregled = pr.idPregled)
                    WHEN pr.nacinPlacanja = 'poduzece' THEN CONCAT('Naziv poduzeća: ',pr.nazivPoduzeca)
                    WHEN pr.nacinPlacanja = 'osobno' THEN pr.nacinPlacanja
                END AS nacinPlacanja,
                TRIM(pr.mkbSifraPrimarna) AS mkbSifraPrimarna, 
                TRIM(d.imeDijagnoza) AS NazivPrimarna, pr.vrijemePregled, pr.tipSlucaj,
                DATE_FORMAT(pr.datumPregled,'%d.%m.%Y') AS Datum, p.imePacijent, p.prezPacijent, p.mboPacijent,
                p2.mboPacijent AS mboAktivniPacijent FROM pregled pr
                JOIN dijagnoze d ON d.mkbSifra = pr.mkbSifraPrimarna 
                JOIN pacijent p ON p.mboPacijent = pr.mboPacijent 
                JOIN obrada_med_sestra o ON o.idObrada = pr.idObradaMedSestra 
                JOIN pacijent p2 ON p2.idPacijent = o.idPacijent
                WHERE pr.idObradaMedSestra = '$idObrada'
                GROUP BY pr.oznaka 
                ORDER BY pr.datumPregled DESC, pr.vrijemePregled DESC";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        
        return $response;
    }

    //Funkcija koja dohvaća ime, prezime i datum pregleda pacijenta
    function dohvatiImePrezimeDatum($tip,$idObrada){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 
        //Ako je tip korisnika "lijecnik":
        if($tip == "lijecnik"){
            $sql = "SELECT p.imePacijent,p.prezPacijent, 
                    DATE_FORMAT(ol.datumDodavanja,'%d.%m.%Y') AS Datum,
                    ol.idObrada,
                    CASE
                    WHEN ul.idUputnica IS NOT NULL THEN (SELECT ROUND(SUM(ul2.iznosUsluga),2) FROM usluge_lijecnik ul2 
                                                        WHERE ul2.idObradaLijecnik = '$idObrada' 
                                                        AND ul2.idUputnica = ul.idUputnica 
                                                        AND ul2.idUputnica IN 
                                                        (SELECT pb.idUputnica FROM povijestbolesti pb 
                                                        WHERE pb.mboPacijent IN 
                                                        (SELECT p.mboPacijent FROM pacijent p 
                                                        WHERE p.idPacijent = ol.idPacijent)))
                    WHEN ul.idRecept IS NOT NULL THEN (SELECT ROUND(SUM(ul2.iznosUsluga),2) FROM usluge_lijecnik ul2 
                                                    WHERE ul2.idObradaLijecnik = '$idObrada' 
                                                    AND ul2.idRecept = ul.idRecept 
                                                    AND ul2.idRecept IN 
                                                    (SELECT pb.idRecept FROM povijestbolesti pb 
                                                    WHERE pb.mboPacijent IN 
                                                    (SELECT p.mboPacijent FROM pacijent p 
                                                    WHERE p.idPacijent = ol.idPacijent)))
                    END AS ukupnaCijenaPregled FROM usluge_lijecnik ul 
                    JOIN obrada_lijecnik ol ON ol.idObrada = ul.idObradaLijecnik 
                    JOIN pacijent p ON p.idPacijent = ol.idPacijent 
                    WHERE ul.idObradaLijecnik = '$idObrada'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }
        //Ako je tip korisnika "sestra":
        else if($tip == "sestra"){
            $sql = "SELECT p.imePacijent,p.prezPacijent,
                    DATE_FORMAT(o.datumDodavanja,'%d.%m.%Y') AS Datum, 
                    o.idObrada,
                    (SELECT CONCAT('[',tm.visina,'cm - ',tm.tezina,'kg] => ',tm.bmi) FROM tjelesna_masa tm 
                    WHERE tm.idBMI = 
                    (SELECT MAX(tm2.idBMI) FROM tjelesna_masa tm2 
                    JOIN usluge_med_sestra ums ON ums.idBMI = tm2.idBMI 
                    WHERE ums.idObradaMedSestra = '$idObrada')) AS bmi,
                    (SELECT CONCAT((SELECT ROUND(SUM(ums.iznosUsluga),2) FROM usluge_med_sestra ums 
                                    WHERE ums.idObradaMedSestra = '$idObrada'),' kn [',(SELECT CONCAT((SELECT COUNT(*) FROM tjelesna_masa tm2 
                                                                        JOIN usluge_med_sestra ums2 ON ums2.idBMI = tm2.idBMI 
                                                                        WHERE ums2.idObradaMedSestra = '$idObrada'),'x',ums.iznosUsluga,' kn') FROM tjelesna_masa tm 
                                                                        JOIN usluge_med_sestra ums ON ums.idBMI = tm.idBMI 
                                                                        WHERE ums.idObradaMedSestra = '$idObrada' 
                                                                        LIMIT 1),']')) AS ukupnaCijenaPregled FROM pacijent p
                    JOIN obrada_med_sestra o ON o.idPacijent = p.idPacijent
                    WHERE o.idObrada = '$idObrada'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }
        return $response;
    }

    //Funkcija koja briše pacijenta iz čekaonice
    function izbrisiPacijentaCekaonica($tip,$idCekaonica){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 
        
        //Ako je tip korisnika koji je dodao ovaj redak u čekaonicu "lijecnik":
        if($tip == "lijecnik"){
            //Kreiram upit koji dohvaćam ID obrade liječnika iz tablice "cekaonica"
            $sqlIDObrada = "SELECT c.idObradaLijecnik FROM cekaonica c 
                            WHERE c.idCekaonica = '$idCekaonica'";
            //Rezultat upita spremam u varijablu $resultIDObrada
            $resultIDObrada = mysqli_query($conn,$sqlIDObrada);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($resultIDObrada) > 0){
                //Idem redak po redak rezultata upita 
                while($rowIDObrada = mysqli_fetch_assoc($resultIDObrada)){
                    //Vrijednost rezultata spremam u varijablu $idObrada
                    $idObrada = $rowIDObrada['idObradaLijecnik'];
                }
            } 
            //Ako idObrada !== NULL
            if(!empty($idObrada)){
                $sqlObrada = "DELETE FROM obrada_lijecnik 
                        WHERE idObrada = ?;";

                //Kreiranje prepared statementa
                $stmtObrada = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmtObrada,$sqlObrada)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement brisanja obrade ne valja!";
                }
                //Ako je prepared statement u redu
                else{
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmtObrada,"i",$idObrada);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmtObrada);

                    $sql = "DELETE FROM cekaonica 
                            WHERE idObradaLijecnik = ?";
                
                    //Kreiranje prepared statementa
                    $stmt = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmt,$sql)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement brisanja čekaonice ne valja!";
                    }
                    //Ako je prepared statement u redu
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmt,"i",$idObrada);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmt);

                        $response["success"] = "true";
                        $response["message"] = "Pacijent uspješno izbrisan!";
                    }
                }
            }
            else{
                $sql = "DELETE FROM cekaonica 
                            WHERE idCekaonica = ?";
                
                    //Kreiranje prepared statementa
                    $stmt = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmt,$sql)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement brisanja čekaonice ne valja!";
                    }
                    //Ako je prepared statement u redu
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmt,"i",$idCekaonica);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmt);

                        $response["success"] = "true";
                        $response["message"] = "Pacijent uspješno izbrisan!";
                    }
            }
        }
        //Ako je tip korisnika koji je dodao ovaj redak u čekaonicu "sestra":
        else if($tip == "sestra"){
            //Kreiram upit koji dohvaćam ID obrade liječnika iz tablice "cekaonica"
            $sqlIDObrada = "SELECT c.idObradaMedSestra FROM cekaonica c 
                            WHERE c.idCekaonica = '$idCekaonica'";
            //Rezultat upita spremam u varijablu $resultIDObrada
            $resultIDObrada = mysqli_query($conn,$sqlIDObrada);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($resultIDObrada) > 0){
                //Idem redak po redak rezultata upita 
                while($rowIDObrada = mysqli_fetch_assoc($resultIDObrada)){
                    //Vrijednost rezultata spremam u varijablu $idObrada
                    $idObrada = $rowIDObrada['idObradaMedSestra'];
                }
            } 
            //Ako idObrada !== NULL
            if(!empty($idObrada)){
                $sqlObrada = "DELETE FROM obrada_med_sestra 
                        WHERE idObrada = ?;";

                //Kreiranje prepared statementa
                $stmtObrada = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmtObrada,$sqlObrada)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement brisanja obrade ne valja!";
                }
                //Ako je prepared statement u redu
                else{
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmtObrada,"i",$idObrada);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmtObrada);

                    $sql = "DELETE FROM cekaonica 
                        WHERE idObradaMedSestra = ?";
                
                    //Kreiranje prepared statementa
                    $stmt = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmt,$sql)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement brisanja čekaonice ne valja!";
                    }
                    //Ako je prepared statement u redu
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmt,"i",$idObrada);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmt);

                        $response["success"] = "true";
                        $response["message"] = "Pacijent uspješno izbrisan!";
                    }
                }
            }
            //Ako je idObrada == NULL
            else{
                $sql = "DELETE FROM cekaonica 
                        WHERE idCekaonica = ?";
                
                    //Kreiranje prepared statementa
                    $stmt = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmt,$sql)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement brisanja čekaonice ne valja!";
                    }
                    //Ako je prepared statement u redu
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmt,"i",$idCekaonica);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmt);

                        $response["success"] = "true";
                        $response["message"] = "Pacijent uspješno izbrisan!";
                    }
            }
        }

        return $response;
    }

    //Funkcija koja provjerava koliko još ima pacijenata u čekaonici
    function provjeriBrojCekaonica(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        //Kreiram sql upit koji će provjeriti koliko ima pacijenata u bazi podataka
        $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM cekaonica";
        //Rezultat upita spremam u varijablu $resultCountPacijent
        $resultCountPacijent = mysqli_query($conn,$sqlCountPacijent);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCountPacijent) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCountPacijent = mysqli_fetch_assoc($resultCountPacijent)){
                //Vrijednost rezultata spremam u varijablu $brojPacijenata
                $brojPacijenata = $rowCountPacijent['BrojPacijent'];
            }
        } 
        //Vrati broj pacijenata
        return $brojPacijenata;
    }

    //Funkcija koja dodava pacijenta u čekaonicu
    function dodajUCekaonicu($tip,$id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Trenutni datum
        $datum = date('Y-m-d');
        //Trenutno vrijeme
        $vrijeme = date('H:i:s');
        //Početni status čekaonice
        $status = "Čeka na pregled";
        //Ako je prijavljeni korisnik "lijecnik"
        if($tip == "lijecnik"){
            $sqlID = "SELECT l.idLijecnik FROM lijecnik l;";
            $result = $conn->query($sqlID);

            if ($result->num_rows > 0) {
                // output data of each row
                while($row = $result->fetch_assoc()) {
                    //Dohvaćam ime liječnika
                    $idLijecnik = $row["idLijecnik"];
                }
            }

            $sql = "INSERT INTO cekaonica(idPacijent,datumDodavanja,vrijemeDodavanja,statusCekaonica,idLijecnik) VALUES (?,?,?,?,?)";
            //Kreiranje prepared statementa
            $stmt = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmt,$sql)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement ne valja!";
            }
            //Ako je prepared statement u redu
            else{
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmt,"isssi",$id,$datum,$vrijeme,$status,$idLijecnik);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmt);

                $response["success"] = "true";
                $response["message"] = "Pacijent je uspješno dodan u čekaonicu!";
            }
        }
        //Ako je prijavljeni korisnik "sestra":
        else if($tip == "sestra"){
            $sqlID = "SELECT m.idMedSestra FROM med_sestra m;";
            $result = $conn->query($sqlID);

            if ($result->num_rows > 0) {
                // output data of each row
                while($row = $result->fetch_assoc()) {
                    //Dohvaćam ime liječnika
                    $idMedSestra = $row["idMedSestra"];
                }
            }
            $sql = "INSERT INTO cekaonica(idPacijent,datumDodavanja,vrijemeDodavanja,statusCekaonica,idMedSestra) VALUES (?,?,?,?,?)";
            //Kreiranje prepared statementa
            $stmt = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmt,$sql)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement ne valja!";
            }
            //Ako je prepared statement u redu
            else{
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmt,"isssi",$id,$datum,$vrijeme,$status,$idMedSestra);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmt);

                $response["success"] = "true";
                $response["message"] = "Pacijent je uspješno dodan u čekaonicu!";
            }
        }
        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja dohvaća pacijente iz čekaonice
    function dohvatiPacijenteCekaonica($tip){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Kreiram sql upit koji će provjeriti postoji li pacijenata u čekaonici
        $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM cekaonica";
        //Rezultat upita spremam u varijablu $resultCountPacijent
        $resultCountPacijent = mysqli_query($conn,$sqlCountPacijent);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCountPacijent) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCountPacijent = mysqli_fetch_assoc($resultCountPacijent)){
                //Vrijednost rezultata spremam u varijablu $brojPacijenata
                $brojPacijenata = $rowCountPacijent['BrojPacijent'];
            }
        }
        //Ako nema pronađenih pacijenata u čekaonici
        if($brojPacijenata == 0){
            $response["success"] = "false";
            $response["message"] = "Čekaonica je prazna!";
        }
        //Ako ima pacijenata u čekaonici
        else{
            //Ako je tip prijavljenog korisnika "lijecnik":
            if($tip == "lijecnik"){
                //Kreiram upit koji dohvaća sve pacijente iz čekaonice
                $sql = "SELECT CASE 
                            WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT m.imeMedSestra) FROM med_sestra m JOIN cekaonica c ON c.idMedSestra = m.idMedSestra)
                            WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT l.imeLijecnik) FROM lijecnik l JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                        END AS OdgovornaOsoba,
                        p.idPacijent,p.imePacijent,p.prezPacijent,c.idCekaonica,
                        DATE_FORMAT(c.datumDodavanja,'%d.%m.%Y') AS DatumDodavanja,c.vrijemeDodavanja,
                        c.statusCekaonica,
                        CASE 
                            WHEN c.idObradaLijecnik IS NULL AND c.idObradaMedSestra IS NULL THEN NULL
                            WHEN c.idObradaLijecnik IS NULL THEN c.idObradaMedSestra 
                            WHEN c.idObradaMedSestra IS NULL THEN c.idObradaLijecnik
                        END AS idObrada,
                        CASE 	
                            WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                            JOIN med_sestra m ON k.idKorisnik = m.idKorisnik 
                                                            JOIN cekaonica c ON c.idMedSestra = m.idMedSestra) 
                            WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                            JOIN lijecnik l ON l.idKorisnik = k.idKorisnik 
                                                            JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                        END AS tip FROM pacijent p 
                        JOIN cekaonica c ON p.idPacijent = c.idPacijent 
                        ORDER BY tip ASC,c.statusCekaonica ASC,c.datumDodavanja DESC, c.vrijemeDodavanja DESC";

                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }
            }
            //Ako je tip prijavljenog korisnika "sestra":
            else if($tip == "sestra"){
                //Kreiram upit koji dohvaća sve pacijente iz čekaonice
                $sql = "SELECT CASE 
                            WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT m.imeMedSestra) FROM med_sestra m JOIN cekaonica c ON c.idMedSestra = m.idMedSestra)
                            WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT l.imeLijecnik) FROM lijecnik l JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                        END AS OdgovornaOsoba,
                        p.idPacijent,p.imePacijent,p.prezPacijent,c.idCekaonica,
                        DATE_FORMAT(c.datumDodavanja,'%d.%m.%Y') AS DatumDodavanja,c.vrijemeDodavanja,
                        c.statusCekaonica,
                        CASE 
                            WHEN c.idObradaLijecnik IS NULL AND c.idObradaMedSestra IS NULL THEN NULL
                            WHEN c.idObradaLijecnik IS NULL THEN c.idObradaMedSestra 
                            WHEN c.idObradaMedSestra IS NULL THEN c.idObradaLijecnik
                        END AS idObrada,
                        CASE 	
                            WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                            JOIN med_sestra m ON k.idKorisnik = m.idKorisnik 
                                                            JOIN cekaonica c ON c.idMedSestra = m.idMedSestra) 
                            WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                            JOIN lijecnik l ON l.idKorisnik = k.idKorisnik 
                                                            JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                        END AS tip FROM pacijent p 
                        JOIN cekaonica c ON p.idPacijent = c.idPacijent 
                        ORDER BY tip DESC,c.statusCekaonica ASC,c.datumDodavanja DESC";

                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }
            }
        }
        
        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja provjerava status u čekaonici
    function provjeraCekaonica($tip,$id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Status pacijenta u čekaonici
        $status = "Čeka na pregled";

        //Ako je prijavljeni korisnik "lijecnik"
        if($tip == "lijecnik"){
            $sqlID = "SELECT l.idLijecnik FROM lijecnik l;";
            $resultID = $conn->query($sqlID);

            if ($resultID->num_rows > 0) {
                // output data of each row
                while($rowID = $resultID->fetch_assoc()) {
                    //Dohvaćam ID liječnika
                    $idLijecnik = $rowID["idLijecnik"];
                }
            }
            //Provjeravam je li postoji već taj pacijent u čekaonici da ga je dodao liječnik (da čeka na pregled) 
            $sql="SELECT c.idPacijent FROM cekaonica c 
                WHERE c.idPacijent = '$id' AND c.statusCekaonica = '$status' AND c.idLijecnik = '$idLijecnik'";

            $result = $conn->query($sql);

            if($result->num_rows > 0){
                $response["success"] = "false";
                $response["message"] = "Pacijent trenutno čeka na pregled!";
                return $response;
            }
            else{
                //Vraćam null
                return null;
            }
        }
        //Ako je prijavljeni korisnik "sestra":
        else if($tip == "sestra"){
            $sqlID = "SELECT m.idMedSestra FROM med_sestra m;";
            $resultID = $conn->query($sqlID);

            if ($resultID->num_rows > 0) {
                // output data of each row
                while($rowID = $resultID->fetch_assoc()) {
                    //Dohvaćam ID liječnika
                    $idMedSestra = $rowID["idMedSestra"];
                }
            }
            //Provjeravam je li postoji već taj pacijent u čekaonici da ga je dodala medicinska sestra (da čeka na pregled) 
            $sql="SELECT c.idPacijent FROM cekaonica c 
                WHERE c.idPacijent = '$id' AND c.statusCekaonica = '$status' AND c.idMedSestra = '$idMedSestra'";

            $result = $conn->query($sql);

            if($result->num_rows > 0){
                $response["success"] = "false";
                $response["message"] = "Pacijent trenutno čeka na pregled!";
                return $response;
            }
            else{
                //Vraćam null
                return null;
            }
        }
    }

    //Funkcija koja dohvaća 10 zadnjih pacijenata u čekaonici
    function dohvati10zadnjih($tip){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Kreiram sql upit koji će provjeriti postoji li pacijenata u čekaonici
        $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM cekaonica";
        //Rezultat upita spremam u varijablu $resultCountPacijent
        $resultCountPacijent = mysqli_query($conn,$sqlCountPacijent);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCountPacijent) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCountPacijent = mysqli_fetch_assoc($resultCountPacijent)){
                //Vrijednost rezultata spremam u varijablu $brojPacijenata
                $brojPacijenata = $rowCountPacijent['BrojPacijent'];
            }
        }
        //Ako nema pronađenih pacijenata u čekaonici
        if($brojPacijenata == 0){
            $response["success"] = "false";
            $response["message"] = "Čekaonica je prazna!";
        }
        //Ako ima pacijenata u čekaonici
        else{
            //Ako je tip prijavljenog korisnika "lijecnik":
            if($tip == "lijecnik"){
                //Kreiram upit koji dohvaća sve pacijente iz čekaonice
                $sql = "SELECT CASE 
                            WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT m.imeMedSestra) FROM med_sestra m JOIN cekaonica c ON c.idMedSestra = m.idMedSestra)
                            WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT l.imeLijecnik) FROM lijecnik l JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                        END AS OdgovornaOsoba,
                        p.idPacijent,p.imePacijent,p.prezPacijent,c.idCekaonica,
                        DATE_FORMAT(c.datumDodavanja,'%d.%m.%Y') AS DatumDodavanja,c.vrijemeDodavanja,
                        c.statusCekaonica,
                        CASE 
                            WHEN c.idObradaLijecnik IS NULL AND c.idObradaMedSestra IS NULL THEN NULL
                            WHEN c.idObradaLijecnik IS NULL THEN c.idObradaMedSestra 
                            WHEN c.idObradaMedSestra IS NULL THEN c.idObradaLijecnik
                        END AS idObrada,
                        CASE 	
                            WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                            JOIN med_sestra m ON k.idKorisnik = m.idKorisnik 
                                                            JOIN cekaonica c ON c.idMedSestra = m.idMedSestra) 
                            WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                            JOIN lijecnik l ON l.idKorisnik = k.idKorisnik 
                                                            JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                        END AS tip FROM pacijent p 
                        JOIN cekaonica c ON p.idPacijent = c.idPacijent 
                        ORDER BY tip ASC,c.statusCekaonica ASC,c.datumDodavanja DESC 
                        LIMIT 10";

                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }
            }
            //Ako je tip prijavljenog korisnika "sestra":
            else if($tip == "sestra"){
                //Kreiram upit koji dohvaća sve pacijente iz čekaonice
                $sql = "SELECT CASE 
                            WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT m.imeMedSestra) FROM med_sestra m JOIN cekaonica c ON c.idMedSestra = m.idMedSestra)
                            WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT l.imeLijecnik) FROM lijecnik l JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                        END AS OdgovornaOsoba,
                        p.idPacijent,p.imePacijent,p.prezPacijent,c.idCekaonica,
                        DATE_FORMAT(c.datumDodavanja,'%d.%m.%Y') AS DatumDodavanja,c.vrijemeDodavanja,
                        c.statusCekaonica,
                        CASE 
                            WHEN c.idObradaLijecnik IS NULL AND c.idObradaMedSestra IS NULL THEN NULL
                            WHEN c.idObradaLijecnik IS NULL THEN c.idObradaMedSestra 
                            WHEN c.idObradaMedSestra IS NULL THEN c.idObradaLijecnik
                        END AS idObrada,
                        CASE 	
                            WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                            JOIN med_sestra m ON k.idKorisnik = m.idKorisnik 
                                                            JOIN cekaonica c ON c.idMedSestra = m.idMedSestra) 
                            WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                            JOIN lijecnik l ON l.idKorisnik = k.idKorisnik 
                                                            JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                        END AS tip FROM pacijent p 
                        JOIN cekaonica c ON p.idPacijent = c.idPacijent 
                        ORDER BY tip DESC,c.statusCekaonica ASC,c.datumDodavanja DESC 
                        LIMIT 10";

                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }
            }
        }
        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja dohvaća pacijente iz čekaonice po njihovom statusu u čekaonici
    function dohvatiPacijentaPoStatusu($tip,$statusi,$dohvati10zadnjih){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Ako polje nije prazno
        if(!empty($statusi)){
            //Ako je tip prijavljenog korisnika "lijecnik"
            if($tip == "lijecnik"){
                foreach($statusi as $status){
                
                    $sql = "SELECT CASE 
                            WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT m.imeMedSestra) FROM med_sestra m JOIN cekaonica c ON c.idMedSestra = m.idMedSestra)
                            WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT l.imeLijecnik) FROM lijecnik l JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                            END AS OdgovornaOsoba,
                            p.idPacijent,p.imePacijent,p.prezPacijent,c.idCekaonica,
                            DATE_FORMAT(c.datumDodavanja,'%d.%m.%Y') AS DatumDodavanja,c.vrijemeDodavanja,
                            c.statusCekaonica,
                            CASE 
                                WHEN c.idObradaLijecnik IS NULL AND c.idObradaMedSestra IS NULL THEN NULL
                                WHEN c.idObradaLijecnik IS NULL THEN c.idObradaMedSestra 
                                WHEN c.idObradaMedSestra IS NULL THEN c.idObradaLijecnik
                            END AS idObrada,
                            CASE 	
                                WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                                JOIN med_sestra m ON k.idKorisnik = m.idKorisnik 
                                                                JOIN cekaonica c ON c.idMedSestra = m.idMedSestra) 
                                WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                                JOIN lijecnik l ON l.idKorisnik = k.idKorisnik 
                                                                JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                            END AS tip FROM pacijent p 
                            JOIN cekaonica c ON p.idPacijent = c.idPacijent 
                            WHERE c.statusCekaonica = '$status'
                            ORDER BY tip ASC,c.statusCekaonica ASC,c.datumDodavanja DESC";
            
                    $result = $conn->query($sql);
            
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $response[] = $row;
                        }
                    }
                }
            }
            //Ako je tip prijavljenog korisnika "sestra":
            else if($tip == "sestra"){
                foreach($statusi as $status){
                
                    $sql = "SELECT CASE 
                            WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT m.imeMedSestra) FROM med_sestra m JOIN cekaonica c ON c.idMedSestra = m.idMedSestra)
                            WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT l.imeLijecnik) FROM lijecnik l JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                            END AS OdgovornaOsoba,
                            p.idPacijent,p.imePacijent,p.prezPacijent,c.idCekaonica,
                            DATE_FORMAT(c.datumDodavanja,'%d.%m.%Y') AS DatumDodavanja,c.vrijemeDodavanja,
                            c.statusCekaonica,
                            CASE 
                                WHEN c.idObradaLijecnik IS NULL AND c.idObradaMedSestra IS NULL THEN NULL
                                WHEN c.idObradaLijecnik IS NULL THEN c.idObradaMedSestra 
                                WHEN c.idObradaMedSestra IS NULL THEN c.idObradaLijecnik
                            END AS idObrada,
                            CASE 	
                                WHEN c.idLijecnik IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                                JOIN med_sestra m ON k.idKorisnik = m.idKorisnik 
                                                                JOIN cekaonica c ON c.idMedSestra = m.idMedSestra) 
                                WHEN c.idMedSestra IS NULL THEN (SELECT GROUP_CONCAT(DISTINCT k.tip) FROM korisnik k 
                                                                JOIN lijecnik l ON l.idKorisnik = k.idKorisnik 
                                                                JOIN cekaonica c ON c.idLijecnik = l.idLijecnik)
                            END AS tip FROM pacijent p 
                            JOIN cekaonica c ON p.idPacijent = c.idPacijent 
                            WHERE c.statusCekaonica = '$status'
                            ORDER BY tip DESC,c.statusCekaonica ASC,c.datumDodavanja DESC";
            
                    $result = $conn->query($sql);
            
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $response[] = $row;
                        }
                    }
                }
            }
        }
        //Kada su STATUSI PRAZNI
        else{
            //Kreiram upit koji dohvaća sve pacijente iz čekaonice
            $response = $dohvati10zadnjih;
        }
        return $response;
    }
}
?>