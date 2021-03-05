<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class PovijestBolestiService{

    //Kreiram funkciju koja će potvrditi povijest bolesti
    function potvrdiPovijestBolesti($idLijecnik,$idPacijent,$razlogDolaska,$anamneza,$status,
                                    $nalaz,$mkbPrimarnaDijagnoza,$mkbSifre,$tipSlucaj,
                                    $terapija,$preporukaLijecnik,$napomena,$idObrada,$poslanaPrimarna, 
                                    $poslaniIDObrada){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];
        //Trenutni datum
        $datum = date('Y-m-d');
        //Trenutno vrijeme za naručivanje
        $vrijeme = date('H:i');
        //Trenutno vrijeme za pregled
        $vrijemePregled = date("H:i");
        //Status pacijenta
        $statusObrada = "Aktivan";
        //Varijabla koja određuje je li pacijent naručen ili nije
        $narucen = NULL;
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
        //Ako pacijent nije aktivan u obradi, generiram RANDOM broj koji će predstavljati tu obradu
        if(empty($idObrada)){
            //Generiram random broj
            $idObrada = rand(1000,10000);
            //Kreiram upit koji provjerava postoji li već ovaj random generirani broj u bazi za ovog pacijenta
            $sqlProvjeraObrada = "SELECT pb.idObradaLijecnik FROM povijestBolesti pb 
                                WHERE pb.idObradaLijecnik = '$idObrada'";
            //Rezultat upita spremam u varijablu $resultProvjeraObrada
            $resultProvjeraObrada = mysqli_query($conn,$sqlProvjeraObrada);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($resultProvjeraObrada) > 0){
                //Generiram novi random broj
                $idObrada = rand(1000,10000);
            } 
        }
        //Gledam je li upisan ID recepta u redak kojega povezujem da mogu ažurirati tablicu "recept"
        $sqlRecept = "SELECT pb.idRecept FROM povijestBolesti pb
                            WHERE pb.mkbSifraPrimarna = '$poslanaPrimarna' AND pb.mboPacijent = '$mboPacijent' 
                            AND pb.idObradaLijecnik = '$poslaniIDObrada'";
        //Rezultat upita spremam u varijablu $resultCountPrimarna
        $resultRecept = mysqli_query($conn,$sqlRecept);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultRecept) > 0){
            //Idem redak po redak rezultata upita 
            while($rowRecept = mysqli_fetch_assoc($resultRecept)){
                //Vrijednost rezultata spremam u varijablu $brojSekundarnaBaza
                $idRecept = $rowRecept['idRecept'];
            }
        }
        //Gledam koliko sek. dijagnoza ima povijest bolesti koju povezujem
        $sqlCountSekundarna = "SELECT COUNT(pb.mkbSifraSekundarna) AS BrojSekundarna FROM povijestBolesti pb
                            WHERE pb.mkbSifraPrimarna = '$poslanaPrimarna' AND pb.mboPacijent = '$mboPacijent' 
                            AND pb.idObradaLijecnik = '$poslaniIDObrada'";
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
        //Ako je polje sekundarnih dijagnoza prazno
        if(empty($mkbSifre)){
            //Ako je novi slučaj
            if($tipSlucaj == 'noviSlucaj'){
                /******************************** */
                //Provjera je li postoji već ova primarna dijagnoza u bazi
                $sqlProvjera = "SELECT pb.mkbSifraPrimarna FROM povijestBolesti pb 
                                WHERE pb.idObradaLijecnik = '$idObrada' AND pb.mboPacijent IN 
                                (SELECT pacijent.mboPacijent FROM pacijent 
                                WHERE pacijent.idPacijent = '$idPacijent')";
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
                //Kreiram upit za spremanje podataka u bazu
                $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                        nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                        preporukaLijecnik, napomena, datum, narucen, mboPacijent,idObradaLijecnik,vrijeme) 
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
                    mysqli_stmt_bind_param($stmt,"sssssssssssssis",$razlogDolaska,$anamneza,$status,$nalaz,$mkbPrimarnaDijagnoza,$prazna,
                                                    $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen,$mboPacijent,$idObrada,$vrijemePregled);
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
            //Ako je POVEZAN SLUČAJ
            else if($tipSlucaj == 'povezanSlucaj'){
                //Ažuriram prethodnu dijagnozu novog slučaja
                if(($brojSekundarnaBaza == 0 || $brojSekundarnaBaza == 1)){
                    $sql ="UPDATE povijestBolesti pb SET pb.razlogDolaska = ?, pb.anamneza = ?, 
                                                        pb.statusPacijent = ?, pb.nalaz = ?, pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ?, 
                                                        pb.tipSlucaj = ?, pb.terapija = ?, pb.preporukaLijecnik = ?, pb.napomena = ?, pb.datum = ?, 
                                                        pb.narucen = ?, pb.vrijeme = ?, pb.mboPacijent = ?, pb.idObradaLijecnik = ?
                            WHERE pb.idObradaLijecnik = ?
                            AND pb.mkbSifraPrimarna = ?
                            AND pb.mboPacijent = ?";
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
                        //Ovo je ako su empty varijable koje želim postaviti
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
                        mysqli_stmt_bind_param($stmt,"ssssssssssssssiiss",$razlogDolaska,$anamneza,$status,$nalaz,$mkbPrimarnaDijagnoza,$prazna,
                                            $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen,$vrijemePregled,$mboPacijent, 
                                            $idObrada,$poslaniIDObrada,$poslanaPrimarna,$mboPacijent);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmt); 
                        $response["success"] = "true";
                        $response["message"] = "Podatci uspješno dodani!";
                    }
                } 
                //Ako već postoji ova primarna dijagnoza u povijesti bolesti ove sesije obrade te ima više od jedne sek. dijagnoze u bazi trenutno
                else if($brojSekundarnaBaza > 1){
                    //Brišem sve retke iz tablice ambulanta za ovu povijest bolesti
                    $sqlDeleteAmbulanta = "DELETE a FROM ambulanta a
                                        JOIN povijestbolesti pb ON pb.idPovijestBolesti = a.idPovijestBolesti 
                                        WHERE pb.idObradaLijecnik = ? AND pb.mkbSifraPrimarna = ? AND pb.mboPacijent = ?";
                    //Kreiranje prepared statementa
                    $stmtDeleteAmbulanta = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmtDeleteAmbulanta,$sqlDeleteAmbulanta)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement ne valja!";
                    }
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmtDeleteAmbulanta,"iss",$poslaniIDObrada,$poslanaPrimarna,$mboPacijent);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmtDeleteAmbulanta);
                        //Brišem sve retke iz tablice povijesti bolesti
                        $sqlDelete = "DELETE FROM povijestBolesti 
                                    WHERE idObradaLijecnik = ? AND mkbSifraPrimarna = ? AND mboPacijent = ?";
                        //Kreiranje prepared statementa
                        $stmtDelete = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmtDelete,$sqlDelete)){
                            $response["success"] = "false";
                            $response["message"] = "Prepared statement ne valja!";
                        }
                        else{
                            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                            mysqli_stmt_bind_param($stmtDelete,"iss",$poslaniIDObrada,$poslanaPrimarna,$mboPacijent);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmtDelete);  
                            //Kreiram upit za dodavanje novog recepta u bazu
                            $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                                    nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                    preporukaLijecnik, napomena, datum, narucen, mboPacijent,idObradaLijecnik,vrijeme) 
                                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                            //Kreiranje prepared statementa
                            $stmt = mysqli_stmt_init($conn);
                            //Ako je statement neuspješan
                            if(!mysqli_stmt_prepare($stmt,$sql)){
                                $response["success"] = "false";
                                $response["message"] = "Prepared statement ne valja!";
                            }
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
                                mysqli_stmt_bind_param($stmt,"sssssssssssssis",$razlogDolaska,$anamneza,$status,$nalaz,$mkbPrimarnaDijagnoza,$prazna,
                                                                $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen,$mboPacijent,$idObrada,$vrijemePregled);
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
                                    //Vraćanje uspješnog odgovora serveru
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
            $sqlMin = "SELECT pb.idPovijestBolesti FROM povijestbolesti pb 
                    WHERE pb.mkbSifraPrimarna = '$poslanaPrimarna' 
                    AND pb.mboPacijent = '$mboPacijent' AND pb.idObradaLijecnik = '$poslaniIDObrada'
                    AND pb.idPovijestBolesti = 
                    (SELECT MIN(pb2.idPovijestBolesti) FROM povijestbolesti pb2 
                    WHERE pb2.mkbSifraPrimarna = '$poslanaPrimarna' 
                    AND pb.mboPacijent = '$mboPacijent' AND pb.idObradaLijecnik = '$poslaniIDObrada')";
            $resultMin = $conn->query($sqlMin);
                    
            //Ako pacijent IMA evidentiranih recepata:
            if ($resultMin->num_rows > 0) {
                while($rowMin = $resultMin->fetch_assoc()) {
                    //Dohvaćam povijest bolesti sa MINIMALNIM ID-om
                    $idMinPovijestBolesti = $rowMin['idPovijestBolesti'];
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
            //Ako je slučaj povezan
            if($tipSlucaj == 'povezanSlucaj'){
                //Ako je broj dijagnoza u bazi VEĆI od broja dijagnoza u formi
                if($brojSekundarnaBaza > $brojacSekundarnaForma){
                    //Označavam da treba obrisati sve retke pa nadodati kasnije
                    $brisanje = true;
                    //Brišem sve retke iz tablice ambulanta za ovu povijest bolesti
                    $sqlDeleteAmbulanta = "DELETE a FROM ambulanta a
                                        JOIN povijestbolesti pb ON pb.idPovijestBolesti = a.idPovijestBolesti 
                                        WHERE pb.mkbSifraPrimarna = ? AND pb.mboPacijent = ? AND pb.idObradaLijecnik = ?;";
                    //Kreiranje prepared statementa
                    $stmtDeleteAmbulanta = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmtDeleteAmbulanta,$sqlDeleteAmbulanta)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement ne valja!";
                    }
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmtDeleteAmbulanta,"ssi",$poslanaPrimarna,$mboPacijent,$poslaniIDObrada);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmtDeleteAmbulanta);
                        //Brišem sve retke iz tablice povijesti bolesti
                        $sqlDelete = "DELETE FROM povijestBolesti 
                                    WHERE mkbSifraPrimarna = ? AND mboPacijent = ? AND idObradaLijecnik = ?";
                        //Kreiranje prepared statementa
                        $stmtDelete = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmtDelete,$sqlDelete)){
                            $response["success"] = "false";
                            $response["message"] = "Prepared statement ne valja!";
                        }
                        else{
                            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                            mysqli_stmt_bind_param($stmtDelete,"ssi",$poslanaPrimarna,$mboPacijent,$poslaniIDObrada);
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
                //Ako NE POSTOJI evidentiranih primarnih dijagnoza za ovu sesiju obrade pacijenta
                if($tipSlucaj == 'noviSlucaj'){
                    //Kreiram upit za spremanje prvog dijela podataka u bazu
                    $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                            nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                            preporukaLijecnik, napomena, datum, narucen, mboPacijent,idObradaLijecnik,vrijeme) 
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
                        mysqli_stmt_bind_param($stmt,"sssssssssssssis",$razlogDolaska,$anamneza,$status,$nalaz,$mkbPrimarnaDijagnoza,$mkb,
                                                                    $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen,$mboPacijent,$idObrada,$vrijemePregled);
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
                //Ako slučaj povezan
                else if($tipSlucaj == 'povezanSlucaj'){
                    //Ako VEĆ POSTOJI primarna dijagnoza za ovu obradu TE npr. (BAZA = 0, FORMA = 1) ILI (BAZA = 1, FORMA = 1)
                    if($brojSekundarnaBaza <= $brojacSekundarnaForma && $brojacSekundarnaForma == 1){
                        $sql ="UPDATE povijestBolesti pb SET pb.razlogDolaska = ?, pb.anamneza = ?, 
                                                        pb.statusPacijent = ?, pb.nalaz = ?, pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ?, 
                                                        pb.tipSlucaj = ?, pb.terapija = ?, pb.preporukaLijecnik = ?, pb.napomena = ?, pb.datum = ?, 
                                                        pb.narucen = ?, pb.vrijeme = ?, pb.mboPacijent = ?, pb.idObradaLijecnik = ?
                                    WHERE pb.mkbSifraPrimarna = ? AND pb.mboPacijent = ? AND pb.idObradaLijecnik = ?;";
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
                                mysqli_stmt_bind_param($stmt,"ssssssssssssssissi",$razlogDolaska,$anamneza,$status,$nalaz,$mkbPrimarnaDijagnoza,$mkb,
                                                                $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen,$vrijemePregled, 
                                                                $mboPacijent,$idObrada,$poslanaPrimarna,$mboPacijent,$poslaniIDObrada);
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
                            $sql ="UPDATE povijestBolesti pb SET pb.razlogDolaska = ?, pb.anamneza = ?, 
                                                        pb.statusPacijent = ?, pb.nalaz = ?, pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ?, 
                                                        pb.tipSlucaj = ?, pb.terapija = ?, pb.preporukaLijecnik = ?, pb.napomena = ?, pb.datum = ?, 
                                                        pb.narucen = ?, pb.vrijeme = ?, pb.mboPacijent = ?,pb.idObradaLijecnik = ?
                                    WHERE pb.mkbSifraPrimarna = ? AND pb.mboPacijent = ? AND pb.idObradaLijecnik = ?;";
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
                                mysqli_stmt_bind_param($stmt,"ssssssssssssssissi",$razlogDolaska,$anamneza,$status,$nalaz,$mkbPrimarnaDijagnoza,$mkb,
                                                                $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen,$vrijemePregled, 
                                                                $mboPacijent,$idObrada,$poslanaPrimarna,$mboPacijent,$poslaniIDObrada);
                                //Izvršavanje statementa
                                mysqli_stmt_execute($stmt);
                            }
                        }
                        //Ako je broj sek. dijagnoza u BAZI JENDAK 0 te je n-ta iteracija (tj. n-ta dijagnoza forme)
                        else if($brojSekundarnaBaza == 0 && $brojacIteracija > 1){
                            //Kreiram upit za spremanje prvog dijela podataka u bazu
                            $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                                    nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                    preporukaLijecnik, napomena, datum, narucen, mboPacijent,idObradaLijecnik,vrijeme) 
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
                                mysqli_stmt_bind_param($stmt,"sssssssssssssis",$razlogDolaska,$anamneza,$status,$nalaz,$mkbPrimarnaDijagnoza,$mkb,
                                                                            $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen, 
                                                                            $mboPacijent,$idObrada,$vrijemePregled);
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
                        //Ako je broj obrađenih redaka manji od broja dijagnoza u bazi te je prva iteracija (koristim PRVI MINIMALNI ID povijesti bolesti) te je idRecept === NULL
                        if($brojacAzuriranihRedaka < $brojSekundarnaBaza && $brojacIteracija == 1){
                            $sql ="UPDATE povijestBolesti pb SET pb.razlogDolaska = ?, pb.anamneza = ?, 
                                                        pb.statusPacijent = ?, pb.nalaz = ?, pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ?, 
                                                        pb.tipSlucaj = ?, pb.terapija = ?, pb.preporukaLijecnik = ?, pb.napomena = ?, pb.datum = ?, 
                                                        pb.narucen = ?, pb.vrijeme = ?, pb.mboPacijent = ?,pb.idObradaLijecnik = ?
                                WHERE pb.idPovijestBolesti = ?;";
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
                                mysqli_stmt_bind_param($stmt,"ssssssssssssssii",$razlogDolaska,$anamneza,$status,$nalaz,$mkbPrimarnaDijagnoza,$mkb,
                                                                $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen,$vrijemePregled, 
                                                                $mboPacijent,$idObrada,$idMinPovijestBolesti);
                                //Izvršavanje statementa
                                mysqli_stmt_execute($stmt);
                                //Povećavam broj ažuriranih redaka za 1
                                $brojacAzuriranihRedaka++;
                            }
                        }
                        //Ako je broj obrađenih redaka manji od broja dijagnoza u bazi te NIJE prva iteracija
                        else if($brojacAzuriranihRedaka < $brojSekundarnaBaza && $brojacIteracija > 1){
                            //Kreiram upit koji dohvaća SLJEDEĆI MINIMALNI ID povijesti bolesti za ovog pacijenta za ovu sesiju obrade
                            $sqlSljedeciMin = "SELECT pb.idPovijestBolesti FROM povijestbolesti pb 
                                            WHERE pb.mkbSifraPrimarna = '$poslanaPrimarna' 
                                            AND pb.mboPacijent = '$mboPacijent' AND pb.idObradaLijecnik = '$poslaniIDObrada'
                                            AND pb.idPovijestBolesti = 
                                            (SELECT pb2.idPovijestBolesti FROM povijestbolesti pb2 
                                            WHERE pb2.mkbSifraPrimarna = '$poslanaPrimarna' 
                                            AND pb.mboPacijent = '$mboPacijent' AND pb.idObradaLijecnik = '$poslaniIDObrada'
                                            AND pb2.idPovijestBolesti > '$idMinPovijestBolesti' 
                                            LIMIT 1)";
                            $resultSljedeciMin = $conn->query($sqlSljedeciMin);
                                    
                            //Ako pacijent IMA evidentiranih povijesti bolesti
                            if ($resultSljedeciMin->num_rows > 0) {
                                while($rowSljedeciMin = $resultSljedeciMin->fetch_assoc()) {
                                    //Dohvaćam povijesti bolesti sa SLJEDEĆIM MINIMALNIM ID-om
                                    $idMinPovijestBolesti = $rowSljedeciMin['idPovijestBolesti'];
                                }
                            }
                            $sql ="UPDATE povijestBolesti pb SET pb.razlogDolaska = ?, pb.anamneza = ?, 
                                                        pb.statusPacijent = ?, pb.nalaz = ?, pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ?, 
                                                        pb.tipSlucaj = ?, pb.terapija = ?, pb.preporukaLijecnik = ?, pb.napomena = ?, pb.datum = ?, 
                                                        pb.narucen = ?, pb.vrijeme = ?, pb.mboPacijent = ?, pb.idObradaLijecnik = ?
                                WHERE pb.idPovijestBolesti = ?;";
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
                                mysqli_stmt_bind_param($stmt,"ssssssssssssssii",$razlogDolaska,$anamneza,$status,$nalaz,$mkbPrimarnaDijagnoza,$mkb,
                                                                $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen,$vrijemePregled, 
                                                                $mboPacijent,$idObrada,$idMinPovijestBolesti);
                                //Izvršavanje statementa
                                mysqli_stmt_execute($stmt);
                                $response["success"] = "true";
                                $response["message"] = "Podatci uspješno dodani!";
                                //Povećavam broj ažuriranih redaka za 1
                                $brojacAzuriranihRedaka++;
                            }
                        }
                        //Ako je broj ažuriranih redak JEDNAK broju sek. dijagnoza u bazi (npr. 2 == 2) I brojač iteracija JE VEĆI od broja sek. dijagnoza u bazi (npr. 3 > 2) 
                        //te da je broj sek. dijagnoza u BAZI VEĆI OD 0
                        if($brojacAzuriranihRedaka == $brojSekundarnaBaza && $brojacIteracija > $brojSekundarnaBaza && $brojSekundarnaBaza > 0){
                            //Kreiram upit za spremanje prvog dijela podataka u bazu
                            $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                                    nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                    preporukaLijecnik, napomena, datum, narucen, mboPacijent,idObradaLijecnik,vrijeme) 
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
                                mysqli_stmt_bind_param($stmt,"sssssssssssssis",$razlogDolaska,$anamneza,$status,$nalaz,$mkbPrimarnaDijagnoza,$mkb,
                                                                            $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen,$mboPacijent,$idObrada,$vrijemePregled);
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
                    /**************************************** */
                    //Ako su retci izbrisani, treba nadodati nove dijagnoze iz forme
                    else if($brisanje == true){
                        //Kreiram upit za spremanje prvog dijela podataka u bazu
                        $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                                nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                preporukaLijecnik, napomena, datum, narucen, mboPacijent,idObradaLijecnik,vrijeme) 
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
                            mysqli_stmt_bind_param($stmt,"sssssssssssssis",$razlogDolaska,$anamneza,$status,$nalaz,$mkbPrimarnaDijagnoza,$mkb,
                                                                        $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen,$mboPacijent,$idObrada,$vrijemePregled);
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
            }
            return $response;
        }
    }
}
?>