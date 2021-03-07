<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

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

    //Funkcija koja DODAVA PODATKE OPĆEG PREGLEDA PACIJENTA u bazu
    function dodajOpcePodatkePregleda($idMedSestra, $idPacijent, $nacinPlacanja, $podrucniUredHZZO, $podrucniUredOzljeda, $nazivPoduzeca,
                                    $oznakaOsiguranika, $nazivDrzave, $mbo, $brIskDopunsko, $mkbPrimarnaDijagnoza,
                                    $mkbSifre, $tipSlucaj,$idObrada){
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
        $vrijeme = date('H:i');
        //Trenutno vrijeme pregleda
        $vrijemePregled = date("H:i");
        //Status obrade
        $statusObrada = "Aktivan";
        //Ako medicinska sestra nije unijela primarnu dijagnozu na pregledu:
        if(empty($mkbPrimarnaDijagnoza)){
            //Postavljam je na NULL
            $mkbPrimarnaDijagnoza = NULL;
        }
        //Gledam koliko sek. dijagnoza ima pregled u bazi kojega povezujem
        $sqlCountSekundarna = "SELECT COUNT(p.mkbSifraSekundarna) AS BrojSekundarna FROM pregled p
                            WHERE p.idObradaMedSestra = '$poslaniIDObradaMedSestra' 
                            AND p.mboPacijent = '$mbo' 
                            AND p.mkbSifraPrimarna = '$poslanaMKBSifra' 
                            AND p.datumPregled = '$poslaniDatum' 
                            AND p.vrijemePregled = '$poslanoVrijeme'";
        //Rezultat upita spremam u varijablu $resultCountPrimarna
        $resultCountSekundarna = mysqli_query($conn,$sqlCountSekundarna);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCountSekundarna) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCountSekundarna = mysqli_fetch_assoc($resultCountSekundarna)){
                //Vrijednost rezultata spremam u varijablu $brojSekundarnaBaza
                $brojSekundarnaBaza = $rowCountSekundarna['BrojSekundarna'];
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
            $vrijeme = date("H:i", strtotime("-".(int)(date('i',strtotime($vrijeme)))." minutes", strtotime($vrijeme) ) );  
        }
        //Ako su minute vremena >= 15 && minute < 30, zaokruži na pola sata 
        else if( (int)(date('i',strtotime($vrijeme))) >= 15 && (int)(date('i',strtotime($vrijeme))) < 30){
            $vrijeme = date("H:i", strtotime("+".(30-(int)(date('i',strtotime($vrijeme))))." minutes",strtotime($vrijeme) ) );
        }
        //Ako su minute vremena > 30 && minute < 45, zaokruži na pola sata
        else if( (int)(date('i',strtotime($vrijeme))) > 30 && (int)(date('i',strtotime($vrijeme))) < 45){
            $vrijeme = date("H:i", strtotime("-".((int)(date('i',strtotime($vrijeme)))-30)." minutes", strtotime($vrijeme) ) );
        }
        //Ako su minute vremena >=45 && minute < 60, zaokruži na veći puni sat
        else if( (int)(date('i',strtotime($vrijeme))) >= 45 && (int)(date('i',strtotime($vrijeme))) < 60){
            $vrijeme = date("H:i", strtotime("+".(60-(int)(date('i',strtotime($vrijeme))))." minutes",strtotime($vrijeme) ) );
        }

        //Kreiram sql upit koji će provjeriti JE LI TRENUTNO AKTIVNI PACIJENT NARUČEN U OVO VRIJEME NA OVAJ DATUM
        $sqlCountNarucen = "SELECT COUNT(*) AS BrojNarucen FROM narucivanje n 
                            WHERE n.idPacijent = '$idPacijent' AND n.vrijemeNarucivanje = '$vrijeme' AND n.datumNarucivanje = '$datum'";
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
            //Ako je NOVI SLUČAJ:
            if($tipSlucaj == 'noviSlucaj'){
                /******************************** */
                //Provjera je li postoji već ova primarna dijagnoza u bazi
                $sqlProvjera = "SELECT p.mkbSifraPrimarna FROM pregled p
                                WHERE p.idObradaMedSestra = '$idObrada' AND p.mboPacijent = '$mbo'";
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
                /******************************** */
                //Kreiram upit za spremanje prvog dijela podataka u bazu
                $sql = "INSERT INTO pregled (nacinPlacanja, podrucniUredHZZO, podrucniUredOzljeda, 
                                            nazivPoduzeca, oznakaOsiguranika, nazivDrzave, mboPacijent, brIskDopunsko,
                                            mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, datumPregled,narucen,idObradaMedSestra,vrijemePregled) 
                                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                //Kreiranje prepared statementa
                $stmt = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmt,$sql)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement ne valja!";
                }
                //Ako je prepared statement u redu
                else{
                    $prazna = NULL;
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
                    mysqli_stmt_bind_param($stmt,"sssssssssssssis",$nacinPlacanja, $sifUredHZZO, $sifUredOzljeda, $nazivPoduzeca,
                                                    $oznakaOsiguranika, $nazivDrzave, $mbo, $brIskDopunsko, $mkbPrimarnaDijagnoza,
                                                    $prazna, $tipSlucaj, $datum,$narucen,$idObrada,$vrijeme);
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

                        $response["success"] = "true";
                        $response["message"] = "Podatci uspješno dodani!";
                    }
                }
            }
            //Ako je slučaj POVEZAN:
            else if($tipSlucaj == 'povezanSlucaj'){
                //Ako je broj sek. dijagnoza na prethodnom pregledu 0 ili 1
                if($brojSekundarnaBaza == 0 || $brojSekundarnaBaza == 1){
                    $sql ="UPDATE pregled p SET p.nacinPlacanja = ?, p.podrucniUredHZZO = ?, 
                                                p.podrucniUredOzljeda = ?, p.nazivPoduzeca = ?, p.oznakaOsiguranika = ?, 
                                                p.nazivDrzave = ?, p.brIskDopunsko = ?, p.mkbSifraPrimarna = ?,
                                                p.mkbSifraSekundarna = ?, p.tipSlucaj = ?, p.datumPregled = ?, p.narucen = ?, 
                                                p.vrijemePregled = ?, p.idObradaMedSestra = ?
                            WHERE p.idObradaMedSestra = ? 
                            AND p.mboPacijent = ? 
                            AND p.datumPregled = ? 
                            AND p.vrijemePregled = ?;"; 
                    //Kreiranje prepared statementa
                    $stmt = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmt,$sql)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement ne valja!";
                    }
                    //Ako je prepared statement u redu
                    else{
                        $sekDijagnoza = NULL;
                        if(empty($status)){
                            $status = NULL;
                        }
                        if(empty($nalaz)){
                            $nalaz = NULL;
                        }
                        if(empty($terapija)){
                            $terapija = NULL;
                        }
                        if(empty($preporukaLijecnik)){
                            $preporukaLijecnik = NULL;
                        }
                        if(empty($napomena)){
                            $napomena = NULL;
                        }
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmt,"sssssssssssssiisss",$nacinPlacanja,$sifUredHZZO,$sifUredOzljeda,$nazivPoduzeca,$oznakaOsiguranika,
                                                                    $nazivDrzave,$brIskDopunsko,$mkbPrimarnaDijagnoza,$sekDijagnoza,$tipSlucaj,
                                                                    $datum,$narucen,$vrijeme,$idObrada,$poslaniIDObradaMedSestra,$mbo, 
                                                                    $poslaniDatum,$poslanoVrijeme);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmt);
                        $response["success"] = "true";
                        $response["message"] = "Podatci uspješno dodani!";
                    }
                } 
                //Ako već postoji ova primarna dijagnoza u povijesti bolesti ove sesije obrade te ima više od jedne sek. dijagnoze u formi unosa
                else if($brojSekundarnaBaza > 1){
                    //Brišem sve retke iz tablice ambulanta za ovu povijest bolesti
                    $sqlDeleteAmbulanta = "DELETE a FROM ambulanta a
                                        JOIN pregled p ON p.idPregled = a.idPregled 
                                        WHERE p.idObradaMedSestra = ? AND p.mboPacijent = ? 
                                        AND p.datumPregled = ? AND p.vrijemePregled = ?;";
                    //Kreiranje prepared statementa
                    $stmtDeleteAmbulanta = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmtDeleteAmbulanta,$sqlDeleteAmbulanta)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement ne valja!";
                    }
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmtDeleteAmbulanta,"isss",$poslaniIDObradaMedSestra,$mbo,$poslaniDatum,$poslanoVrijeme);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmtDeleteAmbulanta);
                        //Brišem sve retke iz tablice povijesti bolesti
                        $sqlDelete = "DELETE FROM pregled 
                                    WHERE idObradaMedSestra = ? AND mboPacijent = ? 
                                    AND datumPregled = ? AND vrijemePregled = ?;";
                        //Kreiranje prepared statementa
                        $stmtDelete = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmtDelete,$sqlDelete)){
                            $response["success"] = "false";
                            $response["message"] = "Prepared statement ne valja!";
                        }
                        else{
                            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                            mysqli_stmt_bind_param($stmtDelete,"isss",$poslaniIDObradaMedSestra,$mbo,$poslaniDatum,$poslanoVrijeme);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmtDelete);  
                            //Kreiram upit za spremanje prvog dijela podataka u bazu
                            $sql = "INSERT INTO pregled (nacinPlacanja, podrucniUredHZZO, podrucniUredOzljeda, 
                                    nazivPoduzeca, oznakaOsiguranika, nazivDrzave, mboPacijent, brIskDopunsko,
                                    mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, datumPregled,narucen,idObradaMedSestra,vrijemePregled) 
                                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                            //Kreiranje prepared statementa
                            $stmt = mysqli_stmt_init($conn);
                            //Ako je statement neuspješan
                            if(!mysqli_stmt_prepare($stmt,$sql)){
                                $response["success"] = "false";
                                $response["message"] = "Prepared statement ne valja!";
                            }
                            //Ako je prepared statement u redu
                            else{
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
                                mysqli_stmt_bind_param($stmt,"sssssssssssssis",$nacinPlacanja, $sifUredHZZO, $sifUredOzljeda, $nazivPoduzeca,
                                                        $oznakaOsiguranika, $nazivDrzave, $mbo, $brIskDopunsko, $mkbPrimarnaDijagnoza,
                                                        $sekDijagnoza, $tipSlucaj, $datum,$narucen,$idObrada,$vrijeme);
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

                                    $response["success"] = "true";
                                    $response["message"] = "Podatci uspješno dodani!";
                                }
                            } 
                        }
                    }
                } 
            } 
            return $response;
        }
        //Ako polje sekundarnih dijagnoza nije prazno
        else{
            //Kreiram upit koji dohvaća MINIMALNI ID povijesti bolesti za određenog pacijenta i određenu sesiju obrade
            $sqlMin = "SELECT p.idPregled FROM pregled p 
                    WHERE p.idObradaMedSestra = '$poslaniIDObradaMedSestra' 
                    AND p.mboPacijent = '$mbo' 
                    AND p.datumPregled = '$poslaniDatum' 
                    AND p.vrijemePregled = '$poslanoVrijeme'
                    AND p.idPregled = 
                    (SELECT MIN(p2.idPregled) FROM pregled p2 
                    WHERE p2.idObradaMedSestra = '$poslaniIDObradaMedSestra' 
                    AND p2.mboPacijent = '$mbo' 
                    AND p2.datumPregled = '$poslaniDatum' 
                    AND p2.vrijemePregled = '$poslanoVrijeme')";
            $resultMin = $conn->query($sqlMin);
                    
            //Ako pacijent IMA evidentiranih recepata:
            if ($resultMin->num_rows > 0) {
                while($rowMin = $resultMin->fetch_assoc()) {
                    //Dohvaćam pregled sa MINIMALNIM ID-om
                    $idMinPregled = $rowMin['idPregled'];
                }
            }
            //Postavljam inicijalno brojač ažuriranih redaka na 0
            $brojacAzuriranihRedaka = 0;
            //Postavljam brojač na 0 (on služi da napravi razliku između prve sekundarne dijagnoze (ažuriranja retka) i drugih sekundarnih dijagnoza (dodavanja redaka))
            $brojacIteracija = 0;
            //Brojim koliko ima sekundarnih dijagnoza u polju
            $brojacSekundarnaForma = count($mkbSifre);
            //Inicijaliziram varijablu $brisanje na false na početku
            $brisanje = false;
            //Ako je POVEZAN SLUČAJ:
            if($tipSlucaj == 'povezanSlucaj'){
                //Ako je broj dijagnoza u bazi VEĆI od broja dijagnoza u formi
                if($brojSekundarnaBaza > $brojacSekundarnaForma){
                    //Označavam da treba obrisati sve retke pa nadodati kasnije
                    $brisanje = true;
                    //Brišem sve retke iz tablice ambulanta za ovu povijest bolesti
                    $sqlDeleteAmbulanta = "DELETE a FROM ambulanta a
                                        JOIN pregled p ON p.idPregled = a.idPregled 
                                        WHERE p.idObradaMedSestra = ? AND p.mboPacijent = ? 
                                        AND p.datumPregled = ? AND p.vrijemePregled = ?;";
                    //Kreiranje prepared statementa
                    $stmtDeleteAmbulanta = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmtDeleteAmbulanta,$sqlDeleteAmbulanta)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement ne valja!";
                    }
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmtDeleteAmbulanta,"isss",$poslaniIDObradaMedSestra,$mbo,$poslaniDatum,$poslanoVrijeme);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmtDeleteAmbulanta);
                        //Brišem sve retke iz tablice povijesti bolesti
                        $sqlDelete = "DELETE FROM pregled 
                                    WHERE idObradaMedSestra = ? AND mboPacijent = ? 
                                    AND datumPregled = ? AND vrijemePregled = ?";
                        //Kreiranje prepared statementa
                        $stmtDelete = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmtDelete,$sqlDelete)){
                            $response["success"] = "false";
                            $response["message"] = "Prepared statement ne valja!";
                        }
                        else{
                            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                            mysqli_stmt_bind_param($stmtDelete,"isss",$poslaniIDObradaMedSestra,$mbo,$poslaniDatum,$poslanoVrijeme);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmtDelete);
                        }
                    }
                }
            }
            //Prolazim kroz polje sekundarnih dijagnoza i za svaku sekundarnu dijagnoze ubacivam novu n-torku u bazu
            foreach($mkbSifre as $mkb){
                //Povećavam brojač za 1
                $brojacIteracija = $brojacIteracija + 1;

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

                //Ako je slučaj NOVI:
                if($tipSlucaj == 'noviSlucaj'){
                    //Kreiram upit za spremanje prvog dijela podataka u bazu
                    $sql = "INSERT INTO pregled (nacinPlacanja, podrucniUredHZZO, podrucniUredOzljeda, 
                            nazivPoduzeca, oznakaOsiguranika, nazivDrzave, mboPacijent, brIskDopunsko,
                            mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, datumPregled,narucen,idObradaMedSestra,vrijemePregled) 
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                    //Kreiranje prepared statementa
                    $stmt = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmt,$sql)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement ne valja!";
                    }
                    //Ako je prepared statement u redu
                    else{
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
                        mysqli_stmt_bind_param($stmt,"sssssssssssssis",$nacinPlacanja, $sifUredHZZO, $sifUredOzljeda, $nazivPoduzeca,
                                                            $oznakaOsiguranika, $nazivDrzave, $mbo, $brIskDopunsko, $mkbPrimarnaDijagnoza,
                                                            $mkb, $tipSlucaj, $datum,$narucen,$idObrada,$vrijeme);
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

                            $response["success"] = "true";
                            $response["message"] = "Podatci uspješno dodani!";
                        }
                    }
                }
                //Ako je slučaj POVEZAN:
                else if($tipSlucaj == 'povezanSlucaj'){
                    //Ako VEĆ POSTOJI primarna dijagnoza za ovu obradu TE npr. (BAZA = 0, FORMA = 1) ILI (BAZA = 1, FORMA = 1)
                    if($brojSekundarnaBaza <= $brojacSekundarnaForma && $brojacSekundarnaForma == 1){
                        $sql ="UPDATE pregled p SET p.nacinPlacanja = ?, p.podrucniUredHZZO = ?, 
                                                        p.podrucniUredOzljeda = ?, p.nazivPoduzeca = ?, p.oznakaOsiguranika = ?, 
                                                        p.nazivDrzave = ?, p.brIskDopunsko = ?, p.mkbSifraPrimarna = ?,
                                                        p.mkbSifraSekundarna = ?, p.tipSlucaj = ?, p.datumPregled = ?, p.narucen = ?, 
                                                        p.vrijemePregled = ?, p.idObradaMedSestra = ?
                                WHERE p.idObradaMedSestra = ? 
                                AND p.mboPacijent = ? 
                                AND p.datumPregled = ? 
                                AND p.vrijemePregled = ?;"; 
                        //Kreiranje prepared statementa
                        $stmt = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmt,$sql)){
                            $response["success"] = "false";
                            $response["message"] = "Prepared statement ne valja!";
                        }
                        //Ako je prepared statement u redu
                        else{
                            if(empty($status)){
                                $status = NULL;
                            }
                            if(empty($nalaz)){
                                $nalaz = NULL;
                            }
                            if(empty($terapija)){
                                $terapija = NULL;
                            }
                            if(empty($preporukaLijecnik)){
                                $preporukaLijecnik = NULL;
                            }
                            if(empty($napomena)){
                                $napomena = NULL;
                            }
                            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                            mysqli_stmt_bind_param($stmt,"sssssssssssssiisss",$nacinPlacanja,$sifUredHZZO,$sifUredOzljeda,$nazivPoduzeca,$oznakaOsiguranika,
                                                                        $nazivDrzave,$brIskDopunsko,$mkbPrimarnaDijagnoza,$mkb,$tipSlucaj,
                                                                        $datum,$narucen,$vrijeme,$idObrada,$poslaniIDObradaMedSestra,$mbo, 
                                                                        $poslaniDatum,$poslanoVrijeme);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmt);
                            $response["success"] = "true";
                            $response["message"] = "Podatci uspješno dodani!";
                        }
                    }
                    //Ako VEĆ POSTOJI unesena primarna dijagnoza te npr. (BAZA = 1, FORMA = 2, BAZA = 2, FORMA = 2)
                    else if($brojSekundarnaBaza <= $brojacSekundarnaForma && $brojacSekundarnaForma > 1){
                        //Ako je broj sek. dijagnoza u bazi JEDNAK 0 te je prva iteracija (tj. prva dijagnoza forme)
                        if($brojSekundarnaBaza == 0 && $brojacIteracija == 1){
                            $sql ="UPDATE pregled p SET p.nacinPlacanja = ?, p.podrucniUredHZZO = ?, 
                                                        p.podrucniUredOzljeda = ?, p.nazivPoduzeca = ?, p.oznakaOsiguranika = ?, 
                                                        p.nazivDrzave = ?, p.brIskDopunsko = ?, p.mkbSifraPrimarna = ?,
                                                        p.mkbSifraSekundarna = ?, p.tipSlucaj = ?, p.datumPregled = ?, p.narucen = ?, 
                                                        p.vrijemePregled = ?, p.idObradaMedSestra = ?
                                    WHERE p.idObradaMedSestra = ? 
                                    AND p.mboPacijent = ? 
                                    AND p.datumPregled = ? 
                                    AND p.vrijemePregled = ?"; 
                            //Kreiranje prepared statementa
                            $stmt = mysqli_stmt_init($conn);
                            //Ako je statement neuspješan
                            if(!mysqli_stmt_prepare($stmt,$sql)){
                                $response["success"] = "false";
                                $response["message"] = "Prepared statement ne valja!";
                            }
                            //Ako je prepared statement u redu
                            else{
                                if(empty($status)){
                                    $status = NULL;
                                }
                                if(empty($nalaz)){
                                    $nalaz = NULL;
                                }
                                if(empty($terapija)){
                                    $terapija = NULL;
                                }
                                if(empty($preporukaLijecnik)){
                                    $preporukaLijecnik = NULL;
                                }
                                if(empty($napomena)){
                                    $napomena = NULL;
                                }
                                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                mysqli_stmt_bind_param($stmt,"sssssssssssssiisss",$nacinPlacanja,$sifUredHZZO,$sifUredOzljeda,$nazivPoduzeca,$oznakaOsiguranika,
                                                                            $nazivDrzave,$brIskDopunsko,$mkbPrimarnaDijagnoza,$mkb,$tipSlucaj,
                                                                            $datum,$narucen,$vrijeme,$idObrada,$poslaniIDObradaMedSestra,$mbo, 
                                                                            $poslaniDatum,$poslanoVrijeme);
                                //Izvršavanje statementa
                                mysqli_stmt_execute($stmt);
                                $response["success"] = "true";
                                $response["message"] = "Podatci uspješno dodani!";
                            }
                        }
                        //Ako je broj sek. dijagnoza u BAZI JENDAK 0 te je n-ta iteracija (tj. n-ta dijagnoza forme)
                        else if($brojSekundarnaBaza == 0 && $brojacIteracija > 1){
                            //Kreiram upit za spremanje prvog dijela podataka u bazu
                            $sql = "INSERT INTO pregled (nacinPlacanja, podrucniUredHZZO, podrucniUredOzljeda, 
                                    nazivPoduzeca, oznakaOsiguranika, nazivDrzave, mboPacijent, brIskDopunsko,
                                    mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, datumPregled,narucen,idObradaMedSestra,vrijemePregled) 
                                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                            //Kreiranje prepared statementa
                            $stmt = mysqli_stmt_init($conn);
                            //Ako je statement neuspješan
                            if(!mysqli_stmt_prepare($stmt,$sql)){
                                $response["success"] = "false";
                                $response["message"] = "Prepared statement ne valja!";
                            }
                            //Ako je prepared statement u redu
                            else{
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
                                mysqli_stmt_bind_param($stmt,"sssssssssssssis",$nacinPlacanja, $sifUredHZZO, $sifUredOzljeda, $nazivPoduzeca,
                                                                    $oznakaOsiguranika, $nazivDrzave, $mbo, $brIskDopunsko, $mkbPrimarnaDijagnoza,
                                                                    $mkb, $tipSlucaj, $datum,$narucen,$idObrada,$vrijeme);
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

                                    $response["success"] = "true";
                                    $response["message"] = "Podatci uspješno dodani!";
                                }
                            }
                        }
                        //Ako je broj obrađenih redaka manji od broja dijagnoza u bazi te je prva iteracija (koristim PRVI MINIMALNI ID recepta)
                        if($brojacAzuriranihRedaka < $brojSekundarnaBaza && $brojacIteracija == 1){
                            $sql ="UPDATE pregled p SET p.nacinPlacanja = ?, p.podrucniUredHZZO = ?, 
                                                        p.podrucniUredOzljeda = ?, p.nazivPoduzeca = ?, p.oznakaOsiguranika = ?, 
                                                        p.nazivDrzave = ?, p.brIskDopunsko = ?, p.mkbSifraPrimarna = ?,
                                                        p.mkbSifraSekundarna = ?, p.tipSlucaj = ?, p.datumPregled = ?, p.narucen = ?, 
                                                        p.vrijemePregled = ?, p.idObradaMedSestra = ?
                                    WHERE p.idObradaMedSestra = ? 
                                    AND p.mboPacijent = ? 
                                    AND p.datumPregled = ? 
                                    AND p.vrijemePregled = ?"; 
                            //Kreiranje prepared statementa
                            $stmt = mysqli_stmt_init($conn);
                            //Ako je statement neuspješan
                            if(!mysqli_stmt_prepare($stmt,$sql)){
                                $response["success"] = "false";
                                $response["message"] = "Prepared statement ne valja!";
                            }
                            //Ako je prepared statement u redu
                            else{
                                if(empty($status)){
                                    $status = NULL;
                                }
                                if(empty($nalaz)){
                                    $nalaz = NULL;
                                }
                                if(empty($terapija)){
                                    $terapija = NULL;
                                }
                                if(empty($preporukaLijecnik)){
                                    $preporukaLijecnik = NULL;
                                }
                                if(empty($napomena)){
                                    $napomena = NULL;
                                }
                                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                mysqli_stmt_bind_param($stmt,"sssssssssssssiisss",$nacinPlacanja,$sifUredHZZO,$sifUredOzljeda,$nazivPoduzeca,$oznakaOsiguranika,
                                                                            $nazivDrzave,$brIskDopunsko,$mkbPrimarnaDijagnoza,$mkb,$tipSlucaj,
                                                                            $datum,$narucen,$vrijeme,$idObrada,$poslaniIDObradaMedSestra,$mbo, 
                                                                            $poslaniDatum,$poslanoVrijeme);
                                //Izvršavanje statementa
                                mysqli_stmt_execute($stmt);
                                //Povećam broj ažuriranih redaka
                                $brojacAzuriranihRedaka++;
                            }
                        }
                        //Ako je broj obrađenih redaka manji od broja dijagnoza u bazi te NIJE prva iteracija
                        else if($brojacAzuriranihRedaka < $brojSekundarnaBaza && $brojacIteracija > 1){
                            //Kreiram upit koji dohvaća SLJEDEĆI MINIMALNI ID povijesti bolesti za ovog pacijenta za ovu sesiju obrade
                            $sqlSljedeciMin = "SELECT p.idPregled FROM pregled p 
                                            WHERE p.idObradaMedSestra = '$poslaniIDObradaMedSestra' 
                                            AND p.mboPacijent = '$mbo' 
                                            AND p.datumPregled = '$poslaniDatum' 
                                            AND p.vrijemePregled = '$poslanoVrijeme'
                                            AND p.idPregled = 
                                            (SELECT p2.idPregled FROM pregled p2  
                                            WHERE p2.idObradaMedSestra = '$poslaniIDObradaMedSestra' 
                                            AND p2.mboPacijent = '$mbo' 
                                            AND p2.datumPregled = '$poslaniDatum' 
                                            AND p2.vrijemePregled = '$poslanoVrijeme'
                                            AND p2.idPregled > '$idMinPregled'
                                            LIMIT 1)";
                            $resultSljedeciMin = $conn->query($sqlSljedeciMin);
                                    
                            //Ako pacijent IMA evidentiranih pregleda
                            if ($resultSljedeciMin->num_rows > 0) {
                                while($rowSljedeciMin = $resultSljedeciMin->fetch_assoc()) {
                                    //Dohvaćam pregled sa SLJEDEĆIM MINIMALNIM ID-om
                                    $idMinPregled = $rowSljedeciMin['idPregled'];
                                }
                            }
                            $sql ="UPDATE pregled p SET p.nacinPlacanja = ?, p.podrucniUredHZZO = ?, 
                                                        p.podrucniUredOzljeda = ?, p.nazivPoduzeca = ?, p.oznakaOsiguranika = ?, 
                                                        p.nazivDrzave = ?, p.brIskDopunsko = ?, p.mkbSifraPrimarna = ?,
                                                        p.mkbSifraSekundarna = ?, p.tipSlucaj = ?, p.datumPregled = ?, p.narucen = ?, 
                                                        p.vrijemePregled = ?, p.idObradaMedSestra = ?
                                    WHERE p.idObradaMedSestra = ? 
                                    AND p.mboPacijent = ? 
                                    AND p.datumPregled = ? 
                                    AND p.vrijemePregled = ?;"; 
                            //Kreiranje prepared statementa
                            $stmt = mysqli_stmt_init($conn);
                            //Ako je statement neuspješan
                            if(!mysqli_stmt_prepare($stmt,$sql)){
                                $response["success"] = "false";
                                $response["message"] = "Prepared statement ne valja!";
                            }
                            //Ako je prepared statement u redu
                            else{
                                if(empty($status)){
                                    $status = NULL;
                                }
                                if(empty($nalaz)){
                                    $nalaz = NULL;
                                }
                                if(empty($terapija)){
                                    $terapija = NULL;
                                }
                                if(empty($preporukaLijecnik)){
                                    $preporukaLijecnik = NULL;
                                }
                                if(empty($napomena)){
                                    $napomena = NULL;
                                }
                                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                mysqli_stmt_bind_param($stmt,"sssssssssssssiisss",$nacinPlacanja,$sifUredHZZO,$sifUredOzljeda,$nazivPoduzeca,$oznakaOsiguranika,
                                                                            $nazivDrzave,$brIskDopunsko,$mkbPrimarnaDijagnoza,$mkb,$tipSlucaj,
                                                                            $datum,$narucen,$vrijeme,$idObrada,$poslaniIDObradaMedSestra,$mbo, 
                                                                            $poslaniDatum,$poslanoVrijeme);
                                //Izvršavanje statementa
                                mysqli_stmt_execute($stmt);
                                //Povećam broj ažuriranih redaka
                                $brojacAzuriranihRedaka++;
                            }
                        }
                        //Ako je broj ažuriranih redak JEDNAK broju sek. dijagnoza u bazi (npr. 2 == 2) I brojač iteracija JE VEĆI od broja sek. dijagnoza u bazi (npr. 3 > 2) 
                        //te da je broj sek. dijagnoza u BAZI VEĆI OD 0
                        if($brojacAzuriranihRedaka == $brojSekundarnaBaza && $brojacIteracija > $brojSekundarnaBaza && $brojSekundarnaBaza > 0){
                            //Kreiram upit za spremanje prvog dijela podataka u bazu
                            $sql = "INSERT INTO pregled (nacinPlacanja, podrucniUredHZZO, podrucniUredOzljeda, 
                                    nazivPoduzeca, oznakaOsiguranika, nazivDrzave, mboPacijent, brIskDopunsko,
                                    mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, datumPregled,narucen,idObradaMedSestra,vrijemePregled) 
                                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                            //Kreiranje prepared statementa
                            $stmt = mysqli_stmt_init($conn);
                            //Ako je statement neuspješan
                            if(!mysqli_stmt_prepare($stmt,$sql)){
                                $response["success"] = "false";
                                $response["message"] = "Prepared statement ne valja!";
                            }
                            //Ako je prepared statement u redu
                            else{
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
                                mysqli_stmt_bind_param($stmt,"sssssssssssssis",$nacinPlacanja, $sifUredHZZO, $sifUredOzljeda, $nazivPoduzeca,
                                                                    $oznakaOsiguranika, $nazivDrzave, $mbo, $brIskDopunsko, $mkbPrimarnaDijagnoza,
                                                                    $mkb, $tipSlucaj, $datum,$narucen,$idObrada,$vrijeme);
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

                                    $response["success"] = "true";
                                    $response["message"] = "Podatci uspješno dodani!";
                                }
                            }
                        }
                    }
                    /**************************************** */
                    //Ako su retci izbrisani, treba nadodati nove dijagnoze iz forme
                    else if($brisanje == true){
                        //Kreiram upit za spremanje prvog dijela podataka u bazu
                        $sql = "INSERT INTO pregled (nacinPlacanja, podrucniUredHZZO, podrucniUredOzljeda, 
                                                nazivPoduzeca, oznakaOsiguranika, nazivDrzave, mboPacijent, brIskDopunsko,
                                                mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, datumPregled,narucen,idObradaMedSestra,vrijemePregled) 
                                                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                        //Kreiranje prepared statementa
                        $stmt = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmt,$sql)){
                            $response["success"] = "false";
                            $response["message"] = "Prepared statement ne valja!";
                        }
                        //Ako je prepared statement u redu
                        else{
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
                            mysqli_stmt_bind_param($stmt,"sssssssssssssis",$nacinPlacanja, $sifUredHZZO, $sifUredOzljeda, $nazivPoduzeca,
                                                                $oznakaOsiguranika, $nazivDrzave, $mbo, $brIskDopunsko, $mkbPrimarnaDijagnoza,
                                                                $mkb, $tipSlucaj, $datum,$narucen,$idObrada,$vrijeme);
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

                                $response["success"] = "true";
                                $response["message"] = "Podatci uspješno dodani!";
                            }
                        }
                    }
                }
            }
            return $response;
        } 
    }

}
?>