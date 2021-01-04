<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class PovijestBolestiService{

    //Kreiram funkciju koja će potvrditi povijest bolesti
    function potvrdiPovijestBolesti($idLijecnik,$idPacijent,$razlogDolaska,$anamneza,$status,
                                    $nalaz,$primarnaDijagnoza,$sekundarneDijagnoze,$tipSlucaj,
                                    $terapija,$preporukaLijecnik,$napomena){
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
            //Status pacijenta
            $statusObrada = "Aktivan";
            //Inicijalno postavljam šifru primarne dijagnoze na NULL
            $mkbSifra = NULL;
            //Postavljam inicijalno broj primarnih dijagnoza na 0
            $brojPrimarna = 0;

            //Ako je unesena primarna dijagnoza
            if(!empty($primarnaDijagnoza)){
                //Dohvaćam šifru primarne dijagnoze da je mogu ubaciti u tablicu "povijestBolesti"
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
                $sqlCountPrimarna = "SELECT COUNT(*) AS BrojPrimarna FROM povijestBolesti pb 
                                    WHERE pb.mkbSifraPrimarna = '$mkbSifra' AND pb.datum = '$datum' 
                                    AND pb.mboPacijent IN 
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

            //Kreiram upit za dohvaćanjem MBO-a pacijenta kojemu se upisivao povijest bolesti
            $sqlMBO = "SELECT p.mboPacijent AS MBO FROM pacijent p 
                    WHERE p.idPacijent = '$idPacijent'";
            //Rezultat upita spremam u varijablu $resultCountDijagnoza
            $resultMBO = mysqli_query($conn,$sqlMBO);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($resultMBO) > 0){
                //Idem redak po redak rezultata upita 
                while($rowMBO = mysqli_fetch_assoc($resultMBO)){
                    //Vrijednost rezultata spremam u varijablu $BrojDijagnoza
                    $mboPacijent = $rowMBO['MBO'];
                }
            }   
            //Ako liječnik NE PONAVLJA istu primarnu dijagnozu na isti datum pacijentu:
            if($brojPrimarna == 0){
                //Kreiram upit za spremanje prvog dijela podataka u bazu
                $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                        nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                        preporukaLijecnik, napomena, datum, narucen, mboPacijent) 
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
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
                    mysqli_stmt_bind_param($stmt,"sssssssssssss",$razlogDolaska,$anamneza,$status,$nalaz,$mkbSifra,$prazna,
                                                    $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen,$mboPacijent);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmt);

                    //Dohvaćam ID povijesti bolesti kojega sam upravo unio
                    $resultPovijestBolesti = mysqli_query($conn,"SELECT MAX(pb.idPovijestBolesti) AS ID FROM povijestBolesti pb");
                        //Ulazim u polje rezultata i idem redak po redak
                        while($rowPovijestBolesti = mysqli_fetch_array($resultPovijestBolesti)){
                            //Dohvaćam željeni ID povijesti bolesti
                            $idPovijestBolesti = $rowPovijestBolesti['ID'];
                    }

                    //Ubacivam nove podatke u tablicu "ambulanta"
                    $sqlAmbulanta = "INSERT INTO ambulanta (idLijecnik,idPacijent,idPovijestBolesti) VALUES (?,?,?)";
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
                        mysqli_stmt_bind_param($stmtAmbulanta,"iii",$idLijecnik,$idPacijent,$idPovijestBolesti);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmtAmbulanta);

                        $response["success"] = "true";
                        $response["message"] = "Podatci uspješno dodani!";
                    }
                }
            }
            //Ako liječnik PONAVLJA ISTU PRIMARNU DIJAGNOZU NA ISTI DATUM, te u formarrayu imam 0 sekundarnih dijagnoza, ništa ne radim
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
                //Trenutni datum
                $datum = date('Y-m-d');
                //Trenutno vrijeme
                $vrijeme = date('H:i');
                //Status pacijenta
                $statusObrada = "Aktivan";
                //Inicijalno postavljam šifru primarne dijagnoze na NULL
                $mkbSifraPrimarna = NULL;
                //Incijalno postavljam broj istih primarnih dijagnoza na 0
                $brojPrimarna = 0;

                $sqlPrimarna = "SELECT mkbSifra FROM dijagnoze
                                WHERE imeDijagnoza = ?";
                $stmtPrimarna = $conn->prepare($sqlPrimarna); 
                $stmtPrimarna->bind_param("s", $primarnaDijagnoza);
                $stmtPrimarna->execute();
                $resultPrimarna = $stmtPrimarna->get_result(); // get the mysqli result
                while ($rowPrimarna = $resultPrimarna->fetch_assoc()) {
                    $mkbSifraPrimarna = $rowPrimarna["mkbSifra"];
                }

                //Kreiram sql upit koji će prebrojiti koliko ima SEKUNDARNIH DIJAGNOZA TRENUTNO U BAZI ZA ODREĐENU PRIMARNU DIJAGNOZU ZA ODREĐENI DATUM I ODREĐENOG PACIJENTA
                $sqlCountSekundarna = "SELECT COUNT(pb.mkbSifraSekundarna) AS BrojSekundarna FROM povijestbolesti pb
                                        WHERE pb.mkbSifraPrimarna = '$mkbSifraPrimarna' AND pb.datum = '$datum' 
                                        AND pb.mboPacijent IN 
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
                $sqlCountPrimarna = "SELECT COUNT(*) AS BrojPrimarna FROM povijestBolesti pb 
                                    WHERE pb.mkbSifraPrimarna = '$mkbSifraPrimarna' AND pb.datum = '$datum' 
                                    AND pb.mboPacijent IN 
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
                //Dohvaćam šifru sekundarne dijagnoze na osnovu njezinog imena
                $sqlSekundarna = "SELECT mkbSifra FROM dijagnoze
                                WHERE imeDijagnoza = ?";
                $stmtSekundarna = $conn->prepare($sqlSekundarna); 
                $stmtSekundarna->bind_param("s", $dijagnoza);
                $stmtSekundarna->execute();
                $resultSekundarna = $stmtSekundarna->get_result(); // get the mysqli result
                while ($rowSekundarna = $resultSekundarna->fetch_assoc()) {
                    $mkbSifraSekundarna = $rowSekundarna["mkbSifra"];
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
                //Kreiram upit za dohvaćanjem MBO-a pacijenta kojemu se upisivao povijest bolesti
                $sqlMBO = "SELECT p.mboPacijent AS MBO FROM pacijent p 
                            WHERE p.idPacijent = '$idPacijent'";
                //Rezultat upita spremam u varijablu $resultCountDijagnoza
                $resultMBO = mysqli_query($conn,$sqlMBO);
                //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                if(mysqli_num_rows($resultMBO) > 0){
                    //Idem redak po redak rezultata upita 
                    while($rowMBO = mysqli_fetch_assoc($resultMBO)){
                        //Vrijednost rezultata spremam u varijablu $BrojDijagnoza
                        $mboPacijent = $rowMBO['MBO'];
                    }
                }    

                //Ako liječnik NE PONAVLJA istu primarnu dijagnozu na isti datum pacijentu:
                if($brojPrimarna == 0){
                    //Kreiram upit za spremanje prvog dijela podataka u bazu
                    $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                                                        nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                                        preporukaLijecnik, napomena, datum, narucen, mboPacijent) 
                                                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
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
                        mysqli_stmt_bind_param($stmt,"sssssssssssss",$razlogDolaska,$anamneza,$status,$nalaz,$mkbSifraPrimarna,$mkbSifraSekundarna,
                                                                    $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen,$mboPacijent);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmt);
        
                        //Dohvaćam ID povijesti bolesti kojega sam upravo unio
                        $resultPovijestBolesti = mysqli_query($conn,"SELECT MAX(pb.idPovijestBolesti) AS ID FROM povijestBolesti pb");
                        //Ulazim u polje rezultata i idem redak po redak
                        while($rowPovijestBolesti = mysqli_fetch_array($resultPovijestBolesti)){
                            //Dohvaćam željeni ID povijesti bolesti
                            $idPovijestBolesti = $rowPovijestBolesti['ID'];
                        } 

                        //Ubacivam nove podatke u tablicu "ambulanta"
                        $sqlAmbulanta = "INSERT INTO ambulanta (idLijecnik,idPacijent,idPovijestBolesti) VALUES (?,?,?)";
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
                            mysqli_stmt_bind_param($stmtAmbulanta,"iii",$idLijecnik,$idPacijent,$idPovijestBolesti);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmtAmbulanta);
    
                            $response["success"] = "true";
                            $response["message"] = "Podatci uspješno dodani!";
                        }
                    }
                }
                //Ako liječnik PONAVLJA ISTU PRIMARNU DIJAGNOZU NA ISTI DATUM te ako ima SAMO JEDNA sekundarna dijagnoza u formarrayu te u bazi je 0 sekundarnih dijagnoza
                else if($brojPrimarna > 0 && $brojacSekundarna == 1 && $brojSekundarnaBaza == 0){
                    //Dohvaćam ID povijesti bolesti čiji redak sadrži primarnu dijagnozu koja se ponavlja
                    $sqlIDPovijestBolesti= "SELECT pb.idPovijestBolesti AS ID FROM povijestBolesti pb 
                                            WHERE pb.mkbSifraPrimarna = ? AND pb.datum = ?
                                            AND pb.mboPacijent IN 
                                            (SELECT pacijent.mboPacijent FROM pacijent 
                                            WHERE pacijent.idPacijent = ?);";
                    $stmtIDPovijestBolesti = $conn->prepare($sqlIDPovijestBolesti); 
                    $stmtIDPovijestBolesti->bind_param("ssi", $mkbSifraPrimarna,$datum,$idPacijent);
                    $stmtIDPovijestBolesti->execute();
                    $resultIDPovijestBolesti = $stmtIDPovijestBolesti->get_result(); // get the mysqli result
                    while ($rowIDPovijestBolesti = $resultIDPovijestBolesti->fetch_assoc()) {
                        $idPovijestBolesti = $rowIDPovijestBolesti["ID"];
                    }
                    //Kreiram upit za spremanje prvog dijela podataka u bazu
                    $sql = "UPDATE povijestBolesti pb SET pb.razlogDolaska = ?, pb.anamneza = ?, pb.statusPacijent = ?, 
                            pb.nalaz = ?, pb.mkbSifraPrimarna = ?,pb.mkbSifraSekundarna = ?,pb.tipSlucaj = ?, pb.terapija = ?,
                            pb.preporukaLijecnik = ?,pb.napomena = ?,pb.datum = ?,pb.narucen = ?, pb.mboPacijent = ? 
                            WHERE pb.idPovijestBolesti = ?";
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
                        mysqli_stmt_bind_param($stmt,"sssssssssssssi",$razlogDolaska,$anamneza,$status,$nalaz,$mkbSifraPrimarna,$mkbSifraSekundarna,
                                                        $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen,$mboPacijent,$idPovijestBolesti);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmt);

                        $response["success"] = "true";
                        $response["message"] = "Podatci uspješno dodani!";
                    }
                }
                //Ako liječnik PONAVLJA PRIMARNU DIJAGNOZU NA ISTI DATUM te ako ima VIŠE SEKUNDARNIH dijagnoza u formarrayu, te 0 sekundarnih dijagnoza u bazi
                else if($brojPrimarna > 0 && $brojacSekundarna > 1 && $brojSekundarnaBaza == 0){
                    //Ako je brojač 1 tj. ako gledam prvu sekundarnu dijagnozu
                    if($brojac == 1){
                        //Dohvaćam ID povijesti bolesti čiji redak sadrži primarnu dijagnozu koja se ponavlja
                        $sqlIDPovijestBolesti= "SELECT pb.idPovijestBolesti AS ID FROM povijestBolesti pb 
                                                WHERE pb.mkbSifraPrimarna = ? AND pb.datum = ?
                                                AND pb.mboPacijent IN 
                                                (SELECT pacijent.mboPacijent FROM pacijent 
                                                WHERE pacijent.idPacijent = ?);";
                        $stmtIDPovijestBolesti = $conn->prepare($sqlIDPovijestBolesti); 
                        $stmtIDPovijestBolesti->bind_param("ssi", $mkbSifraPrimarna,$datum,$idPacijent);
                        $stmtIDPovijestBolesti->execute();
                        $resultIDPovijestBolesti = $stmtIDPovijestBolesti->get_result(); // get the mysqli result
                        while ($rowIDPovijestBolesti = $resultIDPovijestBolesti->fetch_assoc()) {
                            $idPovijestBolesti = $rowIDPovijestBolesti["ID"];
                        }
                        //Kreiram upit za spremanje prvog dijela podataka u bazu
                        $sql = "UPDATE povijestBolesti pb SET pb.razlogDolaska = ?, pb.anamneza = ?, pb.statusPacijent = ?, 
                                pb.nalaz = ?, pb.mkbSifraPrimarna = ?,pb.mkbSifraSekundarna = ?,pb.tipSlucaj = ?, pb.terapija = ?,
                                pb.preporukaLijecnik = ?,pb.napomena = ?,pb.datum = ?,pb.narucen = ?, pb.mboPacijent = ? 
                                WHERE pb.idPovijestBolesti = ?";
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
                            mysqli_stmt_bind_param($stmt,"sssssssssssssi",$razlogDolaska,$anamneza,$status,$nalaz,$mkbSifraPrimarna,$mkbSifraSekundarna,
                                                            $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen,$mboPacijent,$idPovijestBolesti);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmt);

                            $response["success"] = "true";
                            $response["message"] = "Podatci uspješno dodani!";
                        }
                    }
                    //Ako je brojač veći od 1, nastavljam NADODAVATI NOVE RETKE 
                    if($brojac > 1){
                        //Kreiram upit za spremanje prvog dijela podataka u bazu
                        $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                                nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                preporukaLijecnik, napomena, datum, narucen, mboPacijent) 
                                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
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
                            mysqli_stmt_bind_param($stmt,"sssssssssssss",$razlogDolaska,$anamneza,$status,$nalaz,$mkbSifraPrimarna,$mkbSifraSekundarna,
                                                            $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen,$mboPacijent);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmt);

                            //Dohvaćam ID povijesti bolesti kojega sam upravo unio
                            $resultPovijestBolesti = mysqli_query($conn,"SELECT MAX(pb.idPovijestBolesti) AS ID FROM povijestBolesti pb");
                            //Ulazim u polje rezultata i idem redak po redak
                            while($rowPovijestBolesti = mysqli_fetch_array($resultPovijestBolesti)){
                                //Dohvaćam željeni ID povijesti bolesti
                                $idPovijestBolesti = $rowPovijestBolesti['ID'];
                            } 
                            
                            //Ubacivam nove podatke u tablicu "ambulanta"
                            $sqlAmbulanta = "INSERT INTO ambulanta (idLijecnik,idPacijent,idPovijestBolesti) VALUES (?,?,?)";
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
                                mysqli_stmt_bind_param($stmtAmbulanta,"iii",$idLijecnik,$idPacijent,$idPovijestBolesti);
                                //Izvršavanje statementa
                                mysqli_stmt_execute($stmtAmbulanta);

                                $response["success"] = "true";
                                $response["message"] = "Podatci uspješno dodani!";
                            }
                        }
                    }
                }
                //Ako liječnik PONAVLJA istu primarnu dijagnozu, u formarrayu je više od 0 sekundarnih dijagnoza, te u bazi je više sekundarnih dijagnoza
                else if($brojPrimarna > 0 && $brojacSekundarna > 0 && $brojSekundarnaBaza > 0){

                    //Kreiram sql upit koji će prebrojiti koliko ima SEKUNDARNIH DIJAGNOZA TRENUTNO U BAZI KOJE SU ISTE KAO sek dijagnoze iz form arraya
                    //Ako su iste neće se unositi ponovno
                    $sqlCountSekundarna = "SELECT COUNT(pb.mkbSifraSekundarna) AS BrojSekundarnih FROM povijestbolesti pb 
                                        WHERE pb.mkbSifraPrimarna = '$mkbSifraPrimarna' AND pb.datum = '$datum' 
                                        AND pb.mkbSifraSekundarna = '$mkbSifraSekundarna' AND pb.mboPacijent IN 
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
                        //Kreiram upit za spremanje prvog dijela podataka u bazu
                        $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                                nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                preporukaLijecnik, napomena, datum, narucen, mboPacijent) 
                                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
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
                            mysqli_stmt_bind_param($stmt,"sssssssssssss",$razlogDolaska,$anamneza,$status,$nalaz,$mkbSifraPrimarna,$mkbSifraSekundarna,
                                                            $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen,$mboPacijent);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmt);

                            //Dohvaćam ID povijesti bolesti kojega sam upravo unio
                            $resultPovijestBolesti = mysqli_query($conn,"SELECT MAX(pb.idPovijestBolesti) AS ID FROM povijestBolesti pb");
                            //Ulazim u polje rezultata i idem redak po redak
                            while($rowPovijestBolesti = mysqli_fetch_array($resultPovijestBolesti)){
                                //Dohvaćam željeni ID povijesti bolesti
                                $idPovijestBolesti = $rowPovijestBolesti['ID'];
                            } 
                            
                            //Ubacivam nove podatke u tablicu "ambulanta"
                            $sqlAmbulanta = "INSERT INTO ambulanta (idLijecnik,idPacijent,idPovijestBolesti) VALUES (?,?,?)";
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
                                mysqli_stmt_bind_param($stmtAmbulanta,"iii",$idLijecnik,$idPacijent,$idPovijestBolesti);
                                //Izvršavanje statementa
                                mysqli_stmt_execute($stmtAmbulanta);
                            }
                        } 
                        $response["success"] = "true";
                        $response["message"] = "Podatci uspješno dodani!"; 
                    }
                    //Ako u bazi već postoji sekundarna dijagnoza koju korisnik pokušava unijeti, ne unosi se te server vraća grešku 
                    else{
                        $response["success"] = "false";
                        $response["message"] = "Već ste unijeli ovu sekundarnu dijagnozu danas!";
                    }
                }
            }
            return $response;
        }
    }
}
?>