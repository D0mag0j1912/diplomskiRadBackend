<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';
//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class ObradaService{

    //Funkcija koja provjerava JE LI PACIJENT OBRAĐEN OD STRANE LIJEČNIKA 
    function dohvatiObradenPovijestBolesti(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        $status = "Aktivan";
        //Kreiram upit koji će provjeriti je li pacijent obrađen od strane liječnika (je li napisana povijest bolesti za njega)
        $sql = "SELECT MAX(a.idPovijestBolesti) AS IDPovijestBolesti FROM ambulanta a 
                JOIN povijestBolesti pb ON pb.idPovijestBolesti = a.idPovijestBolesti
                WHERE a.idPacijent IN 
                (SELECT o.idPacijent FROM obrada o 
                WHERE o.statusObrada = '$status') 
                AND pb.datum = CURDATE();";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }

        return $response;
    }

    //Funkcija koja provjerava JE LI PACIJENT OBRAĐEN OD STRANE MEDICINSKE SESTRE 
    function dohvatiObradenOpciPodatci(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        $status = "Aktivan";

        $sql = "SELECT MAX(a.idPregled) AS IDPregled FROM ambulanta a 
                JOIN pregled p ON p.idPregled = a.idPregled
                WHERE a.idPacijent IN 
                (SELECT o.idPacijent FROM obrada o 
                WHERE o.statusObrada = '$status') 
                AND p.datumPregled = CURDATE();";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }

        return $response;
    }

    //Funkcija koja dohvaća vrijeme narudžbe trenutno aktivnog pacijenta
    function dohvatiVrijemeNarudzbe(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        $status = "Aktivan";

        //Kreiram sql upit koji će provjeriti je li trenutno aktivni pacijent naručen na današnji datum
        $sql = "SELECT COUNT(*) AS BrojPacijent FROM narucivanje n 
                WHERE n.datumNarucivanje = CURDATE() AND n.idPacijent IN 
                (SELECT o.idPacijent FROM obrada o 
                WHERE o.statusObrada = '$status');";

        //Rezultat upita spremam u varijablu $resultCountPacijent
        $resultCountPacijent = mysqli_query($conn,$sql);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCountPacijent) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCountPacijent = mysqli_fetch_assoc($resultCountPacijent)){
                //Vrijednost rezultata spremam u varijablu $brojPacijenata
                $brojPacijenata = $rowCountPacijent['BrojPacijent'];
            }
        }
        //Ako trenutno aktivni pacijent nije naručen na današnji datum
        if($brojPacijenata == 0){
            $response["success"] = "false";
            $response["message"] = "Pacijent je nenaručen!";
        }
        //Ako JE trenutno aktivni pacijent naručen na današnji datum
        else{
            $sql = "SELECT DATE_FORMAT(n.vrijemeNarucivanje,'%H:%i') AS Vrijeme FROM narucivanje n 
                    WHERE n.datumNarucivanje = CURDATE() AND n.idPacijent IN 
                    (SELECT o.idPacijent FROM obrada o 
                    WHERE o.statusObrada = '$status');";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }
        return $response;
    }

    //Funkcija koja provjerava postoji li već neki pacijent aktivan u obradi
    function provjeraObrada(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        //Status pacijenta u čekaonici
        $status = "Aktivan";
        //Kreiram sql upit koji će provjeriti postoji li aktivan pacijent u obradi
        $sql = "SELECT COUNT(*) AS BrojPacijent FROM obrada o 
                 WHERE o.statusObrada = '$status'";

        //Rezultat upita spremam u varijablu $resultCountPacijent
        $resultCountPacijent = mysqli_query($conn,$sql);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCountPacijent) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCountPacijent = mysqli_fetch_assoc($resultCountPacijent)){
                //Vrijednost rezultata spremam u varijablu $brojPacijenata
                $brojPacijenata = $rowCountPacijent['BrojPacijent'];
            }
        }
        //Ako je već neki pacijent aktivan u obradi
        if($brojPacijenata > 0){
            $response["success"] = "false";
            $response["message"] = "Već postoji aktivan pacijent!";
            return $response;
        }
        else{
            return null;
        }
    }
    
    //Funkcija koja dodava pacijenta u obradu
    function dodajUObradu($id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        //Trenutni datum
        $datum = date('Y-m-d');
        //Trenutno vrijeme
        $vrijeme = date('H:i:s');
        //Početni status u obradu
        $status = "Aktivan";

        $sql = "INSERT INTO obrada (idPacijent,datumDodavanja,vrijemeDodavanja,statusObrada) VALUES (?,?,?,?)";

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
            mysqli_stmt_bind_param($stmt,"isss",$id,$datum,$vrijeme,$status);
            //Izvršavanje statementa
            mysqli_stmt_execute($stmt);

            //Dohvaćam ID obrade kojega sam upravo unio
            $resultObrada = mysqli_query($conn,"SELECT MAX(o.idObrada) AS IDObrada FROM obrada o");
            //Ulazim u polje rezultata i idem redak po redak
            while($rowObrada = mysqli_fetch_array($resultObrada)){
                //Dohvaćam željeni ID pregleda
                $idObrada = $rowObrada['IDObrada'];
            }

            //Status čekaonice
            $statusCekaonicaPrije = "Čeka na pregled";
            $statusCekaonicaPoslije = "Na pregledu";

            //Ažurirati atribut "statusCekaonica" na "Na pregledu"
            $sqlCekaonica = "UPDATE cekaonica SET statusCekaonica = ?,idObrada = ?
                            WHERE idPacijent = ? AND statusCekaonica = ?";
            //Kreiranje prepared statementa
            $stmtCekaonica = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmtCekaonica,$sqlCekaonica)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement cekaonice ne valja!";
            }
            //Ako je prepared statement u redu
            else{
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmtCekaonica,"siis",$statusCekaonicaPoslije,$idObrada,$id,$statusCekaonicaPrije);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmtCekaonica);

                $response["success"] = "true";
                $response["message"] = "Pacijent je uspješno dodan u obradu!";
            }
        }
        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja dohvaća trenutno aktivnog pacijenta u obradi
    function dohvatiPacijentObrada(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        $status = "Aktivan";
        //Kreiram sql upit koji će provjeriti postoji li aktivnih pacijenata u obradi
        $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM obrada o
                            WHERE o.statusObrada = '$status'";
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
        //Ako nema pronađenih pacijenata u obradi
        if($brojPacijenata == 0){
            $response["success"] = "false";
            $response["message"] = "Nema aktivnih pacijenata!";
        }
        //Ako ima pacijenata u obradi
        else{
            //Kreiram upit koji dohvaća podatke pacijenta koji je trenutno aktivan u obradi
            $sql = "SELECT o.idObrada,o.idPacijent,o.datumDodavanja,o.vrijemeDodavanja,o.statusObrada,
                    p.imePacijent,p.prezPacijent,DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS DatumRodenja,p.adresaPacijent,p.mboPacijent,z.brojIskazniceDopunsko FROM obrada o 
                    JOIN pacijent p ON o.idPacijent = p.idPacijent 
                    JOIN zdr_podatci z ON z.mboPacijent = p.mboPacijent
                    WHERE o.statusObrada = '$status'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }

        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja ažurira statuse tablica "obrada" i "cekaonica" pri završenom pregledu
    function azurirajStatus($id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        $sqlCekaonica = "UPDATE cekaonica SET statusCekaonica = ? 
                        WHERE idPacijent = ? AND statusCekaonica = ?";

        //Kreiranje prepared statementa
        $stmtCekaonica = mysqli_stmt_init($conn);
        //Ako je statement neuspješan
        if(!mysqli_stmt_prepare($stmtCekaonica,$sqlCekaonica)){
            $response["success"] = "false";
            $response["message"] = "Prepared statement cekaonice ne valja!";
        }
        //Ako je prepared statement u redu
        else{
            //Početni status
            $statusCekaonicaPrije = "Na pregledu";
            $statusCekaonicaPoslije = "Završen pregled";
            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
            mysqli_stmt_bind_param($stmtCekaonica,"sis",$statusCekaonicaPoslije,$id,$statusCekaonicaPrije);
            //Izvršavanje statementa
            mysqli_stmt_execute($stmtCekaonica);

            $sqlObrada = "UPDATE obrada SET statusObrada = ? 
                        WHERE idPacijent = ? AND statusObrada = ?";

            //Kreiranje prepared statementa
            $stmtObrada = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmtObrada,$sqlObrada)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement cekaonice ne valja!";
            }
            //Ako je prepared statement u redu
            else{
                //Početni status
                $statusObradaPrije = "Aktivan";
                $statusObradaPoslije = "Neaktivan";
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmtObrada,"sis",$statusObradaPoslije,$id,$statusObradaPrije);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmtObrada);

                $response["success"] = "true";
                $response["message"] = "Tablice ažurirane!";
            }
        }
        return $response;
    }

    //Funkcija koja dohvaća OSNOVNE podatke pacijenta koji je trenutno u obradi
    function dohvatiOsnovnePodatkePacijenta(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
        $status = "Aktivan";

        //Kreiram sql upit koji će provjeriti postoji li aktivnih pacijenata u obradi
        $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM pacijent p
                            WHERE p.idPacijent IN 
                            (SELECT o.idPacijent FROM obrada o 
                            WHERE o.statusObrada = '$status')";
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
        //Ako nema pronađenih pacijenata u obradi
        if($brojPacijenata == 0){
            $response["success"] = "false";
            $response["message"] = "Nema aktivnih pacijenata!";
        }
        //Ako ima pacijenata u obradi
        else{
            //Kreiram upit koji dohvaća OSNOVNE podatke pacijente koji je trenutno aktivan u obradi
            $sql = "SELECT p.*,m.nazivMjesto FROM pacijent p
                    JOIN mjesto m ON p.pbrMjestoPacijent = m.pbrMjesto
                    WHERE p.idPacijent IN 
                    (SELECT o.idPacijent FROM obrada o 
                    WHERE o.statusObrada = '$status')";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }

        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja dohvaća ZDRAVSTVENE podatke pacijenta koji je trenutno u obradi
    function dohvatiZdravstvenePodatkePacijenta(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
        $status = "Aktivan";

        //Kreiram sql upit koji će provjeriti postoji li aktivnih pacijenata u obradi
        $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM pacijent p
                            WHERE p.idPacijent IN 
                            (SELECT o.idPacijent FROM obrada o 
                            WHERE o.statusObrada = '$status')";
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
        //Ako nema pronađenih pacijenata u obradi
        if($brojPacijenata == 0){
            $response["success"] = "false";
            $response["message"] = "Nema aktivnih pacijenata!";
        }
        //Ako ima pacijenata u obradi
        else{
            //Kreiram upit koji dohvaća ZDRAVSTVENE podatke pacijente koji je trenutno aktivan u obradi
            $sql = "SELECT z.*,p.*,pu.sifUred,pu.nazivSluzbe FROM zdr_podatci z 
                    JOIN pacijent p ON z.mboPacijent = p.mboPacijent 
                    JOIN podrucni_ured pu ON pu.sifUred = z.sifUred 
                    WHERE p.idPacijent IN 
                    (SELECT o.idPacijent FROM obrada o 
                    WHERE o.statusObrada = '$status')";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }

        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja POTVRĐUJE OSOBNE PODATKE PACIJENTA
    function potvrdiOsnovnePodatke($idPacijent, $ime, $prezime,$datRod,$adresa,$oib,$email,$spol,
                                $pbr,$mobitel,$bracnoStanje,$radniStatus,$status){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        $sql = "UPDATE pacijent p SET p.imePacijent = ?, p.prezPacijent = ?, p.spolPacijent = ?, p.datRodPacijent = ?,
                                            p.adresaPacijent = ?, p.pbrMjestoPacijent = ?, p.mobitelPacijent = ?, p.bracnoStanjePacijent = ?, 
                                            p.radniStatusPacijent = ?, p.statusPacijent = ?, p.oibPacijent = ?, p.emailPacijent = ? 
                        WHERE p.idPacijent = ?";

        //Kreiranje prepared statementa
        $stmt = mysqli_stmt_init($conn);
        //Ako je statement neuspješan
        if(!mysqli_stmt_prepare($stmt,$sql)){
            $response["success"] = "false";
            $response["message"] = "Prepared statement osnovnih podataka ne valja!";
        }
        //Ako je prepared statement u redu
        else{
            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
            mysqli_stmt_bind_param($stmt,"sssssissssssi",$ime, $prezime, $spol, $datRod, $adresa, $pbr, $mobitel, $bracnoStanje,
                                                        $radniStatus, $status, $oib, $email, $idPacijent);
            //Izvršavanje statementa
            mysqli_stmt_execute($stmt);

            //Ubacivam podatke u tablicu "pacijent_dodatno" ZATO ŠTO JE PACIJENT AŽURIRAN
            $sqlPacijentDodavno = "INSERT INTO pacijent_dodatno (idPacijent, datAzurPacijent, tipAzurPacijent) VALUES (?,?,?)";

            //Kreiranje prepared statementa
            $stmtPacijentDodatno = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmtPacijentDodatno,$sqlPacijentDodavno)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement pacijent_dodatno ne valja!";
            }
            //Ako je prepared statement u redu
            else{
                //Trenutni datum
                $datum = date('Y-m-d');
                $tipAzurPacijent = "osnovniPodatci";
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmtPacijentDodatno,"iss", $idPacijent, $datum, $tipAzurPacijent);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmtPacijentDodatno);

                $response["success"] = "true";
                $response["message"] = "Podatci uspješno ažurirani!";
            }
        }
        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja POTVRĐUJE ZDRAVSTVENE PODATKE
    function potvrdiZdravstvenePodatke($idPacijent,$mbo,$nositeljOsiguranja,$drzavaOsiguranja,$kategorijaOsiguranja,
                                    $trajnoOsnovno,$osnovnoOsiguranjeOd,$osnovnoOsiguranjeDo,$brIskDopunsko,
                                    $dopunskoOsiguranjeOd,$dopunskoOsiguranjeDo,$oslobodenParticipacije,
                                    $clanakParticipacija,$trajnoParticipacija,$participacijaDo,$sifUred){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
        if(empty($trajnoOsnovno)){
            $trajnoOsnovno = NULL;
        }  
        if(empty($osnovnoOsiguranjeOd) && empty($osnovnoOsiguranjeDo)){
            $osnovnoOsiguranjeOd = NULL;
            $osnovnoOsiguranjeDo = NULL;
        } 
        if(empty($trajnoParticipacija)){
            $trajnoParticipacija = NULL;
        }  
        if(empty($participacijaDo)){
            $participacijaDo = NULL;
        }
        if(empty($oslobodenParticipacije)){
            $oslobodenParticipacije = NULL;
        }  
        if(empty($clanakParticipacija)){
            $clanakParticipacija = NULL;
        } 
        if(empty($brIskDopunsko)){
            $brIskDopunsko = NULL;
        }
        if(empty($dopunskoOsiguranjeOd)){
            $dopunskoOsiguranjeOd = NULL;
        }
        if(empty($dopunskoOsiguranjeDo)){
            $dopunskoOsiguranjeDo = NULL;
        }

        $sqlZdr = "UPDATE zdr_podatci z SET z.mboPacijent = ?,z.nositeljOsiguranja = ?, z.drzavaOsiguranja = ?, 
                                    z.kategorijaOsiguranja = ?, z.trajnoOsnovno = ?, z.osnovnoOsiguranjeOd = ?, 
                                    z.osnovnoOsiguranjeDo = ?, z.brojIskazniceDopunsko = ?, z.dopunskoOsiguranjeOd = ?,
                                    z.dopunskoOsiguranjeDo = ?, z.oslobodenParticipacije = ?, z.clanakParticipacija = ?,
                                    z.trajnoParticipacija = ?, z.participacijaDo = ?, z.sifUred = ? 
                    WHERE z.mboPacijent IN 
                    (SELECT p.mboPacijent FROM pacijent p 
                    WHERE p.idPacijent = ?)";
        //Kreiranje prepared statementa
        $stmtZdr = mysqli_stmt_init($conn);
        //Ako je statement neuspješan
        if(!mysqli_stmt_prepare($stmtZdr,$sqlZdr)){
            $response["success"] = "false";
            $response["message"] = "Prepared statement zdravstvenih podataka ne valja!";
        }
        //Ako je prepared statement u redu
        else{
            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
            mysqli_stmt_bind_param($stmtZdr,"ssssssssssssssii",$mbo,$nositeljOsiguranja,$drzavaOsiguranja,$kategorijaOsiguranja,
                                                            $trajnoOsnovno,$osnovnoOsiguranjeOd,$osnovnoOsiguranjeDo,$brIskDopunsko,
                                                            $dopunskoOsiguranjeOd,$dopunskoOsiguranjeDo,$oslobodenParticipacije,
                                                            $clanakParticipacija,$trajnoParticipacija,$participacijaDo,$sifUred,$idPacijent);
            //Izvršavanje statementa
            mysqli_stmt_execute($stmtZdr);

            //Ubacivam podatke u tablicu "pacijent_dodatno" ZATO ŠTO JE PACIJENT AŽURIRAN
            $sqlPacijentDodavno = "INSERT INTO pacijent_dodatno (idPacijent, datAzurPacijent, tipAzurPacijent) VALUES (?,?,?)";

            //Kreiranje prepared statementa
            $stmtPacijentDodatno = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmtPacijentDodatno,$sqlPacijentDodavno)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement pacijent_dodatno ne valja!";
            }
            //Ako je prepared statement u redu
            else{
                //Trenutni datum
                $datum = date('Y-m-d');
                $tipAzurPacijent = "zdravstveniPodatci";
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmtPacijentDodatno,"iss", $idPacijent, $datum, $tipAzurPacijent);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmtPacijentDodatno);

                $sql = "UPDATE pacijent p SET p.mboPacijent = ? 
                        WHERE p.idPacijent = ?";
                //Kreiranje prepared statementa
                $stmt = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmt,$sql)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement osnovnih podataka ne valja!";
                }
                //Ako je prepared statement u redu
                else{
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmt,"si",$mbo,$idPacijent);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmt);

                    $response["success"] = "true";
                    $response["message"] = "Podatci uspješno ažurirani!";
                }
            }
        }
        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja dohvaća sljedećeg pacijenta čekaonice
    function dohvatiSljedeciPacijent(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
        
        $status = "Čeka na pregled";

        //Kreiram sql upit koji će provjeriti koji čekaju na pregled u čekaonici
        $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM pacijent p 
                            JOIN cekaonica c ON c.idPacijent = p.idPacijent 
                            WHERE c.idCekaonica = 
                            (SELECT MAX(idCekaonica) FROM cekaonica 
                            WHERE statusCekaonica = '$status')";
        //Rezultat upita spremam u varijablu $resultCountPacijent
        $resultCountPacijent = mysqli_query($conn,$sqlCountPacijent);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCountPacijent) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCountPacijent = mysqli_fetch_assoc($resultCountPacijent)){
                //Vrijednost rezultata spremam u varijablu $brojNarudzba
                $brojPacijent = $rowCountPacijent['BrojPacijent'];
            }
        }
        //Ako nema pacijenata koji čekaju na pregled
        if($brojPacijent == 0){
            $response["success"] = "false";
            $response["message"] = "Nema pacijenata koji čekaju na pregled!";
        }
        //Ako ima pacijenata koji čekaju na pregled
        else{
            $sql = "SELECT p.imePacijent,p.prezPacijent FROM pacijent p 
                JOIN cekaonica c ON c.idPacijent = p.idPacijent 
                WHERE c.idCekaonica = 
                (SELECT MAX(idCekaonica) FROM cekaonica 
                WHERE statusCekaonica = '$status')";

            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }

        return $response;

    }
}

?>