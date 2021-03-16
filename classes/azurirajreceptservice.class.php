<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class AzurirajReceptService{
    //Funkcija koja dodava novi recept u bazu podataka
    function azurirajRecept($idLijecnik,$mkbSifraPrimarna,$mkbSifraSekundarna,$osnovnaListaLijekDropdown,
                    $osnovnaListaLijekText,$dopunskaListaLijekDropdown,$dopunskaListaLijekText,
                    $osnovnaListaMagPripravakDropdown,$osnovnaListaMagPripravakText,$dopunskaListaMagPripravakDropdown,
                    $dopunskaListaMagPripravakText,$kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv,$brojPonavljanja,
                    $sifraSpecijalist,$idPacijent,$poslaniDatum,$poslanoVrijeme,$poslanaMKBSifra){
    //Dohvaćam bazu 
    $baza = new Baza();
    $conn = $baza->spojiSBazom();
    //Kreiram prazno polje odgovora
    $response = [];
    //Trenutni datum
    $datum = date('Y-m-d');
    //Trenutno vrijeme
    $vrijeme = date('H:i');
    //Kreiram sql upit koji će prebrojiti koliko ima SEKUNDARNIH DIJAGNOZA TRENUTNO U BAZI ZA ODREĐENU PRIMARNU DIJAGNOZU, ZA ODREĐENI DATUM, VRIJEME I PACIJENTA
    $sqlCountSekundarna = "SELECT COUNT(r.mkbSifraSekundarna) AS BrojSekundarna FROM recept r
                        WHERE r.mkbSifraPrimarna = '$poslanaMKBSifra' 
                        AND r.datumRecept = '$poslaniDatum' 
                        AND r.vrijemeRecept = '$poslanoVrijeme' 
                        AND r.idPacijent = '$idPacijent';";
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
    //Ako nema sekundarnih dijagnoza
    if(empty($mkbSifraSekundarna)){
        //Postavljam MKB šifru sekundarne dijagnoze na NULL
        $prazna = NULL;
        //Inicijalno postavljam proizvod na NULL
        $proizvod = NULL;
        //Inicijalno postavljam oblik, jačinu i pakiranje lijeka na NULL
        $oblikJacinaPakiranjeLijek = NULL;
        //Postavljam inicijalno da nisam pronašao lijek u bazi
        $pronasao = false;
        //Inicijalno postavljam brojač na 2
        $brojac = 2;
        
        if(empty($osnovnaListaLijekDropdown)){
            $osnovnaListaLijekDropdown = NULL;
        }
        else{
            //Dohvaćam OJP ako ga ima
            while($pronasao !== true){
                //Splitam string da mu uzmem ime i oblik-jačinu-pakiranje (KREĆEM OD 2)
                $polje = explode(" ",$osnovnaListaLijekDropdown,$brojac);
                //Dohvaćam oblik,jačinu i pakiranje lijeka
                $ojpLijek = array_pop($polje);
                //Dohvaćam ime lijeka
                $imeLijek = implode(" ", $polje);

                //Kreiram sql upit kojim provjeravam postoji li LIJEK u osnovnoj listi lijekova
                $sqlOsnovnaLista = "SELECT o.zasticenoImeLijek,o.oblikJacinaPakiranjeLijek FROM osnovnalistalijekova o 
                                WHERE o.oblikJacinaPakiranjeLijek = '$ojpLijek' AND o.zasticenoImeLijek = '$imeLijek'";

                $resultOsnovnaLista = $conn->query($sqlOsnovnaLista);
                //Ako je lijek pronađen u OSNOVNOJ LISTI LIJEKOVA
                if ($resultOsnovnaLista->num_rows > 0) {
                    while($rowOsnovnaLista = $resultOsnovnaLista->fetch_assoc()) {
                        //Dohvaćam oblik, jačinu i pakiranje unesenog lijeka
                        $oblikJacinaPakiranjeLijek = $rowOsnovnaLista['oblikJacinaPakiranjeLijek'];
                        $proizvod = $rowOsnovnaLista['zasticenoImeLijek'];
                    }
                    //Izlazim iz petlje
                    $pronasao = true;
                }
                //Povećavam brojač za 1
                $brojac++;
            }
        }
        if(empty($osnovnaListaLijekText)){
            $osnovnaListaLijekText = NULL;
        }
        else{
            //Dohvaćam OJP ako ga ima
            while($pronasao !== true){
                //Splitam string da mu uzmem ime i oblik-jačinu-pakiranje (KREĆEM OD 2)
                $polje = explode(" ",$osnovnaListaLijekText,$brojac);
                //Dohvaćam oblik,jačinu i pakiranje lijeka
                $ojpLijek = array_pop($polje);
                //Dohvaćam ime lijeka
                $imeLijek = implode(" ", $polje);

                //Kreiram sql upit kojim provjeravam postoji li LIJEK u osnovnoj listi lijekova
                $sqlOsnovnaLista = "SELECT o.zasticenoImeLijek,o.oblikJacinaPakiranjeLijek FROM osnovnalistalijekova o 
                                WHERE o.oblikJacinaPakiranjeLijek = '$ojpLijek' AND o.zasticenoImeLijek = '$imeLijek'";

                $resultOsnovnaLista = $conn->query($sqlOsnovnaLista);
                //Ako je lijek pronađen u OSNOVNOJ LISTI LIJEKOVA
                if ($resultOsnovnaLista->num_rows > 0) {
                    while($rowOsnovnaLista = $resultOsnovnaLista->fetch_assoc()) {
                        //Dohvaćam oblik, jačinu i pakiranje unesenog lijeka
                        $oblikJacinaPakiranjeLijek = $rowOsnovnaLista['oblikJacinaPakiranjeLijek'];
                        $proizvod = $rowOsnovnaLista['zasticenoImeLijek'];
                    }
                    //Izlazim iz petlje
                    $pronasao = true;
                }
                //Povećavam brojač za 1
                $brojac++;
            }
        }
        if(empty($dopunskaListaLijekDropdown)){
            $dopunskaListaLijekDropdown = NULL;
        }
        else{
            //Dohvaćam OJP ako ga ima
            while($pronasao !== true){
                //Splitam string da mu uzmem ime i oblik-jačinu-pakiranje (KREĆEM OD 2)
                $polje = explode(" ",$dopunskaListaLijekDropdown,$brojac);
                //Dohvaćam oblik,jačinu i pakiranje lijeka
                $ojpLijek = array_pop($polje);
                //Dohvaćam ime lijeka
                $imeLijek = implode(" ", $polje);

                //Kreiram sql upit kojim provjeravam postoji li LIJEK u osnovnoj listi lijekova
                $sqlDopunskaLista = "SELECT d.zasticenoImeLijek,d.oblikJacinaPakiranjeLijek FROM dopunskalistalijekova d 
                                WHERE d.oblikJacinaPakiranjeLijek = '$ojpLijek' AND d.zasticenoImeLijek = '$imeLijek'";

                $resultDopunskaLista = $conn->query($sqlDopunskaLista);
                //Ako je lijek pronađen u DOPUNSKOJ LISTI LIJEKOVA
                if ($resultDopunskaLista->num_rows > 0) {
                    while($rowDopunskaLista = $resultDopunskaLista->fetch_assoc()) {
                        //Dohvaćam oblik, jačinu i pakiranje unesenog lijeka
                        $oblikJacinaPakiranjeLijek = $rowDopunskaLista['oblikJacinaPakiranjeLijek'];
                        $proizvod = $rowDopunskaLista['zasticenoImeLijek'];
                    }
                    //Izlazim iz petlje
                    $pronasao = true;
                }
                //Povećavam brojač za 1
                $brojac++;
            }
        }
        if(empty($dopunskaListaLijekText)){
            $dopunskaListaLijekText = NULL;
        }
        else{
            //Dohvaćam OJP ako ga ima
            while($pronasao !== true){
                //Splitam string da mu uzmem ime i oblik-jačinu-pakiranje (KREĆEM OD 2)
                $polje = explode(" ",$dopunskaListaLijekText,$brojac);
                //Dohvaćam oblik,jačinu i pakiranje lijeka
                $ojpLijek = array_pop($polje);
                //Dohvaćam ime lijeka
                $imeLijek = implode(" ", $polje);

                //Kreiram sql upit kojim provjeravam postoji li LIJEK u osnovnoj listi lijekova
                $sqlDopunskaLista = "SELECT d.zasticenoImeLijek,d.oblikJacinaPakiranjeLijek FROM dopunskalistalijekova d 
                                WHERE d.oblikJacinaPakiranjeLijek = '$ojpLijek' AND d.zasticenoImeLijek = '$imeLijek'";

                $resultDopunskaLista = $conn->query($sqlDopunskaLista);
                //Ako je lijek pronađen u DOPUNSKOJ LISTI LIJEKOVA
                if ($resultDopunskaLista->num_rows > 0) {
                    while($rowDopunskaLista = $resultDopunskaLista->fetch_assoc()) {
                        //Dohvaćam oblik, jačinu i pakiranje unesenog lijeka
                        $oblikJacinaPakiranjeLijek = $rowDopunskaLista['oblikJacinaPakiranjeLijek'];
                        $proizvod = $rowDopunskaLista['zasticenoImeLijek'];
                    }
                    //Izlazim iz petlje
                    $pronasao = true;
                }
                //Povećavam brojač za 1
                $brojac++;
            }
        }
        if(empty($osnovnaListaMagPripravakDropdown)){
            $osnovnaListaMagPripravakDropdown = NULL;
        }
        else{
            $proizvod = $osnovnaListaMagPripravakDropdown;
        }
        if(empty($osnovnaListaMagPripravakText)){
            $osnovnaListaMagPripravakText = NULL;
        }
        else{
            $proizvod = $osnovnaListaMagPripravakText;
        }
        if(empty($dopunskaListaMagPripravakDropdown)){
            $dopunskaListaMagPripravakDropdown = NULL;
        }
        else{
            $proizvod = $dopunskaListaMagPripravakDropdown;
        }
        if(empty($dopunskaListaMagPripravakText)){
            $dopunskaListaMagPripravakText = NULL;
        }
        else{
            $proizvod = $dopunskaListaMagPripravakText;
        }
        if(empty($hitnost)){
            $hitnost = NULL;
        }
        if(empty($ponovljiv)){
            $ponovljiv = NULL;
        }
        if(empty($brojPonavljanja)){
            $brojPonavljanja = NULL;
        }
        if(empty($sifraSpecijalist)){
            $sifraSpecijalist = NULL;
        }

        //Ako je BROJ DIJAGNOZA U BAZI JEDNAK 0 ILI 1
        if($brojSekundarnaBaza == 0 || $brojSekundarnaBaza == 1){
            //Kreiram upit za dodavanje novog recepta u bazu
            $sql = "UPDATE recept r SET r.mkbSifraPrimarna = ?, r.mkbSifraSekundarna = ?, r.proizvod = ?, 
                                        r.oblikJacinaPakiranjeLijek = ?, r.kolicina = ?, r.doziranje = ?,
                                        r.dostatnost = ?, r.hitnost = ?, r.ponovljiv = ?, r.brojPonavljanja = ?, 
                                        r.sifraSpecijalist = ?, r.datumRecept = ?, r.vrijemeRecept = ? 
                    WHERE r.idPacijent = ? 
                    AND r.datumRecept = ? 
                    AND r.vrijemeRecept = ? 
                    AND r.mkbSifraPrimarna = ?";
            //Kreiranje prepared statementa
            $stmt = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmt,$sql)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement ne valja!";
            }
            else{
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmt,"ssssisissiississs",$mkbSifraPrimarna,$prazna,$proizvod,$oblikJacinaPakiranjeLijek,
                                                            $kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv, 
                                                            $brojPonavljanja,$sifraSpecijalist,$datum,$vrijeme,$idPacijent,
                                                            $poslaniDatum,$poslanoVrijeme,$poslanaMKBSifra);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmt);

                //Dohvaćam ID recepta kojega sam upravo ažurirao
                $sqlRecept = "SELECT r.idRecept FROM recept r 
                            WHERE r.idPacijent = '$idPacijent' 
                            AND r.datumRecept = '$datum' 
                            AND r.vrijemeRecept = '$vrijeme' 
                            AND r.mkbSifraPrimarna = '$mkbSifraPrimarna'";
                $resultRecept = $conn->query($sqlRecept);
                
                //Ako pacijent IMA evidentiranih recepata:
                if ($resultRecept ->num_rows > 0) {
                    while($rowRecept  = $resultRecept ->fetch_assoc()) {
                        //Dohvaćam ažurirani ID recepta
                        $idRecept = $rowRecept ['idRecept'];
                    }
                }

                //Kreiram upit za ažuriranje povijesti bolesti
                $sqlPovijestBolesti = "UPDATE povijestBolesti pb SET pb.mkbSifraPrimarna = ?, pb.datum = ?, pb.vrijeme = ? 
                                    WHERE pb.idRecept = ?";
                //Kreiranje prepared statementa
                $stmtPovijestBolesti = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmtPovijestBolesti,$sqlPovijestBolesti)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement ne valja!";
                }
                else{
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmtPovijestBolesti,"sssi",$mkbSifraPrimarna,$datum,$vrijeme,$idRecept);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmtPovijestBolesti);
                    //Vraćanje uspješnog odgovora serveru
                    $response["success"] = "true";
                    $response["message"] = "Recept uspješno ažuriran!";
                }
            }
        }
        //Ako je BROJ DIJAGNOZA U BAZI VEĆI OD 1
        else if($brojSekundarnaBaza > 1){
            //Kreiram upit kojim dohvaćam sve ID-ove recepata
            $sqlRecept = "SELECT r.idRecept FROM recept r 
                        WHERE r.mkbSifraPrimarna = '$poslanaMKBSifra' 
                        AND r.idPacijent = '$idPacijent' 
                        AND r.datumRecept = '$poslaniDatum' 
                        AND r.vrijemeRecept = '$poslanoVrijeme'";
            $resultRecept  = $conn->query($sqlRecept);

            if ($resultRecept ->num_rows > 0) {
                while($rowRecept  = $resultRecept->fetch_assoc()) {
                    $idRecept = $rowRecept['idRecept'];
                    //Prije nego što izbrišem redak povijesti bolesti, dohvaćam ga
                    $sqlSelectPov = "SELECT * FROM povijestbolesti 
                                    WHERE idRecept = '$idRecept'";
                    $resultSelectPov  = $conn->query($sqlSelectPov);

                    if ($resultSelectPov ->num_rows > 0) {
                        while($rowSelectPov  = $resultSelectPov->fetch_assoc()) {
                            //Dohvaćam određene vrijednosti povijesti bolesti
                            $razlogDolaska = $rowSelectPov['razlogDolaska'];
                            $anamneza = $rowSelectPov['anamneza'];
                            $status = $rowSelectPov['statusPacijent'];
                            $nalaz = $rowSelectPov['nalaz'];
                            $tipSlucaj = $rowSelectPov['tipSlucaj'];
                            $terapija = $rowSelectPov['terapija'];
                            $idObradaLijecnik = $rowSelectPov['idObradaLijecnik'];
                            $preporukaLijecnik = $rowSelectPov['preporukaLijecnik'];
                            $napomena = $rowSelectPov['napomena'];
                            $datumPovijestiBolesti = $rowSelectPov['datum'];
                            $narucen = $rowSelectPov['narucen'];
                            $mboPacijent = $rowSelectPov['mboPacijent'];
                            $vrijemePovijestiBolesti = $rowSelectPov['vrijeme'];
                            $prosliPregled = $rowSelectPov['prosliPregled'];
                            $bojaPregled = $rowSelectPov['bojaPregled'];
                        }
                    } 
                    //Kreiram upit koji briše sve retke iz povijesti bolesti
                    $sqlDeletePov = "DELETE FROM povijestBolesti 
                                    WHERE idRecept = ?"; 
                    //Kreiranje prepared statementa
                    $stmtDeletePov = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmtDeletePov,$sqlDeletePov)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement ne valja!";
                    }
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmtDeletePov,"i",$idRecept);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmtDeletePov);
                    }
                }
            } 
            //Brišem sve retke
            $sqlDelete = "DELETE FROM recept 
                        WHERE mkbSifraPrimarna = ? 
                        AND idPacijent = ? 
                        AND datumRecept = ? 
                        AND vrijemeRecept = ?";
            //Kreiranje prepared statementa
            $stmtDelete = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmtDelete,$sqlDelete)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement ne valja!";
            }
            else{
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmtDelete,"siss",$poslanaMKBSifra,$idPacijent,$poslaniDatum,$poslanoVrijeme);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmtDelete);  
                
                //Kreiram upit za dodavanje novog recepta u bazu
                $sql = "INSERT INTO recept (mkbSifraPrimarna,mkbSifraSekundarna,proizvod,oblikJacinaPakiranjeLijek, 
                                            kolicina,doziranje,dostatnost,hitnost,ponovljiv,brojPonavljanja, 
                                            sifraSpecijalist,idPacijent,datumRecept,vrijemeRecept) VALUES 
                                            (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                //Kreiranje prepared statementa
                $stmt = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmt,$sql)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement ne valja!";
                }
                else{
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmt,"ssssisissiiiss",$mkbSifraPrimarna,$prazna,$proizvod,$oblikJacinaPakiranjeLijek,
                                                        $kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv, 
                                                        $brojPonavljanja,$sifraSpecijalist,$idPacijent,$datum,$vrijeme);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmt);
                    //Dohvaćam ID recepta kojega sam upravo unio
                    $resultMaxIDrecept = mysqli_query($conn,"SELECT MAX(r.idRecept) AS ID FROM recept r");
                    //Ulazim u polje rezultata i idem redak po redak
                    while($rowMaxIDrecept = mysqli_fetch_array($resultMaxIDrecept)){
                        //Dohvaćam željeni ID recept
                        $maxIDrecept = $rowMaxIDrecept['ID'];
                    } 
                    //Kreiram upit za dodavanje novog recepta u bazu
                    $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                                                        nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                                        preporukaLijecnik, napomena, datum, narucen, mboPacijent, 
                                                        idObradaLijecnik,vrijeme,idRecept,prosliPregled,bojaPregled) 
                                                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                    //Kreiranje prepared statementa
                    $stmt = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmt,$sql)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement ne valja!";
                    }
                    else{
                        $prazna = NULL;
                        if(empty($prosliPregled)){
                            $prosliPregled = NULL;
                        }
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
                        mysqli_stmt_bind_param($stmt,"sssssssssssssisiis",$razlogDolaska,$anamneza,$status,$nalaz,$mkbSifraPrimarna,$prazna,
                                                        $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datumPovijestiBolesti, 
                                                        $narucen,$mboPacijent,$idObradaLijecnik,$vrijemePovijestiBolesti,$maxIDrecept, 
                                                        $prosliPregled,$bojaPregled);
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
                            $response["message"] = "Recept uspješno dodan!";
                        }
                    }
                    //Vraćanje uspješnog odgovora serveru
                    $response["success"] = "true";
                    $response["message"] = "Recept uspješno ažuriran!";
                } 
            }  
        } 
    }
    //Ako IMA MKB šifri sek. dijagnoza
    else{
        //Kreiram upit koji dohvaća MINIMALNI ID recepta za određenog pacijenta, datum i vrijeme
        $sqlMin = "SELECT r.idRecept FROM recept r 
                WHERE r.idPacijent = '$idPacijent' 
                AND r.datumRecept = '$poslaniDatum' 
                AND r.vrijemeRecept = '$poslanoVrijeme' 
                AND r.idRecept = 
                (SELECT MIN(r2.idRecept) FROM recept r2 
                WHERE r2.idPacijent = '$idPacijent' 
                AND r2.datumRecept = '$poslaniDatum' 
                AND r2.vrijemeRecept = '$poslanoVrijeme')";
        $resultMin = $conn->query($sqlMin);
                
        //Ako pacijent IMA evidentiranih recepata:
        if ($resultMin->num_rows > 0) {
            while($rowMin = $resultMin->fetch_assoc()) {
                //Dohvaćam recept sa MINIMALNIM ID-om
                $idMinRecept = $rowMin['idRecept'];
            }
        }
        //Brojim koliko ima sekundarnih dijagnoza u formi 
        $brojacSekundarnaDijagnozaForma = count($mkbSifraSekundarna);
        //Inicijaliziram varijablu $brisanje na false na početku
        $brisanje = false;
        //Ako je broj dijagnoza u bazi VEĆI od broja dijagnoza u formi
        if($brojSekundarnaBaza > $brojacSekundarnaDijagnozaForma){
            //Označavam da treba obrisati sve retke pa nadodati kasnije
            $brisanje = true;
            //Kreiram upit kojim dohvaćam sve ID-ove recepata
            $sqlRecept = "SELECT r.idRecept FROM recept r 
                        WHERE r.mkbSifraPrimarna = '$poslanaMKBSifra' 
                        AND r.idPacijent = '$idPacijent' 
                        AND r.datumRecept = '$poslaniDatum' 
                        AND r.vrijemeRecept = '$poslanoVrijeme'";
            $resultRecept  = $conn->query($sqlRecept);

            if ($resultRecept ->num_rows > 0) {
                while($rowRecept  = $resultRecept->fetch_assoc()) {
                    $idRecept = $rowRecept['idRecept'];
                    //Prije nego što izbrišem redak povijesti bolesti, dohvaćam ga
                    $sqlSelectPov = "SELECT * FROM povijestbolesti 
                                    WHERE idRecept = '$idRecept'";
                    $resultSelectPov = $conn->query($sqlSelectPov);

                    if ($resultSelectPov->num_rows > 0) {
                        while($rowSelectPov = $resultSelectPov->fetch_assoc()) {
                            //Dohvaćam određene vrijednosti povijesti bolesti
                            $razlogDolaska = $rowSelectPov['razlogDolaska'];
                            $anamneza = $rowSelectPov['anamneza'];
                            $status = $rowSelectPov['statusPacijent'];
                            $nalaz = $rowSelectPov['nalaz'];
                            $tipSlucaj = $rowSelectPov['tipSlucaj'];
                            $terapija = $rowSelectPov['terapija'];
                            $idObradaLijecnik = $rowSelectPov['idObradaLijecnik'];
                            $preporukaLijecnik = $rowSelectPov['preporukaLijecnik'];
                            $napomena = $rowSelectPov['napomena'];
                            $datumPovijestiBolesti = $rowSelectPov['datum'];
                            $narucen = $rowSelectPov['narucen'];
                            $mboPacijent = $rowSelectPov['mboPacijent'];
                            $vrijemePovijestiBolesti = $rowSelectPov['vrijeme'];
                            $prosliPregled = $rowSelectPov['prosliPregled'];
                            $bojaPregled = $rowSelectPov['bojaPregled'];
                        }
                    } 
                    //Kreiram upit koji briše sve retke iz povijesti bolesti
                    $sqlDeletePov = "DELETE FROM povijestBolesti 
                                    WHERE idRecept = ?"; 
                    //Kreiranje prepared statementa
                    $stmtDeletePov = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmtDeletePov,$sqlDeletePov)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement ne valja!";
                    }
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmtDeletePov,"i",$idRecept);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmtDeletePov);
                    }
                }
            } 
            //Kreiram upit koji će obrisati sve retke u bazi za određenog pacijenta, određeni datum, vrijeme i primarnu dijagnozu
            $sqlDelete = "DELETE FROM recept 
                        WHERE mkbSifraPrimarna = ? 
                        AND idPacijent = ? 
                        AND datumRecept = ? 
                        AND vrijemeRecept = ?";
            //Kreiranje prepared statementa
            $stmtDelete = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmtDelete,$sqlDelete)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement ne valja!";
            }
            else{
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmtDelete,"siss",$poslanaMKBSifra,$idPacijent,$poslaniDatum,$poslanoVrijeme);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmtDelete); 
            }
        }
        //Inicijaliziram brojač obrađenih redaka na 0
        $brojacAzuriranihRedaka = 0;
        //Inicijaliziram brojač iteracija na 0 isprva
        $brojacIteracija = 0;
        //Prolazim kroz svaku MKB šifru polja sekundarnih dijagnoza
        foreach($mkbSifraSekundarna as $mkb){
            //Povećavam iteraciju za 1
            $brojacIteracija = $brojacIteracija + 1;
            //Inicijalno postavljam proizvod na NULL
            $proizvod = NULL;
            //Inicijalno postavljam oblik, jačinu i pakiranje lijeka na NULL
            $oblikJacinaPakiranjeLijek = NULL;
            //Postavljam inicijalno da nisam pronašao lijek u bazi
            $pronasao = false;
            //Inicijalno postavljam brojač na 2
            $brojac = 2;
            if(empty($osnovnaListaLijekDropdown)){
                $osnovnaListaLijekDropdown = NULL;
            }
            else{
                //Dohvaćam OJP ako ga ima
                while($pronasao !== true){
                    //Splitam string da mu uzmem ime i oblik-jačinu-pakiranje (KREĆEM OD 2)
                    $polje = explode(" ",$osnovnaListaLijekDropdown,$brojac);
                    //Dohvaćam oblik,jačinu i pakiranje lijeka
                    $ojpLijek = array_pop($polje);
                    //Dohvaćam ime lijeka
                    $imeLijek = implode(" ", $polje);

                    //Kreiram sql upit kojim provjeravam postoji li LIJEK u osnovnoj listi lijekova
                    $sqlOsnovnaLista = "SELECT o.zasticenoImeLijek,o.oblikJacinaPakiranjeLijek FROM osnovnalistalijekova o 
                            WHERE o.oblikJacinaPakiranjeLijek = '$ojpLijek' AND o.zasticenoImeLijek = '$imeLijek'";

                    $resultOsnovnaLista = $conn->query($sqlOsnovnaLista);
                    //Ako je lijek pronađen u OSNOVNOJ LISTI LIJEKOVA
                    if ($resultOsnovnaLista->num_rows > 0) {
                        while($rowOsnovnaLista = $resultOsnovnaLista->fetch_assoc()) {
                            //Dohvaćam oblik, jačinu i pakiranje unesenog lijeka
                            $oblikJacinaPakiranjeLijek = $rowOsnovnaLista['oblikJacinaPakiranjeLijek'];
                            $proizvod = $rowOsnovnaLista['zasticenoImeLijek'];
                        }
                        //Izlazim iz petlje
                        $pronasao = true;
                    }
                    //Povećavam brojač za 1
                    $brojac++;
                }
            }
            if(empty($osnovnaListaLijekText)){
                $osnovnaListaLijekText = NULL;
            }
            else{
                //Dohvaćam OJP ako ga ima
                while($pronasao !== true){
                    //Splitam string da mu uzmem ime i oblik-jačinu-pakiranje (KREĆEM OD 2)
                    $polje = explode(" ",$osnovnaListaLijekText,$brojac);
                    //Dohvaćam oblik,jačinu i pakiranje lijeka
                    $ojpLijek = array_pop($polje);
                    //Dohvaćam ime lijeka
                    $imeLijek = implode(" ", $polje);

                    //Kreiram sql upit kojim provjeravam postoji li LIJEK u osnovnoj listi lijekova
                    $sqlOsnovnaLista = "SELECT o.zasticenoImeLijek,o.oblikJacinaPakiranjeLijek FROM osnovnalistalijekova o 
                            WHERE o.oblikJacinaPakiranjeLijek = '$ojpLijek' AND o.zasticenoImeLijek = '$imeLijek'";

                    $resultOsnovnaLista = $conn->query($sqlOsnovnaLista);
                    //Ako je lijek pronađen u OSNOVNOJ LISTI LIJEKOVA
                    if ($resultOsnovnaLista->num_rows > 0) {
                        while($rowOsnovnaLista = $resultOsnovnaLista->fetch_assoc()) {
                            //Dohvaćam oblik, jačinu i pakiranje unesenog lijeka
                            $oblikJacinaPakiranjeLijek = $rowOsnovnaLista['oblikJacinaPakiranjeLijek'];
                            $proizvod = $rowOsnovnaLista['zasticenoImeLijek'];
                        }
                        //Izlazim iz petlje
                        $pronasao = true;
                    }
                    //Povećavam brojač za 1
                    $brojac++;
                }
            }
            if(empty($dopunskaListaLijekDropdown)){
                $dopunskaListaLijekDropdown = NULL;
            }
            else{
                //Dohvaćam OJP ako ga ima
                while($pronasao !== true){
                    //Splitam string da mu uzmem ime i oblik-jačinu-pakiranje (KREĆEM OD 2)
                    $polje = explode(" ",$dopunskaListaLijekDropdown,$brojac);
                    //Dohvaćam oblik,jačinu i pakiranje lijeka
                    $ojpLijek = array_pop($polje);
                    //Dohvaćam ime lijeka
                    $imeLijek = implode(" ", $polje);

                    //Kreiram sql upit kojim provjeravam postoji li LIJEK u osnovnoj listi lijekova
                    $sqlDopunskaLista = "SELECT d.zasticenoImeLijek,d.oblikJacinaPakiranjeLijek FROM dopunskalistalijekova d 
                            WHERE d.oblikJacinaPakiranjeLijek = '$ojpLijek' AND d.zasticenoImeLijek = '$imeLijek'";

                    $resultDopunskaLista = $conn->query($sqlDopunskaLista);
                    //Ako je lijek pronađen u DOPUNSKOJ LISTI LIJEKOVA
                    if ($resultDopunskaLista->num_rows > 0) {
                        while($rowDopunskaLista = $resultDopunskaLista->fetch_assoc()) {
                            //Dohvaćam oblik, jačinu i pakiranje unesenog lijeka
                            $oblikJacinaPakiranjeLijek = $rowDopunskaLista['oblikJacinaPakiranjeLijek'];
                            $proizvod = $rowDopunskaLista['zasticenoImeLijek'];
                        }
                        //Izlazim iz petlje
                        $pronasao = true;
                    }
                    //Povećavam brojač za 1
                    $brojac++;
                }
            }
            if(empty($dopunskaListaLijekText)){
                $dopunskaListaLijekText = NULL;
            }
            else{
                //Dohvaćam OJP ako ga ima
                while($pronasao !== true){
                    //Splitam string da mu uzmem ime i oblik-jačinu-pakiranje (KREĆEM OD 2)
                    $polje = explode(" ",$dopunskaListaLijekText,$brojac);
                    //Dohvaćam oblik,jačinu i pakiranje lijeka
                    $ojpLijek = array_pop($polje);
                    //Dohvaćam ime lijeka
                    $imeLijek = implode(" ", $polje);

                    //Kreiram sql upit kojim provjeravam postoji li LIJEK u osnovnoj listi lijekova
                    $sqlDopunskaLista = "SELECT d.zasticenoImeLijek,d.oblikJacinaPakiranjeLijek FROM dopunskalistalijekova d 
                                        WHERE d.oblikJacinaPakiranjeLijek = '$ojpLijek' AND d.zasticenoImeLijek = '$imeLijek'";

                    $resultDopunskaLista = $conn->query($sqlDopunskaLista);
                    //Ako je lijek pronađen u DOPUNSKOJ LISTI LIJEKOVA
                    if ($resultDopunskaLista->num_rows > 0) {
                        while($rowDopunskaLista = $resultDopunskaLista->fetch_assoc()) {
                            //Dohvaćam oblik, jačinu i pakiranje unesenog lijeka
                            $oblikJacinaPakiranjeLijek = $rowDopunskaLista['oblikJacinaPakiranjeLijek'];
                            $proizvod = $rowDopunskaLista['zasticenoImeLijek'];
                        }
                        //Izlazim iz petlje
                        $pronasao = true;
                    }
                    //Povećavam brojač za 1
                    $brojac++;
                }
            }
            if(empty($osnovnaListaMagPripravakDropdown)){
                $osnovnaListaMagPripravakDropdown = NULL;
            }
            else{
                $proizvod = $osnovnaListaMagPripravakDropdown;
            }
            if(empty($osnovnaListaMagPripravakText)){
                $osnovnaListaMagPripravakText = NULL;
            }
            else{
                $proizvod = $osnovnaListaMagPripravakText;
            }
            if(empty($dopunskaListaMagPripravakDropdown)){
                $dopunskaListaMagPripravakDropdown = NULL;
            }
            else{
                $proizvod = $dopunskaListaMagPripravakDropdown;
            }
            if(empty($dopunskaListaMagPripravakText)){
                $dopunskaListaMagPripravakText = NULL;
            }
            else{
                $proizvod = $dopunskaListaMagPripravakText;
            }
            if(empty($hitnost)){
                $hitnost = NULL;
            }
            if(empty($ponovljiv)){
                $ponovljiv = NULL;
            }
            if(empty($brojPonavljanja)){
                $brojPonavljanja = NULL;
            }
            if(empty($sifraSpecijalist)){
                $sifraSpecijalist = NULL;
            }
            /*************************************** */
            /*OVO JE AŽURIRANJE PREMA GORE (KADA JE $brojSekundarnaBaza <= $brojacSekundarnaDijagnozaForma) */
            //Ako je broj sek. dijagnoza u bazi manji ili jednak od broja sek. dijagnoza u formi te je broj sek. dijagnoza u formi manji ili jednak 1
            if($brojSekundarnaBaza <= $brojacSekundarnaDijagnozaForma && $brojacSekundarnaDijagnozaForma == 1){
                //Kreiram upit za dodavanje novog recepta u bazu
                $sql = "UPDATE recept r SET r.mkbSifraPrimarna = ?, r.mkbSifraSekundarna = ?, r.proizvod = ?, 
                                            r.oblikJacinaPakiranjeLijek = ?, r.kolicina = ?, r.doziranje = ?,
                                            r.dostatnost = ?, r.hitnost = ?, r.ponovljiv = ?, r.brojPonavljanja = ?, 
                                            r.sifraSpecijalist = ?, r.datumRecept = ?, r.vrijemeRecept = ?
                        WHERE r.idPacijent = ? 
                        AND r.datumRecept = ? 
                        AND r.vrijemeRecept = ? 
                        AND r.mkbSifraPrimarna = ?";
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
                    mysqli_stmt_bind_param($stmt,"ssssisissiississs",$mkbSifraPrimarna,$mkb,$proizvod,$oblikJacinaPakiranjeLijek,
                                                    $kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv, 
                                                    $brojPonavljanja,$sifraSpecijalist,$datum,$vrijeme,$idPacijent,
                                                    $poslaniDatum,$poslanoVrijeme,$poslanaMKBSifra);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmt);
                    //Dohvaćam ID recepta kojega sam upravo ažurirao
                    $sqlRecept = "SELECT r.idRecept FROM recept r 
                                WHERE r.idPacijent = '$idPacijent' 
                                AND r.datumRecept = '$datum' 
                                AND r.vrijemeRecept = '$vrijeme' 
                                AND r.mkbSifraPrimarna = '$mkbSifraPrimarna'";
                    $resultRecept = $conn->query($sqlRecept);
                    
                    //Ako pacijent IMA evidentiranih recepata:
                    if ($resultRecept ->num_rows > 0) {
                        while($rowRecept  = $resultRecept ->fetch_assoc()) {
                            //Dohvaćam ažurirani ID recepta
                            $idRecept = $rowRecept ['idRecept'];
                        }
                    }

                    //Kreiram upit za ažuriranje povijesti bolesti
                    $sqlPovijestBolesti = "UPDATE povijestBolesti pb SET pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ?, pb.datum = ?, pb.vrijeme = ? 
                                        WHERE pb.idRecept = ?";
                    //Kreiranje prepared statementa
                    $stmtPovijestBolesti = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmtPovijestBolesti,$sqlPovijestBolesti)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement ne valja!";
                    }
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmtPovijestBolesti,"ssssi",$mkbSifraPrimarna,$mkb, $datum,$vrijeme,$idRecept);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmtPovijestBolesti);
                        //Vraćanje uspješnog odgovora serveru
                        $response["success"] = "true";
                        $response["message"] = "Recept uspješno ažuriran!";
                    }
                }
            }
            //Ako je broj sek. dijagnoza u bazi manji ili jednak od broja sek. dijagnoza u formi te je broj sek. dijagnoza u formi veći od 1
            else if($brojSekundarnaBaza <= $brojacSekundarnaDijagnozaForma && $brojacSekundarnaDijagnozaForma > 1){
                //Ako je broj sek. dijagnoza u bazi JEDNAK 0 te je prva iteracija (tj. prva dijagnoza forme)
                if($brojSekundarnaBaza == 0 && $brojacIteracija == 1){
                    //Ažuriram recept koji ima MINIMALNI ID recepta za ovog pacijenta, datum i vrijeme
                    $sql = "UPDATE recept r SET r.mkbSifraPrimarna = ?, r.mkbSifraSekundarna = ?, r.proizvod = ?, 
                                                r.oblikJacinaPakiranjeLijek = ?, r.kolicina = ?, r.doziranje = ?,
                                                r.dostatnost = ?, r.hitnost = ?, r.ponovljiv = ?, r.brojPonavljanja = ?, 
                                                r.sifraSpecijalist = ?, r.datumRecept = ?, r.vrijemeRecept = ?
                            WHERE r.idRecept = ?";
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
                        mysqli_stmt_bind_param($stmt,"ssssisissiissi",$mkbSifraPrimarna,$mkb,$proizvod,$oblikJacinaPakiranjeLijek,
                                        $kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv, 
                                        $brojPonavljanja,$sifraSpecijalist,$datum,$vrijeme,$idMinRecept);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmt);
                        //Dohvaćam ID recepta kojega sam upravo ažurirao
                        $sqlRecept = "SELECT r.idRecept FROM recept r 
                                    WHERE r.idPacijent = '$idPacijent' 
                                    AND r.datumRecept = '$datum' 
                                    AND r.vrijemeRecept = '$vrijeme' 
                                    AND r.mkbSifraPrimarna = '$mkbSifraPrimarna'";
                        $resultRecept = $conn->query($sqlRecept);
                        
                        //Ako pacijent IMA evidentiranih recepata:
                        if ($resultRecept ->num_rows > 0) {
                            while($rowRecept  = $resultRecept ->fetch_assoc()) {
                                //Dohvaćam ažurirani ID recepta
                                $idRecept = $rowRecept['idRecept'];
                            }
                        }
                        //Prije nego što izbrišem redak povijesti bolesti, dohvaćam ga
                        $sqlSelectPov = "SELECT * FROM povijestbolesti 
                                        WHERE idRecept = '$idRecept'";
                        $resultSelectPov = $conn->query($sqlSelectPov);

                        if ($resultSelectPov->num_rows > 0) {
                            while($rowSelectPov = $resultSelectPov->fetch_assoc()) {
                                //Dohvaćam određene vrijednosti povijesti bolesti
                                $razlogDolaska = $rowSelectPov['razlogDolaska'];
                                $anamneza = $rowSelectPov['anamneza'];
                                $status = $rowSelectPov['statusPacijent'];
                                $nalaz = $rowSelectPov['nalaz'];
                                $tipSlucaj = $rowSelectPov['tipSlucaj'];
                                $terapija = $rowSelectPov['terapija'];
                                $idObradaLijecnik = $rowSelectPov['idObradaLijecnik'];
                                $preporukaLijecnik = $rowSelectPov['preporukaLijecnik'];
                                $napomena = $rowSelectPov['napomena'];
                                $datumPovijestiBolesti = $rowSelectPov['datum'];
                                $narucen = $rowSelectPov['narucen'];
                                $mboPacijent = $rowSelectPov['mboPacijent'];
                                $vrijemePovijestiBolesti = $rowSelectPov['vrijeme'];
                                $prosliPregled = $rowSelectPov['prosliPregled'];
                                $bojaPregled = $rowSelectPov['bojaPregled'];
                            }
                        } 
                        //Kreiram upit za ažuriranje povijesti bolesti
                        $sqlPovijestBolesti = "UPDATE povijestBolesti pb SET pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ? 
                                            WHERE pb.idRecept = ?";
                        //Kreiranje prepared statementa
                        $stmtPovijestBolesti = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmtPovijestBolesti,$sqlPovijestBolesti)){
                            $response["success"] = "false";
                            $response["message"] = "Prepared statement ne valja!";
                        }
                        else{
                            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                            mysqli_stmt_bind_param($stmtPovijestBolesti,"ssi",$mkbSifraPrimarna,$mkb,$idRecept);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmtPovijestBolesti);
                            //Vraćanje uspješnog odgovora serveru
                            $response["success"] = "true";
                            $response["message"] = "Recept uspješno ažuriran!";
                        }
                    }
                }
                //Ako je broj sek. dijagnoza u BAZI JENDAK 0 te je n-ta iteracija (tj. n-ta dijagnoza forme)
                else if($brojSekundarnaBaza == 0 && $brojacIteracija > 1){
                    //Kreiram upit za dodavanje novog recepta u bazu
                    $sql = "INSERT INTO recept (mkbSifraPrimarna,mkbSifraSekundarna,proizvod,oblikJacinaPakiranjeLijek, 
                                                kolicina,doziranje,dostatnost,hitnost,ponovljiv,brojPonavljanja, 
                                                sifraSpecijalist,idPacijent,datumRecept,vrijemeRecept) VALUES 
                                                (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                    //Kreiranje prepared statementa
                    $stmt = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmt,$sql)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement ne valja!";
                    }
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmt,"ssssisissiiiss",$mkbSifraPrimarna,$mkb,$proizvod,$oblikJacinaPakiranjeLijek,
                                                                    $kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv, 
                                                                    $brojPonavljanja,$sifraSpecijalist,$idPacijent,$datum,$vrijeme);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmt);
                        //Dohvaćam ID recepta kojega sam upravo unio
                        $resultMaxIDrecept = mysqli_query($conn,"SELECT MAX(r.idRecept) AS ID FROM recept r");
                        //Ulazim u polje rezultata i idem redak po redak
                        while($rowMaxIDrecept = mysqli_fetch_array($resultMaxIDrecept)){
                            //Dohvaćam željeni ID recept
                            $maxIDrecept = $rowMaxIDrecept['ID'];
                        } 
                        //Kreiram upit za dodavanje novog recepta u bazu
                        $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                                                    nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                                    preporukaLijecnik, napomena, datum, narucen, mboPacijent, 
                                                    idObradaLijecnik,vrijeme,idRecept,prosliPregled,bojaPregled) 
                                                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                        //Kreiranje prepared statementa
                        $stmt = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmt,$sql)){
                            $response["success"] = "false";
                            $response["message"] = "Prepared statement ne valja!";
                        }
                        else{
                            if(empty($prosliPregled)){
                                $prosliPregled = NULL;
                            }
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
                            mysqli_stmt_bind_param($stmt,"sssssssssssssisiis",$razlogDolaska,$anamneza,$status,$nalaz,$mkbSifraPrimarna,$mkb,
                                                        $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datumPovijestiBolesti, 
                                                        $narucen,$mboPacijent,$idObradaLijecnik,$vrijemePovijestiBolesti,$maxIDrecept, 
                                                        $prosliPregled,$bojaPregled);
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
                                $response["message"] = "Recept uspješno ažuriran!";
                            }
                        }
                    } 
                }
                //Ako je broj obrađenih redaka manji od broja dijagnoza u bazi te je prva iteracija (koristim PRVI MINIMALNI ID recepta)
                if($brojacAzuriranihRedaka < $brojSekundarnaBaza && $brojacIteracija == 1){
                    //Ažuriram recept koji ima MINIMALNI ID recepta za ovog pacijenta, datum i vrijeme
                    $sql = "UPDATE recept r SET r.mkbSifraPrimarna = ?, r.mkbSifraSekundarna = ?, r.proizvod = ?, 
                                                r.oblikJacinaPakiranjeLijek = ?, r.kolicina = ?, r.doziranje = ?,
                                                r.dostatnost = ?, r.hitnost = ?, r.ponovljiv = ?, r.brojPonavljanja = ?, 
                                                r.sifraSpecijalist = ?, r.datumRecept = ?, r.vrijemeRecept = ?
                            WHERE r.idRecept = ?";
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
                        mysqli_stmt_bind_param($stmt,"ssssisissiissi",$mkbSifraPrimarna,$mkb,$proizvod,$oblikJacinaPakiranjeLijek,
                                            $kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv, 
                                            $brojPonavljanja,$sifraSpecijalist,$datum,$vrijeme,$idMinRecept);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmt);

                        //Kreiram upit za ažuriranje povijesti bolesti
                        $sqlPovijestBolesti = "UPDATE povijestBolesti pb SET pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ? 
                                            WHERE pb.idRecept = ?";
                        //Kreiranje prepared statementa
                        $stmtPovijestBolesti = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmtPovijestBolesti,$sqlPovijestBolesti)){
                            $response["success"] = "false";
                            $response["message"] = "Prepared statement ne valja!";
                        }
                        else{
                            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                            mysqli_stmt_bind_param($stmtPovijestBolesti,"ssi",$mkbSifraPrimarna,$mkb,$idMinRecept);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmtPovijestBolesti);
                            //Vraćanje uspješnog odgovora serveru
                            $response["success"] = "true";
                            $response["message"] = "Recept uspješno ažuriran!";
                            //Prije nego što izbrišem redak povijesti bolesti, dohvaćam ga
                            $sqlSelectPov = "SELECT * FROM povijestbolesti 
                                            WHERE idRecept = '$idMinRecept'";
                            $resultSelectPov = $conn->query($sqlSelectPov);

                            if ($resultSelectPov->num_rows > 0) {
                                while($rowSelectPov = $resultSelectPov->fetch_assoc()) {
                                    //Dohvaćam određene vrijednosti povijesti bolesti
                                    $razlogDolaska = $rowSelectPov['razlogDolaska'];
                                    $anamneza = $rowSelectPov['anamneza'];
                                    $status = $rowSelectPov['statusPacijent'];
                                    $nalaz = $rowSelectPov['nalaz'];
                                    $tipSlucaj = $rowSelectPov['tipSlucaj'];
                                    $terapija = $rowSelectPov['terapija'];
                                    $idObradaLijecnik = $rowSelectPov['idObradaLijecnik'];
                                    $preporukaLijecnik = $rowSelectPov['preporukaLijecnik'];
                                    $napomena = $rowSelectPov['napomena'];
                                    $datumPovijestiBolesti = $rowSelectPov['datum'];
                                    $narucen = $rowSelectPov['narucen'];
                                    $mboPacijent = $rowSelectPov['mboPacijent'];
                                    $vrijemePovijestiBolesti = $rowSelectPov['vrijeme'];
                                    $prosliPregled = $rowSelectPov['prosliPregled'];
                                    $bojaPregled = $rowSelectPov['bojaPregled'];
                                }
                            } 
                            //Povećavam broj obrađenih redaka za 1
                            $brojacAzuriranihRedaka++;
                        }
                    }
                }
                //Ako je broj obrađenih redaka manji od broja dijagnoza u bazi te NIJE prva iteracija
                else if($brojacAzuriranihRedaka < $brojSekundarnaBaza && $brojacIteracija > 1){
                    
                    $sqlSljedeciMin = "SELECT r.idRecept FROM recept r 
                                    WHERE r.idPacijent = '$idPacijent' 
                                    AND r.datumRecept = '$poslaniDatum' 
                                    AND r.vrijemeRecept = '$poslanoVrijeme' 
                                    AND r.idRecept = 
                                    (SELECT r2.idRecept FROM recept r2 
                                    WHERE r2.idPacijent = '$idPacijent' 
                                    AND r2.datumRecept = '$poslaniDatum' 
                                    AND r2.vrijemeRecept = '$poslanoVrijeme' 
                                    AND r2.idRecept > '$idMinRecept' 
                                    LIMIT 1)";
                    $resultSljedeciMin = $conn->query($sqlSljedeciMin);
                            
                    //Ako pacijent IMA evidentiranih recepata:
                    if ($resultSljedeciMin->num_rows > 0) {
                        while($rowSljedeciMin = $resultSljedeciMin->fetch_assoc()) {
                            //Dohvaćam recept sa MINIMALNIM ID-om
                            $idMinRecept = $rowSljedeciMin['idRecept'];
                        }
                    }
                    //Ažuriram recept koji ima MINIMALNI ID recepta za ovog pacijenta, datum i vrijeme
                    $sqlUpdate = "UPDATE recept r SET r.mkbSifraPrimarna = ?, r.mkbSifraSekundarna = ?, r.proizvod = ?, 
                                                r.oblikJacinaPakiranjeLijek = ?, r.kolicina = ?, r.doziranje = ?,
                                                r.dostatnost = ?, r.hitnost = ?, r.ponovljiv = ?, r.brojPonavljanja = ?, 
                                                r.sifraSpecijalist = ?, r.datumRecept = ?, r.vrijemeRecept = ?
                            WHERE r.idRecept = ?";
                    //Kreiranje prepared statementa
                    $stmtUpdate = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmtUpdate,$sqlUpdate)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement ne valja!";
                    }
                    //Ako je prepared statement u redu
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmtUpdate,"ssssisissiissi",$mkbSifraPrimarna,$mkb,$proizvod,$oblikJacinaPakiranjeLijek,
                                            $kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv, 
                                            $brojPonavljanja,$sifraSpecijalist,$datum,$vrijeme,$idMinRecept);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmtUpdate);
                        //Kreiram upit za ažuriranje povijesti bolesti
                        $sqlPovijestBolesti = "UPDATE povijestBolesti pb SET pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ? 
                                            WHERE pb.idRecept = ?";
                        //Kreiranje prepared statementa
                        $stmtPovijestBolesti = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmtPovijestBolesti,$sqlPovijestBolesti)){
                            $response["success"] = "false";
                            $response["message"] = "Prepared statement ne valja!";
                        }
                        else{
                            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                            mysqli_stmt_bind_param($stmtPovijestBolesti,"ssi",$mkbSifraPrimarna,$mkb,$idMinRecept);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmtPovijestBolesti);
                            //Vraćanje uspješnog odgovora serveru
                            $response["success"] = "true";
                            $response["message"] = "Recept uspješno ažuriran!";
                            //Prije nego što izbrišem redak povijesti bolesti, dohvaćam ga
                            $sqlSelectPov = "SELECT * FROM povijestbolesti 
                                            WHERE idRecept = '$idMinRecept'";
                            $resultSelectPov = $conn->query($sqlSelectPov);

                            if ($resultSelectPov->num_rows > 0) {
                                while($rowSelectPov = $resultSelectPov->fetch_assoc()) {
                                    //Dohvaćam određene vrijednosti povijesti bolesti
                                    $razlogDolaska = $rowSelectPov['razlogDolaska'];
                                    $anamneza = $rowSelectPov['anamneza'];
                                    $status = $rowSelectPov['statusPacijent'];
                                    $nalaz = $rowSelectPov['nalaz'];
                                    $tipSlucaj = $rowSelectPov['tipSlucaj'];
                                    $terapija = $rowSelectPov['terapija'];
                                    $idObradaLijecnik = $rowSelectPov['idObradaLijecnik'];
                                    $preporukaLijecnik = $rowSelectPov['preporukaLijecnik'];
                                    $napomena = $rowSelectPov['napomena'];
                                    $datumPovijestiBolesti = $rowSelectPov['datum'];
                                    $narucen = $rowSelectPov['narucen'];
                                    $mboPacijent = $rowSelectPov['mboPacijent'];
                                    $vrijemePovijestiBolesti = $rowSelectPov['vrijeme'];
                                    $prosliPregled = $rowSelectPov['prosliPregled'];
                                    $bojaPregled = $rowSelectPov['bojaPregled'];
                                }
                            } 
                            //Povećavam broj obrađenih redaka za 1
                            $brojacAzuriranihRedaka++;
                        }
                    }
                }
                //Ako je broj ažuriranih redak JEDNAK broju sek. dijagnoza u bazi (npr. 2 == 2) I brojač iteracija JE VEĆI od broja sek. dijagnoza u bazi (npr. 3 > 2) 
                //te da je broj sek. dijagnoza u BAZI VEĆI OD 0
                if($brojacAzuriranihRedaka == $brojSekundarnaBaza && $brojacIteracija > $brojSekundarnaBaza && $brojSekundarnaBaza > 0){
                    //Kreiram upit za dodavanje novog recepta u bazu
                    $sql = "INSERT INTO recept (mkbSifraPrimarna,mkbSifraSekundarna,proizvod,oblikJacinaPakiranjeLijek, 
                                                kolicina,doziranje,dostatnost,hitnost,ponovljiv,brojPonavljanja, 
                                                sifraSpecijalist,idPacijent,datumRecept,vrijemeRecept) VALUES 
                                                (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                    //Kreiranje prepared statementa
                    $stmt = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmt,$sql)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement ne valja!";
                    }
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmt,"ssssisissiiiss",$mkbSifraPrimarna,$mkb,$proizvod,$oblikJacinaPakiranjeLijek,
                                                                    $kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv, 
                                                                    $brojPonavljanja,$sifraSpecijalist,$idPacijent,$datum,$vrijeme);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmt);
                        //Dohvaćam ID recepta kojega sam upravo unio
                        $resultMaxIDrecept = mysqli_query($conn,"SELECT MAX(r.idRecept) AS ID FROM recept r");
                        //Ulazim u polje rezultata i idem redak po redak
                        while($rowMaxIDrecept = mysqli_fetch_array($resultMaxIDrecept)){
                            //Dohvaćam željeni ID recept
                            $maxIDrecept = $rowMaxIDrecept['ID'];
                        } 
                        //Kreiram upit za dodavanje novog recepta u bazu
                        $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                                                    nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                                    preporukaLijecnik, napomena, datum, narucen, mboPacijent,idObradaLijecnik, 
                                                    vrijeme,idRecept,prosliPregled,bojaPregled) 
                                                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                        //Kreiranje prepared statementa
                        $stmt = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmt,$sql)){
                            $response["success"] = "false";
                            $response["message"] = "Prepared statement ne valja!";
                        }
                        else{
                            if(empty($prosliPregled)){
                                $prosliPregled = NULL;
                            }
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
                            mysqli_stmt_bind_param($stmt,"sssssssssssssisiis",$razlogDolaska,$anamneza,$status,$nalaz,$mkbSifraPrimarna,$mkb,
                                                        $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datumPovijestiBolesti, 
                                                        $narucen,$mboPacijent,$idObradaLijecnik,$vrijemePovijestiBolesti,$maxIDrecept, 
                                                        $prosliPregled,$bojaPregled);
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
                                $response["message"] = "Recept uspješno ažuriran!";
                            }
                        }
                    } 
                }
            }
            /**************************************** */
            //Ako su retci izbrisani, treba nadodati nove dijagnoze iz forme
            else if($brisanje == true){
                 //Kreiram upit za dodavanje novog recepta u bazu
                $sql = "INSERT INTO recept (mkbSifraPrimarna,mkbSifraSekundarna,proizvod,oblikJacinaPakiranjeLijek, 
                                            kolicina,doziranje,dostatnost,hitnost,ponovljiv,brojPonavljanja, 
                                            sifraSpecijalist,idPacijent,datumRecept,vrijemeRecept) VALUES 
                                            (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                //Kreiranje prepared statementa
                $stmt = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmt,$sql)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement ne valja!";
                }
                else{
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmt,"ssssisissiiiss",$mkbSifraPrimarna,$mkb,$proizvod,$oblikJacinaPakiranjeLijek,
                                                $kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv, 
                                                $brojPonavljanja,$sifraSpecijalist,$idPacijent,$datum,$vrijeme);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmt);
                    //Dohvaćam ID recepta kojega sam upravo unio
                    $resultMaxIDrecept = mysqli_query($conn,"SELECT MAX(r.idRecept) AS ID FROM recept r");
                    //Ulazim u polje rezultata i idem redak po redak
                    while($rowMaxIDrecept = mysqli_fetch_array($resultMaxIDrecept)){
                        //Dohvaćam željeni ID recept
                        $maxIDrecept = $rowMaxIDrecept['ID'];
                    } 
                    //Kreiram upit za dodavanje novog recepta u bazu
                    $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                                                nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                                preporukaLijecnik, napomena, datum, narucen, mboPacijent, 
                                                idObradaLijecnik,vrijeme,idRecept,prosliPregled,bojaPregled) 
                                                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                    //Kreiranje prepared statementa
                    $stmt = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmt,$sql)){
                        $response["success"] = "false";
                        $response["message"] = "Prepared statement ne valja!";
                    }
                    else{
                        if(empty($prosliPregled)){
                            $prosliPregled = NULL;
                        }
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
                        mysqli_stmt_bind_param($stmt,"sssssssssssssisiis",$razlogDolaska,$anamneza,$status,$nalaz,$mkbSifraPrimarna,$mkb,
                                                    $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datumPovijestiBolesti, 
                                                    $narucen,$mboPacijent,$idObradaLijecnik,$vrijemePovijestiBolesti,$maxIDrecept, 
                                                    $prosliPregled,$bojaPregled);
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
                            $response["message"] = "Recept uspješno ažuriran!";
                        }
                    }
                }  
            }
        }
    }
    //Vraćam odgovor frontendu
    return $response;
    }
}
?>