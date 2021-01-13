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
                                    $oznakaOsiguranika, $nazivDrzave, $mbo, $brIskDopunsko, $primarnaDijagnoza,
                                    $sekundarneDijagnoze, $tipSlucaj,$idObrada){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = []; 

        //Ako je polje sekundarnih dijagnoza prazno
        if(empty($sekundarneDijagnoze)){
            //Varijabla koja određuje je li pacijent naručen ili nije
            $narucen = NULL;
            //Trenutni datum
            $datum = date('Y-m-d');
            //Trenutno vrijeme
            $vrijeme = date('H:i');
            //Status obrade
            $statusObrada = "Aktivan";
            //Postavljam inicijalno šifru primarne dijagnoze na NULL
            $mkbSifra = NULL;
            //Postavljam inicijalno broj primarnih dijagnoza na 0
            $brojPrimarna = 0;

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
            if(!empty($primarnaDijagnoza)){
                //Dohvaćam šifru primarne dijagnoze da je mogu ubaciti u tablicu "pregled"
                $sqlPrimarna = "SELECT mkbSifra FROM dijagnoze
                WHERE imeDijagnoza = ?";
                $stmtPrimarna = $conn->prepare($sqlPrimarna); 
                $stmtPrimarna->bind_param("s", $primarnaDijagnoza);
                $stmtPrimarna->execute();
                $resultPrimarna = $stmtPrimarna->get_result(); // get the mysqli result
                while ($rowPrimarna = $resultPrimarna->fetch_assoc()) {
                    $mkbSifra = $rowPrimarna["mkbSifra"];
                }

                //Kreiram sql upit koji će prebrojiti koliko ima redaka sa istom primarnom dijagnozom na isti datum
                $sqlCountPrimarna = "SELECT COUNT(*) AS BrojPrimarna FROM pregled p 
                                    WHERE p.mkbSifraPrimarna = '$mkbSifra' AND p.datumPregled = '$datum' 
                                    AND p.mboPacijent IN 
                                    (SELECT pacijent.mboPacijent FROM pacijent 
                                    WHERE pacijent.idPacijent = '$idPacijent');";
                //Rezultat upita spremam u varijablu $resultCountPrimarna
                $resultCountPrimarna = mysqli_query($conn,$sqlCountPrimarna);
                //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                if(mysqli_num_rows($resultCountPrimarna) > 0){
                    //Idem redak po redak rezultata upita 
                    while($rowCountPrimarna = mysqli_fetch_assoc($resultCountPrimarna)){
                        //Vrijednost rezultata spremam u varijablu $brojPrimarna
                        $brojPrimarna = $rowCountPrimarna['BrojPrimarna'];
                    }
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

            //Ako medicinska sestra NE PONAVLJA istu primarnu dijagnozu na ISTI DATUM pacijentu:
            if($brojPrimarna == 0){
                //Kreiram upit za spremanje prvog dijela podataka u bazu
                $sql = "INSERT INTO pregled (nacinPlacanja, podrucniUredHZZO, podrucniUredOzljeda, 
                                            nazivPoduzeca, oznakaOsiguranika, nazivDrzave, mboPacijent, brIskDopunsko,
                                            mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, datumPregled,narucen,idObradaMedSestra) 
                                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
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
                    mysqli_stmt_bind_param($stmt,"sssssssssssssi",$nacinPlacanja, $sifUredHZZO, $sifUredOzljeda, $nazivPoduzeca,
                                                    $oznakaOsiguranika, $nazivDrzave, $mbo, $brIskDopunsko, $mkbSifra,
                                                    $prazna, $tipSlucaj, $datum,$narucen,$idObrada);
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
            //Ako medicinska sestra PONAVLJA ISTU PRIMARNU DIJAGNOZU NA ISTI DATUM, te u formarrayu imam 0 sekundarnih dijagnoza, ništa ne radim
            else{
                $response["success"] = "true";
                $response["message"] = "Podatci uspješno dodani!";  
            } 
            return $response;
        }
        //Ako polje sekundarnih dijagnoza nije prazno
        else{
            //Postavljam brojač na 0 (on služi da napravi razliku između prve sekundarne dijagnoze (ažuriranja retka) i drugih sekundarnih dijagnoza (dodavanja redaka))
            $brojac = 0;
            //Brojim koliko ima sekundarnih dijagnoza u polju
            $brojacSekundarna = count($sekundarneDijagnoze);
            //Prolazim kroz polje sekundarnih dijagnoza i za svaku sekundarnu dijagnoze ubacivam novu n-torku u bazu
            foreach($sekundarneDijagnoze as $dijagnoza){
                //Povećavam brojač za 1
                $brojac = $brojac + 1;
                //Varijabla koja određuje je li pacijent naručen ili nije
                $narucen = NULL;
                //Trenutno vrijeme
                $vrijeme = date('H:i');
                //Trenutni datum
                $datum = date('Y-m-d');
                //Status obrade
                $statusObrada = "Aktivan";
                //Postavljam inicijalno šifru primarne dijagnoze na NULL
                $mkbSifraPrimarna = NULL;
                //Incijalno postavljam broj istih primarnih dijagnoza na 0
                $brojPrimarna = 0;

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
                if(!empty($primarnaDijagnoza)){
                    //Dohvaćam šifru primarne dijagnoze na osnovu njezina imena
                    $sqlPrimarna = "SELECT mkbSifra FROM dijagnoze
                                WHERE imeDijagnoza = ?";
                    $stmtPrimarna = $conn->prepare($sqlPrimarna); 
                    $stmtPrimarna->bind_param("s", $primarnaDijagnoza);
                    $stmtPrimarna->execute();
                    $resultPrimarna = $stmtPrimarna->get_result(); // get the mysqli result
                    while ($rowPrimarna = $resultPrimarna->fetch_assoc()) {
                        $mkbSifraPrimarna = $rowPrimarna["mkbSifra"];
                    }

                    //Dohvaćam šifru sekundarne dijagnoze na osnovu njezina imena
                    $sqlSekundarna = "SELECT mkbSifra FROM dijagnoze
                                WHERE imeDijagnoza = ?";
                    $stmtSekundarna = $conn->prepare($sqlSekundarna); 
                    $stmtSekundarna->bind_param("s", $dijagnoza);
                    $stmtSekundarna->execute();
                    $resultSekundarna = $stmtSekundarna->get_result(); // get the mysqli result
                    while ($rowSekundarna = $resultSekundarna->fetch_assoc()) {
                        $mkbSifraSekundarna = $rowSekundarna["mkbSifra"];
                    }
                    //Kreiram sql upit koji će prebrojiti koliko ima SEKUNDARNIH DIJAGNOZA TRENUTNO U BAZI
                    $sqlCountSekundarna = "SELECT COUNT(p.mkbSifraSekundarna) AS BrojSekundarna FROM pregled p
                                            WHERE p.mkbSifraPrimarna = '$mkbSifraPrimarna' AND p.datumPregled = '$datum' 
                                            AND p.mboPacijent IN 
                                            (SELECT pacijent.mboPacijent FROM pacijent 
                                            WHERE pacijent.idPacijent = '$idPacijent');";
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

                    //Kreiram sql upit koji će prebrojiti koliko ima redaka sa istom primarnom dijagnozom na isti datum
                    $sqlCountPrimarna = "SELECT COUNT(*) AS BrojPrimarna FROM pregled p 
                                WHERE p.mkbSifraPrimarna = '$mkbSifraPrimarna' AND p.datumPregled = '$datum' 
                                AND p.mboPacijent IN 
                                (SELECT pacijent.mboPacijent FROM pacijent 
                                WHERE pacijent.idPacijent = '$idPacijent')";
                    //Rezultat upita spremam u varijablu $resultCountPrimarna
                    $resultCountPrimarna = mysqli_query($conn,$sqlCountPrimarna);
                    //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                    if(mysqli_num_rows($resultCountPrimarna) > 0){
                        //Idem redak po redak rezultata upita 
                        while($rowCountPrimarna = mysqli_fetch_assoc($resultCountPrimarna)){
                            //Vrijednost rezultata spremam u varijablu $brojPrimarna
                            $brojPrimarna = $rowCountPrimarna['BrojPrimarna'];
                        }
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

                //Ako medicinska sestra NE PONAVLJA istu primarnu dijagnozu na ISTI DATUM pacijentu:
                if($brojPrimarna == 0){
                    //Kreiram upit za spremanje prvog dijela podataka u bazu
                    $sql = "INSERT INTO pregled (nacinPlacanja, podrucniUredHZZO, podrucniUredOzljeda, 
                            nazivPoduzeca, oznakaOsiguranika, nazivDrzave, mboPacijent, brIskDopunsko,
                            mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, datumPregled,narucen,idObradaMedSestra) 
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
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
                        mysqli_stmt_bind_param($stmt,"sssssssssssssi",$nacinPlacanja, $sifUredHZZO, $sifUredOzljeda, $nazivPoduzeca,
                                                            $oznakaOsiguranika, $nazivDrzave, $mbo, $brIskDopunsko, $mkbSifraPrimarna,
                                                            $mkbSifraSekundarna, $tipSlucaj, $datum,$narucen,$idObrada);
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
                //Ako medicinska sestra PONAVLJA ISTU PRIMARNU DIJAGNOZU NA ISTI DATUM te ako ima SAMO JEDNA sekundarna dijagnoza u formarrayu te u bazi je 0 sekundarnih dijagnoza
                else if($brojPrimarna > 0 && $brojacSekundarna == 1 && $brojSekundarnaBaza == 0){ 
                    //Dohvaćam ID pregleda čiji redak sadrži primarnu dijagnozu koja se ponavlja
                    $sqlIDPregled= "SELECT p.idPregled AS ID FROM pregled p 
                                            WHERE p.mkbSifraPrimarna = ? AND p.datumPregled = ?
                                            AND p.mboPacijent IN 
                                            (SELECT pacijent.mboPacijent FROM pacijent 
                                            WHERE pacijent.idPacijent = ?);";
                    $stmtIDPregled = $conn->prepare($sqlIDPregled); 
                    $stmtIDPregled->bind_param("ssi", $mkbSifraPrimarna,$datum,$idPacijent);
                    $stmtIDPregled->execute();
                    $resultIDPregled = $stmtIDPregled->get_result(); // get the mysqli result
                    while ($rowIDPregled = $resultIDPregled->fetch_assoc()) {
                        $idPregled = $rowIDPregled["ID"];
                    }
                    //Ažuriram podatke
                    $sql = "UPDATE pregled p SET p.nacinPlacanja = ?, p.podrucniUredHZZO = ?, p.podrucniUredOzljeda = ?, 
                            p.nazivPoduzeca = ?, p.oznakaOsiguranika = ?,p.nazivDrzave = ?,p.mboPacijent = ?, p.brIskDopunsko = ?,
                            p.mkbSifraPrimarna = ?,p.mkbSifraSekundarna = ?,p.tipSlucaj = ?,p.datumPregled = ?, p.narucen = ?,p.idObradaMedSestra = ? 
                            WHERE p.idPregled = ?";
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
                        mysqli_stmt_bind_param($stmt,"sssssssssssssii",$nacinPlacanja,$sifUredHZZO,$sifUredOzljeda,$nazivPoduzeca,
                                                                    $oznakaOsiguranika,$nazivDrzave,$mbo,$brIskDopunsko,$mkbSifraPrimarna,
                                                                    $mkbSifraSekundarna,$tipSlucaj,$datum,$narucen,$idObrada,$idPregled);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmt);

                        $response["success"] = "true";
                        $response["message"] = "Podatci uspješno dodani!";
                    }    
                }
                //Ako medicinska sestra PONAVLJA PRIMARNU DIJAGNOZU NA ISTI DATUM te ako ima VIŠE SEKUNDARNIH dijagnoza u formarrayu, te 0 sekundarnih dijagnoza u bazi
                else if($brojPrimarna > 0 && $brojacSekundarna > 1 && $brojSekundarnaBaza == 0){
                    //Ako je brojač 1 tj. ako gledam prvu sekundarnu dijagnozu
                    if($brojac == 1){
                        //Dohvaćam ID pregleda čiji redak sadrži primarnu dijagnozu koja se ponavlja
                        $sqlIDPregled= "SELECT p.idPregled AS ID FROM pregled p 
                                        WHERE p.mkbSifraPrimarna = ? AND p.datumPregled = ?
                                        AND p.mboPacijent IN 
                                        (SELECT pacijent.mboPacijent FROM pacijent 
                                        WHERE pacijent.idPacijent = ?);";
                        $stmtIDPregled = $conn->prepare($sqlIDPregled); 
                        $stmtIDPregled->bind_param("ssi", $mkbSifraPrimarna,$datum,$idPacijent);
                        $stmtIDPregled->execute();
                        $resultIDPregled = $stmtIDPregled->get_result(); // get the mysqli result
                        while ($rowIDPregled = $resultIDPregled->fetch_assoc()) {
                            $idPregled = $rowIDPregled["ID"];
                        }
                        //Ažuriram podatke
                        $sql = "UPDATE pregled p SET p.nacinPlacanja = ?, p.podrucniUredHZZO = ?, p.podrucniUredOzljeda = ?, 
                                p.nazivPoduzeca = ?, p.oznakaOsiguranika = ?,p.nazivDrzave = ?,p.mboPacijent = ?, p.brIskDopunsko = ?,
                                p.mkbSifraPrimarna = ?,p.mkbSifraSekundarna = ?,p.tipSlucaj = ?,p.datumPregled = ?, p.narucen = ?, p.idObradaMedSestra = ? 
                                WHERE p.idPregled = ?";
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
                            mysqli_stmt_bind_param($stmt,"sssssssssssssii",$nacinPlacanja,$sifUredHZZO,$sifUredOzljeda,$nazivPoduzeca,
                                                                        $oznakaOsiguranika,$nazivDrzave,$mbo,$brIskDopunsko,$mkbSifraPrimarna,
                                                                        $mkbSifraSekundarna,$tipSlucaj,$datum,$narucen,$idObrada,$idPregled);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmt);

                            $response["success"] = "true";
                            $response["message"] = "Podatci uspješno dodani!";
                        }
                    }
                    //Ako je brojač veći od 1, nastavljam NADODAVATI NOVE RETKE 
                    if($brojac > 1){
                        //Kreiram upit za spremanje prvog dijela podataka u bazu
                        $sql = "INSERT INTO pregled (nacinPlacanja, podrucniUredHZZO, podrucniUredOzljeda, 
                                nazivPoduzeca, oznakaOsiguranika, nazivDrzave, mboPacijent, brIskDopunsko,
                                mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, datumPregled,narucen,idObradaMedSestra) 
                                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
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
                            mysqli_stmt_bind_param($stmt,"sssssssssssssi",$nacinPlacanja, $sifUredHZZO, $sifUredOzljeda, $nazivPoduzeca,
                                                                $oznakaOsiguranika, $nazivDrzave, $mbo, $brIskDopunsko, $mkbSifraPrimarna,
                                                                $mkbSifraSekundarna, $tipSlucaj, $datum,$narucen,$idObrada);
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
                //Ako medicinska sestra PONAVLJA istu primarnu dijagnozu, u formarrayu je više od 0 sekundarnih dijagnoza, te u bazi je više sekundarnih dijagnoza
                else if($brojPrimarna > 0 && $brojacSekundarna > 0 && $brojSekundarnaBaza > 0){
                    //Kreiram sql upit koji će prebrojiti koliko ima SEKUNDARNIH DIJAGNOZA TRENUTNO U BAZI KOJE SU ISTE KAO sek dijagnoze iz form arraya
                    //Ako su iste neće se unositi ponovno
                    $sqlCountSekundarna = "SELECT COUNT(p.mkbSifraSekundarna) AS BrojSekundarnih FROM pregled p 
                                        WHERE p.mkbSifraPrimarna = '$mkbSifraPrimarna' AND p.datumPregled = '$datum' 
                                        AND p.mkbSifraSekundarna = '$mkbSifraSekundarna' AND p.mboPacijent IN 
                                        (SELECT pacijent.mboPacijent FROM pacijent 
                                        WHERE pacijent.idPacijent = '$idPacijent')";
                    //Rezultat upita spremam u varijablu $resultCountPrimarna
                    $resultCountSekundarna = mysqli_query($conn,$sqlCountSekundarna);
                    //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                    if(mysqli_num_rows($resultCountSekundarna) > 0){
                        //Idem redak po redak rezultata upita 
                        while($rowCountSekundarna = mysqli_fetch_assoc($resultCountSekundarna)){
                            //Vrijednost rezultata spremam u varijablu $brojSekundarnihBaza
                            $brojSekundarnihBaza = $rowCountSekundarna['BrojSekundarnih'];
                        }
                    }
                    
                    //Ako u bazi već postoji sekundarna dijagnoza koju korisnik pokušava unijeti, neće se unijeti, ako AKO NE POSTOJI, unosi se
                    if($brojSekundarnihBaza == 0){
                        //Kreiram upit za spremanje podataka
                        $sql = "INSERT INTO pregled (nacinPlacanja, podrucniUredHZZO, podrucniUredOzljeda, 
                                nazivPoduzeca, oznakaOsiguranika, nazivDrzave, mboPacijent, brIskDopunsko,
                                mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, datumPregled,narucen,idObradaMedSestra) 
                                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
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
                            mysqli_stmt_bind_param($stmt,"sssssssssssssi",$nacinPlacanja, $sifUredHZZO, $sifUredOzljeda, $nazivPoduzeca,
                                                                $oznakaOsiguranika, $nazivDrzave, $mbo, $brIskDopunsko, $mkbSifraPrimarna,
                                                                $mkbSifraSekundarna, $tipSlucaj, $datum,$narucen,$idObrada);
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
                        $response["success"] = "true";
                        $response["message"] = "Podatci uspješno dodani!"; 
                    }
                }
            }
            return $response;
        } 
    }

}
?>