<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class OpciPodatciService{

    //Funkcija koja dohvaća zdravstvene podatke trenutno aktivnog pacijenta
    function dohvatiZdravstvenePodatke($tip){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];
        //Postavljam status 
        $status = "Aktivan";

        //Ako je tip korisnika "sestra":
        if($tip == "sestra"){
            //Kreiram sql upit koji će provjeriti postoji li aktivnih pacijenata u obradi
            $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM obrada_med_sestra o
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
                //Kreiram upit koji dohvaća ZDRAVSTVENE podatke pacijente koji je trenutno aktivan u obradi
                $sql = "SELECT z.mboPacijent,z.drzavaOsiguranja,z.brojIskazniceDopunsko,ko.opisOsiguranika FROM zdr_podatci z 
                        JOIN kategorije_osiguranje ko ON ko.oznakaOsiguranika = z.kategorijaOsiguranja
                        JOIN pacijent p ON p.mboPacijent = z.mboPacijent 
                        WHERE p.idPacijent IN 
                        (SELECT o.idPacijent FROM obrada_med_sestra o 
                        WHERE o.statusObrada = '$status');";
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

    //Funkcija koja dohvaća ID pregleda kojega povezujem
    function getIDPregled($mboPacijent,$idObrada,$mkbSifraPrimarna){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        $sql = "SELECT p.idPregled,p.bojaPregled FROM pregled p 
                WHERE p.mboPacijent = '$mboPacijent' 
                AND p.idObradaMedSestra = '$idObrada' 
                AND TRIM(p.mkbSifraPrimarna) = '$mkbSifraPrimarna' 
                AND p.idPregled = 
                (SELECT MAX(p2.idPregled) FROM pregled p2 
                WHERE p2.mboPacijent = '$mboPacijent' 
                AND p2.idObradaMedSestra = '$idObrada' 
                AND TRIM(p2.mkbSifraPrimarna) = '$mkbSifraPrimarna')";
        //Rezultat upita spremam u varijablu $resultMBO
        $result = mysqli_query($conn,$sql);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($result) > 0){
            //Idem redak po redak rezultata upita 
            while($row = mysqli_fetch_assoc($result)){
                $response[] = $row;
            }
        } 
        return $response;
    }

    //Funkcija koja DODAVA PODATKE OPĆEG PREGLEDA PACIJENTA u bazu
    function dodajOpcePodatkePregleda($idMedSestra, $idPacijent, $nacinPlacanja, $podrucniUredHZZO, $podrucniUredOzljeda, $nazivPoduzeca,
                                    $oznakaOsiguranika, $nazivDrzave, $mbo, $brIskDopunsko, $mkbPrimarnaDijagnoza,
                                    $mkbSifre, $tipSlucaj,$idObrada,$prosliPregled,$proslaBoja){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = []; 
        //Varijabla koja određuje je li pacijent naručen ili nije
        $narucen = NULL;
        //Trenutni datum
        $datum = date('Y-m-d');
        //Trenutno vrijeme za naručivanje
        $vrijeme = date('H:i:s');
        //Trenutno vrijeme pregleda
        $vrijemePregled = date("H:i:s");
        //Status obrade
        $statusObrada = "Aktivan";
        //Inicijaliziram polje random boja 
        $poljeBoja = ['#006400','#C71585','#BDB76B','#40E0D0','#000000','#4B0082','#48D1CC','#D2691E','#FF0000'];
        //Ako medicinska sestra nije unijela primarnu dijagnozu na pregledu:
        if(empty($mkbPrimarnaDijagnoza)){
            //Postavljam je na NULL
            $mkbPrimarnaDijagnoza = NULL;
        }
        //Označavam da slučajno generirana oznaka već postoji u bazi
        $ispravnaOznaka = false;
        while($ispravnaOznaka != true){
            //Generiram slučajni oznaku po kojom grupiram
            $oznaka = uniqid();
            //Kreiram upit koji provjerava postoji li već ova random generirana oznaka u bazi
            $sqlProvjeraOznaka = "SELECT p.oznaka FROM pregled p 
                                WHERE p.oznaka = '$oznaka';";
            //Rezultat upita spremam u varijablu $resultProvjeraOznaka
            $resultProvjeraOznaka = mysqli_query($conn,$sqlProvjeraOznaka);
            //Ako se novo generirana oznaka NE NALAZI u bazi
            if(mysqli_num_rows($resultProvjeraOznaka) == 0){
                //Izlazim iz petlje
                $ispravnaOznaka = true;
            } 
        }
        //Ako su minute vremena == 0, ostavi kako jest
        if((int)(date('i',strtotime($vrijeme))) === 0){
            $vrijeme = $vrijeme;
        }
        //Ako su minute vremena == 30, ostavi kako jest
        else if( (int)(date('i',strtotime($vrijeme))) === 30){
            $vrijeme = $vrijeme;
        }
        //Ako su minute vremena > 0 && minute < 15, zaokruži na manji puni sat
        else if( (int)(date('i',strtotime($vrijeme))) > 0 && (int)(date('i',strtotime($vrijeme))) < 15){
            $vrijeme = date("H:i:s", strtotime("-".(int)(date('i',strtotime($vrijeme)))." minutes", strtotime($vrijeme) ) );  
        }
        //Ako su minute vremena >= 15 && minute < 30, zaokruži na pola sata 
        else if( (int)(date('i',strtotime($vrijeme))) >= 15 && (int)(date('i',strtotime($vrijeme))) < 30){
            $vrijeme = date("H:i:s", strtotime("+".(30-(int)(date('i',strtotime($vrijeme))))." minutes",strtotime($vrijeme) ) );
        }
        //Ako su minute vremena > 30 && minute < 45, zaokruži na pola sata
        else if( (int)(date('i',strtotime($vrijeme))) > 30 && (int)(date('i',strtotime($vrijeme))) < 45){
            $vrijeme = date("H:i:s", strtotime("-".((int)(date('i',strtotime($vrijeme)))-30)." minutes", strtotime($vrijeme) ) );
        }
        //Ako su minute vremena >=45 && minute < 60, zaokruži na veći puni sat
        else if( (int)(date('i',strtotime($vrijeme))) >= 45 && (int)(date('i',strtotime($vrijeme))) < 60){
            $vrijeme = date("H:i:s", strtotime("+".(60-(int)(date('i',strtotime($vrijeme))))." minutes",strtotime($vrijeme) ) );
        }

        //Kreiram sql upit koji će provjeriti JE LI TRENUTNO AKTIVNI PACIJENT NARUČEN U OVO VRIJEME NA OVAJ DATUM
        $sqlCountNarucen = "SELECT COUNT(*) AS BrojNarucen FROM narucivanje n 
                            WHERE n.idPacijent = '$idPacijent' 
                            AND n.vrijemeNarucivanje = '$vrijeme' 
                            AND n.datumNarucivanje = '$datum'";
        //Rezultat upita spremam u varijablu $resultCountPacijent
        $resultCountNarucen = mysqli_query($conn,$sqlCountNarucen);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCountNarucen) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCountNarucen = mysqli_fetch_assoc($resultCountNarucen)){
                //Vrijednost rezultata spremam u varijablu $brojNarudzba
                $brojNarucen = $rowCountNarucen['BrojNarucen'];
            }
        }

        //Ako pacijent nije pronađen u listi naručenih za to vrijeme i za taj datum
        if($brojNarucen == 0){
            //Postavljam varijablu
            $narucen = "Ne";
        }
        else{
            $narucen = "Da";
        }
        //Ako je NOVI SLUČAJ:
        if($tipSlucaj == 'noviSlucaj'){
            /******************************** */
            //Provjera je li postoji već ova primarna dijagnoza u bazi
            $sqlProvjera = "SELECT TRIM(p.mkbSifraPrimarna) AS mkbSifraPrimarna FROM pregled p
                            WHERE p.idObradaMedSestra = '$idObrada' 
                            AND p.mboPacijent = '$mbo'";
            //Rezultat upita spremam u varijablu $resultProvjera
            $resultProvjera = mysqli_query($conn,$sqlProvjera);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($resultProvjera) > 0){
                //Idem redak po redak rezultata upita 
                while($rowProvjera = mysqli_fetch_assoc($resultProvjera)){
                    if($mkbPrimarnaDijagnoza == $rowProvjera['mkbSifraPrimarna']){
                        $response["success"] = "false";
                        $response["message"] = "Već ste unijeli ovu primarnu dijagnozu u ovoj sesiji obrade!";
                        return $response;
                    }
                }
            }
        }
        //Ako je polje sekundarnih dijagnoza prazno
        if(empty($mkbSifre)){
            //Dohvaćam šifru područnog ureda HZZO-a, šifru ureda ozljede na radu te šifru primarne dijagnoze
            $sqlHZZO = "SELECT sifUred FROM podrucni_ured 
                        WHERE nazivSluzbe = ?";
            $stmtHZZO = $conn->prepare($sqlHZZO); 
            $stmtHZZO->bind_param("s", $podrucniUredHZZO);
            $stmtHZZO->execute();
            $resultHZZO = $stmtHZZO->get_result(); // get the mysqli result
            while ($rowHZZO = $resultHZZO->fetch_assoc()) {
                $sifUredHZZO = $rowHZZO["sifUred"];
            }

            $sqlOzljeda = "SELECT sifUred FROM podrucni_ured 
                            WHERE nazivSluzbe = ?";
            $stmtOzljeda = $conn->prepare($sqlOzljeda); 
            $stmtOzljeda->bind_param("s", $podrucniUredOzljeda);
            $stmtOzljeda->execute();
            $resultOzljeda = $stmtOzljeda->get_result(); // get the mysqli result
            while ($rowOzljeda = $resultOzljeda->fetch_assoc()) {
                $sifUredOzljeda = $rowOzljeda["sifUred"];
            }
            //Kreiram upit za spremanje prvog dijela podataka u bazu
            $sql = "INSERT INTO pregled (nacinPlacanja, podrucniUredHZZO, podrucniUredOzljeda, 
                                        nazivPoduzeca, oznakaOsiguranika, nazivDrzave, mboPacijent, brIskDopunsko,
                                        mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, datumPregled,narucen, 
                                        idObradaMedSestra,vrijemePregled,prosliPregled,bojaPregled,oznaka) 
                                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            //Kreiranje prepared statementa
            $stmt = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmt,$sql)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement ne valja!";
            }
            //Ako je prepared statement u redu
            else{
                //Ako je slučaj novi:
                if(empty($prosliPregled)){
                    $prosliPregled = NULL;
                }
                //Ako je "proslaBoja" prazna, to znači da se generira novi slučaj
                if(empty($proslaBoja)){
                    //Na početku inicijaliziram broj pronađenih boja na 0 (u slučaju da ne postoji još ova obrada i boja)
                    $brojBoja = 0;
                    //Generiram random boju
                    $boja = $poljeBoja[array_rand($poljeBoja)];
                    //Tražim je li se novo generirana boja nalazi u bazi
                    $sql = "SELECT COUNT(*) AS brojBoja FROM pregled p 
                            WHERE p.idObradaMedSestra = '$idObrada' AND p.bojaPregled = '$boja'";
                    $result = $conn->query($sql);
                    //Ako ima pronađenih rezultata za navedenu pretragu
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $brojBoja = $row['brojBoja'];
                        }
                    }
                    //Dok ne pronađem boju koja još ne postoji u bazi za ovu sesiju obrade
                    while($brojBoja != 0){
                        //Generiraj ponovno boju
                        $boja = $poljeBoja[array_rand($poljeBoja)];
                        //Ponovno traži
                        $sql = "SELECT COUNT(*) AS brojBoja FROM pregled p
                                WHERE p.idObradaMedSestra = '$idObrada' AND p.bojaPregled = '$boja'";
                        $result = $conn->query($sql);
                        //Ako ima pronađenih rezultata za navedenu pretragu
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $brojBoja = $row['brojBoja'];
                            }
                        }
                    }
                }
                //Ako "proslaBoja" nije prazna, to znači da je slučaj povezan
                else{
                    $boja = $proslaBoja;
                }
                //Postavljam sek. dijagnozu na NULL
                $sekDijagnoza = NULL;
                if(empty($nazivPoduzeca)){
                    $nazivPoduzeca = NULL;
                }
                if(empty($brIskDopunsko)){
                    $brIskDopunsko = NULL;
                }
                if(empty($oznakaOsiguranika)){
                    $oznakaOsiguranika = NULL;
                }
                if(empty($nazivDrzave)){
                    $nazivDrzave = NULL;
                }
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmt,"sssssssssssssisiss",$nacinPlacanja, $sifUredHZZO, $sifUredOzljeda, $nazivPoduzeca,
                                        $oznakaOsiguranika, $nazivDrzave, $mbo, $brIskDopunsko, $mkbPrimarnaDijagnoza,
                                        $sekDijagnoza, $tipSlucaj, $datum,$narucen,$idObrada,$vrijemePregled,$prosliPregled,$boja,$oznaka);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmt);

                //Dohvaćam ID pregleda kojega sam upravo unio
                $resultPregled = mysqli_query($conn,"SELECT MAX(p.idPregled) AS IDPregled FROM pregled p");
                //Ulazim u polje rezultata i idem redak po redak
                while($rowPregled = mysqli_fetch_array($resultPregled)){
                    //Dohvaćam željeni ID pregleda
                    $idPregled = $rowPregled['IDPregled'];
                } 

                //Ubacivam nove podatke u tablicu "ambulanta"
                $sqlAmbulanta = "INSERT INTO ambulanta (idMedSestra,idPacijent,idPregled) VALUES (?,?,?)";
                //Kreiranje prepared statementa
                $stmtAmbulanta = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmtAmbulanta,$sqlAmbulanta)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement ne valja!";
                }
                //Ako je prepared statement u redu
                else{
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmtAmbulanta,"iii",$idMedSestra,$idPacijent,$idPregled);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmtAmbulanta);

                    //Ažuriram tablicu zdravstvenih podataka
                    $sqlZdr = "UPDATE zdr_podatci z SET z.brojIskazniceDopunsko = ?, z.kategorijaOsiguranja = ?,
                                z.drzavaOsiguranja = ?
                                WHERE z.mboPacijent = ?";
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
                        mysqli_stmt_bind_param($stmtZdr,"ssss",$brIskDopunsko, $oznakaOsiguranika, $nazivDrzave, $mbo);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmtZdr);
                        $response["success"] = "true";
                        $response["message"] = "Podatci uspješno dodani!";
                    }
                }
            }
            return $response;
        }
        //Ako polje sekundarnih dijagnoza nije prazno
        else{
            //Inicijaliziram brojač da dopustim samo generiranje jedinstvene boje
            $brojac = 0;
            //Prolazim kroz polje sekundarnih dijagnoza i za svaku sekundarnu dijagnoze ubacivam novu n-torku u bazu
            foreach($mkbSifre as $mkb){
                //Povećavam brojač za 1 
                $brojac++;
                //Dohvaćam šifru područnog ureda HZZO-a, šifru ureda ozljede na radu, šifru sekundarne dijagnoze te šifru primarne dijagnoze
                $sqlHZZO = "SELECT sifUred FROM podrucni_ured 
                            WHERE nazivSluzbe = ?";
                $stmtHZZO = $conn->prepare($sqlHZZO); 
                $stmtHZZO->bind_param("s", $podrucniUredHZZO);
                $stmtHZZO->execute();
                $resultHZZO = $stmtHZZO->get_result(); // get the mysqli result
                while ($rowHZZO = $resultHZZO->fetch_assoc()) {
                    $sifUredHZZO = $rowHZZO["sifUred"];
                }

                $sqlOzljeda = "SELECT sifUred FROM podrucni_ured 
                                WHERE nazivSluzbe = ?";
                $stmtOzljeda = $conn->prepare($sqlOzljeda); 
                $stmtOzljeda->bind_param("s", $podrucniUredOzljeda);
                $stmtOzljeda->execute();
                $resultOzljeda = $stmtOzljeda->get_result(); // get the mysqli result
                while ($rowOzljeda = $resultOzljeda->fetch_assoc()) {
                    $sifUredOzljeda = $rowOzljeda["sifUred"];
                }

                //Kreiram upit za spremanje prvog dijela podataka u bazu
                $sql = "INSERT INTO pregled (nacinPlacanja, podrucniUredHZZO, podrucniUredOzljeda, 
                                            nazivPoduzeca, oznakaOsiguranika, nazivDrzave, mboPacijent, brIskDopunsko,
                                            mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, datumPregled,narucen, 
                                            idObradaMedSestra,vrijemePregled,prosliPregled,bojaPregled,oznaka) 
                                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                //Kreiranje prepared statementa
                $stmt = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmt,$sql)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement ne valja!";
                }
                //Ako je prepared statement u redu
                else{
                    //Ako je slučaj novi:
                    if(empty($prosliPregled)){
                        $prosliPregled = NULL;
                    }
                    //Ako je "proslaBoja" prazna, to znači da se generira novi slučaj
                    if(empty($proslaBoja)){
                        //Samo ako je prva iteracija
                        if($brojac == 1){
                            //Na početku inicijaliziram broj pronađenih boja na 0 (u slučaju da ne postoji još ova obrada i boja)
                            $brojBoja = 0;
                            //Generiram random boju
                            $boja = $poljeBoja[array_rand($poljeBoja)];
                            //Tražim je li se novo generirana boja nalazi u bazi
                            $sql = "SELECT COUNT(*) AS brojBoja FROM pregled p 
                                    WHERE p.idObradaMedSestra = '$idObrada' AND p.bojaPregled = '$boja'";
                            $result = $conn->query($sql);
                            //Ako ima pronađenih rezultata za navedenu pretragu
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $brojBoja = $row['brojBoja'];
                                }
                            }
                            //Dok ne pronađem boju koja još ne postoji u bazi za ovu sesiju obrade
                            while($brojBoja != 0){
                                //Generiraj ponovno boju
                                $boja = $poljeBoja[array_rand($poljeBoja)];
                                //Ponovno traži
                                $sql = "SELECT COUNT(*) AS brojBoja FROM pregled p
                                        WHERE p.idObradaMedSestra = '$idObrada' AND p.bojaPregled = '$boja'";
                                $result = $conn->query($sql);
                                //Ako ima pronađenih rezultata za navedenu pretragu
                                if ($result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        $brojBoja = $row['brojBoja'];
                                    }
                                }
                            }
                        }
                    }
                    //Ako "proslaBoja" nije prazna, to znači da je slučaj povezan
                    else{
                        $boja = $proslaBoja;
                    }
                    if(empty($nazivPoduzeca)){
                        $nazivPoduzeca = NULL;
                    }
                    if(empty($brIskDopunsko)){
                        $brIskDopunsko = NULL;
                    }
                    if(empty($oznakaOsiguranika)){
                        $oznakaOsiguranika = NULL;
                    }
                    if(empty($nazivDrzave)){
                        $nazivDrzave = NULL;
                    }
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmt,"sssssssssssssisiss",$nacinPlacanja, $sifUredHZZO, $sifUredOzljeda, $nazivPoduzeca,
                                $oznakaOsiguranika, $nazivDrzave, $mbo, $brIskDopunsko, $mkbPrimarnaDijagnoza,
                                $mkb, $tipSlucaj, $datum,$narucen,$idObrada,$vrijemePregled,$prosliPregled,$boja,$oznaka);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmt);

                    //Dohvaćam ID pregleda kojega sam upravo unio
                    $resultPregled = mysqli_query($conn,"SELECT MAX(p.idPregled) AS IDPregled FROM pregled p");
                    //Ulazim u polje rezultata i idem redak po redak
                    while($rowPregled = mysqli_fetch_array($resultPregled)){
                        //Dohvaćam željeni ID pregleda
                        $idPregled = $rowPregled['IDPregled'];
                    } 

                    //Ubacivam nove podatke u tablicu "ambulanta"
                    $sqlAmbulanta = "INSERT INTO ambulanta (idMedSestra,idPacijent,idPregled) VALUES (?,?,?)";
                    //Kreiranje prepared statementa
                    $stmtAmbulanta = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmtAmbulanta,$sqlAmbulanta)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement ne valja!";
                    }
                    //Ako je prepared statement u redu
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmtAmbulanta,"iii",$idMedSestra,$idPacijent,$idPregled);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmtAmbulanta);
                    }
                }
            }
            //Ažuriram tablicu zdravstvenih podataka
            $sqlZdr = "UPDATE zdr_podatci z SET z.brojIskazniceDopunsko = ?, z.kategorijaOsiguranja = ?,
                        z.drzavaOsiguranja = ?
                        WHERE z.mboPacijent = ?";
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
                mysqli_stmt_bind_param($stmtZdr,"ssss",$brIskDopunsko, $oznakaOsiguranika, $nazivDrzave, $mbo);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmtZdr);
                $response["success"] = "true";
                $response["message"] = "Podatci uspješno dodani!";
            } 
        } 
        return $response;
    }
}
?>