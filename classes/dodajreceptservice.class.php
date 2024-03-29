<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class DodajReceptService{
    //Funkcija koja dodava novi recept u bazu podataka
    function dodajRecept($mkbSifraPrimarna,$mkbSifraSekundarna,$osnovnaListaLijekDropdown,
                    $osnovnaListaLijekText,$dopunskaListaLijekDropdown,$dopunskaListaLijekText,
                    $osnovnaListaMagPripravakDropdown,$osnovnaListaMagPripravakText,$dopunskaListaMagPripravakDropdown,
                    $dopunskaListaMagPripravakText,$kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv,$brojPonavljanja,
                    $sifraSpecijalist,$idPacijent,$mboPacijent,$idLijecnik,$poslanaMKBSifra,$poslaniIDObrada,$poslaniTipSlucaj,$poslanoVrijeme){
    //Dohvaćam bazu 
    $baza = new Baza();
    $conn = $baza->spojiSBazom();
    //Kreiram prazno polje odgovora
    $response = [];
    //Trenutni datum
    $datum = date('Y-m-d');
    //Trenutno vrijeme
    $vrijeme = date('H:i:s');
    //Označavam da slučajno generirana oznaka već postoji u bazi
    $ispravnaOznaka = false;
    while($ispravnaOznaka != true){
        //Generiram slučajni oznaku po kojom grupiram
        $oznaka = sha1(uniqid());
        //Kreiram upit koji provjerava postoji li već ova random generirana oznaka u bazi
        $sqlProvjeraOznaka = "SELECT pb.oznaka FROM povijest_bolesti pb 
                            WHERE pb.oznaka = '$oznaka';";
        //Rezultat upita spremam u varijablu $resultProvjeraOznaka
        $resultProvjeraOznaka = mysqli_query($conn,$sqlProvjeraOznaka);
        //Ako se novo generirana oznaka NE NALAZI u bazi
        if(mysqli_num_rows($resultProvjeraOznaka) == 0){
            //Izlazim iz petlje
            $ispravnaOznaka = true;
        } 
    }
    //Gledam koliko ima sek. dijagnoza pregled u bazi gdje se dodava ID recepta
    $sqlCountSekundarna = "SELECT COUNT(pb.mkbSifraSekundarna) AS BrojSekundarna FROM povijest_bolesti pb
                        WHERE TRIM(pb.mkbSifraPrimarna) = '$poslanaMKBSifra' 
                        AND pb.idObradaLijecnik = '$poslaniIDObrada' 
                        AND pb.mboPacijent = '$mboPacijent' 
                        AND pb.tipSlucaj = '$poslaniTipSlucaj' 
                        AND pb.vrijeme = '$poslanoVrijeme'";
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
        //Kreiram upit za dodavanje novog recepta u bazu
        $sql = "INSERT INTO recept (mkbSifraPrimarna,mkbSifraSekundarna,proizvod,oblikJacinaPakiranjeLijek, 
                                    kolicina,doziranje,dostatnost,hitnost,ponovljiv,brojPonavljanja, 
                                    sifraSpecijalist,idPacijent,datumRecept,vrijemeRecept,oznaka) VALUES 
                                    (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        //Kreiranje prepared statementa
        $stmt = mysqli_stmt_init($conn);
        //Ako je statement neuspješan
        if(!mysqli_stmt_prepare($stmt,$sql)){
            $response["success"] = "false";
            $response["message"] = "Došlo je do pogreške!";
            $response["idRecept"] = null;
        }
        //Ako je prepared statement u redu
        else{
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
                    $sqlOsnovnaLista = "SELECT o.zasticenoImeLijek,o.oblikJacinaPakiranjeLijek FROM osnovna_lista_lijekova o 
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
                    $sqlOsnovnaLista = "SELECT o.zasticenoImeLijek,o.oblikJacinaPakiranjeLijek FROM osnovna_lista_lijekova o 
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
                    $sqlDopunskaLista = "SELECT d.zasticenoImeLijek,d.oblikJacinaPakiranjeLijek FROM dopunska_lista_lijekova d 
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
                    $sqlDopunskaLista = "SELECT d.zasticenoImeLijek,d.oblikJacinaPakiranjeLijek FROM dopunska_lista_lijekova d 
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
            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
            mysqli_stmt_bind_param($stmt,"ssssisissiiisss",$mkbSifraPrimarna,$prazna,$proizvod,$oblikJacinaPakiranjeLijek,
                                    $kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv, 
                                    $brojPonavljanja,$sifraSpecijalist,$idPacijent,$datum,$vrijeme,$oznaka);
            //Izvršavanje statementa
            mysqli_stmt_execute($stmt);

            //Dohvaćam ZADNJE UNESENI ID recepta
            $resultRecept = mysqli_query($conn,"SELECT MAX(r.idRecept) AS ID FROM recept r");
            //Ulazim u polje rezultata i idem redak po redak
            while($rowRecept = mysqli_fetch_array($resultRecept)){
                //Dohvaćam željeni ID recepta
                $idRecept = $rowRecept['ID'];
            } 

            //Ako je broj trenutnih sek. dijagnoza u bazi povijesti bolesti 0 ILI 1
            if(($brojSekundarnaBaza == 0 || $brojSekundarnaBaza == 1)){
                //Kreiram upit kojim ću unijeti ID recepta u tablicu "povijest_bolesti"
                $sqlUpdate ="UPDATE povijest_bolesti pb SET pb.idRecept = ?, 
                            pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ?  
                            WHERE TRIM(pb.mkbSifraPrimarna) = ? 
                            AND pb.idObradaLijecnik = ? 
                            AND pb.mboPacijent = ? 
                            AND pb.vrijeme = ? 
                            AND pb.tipSlucaj = ?";
                //Kreiranje prepared statementa
                $stmtUpdate = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmtUpdate,$sqlUpdate)){
                    $response["success"] = "false";
                    $response["message"] = "Došlo je do pogreške!";
                    $response["idRecept"] = null;
                }
                //Ako je prepared statement u redu
                else{
                    $prazna = NULL;
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmtUpdate,"isssisss",$idRecept,$mkbSifraPrimarna,$prazna,$poslanaMKBSifra, 
                                                            $poslaniIDObrada,$mboPacijent,$poslanoVrijeme,$poslaniTipSlucaj);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmtUpdate);
                    //Vraćanje uspješnog odgovora serveru
                    $response["success"] = "true";
                    $response["message"] = "Recept uspješno dodan!";
                    $response["idRecept"] = $idRecept;
                } 
            }
            //Ako je broj sek. dijagnoza u bazi povijesti bolesti VEĆI OD 1 (npr. POVIJEST BOLESTI = A00-A01,A00-A02 RECEPT = A00-NULL)
            else if($brojSekundarnaBaza > 1){
                //Brišem sve retke iz tablice ambulanta za ovu povijest bolesti
                $sqlDeleteAmbulanta = "DELETE a FROM ambulanta a
                                    JOIN povijest_bolesti pb ON pb.idPovijestBolesti = a.idPovijestBolesti 
                                    WHERE TRIM(pb.mkbSifraPrimarna) = ? 
                                    AND pb.idObradaLijecnik = ? 
                                    AND pb.mboPacijent = ? 
                                    AND pb.vrijeme = ? 
                                    AND pb.tipSlucaj = ?;";
                //Kreiranje prepared statementa
                $stmtDeleteAmbulanta = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmtDeleteAmbulanta,$sqlDeleteAmbulanta)){
                    $response["success"] = "false";
                    $response["message"] = "Došlo je do pogreške!";
                    $response["idRecept"] = null;
                }
                else{
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmtDeleteAmbulanta,"sisss",$poslanaMKBSifra,$poslaniIDObrada,$mboPacijent,$poslanoVrijeme,$poslaniTipSlucaj);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmtDeleteAmbulanta);

                    //Prije nego što izbrišem redak povijesti bolesti, dohvaćam ga
                    $sqlPovijestBolesti = "SELECT * FROM povijest_bolesti 
                                        WHERE TRIM(mkbSifraPrimarna) = '$poslanaMKBSifra' 
                                        AND idObradaLijecnik = '$poslaniIDObrada' 
                                        AND mboPacijent = '$mboPacijent' 
                                        AND vrijeme = '$poslanoVrijeme' 
                                        AND tipSlucaj = '$poslaniTipSlucaj'";
                    $resultPovijestBolesti = $conn->query($sqlPovijestBolesti);

                    if ($resultPovijestBolesti->num_rows > 0) {
                        while($rowPovijestBolesti = $resultPovijestBolesti->fetch_assoc()) {
                            //Dohvaćam određene vrijednosti povijesti bolesti
                            $razlogDolaska = $rowPovijestBolesti['razlogDolaska'];
                            $anamneza = $rowPovijestBolesti['anamneza'];
                            $status = $rowPovijestBolesti['statusPacijent'];
                            $nalaz = $rowPovijestBolesti['nalaz'];
                            $tipSlucaj = $rowPovijestBolesti['tipSlucaj'];
                            $terapija = $rowPovijestBolesti['terapija'];
                            $preporukaLijecnik = $rowPovijestBolesti['preporukaLijecnik'];
                            $napomena = $rowPovijestBolesti['napomena'];
                            $datumPovijestiBolesti = $rowPovijestBolesti['datum'];
                            $narucen = $rowPovijestBolesti['narucen'];
                            $mboPacijent = $rowPovijestBolesti['mboPacijent'];
                            $vrijemePovijestiBolesti = $rowPovijestBolesti['vrijeme'];
                            $prosliPregled = $rowPovijestBolesti['prosliPregled'];
                            $bojaPregled = $rowPovijestBolesti['bojaPregled'];
                            $oznakaPov = $rowPovijestBolesti['oznaka'];
                        }
                    } 
                    //Brišem sve retke iz tablice povijesti bolesti
                    $sqlDelete = "DELETE FROM povijest_bolesti 
                                WHERE TRIM(mkbSifraPrimarna) = ? 
                                AND idObradaLijecnik = ? 
                                AND mboPacijent = ? 
                                AND vrijeme = ? 
                                AND tipSlucaj = ?";
                    //Kreiranje prepared statementa
                    $stmtDelete = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmtDelete,$sqlDelete)){
                        $response["success"] = "false";
                        $response["message"] = "Došlo je do pogreške!";
                        $response["idRecept"] = null;
                    }
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmtDelete,"sisss",$poslanaMKBSifra,$poslaniIDObrada,$mboPacijent,$poslanoVrijeme,$poslaniTipSlucaj);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmtDelete);  
                        //Kreiram upit za dodavanje novog recepta u bazu
                        $sql = "INSERT INTO povijest_bolesti (razlogDolaska, anamneza, statusPacijent, 
                                nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                preporukaLijecnik, napomena, datum, narucen, mboPacijent,idObradaLijecnik, 
                                vrijeme,idRecept,prosliPregled, bojaPregled,oznaka) 
                                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                        //Kreiranje prepared statementa
                        $stmt = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmt,$sql)){
                            $response["success"] = "false";
                            $response["message"] = "Došlo je do pogreške!";
                            $response["idRecept"] = null;
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
                            mysqli_stmt_bind_param($stmt,"sssssssssssssisiiss",$razlogDolaska,$anamneza,$status,$nalaz,$mkbSifraPrimarna,$prazna,
                                                            $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datumPovijestiBolesti, 
                                                            $narucen,$mboPacijent,$poslaniIDObrada,$vrijemePovijestiBolesti,$idRecept, 
                                                            $prosliPregled,$bojaPregled,$oznakaPov);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmt);

                            //Dohvaćam ID povijesti bolesti kojega sam upravo unio
                            $resultPovijestBolesti = mysqli_query($conn,"SELECT MAX(pb.idPovijestBolesti) AS ID FROM povijest_bolesti pb");
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
                                $response["message"] = "Došlo je do pogreške!";
                                $response["idRecept"] = null;
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
                                $response["idRecept"] = $idRecept;
                            }
                        } 
                    }
                }
            }
        }
    }
    //Ako IMA MKB šifri sek. dijagnoza
    else{
        //Kreiram upit koji dohvaća MINIMALNI ID povijesti bolesti za određenog pacijenta i određenu sesiju obrade
        $sqlMin = "SELECT pb.idPovijestBolesti FROM povijest_bolesti pb 
                WHERE TRIM(pb.mkbSifraPrimarna) = '$poslanaMKBSifra' 
                AND pb.idObradaLijecnik = '$poslaniIDObrada' 
                AND pb.mboPacijent = '$mboPacijent' 
                AND pb.tipSlucaj = '$poslaniTipSlucaj' 
                AND pb.vrijeme = '$poslanoVrijeme'
                AND pb.idPovijestBolesti = 
                (SELECT MIN(pb2.idPovijestBolesti) FROM povijest_bolesti pb2  
                WHERE TRIM(pb2.mkbSifraPrimarna) = '$poslanaMKBSifra' 
                AND pb2.idObradaLijecnik = '$poslaniIDObrada' 
                AND pb2.mboPacijent = '$mboPacijent' 
                AND pb2.tipSlucaj = '$poslaniTipSlucaj' 
                AND pb2.vrijeme = '$poslanoVrijeme')";
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
        $brojacSekundarnaForma = count($mkbSifraSekundarna); 
        //Inicijaliziram varijablu $brisanje na false na početku
        $brisanje = false;
        //Ako je broj dijagnoza u bazi VEĆI od broja dijagnoza u formi
        if($brojSekundarnaBaza > $brojacSekundarnaForma){
            //Označavam da treba obrisati sve retke pa nadodati kasnije
            $brisanje = true;
            //Brišem sve retke iz tablice ambulanta za ovu povijest bolesti
            $sqlDeleteAmbulanta = "DELETE a FROM ambulanta a
                                JOIN povijest_bolesti pb ON pb.idPovijestBolesti = a.idPovijestBolesti 
                                WHERE TRIM(pb.mkbSifraPrimarna) = ? 
                                AND pb.idObradaLijecnik = ? 
                                AND pb.mboPacijent = ? 
                                AND pb.tipSlucaj = ? 
                                AND pb.vrijeme = ?;";
            //Kreiranje prepared statementa
            $stmtDeleteAmbulanta = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmtDeleteAmbulanta,$sqlDeleteAmbulanta)){
                $response["success"] = "false";
                $response["message"] = "Došlo je do pogreške!";
                $response["idRecept"] = null;
            }
            else{
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmtDeleteAmbulanta,"sisss",$poslanaMKBSifra,$poslaniIDObrada,$mboPacijent,$poslaniTipSlucaj,$poslanoVrijeme);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmtDeleteAmbulanta);
                //Prije nego što ubacim novi redak povijesti bolesti, dohvaćam redak koji sam ažurirao u prethodnom if uvjetu 
                $sqlPovijestBolesti = "SELECT * FROM povijest_bolesti 
                                    WHERE TRIM(mkbSifraPrimarna) = '$poslanaMKBSifra' 
                                    AND idObradaLijecnik = '$poslaniIDObrada' 
                                    AND mboPacijent = '$mboPacijent' 
                                    AND tipSlucaj = '$poslaniTipSlucaj' 
                                    AND vrijeme = '$poslanoVrijeme'";
                $resultPovijestBolesti = $conn->query($sqlPovijestBolesti);

                if ($resultPovijestBolesti->num_rows > 0) {
                    while($rowPovijestBolesti = $resultPovijestBolesti->fetch_assoc()) {
                        //Dohvaćam određene vrijednosti povijesti bolesti
                        $razlogDolaska = $rowPovijestBolesti['razlogDolaska'];
                        $anamneza = $rowPovijestBolesti['anamneza'];
                        $status = $rowPovijestBolesti['statusPacijent'];
                        $nalaz = $rowPovijestBolesti['nalaz'];
                        $tipSlucaj = $rowPovijestBolesti['tipSlucaj'];
                        $terapija = $rowPovijestBolesti['terapija'];
                        $preporukaLijecnik = $rowPovijestBolesti['preporukaLijecnik'];
                        $napomena = $rowPovijestBolesti['napomena'];
                        $datumPovijestiBolesti = $rowPovijestBolesti['datum'];
                        $narucen = $rowPovijestBolesti['narucen'];
                        $mboPacijent = $rowPovijestBolesti['mboPacijent'];
                        $vrijemePovijestiBolesti = $rowPovijestBolesti['vrijeme'];
                        $prosliPregled = $rowPovijestBolesti['prosliPregled'];
                        $bojaPregled = $rowPovijestBolesti['bojaPregled'];
                        $oznakaPov = $rowPovijestBolesti['oznaka'];
                    }
                } 
                //Brišem sve retke iz tablice povijesti bolesti
                $sqlDelete = "DELETE FROM povijest_bolesti 
                            WHERE TRIM(mkbSifraPrimarna) = ? 
                            AND idObradaLijecnik = ? 
                            AND mboPacijent = ? 
                            AND tipSlucaj = ? 
                            AND vrijeme = ?";
                //Kreiranje prepared statementa
                $stmtDelete = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmtDelete,$sqlDelete)){
                    $response["success"] = "false";
                    $response["message"] = "Došlo je do pogreške!";
                    $response["idRecept"] = null;
                }
                else{
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmtDelete,"sisss",$poslanaMKBSifra,$poslaniIDObrada,$mboPacijent,$poslaniTipSlucaj,$poslanoVrijeme);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmtDelete);
                }
            }
        }
        //Prolazim kroz svaku MKB šifru polja sekundarnih dijagnoza
        foreach($mkbSifraSekundarna as $mkb){
            //Inkrementiram brojač iteracija 
            $brojacIteracija = $brojacIteracija + 1;
            //Kreiram upit za dodavanje novog recepta u bazu
            $sql = "INSERT INTO recept (mkbSifraPrimarna,mkbSifraSekundarna,proizvod,oblikJacinaPakiranjeLijek, 
                                        kolicina,doziranje,dostatnost,hitnost,ponovljiv,brojPonavljanja, 
                                        sifraSpecijalist,idPacijent,datumRecept,vrijemeRecept,oznaka) VALUES 
                                        (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            //Kreiranje prepared statementa
            $stmt = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmt,$sql)){
                $response["success"] = "false";
                $response["message"] = "Došlo je do pogreške!";
                $response["idRecept"] = null;
            }
            //Ako je prepared statement u redu
            else{
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
                        $sqlOsnovnaLista = "SELECT o.zasticenoImeLijek,o.oblikJacinaPakiranjeLijek FROM osnovna_lista_lijekova o 
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
                        $sqlOsnovnaLista = "SELECT o.zasticenoImeLijek,o.oblikJacinaPakiranjeLijek FROM osnovna_lista_lijekova o 
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
                        $sqlDopunskaLista = "SELECT d.zasticenoImeLijek,d.oblikJacinaPakiranjeLijek FROM dopunska_lista_lijekova d 
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
                        $sqlDopunskaLista = "SELECT d.zasticenoImeLijek,d.oblikJacinaPakiranjeLijek FROM dopunska_lista_lijekova d 
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
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmt,"ssssisissiiisss",$mkbSifraPrimarna,$mkb,$proizvod,$oblikJacinaPakiranjeLijek,
                        $kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv, 
                        $brojPonavljanja,$sifraSpecijalist,$idPacijent,$datum,$vrijeme,$oznaka);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmt);

                //Dohvaćam ZADNJE UNESENI ID recepta
                $resultRecept = mysqli_query($conn,"SELECT MAX(r.idRecept) AS ID FROM recept r");
                //Ulazim u polje rezultata i idem redak po redak
                while($rowRecept = mysqli_fetch_array($resultRecept)){
                    //Dohvaćam željeni ID povijesti bolesti
                    $idRecept = $rowRecept['ID'];
                    //Ako je prva sekundarna dijagnoza
                    if($brojacIteracija == 1){
                        $pomIdRecept = $idRecept;
                    }
                } 
                //(BAZA = 0, FORMA = 1) ILI (BAZA = 1, FORMA = 1)
                if($brojSekundarnaBaza <= $brojacSekundarnaForma && $brojacSekundarnaForma == 1){
                    //Kreiram upit kojim ću unijeti ID recepta u tablicu "povijest_bolesti"
                    $sqlUpdate ="UPDATE povijest_bolesti pb SET pb.idRecept = ?,pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ? 
                                WHERE TRIM(pb.mkbSifraPrimarna) = ? 
                                AND pb.idObradaLijecnik = ? 
                                AND pb.mboPacijent = ? 
                                AND pb.tipSlucaj = ? 
                                AND pb.vrijeme = ?";
                    //Kreiranje prepared statementa
                    $stmtUpdate = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmtUpdate,$sqlUpdate)){
                        $response["success"] = "false";
                        $response["message"] = "Došlo je do pogreške!";
                        $response["idRecept"] = null;
                    }
                    //Ako je prepared statement u redu
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmtUpdate,"isssisss",$idRecept,$mkbSifraPrimarna,$mkb,$poslanaMKBSifra,
                                                                    $poslaniIDObrada,$mboPacijent,$poslaniTipSlucaj,$poslanoVrijeme);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmtUpdate);
                        //Vraćanje uspješnog odgovora serveru
                        $response["success"] = "true";
                        $response["message"] = "Recept uspješno dodan!";
                        $response["idRecept"] = $idRecept;
                    } 
                }
                //npr. (BAZA = 1, FORMA = 2, BAZA = 2, FORMA = 2)
                else if($brojSekundarnaBaza <= $brojacSekundarnaForma && $brojacSekundarnaForma > 1){
                    //Ako je broj sek. dijagnoza u bazi JEDNAK 0 te je prva iteracija (tj. prva dijagnoza forme)
                    if($brojSekundarnaBaza == 0 && $brojacIteracija == 1){
                        //Kreiram upit kojim ću unijeti ID recepta u tablicu "povijest_bolesti"
                        $sqlUpdate ="UPDATE povijest_bolesti pb SET pb.idRecept = ?,pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ? 
                                    WHERE TRIM(pb.mkbSifraPrimarna) = ? 
                                    AND pb.idObradaLijecnik = ? 
                                    AND pb.mboPacijent = ? 
                                    AND pb.tipSlucaj = ? 
                                    AND pb.vrijeme = ?";
                        //Kreiranje prepared statementa
                        $stmtUpdate = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmtUpdate,$sqlUpdate)){
                            $response["success"] = "false";
                            $response["message"] = "Došlo je do pogreške!";
                            $response["idRecept"] = null;
                        }
                        //Ako je prepared statement u redu
                        else{
                            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                            mysqli_stmt_bind_param($stmtUpdate,"isssisss",$idRecept,$mkbSifraPrimarna,$mkb,$poslanaMKBSifra,
                                                                        $poslaniIDObrada,$mboPacijent,$poslaniTipSlucaj,$poslanoVrijeme);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmtUpdate);
                            //Vraćanje uspješnog odgovora serveru
                            $response["success"] = "true";
                            $response["message"] = "Recept uspješno dodan!";
                            $response["idRecept"] = $pomIdRecept;
                        } 
                    }
                    //Ako je broj sek. dijagnoza u BAZI JENDAK 0 te je n-ta iteracija (tj. n-ta dijagnoza forme)
                    else if($brojSekundarnaBaza == 0 && $brojacIteracija > 1){
                        //Prije nego što ubacim novi redak povijesti bolesti, dohvaćam redak koji sam ažurirao u prethodnom if uvjetu 
                        $sqlPovijestBolesti = "SELECT * FROM povijest_bolesti pb
                                            WHERE pb.idObradaLijecnik = '$poslaniIDObrada' 
                                            AND pb.mboPacijent = '$mboPacijent' 
                                            AND TRIM(pb.mkbSifraPrimarna) = '$poslanaMKBSifra' 
                                            AND pb.tipSlucaj = '$poslaniTipSlucaj' 
                                            AND pb.vrijeme = '$poslanoVrijeme'
                                            AND pb.idPovijestBolesti = 
                                            (SELECT MAX(pb2.idPovijestBolesti) FROM povijest_bolesti pb2 
                                            WHERE pb2.mboPacijent = '$mboPacijent' 
                                            AND pb2.idObradaLijecnik = '$poslaniIDObrada' 
                                            AND TRIM(pb2.mkbSifraPrimarna) = '$poslanaMKBSifra' 
                                            AND pb2.tipSlucaj = '$poslaniTipSlucaj' 
                                            AND pb2.vrijeme = '$poslanoVrijeme')";
                        $resultPovijestBolesti = $conn->query($sqlPovijestBolesti);

                        if ($resultPovijestBolesti->num_rows > 0) {
                            while($rowPovijestBolesti = $resultPovijestBolesti->fetch_assoc()) {
                                //Dohvaćam određene vrijednosti povijesti bolesti
                                $razlogDolaska = $rowPovijestBolesti['razlogDolaska'];
                                $anamneza = $rowPovijestBolesti['anamneza'];
                                $status = $rowPovijestBolesti['statusPacijent'];
                                $nalaz = $rowPovijestBolesti['nalaz'];
                                $tipSlucaj = $rowPovijestBolesti['tipSlucaj'];
                                $terapija = $rowPovijestBolesti['terapija'];
                                $preporukaLijecnik = $rowPovijestBolesti['preporukaLijecnik'];
                                $napomena = $rowPovijestBolesti['napomena'];
                                $datumPovijestiBolesti = $rowPovijestBolesti['datum'];
                                $narucen = $rowPovijestBolesti['narucen'];
                                $mboPacijent = $rowPovijestBolesti['mboPacijent'];
                                $vrijemePovijestiBolesti = $rowPovijestBolesti['vrijeme'];
                                $prosliPregled = $rowPovijestBolesti['prosliPregled'];
                                $bojaPregled = $rowPovijestBolesti['bojaPregled'];
                                $oznakaPov = $rowPovijestBolesti['oznaka'];
                            }
                        } 
                        //Kreiram upit za spremanje prvog dijela podataka u bazu
                        $sql = "INSERT INTO povijest_bolesti (razlogDolaska, anamneza, statusPacijent, 
                                nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                preporukaLijecnik, napomena, datum, narucen, mboPacijent,idObradaLijecnik, 
                                vrijeme,idRecept,prosliPregled,bojaPregled,oznaka) 
                                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                        //Kreiranje prepared statementa
                        $stmt = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmt,$sql)){
                            $response["success"] = "false";
                            $response["message"] = "Došlo je do pogreške!";
                            $response["idRecept"] = null;
                        }
                        //Ako je prepared statement u redu
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
                            mysqli_stmt_bind_param($stmt,"sssssssssssssisiiss",$razlogDolaska,$anamneza,$status,$nalaz,$mkbSifraPrimarna,$mkb,
                                                                        $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datumPovijestiBolesti, 
                                                                        $narucen,$mboPacijent,$poslaniIDObrada,$vrijemePovijestiBolesti,$idRecept, 
                                                                        $prosliPregled,$bojaPregled,$oznakaPov);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmt);

                            //Dohvaćam ID povijesti bolesti kojega sam upravo unio
                            $resultPovijestBolesti = mysqli_query($conn,"SELECT MAX(pb.idPovijestBolesti) AS ID FROM povijest_bolesti pb");
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
                                $response["message"] = "Došlo je do pogreške!";
                                $response["idRecept"] = null;
                            }
                            //Ako je prepared statement u redu
                            else{
                                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                mysqli_stmt_bind_param($stmtAmbulanta,"iii",$idLijecnik,$idPacijent,$idPovijestBolesti);
                                //Izvršavanje statementa
                                mysqli_stmt_execute($stmtAmbulanta);

                                $response["success"] = "true";
                                $response["message"] = "Recept uspješno dodan!";
                                $response["idRecept"] = $pomIdRecept;
                            }
                        }
                    }
                    //Ako je broj obrađenih redaka manji od broja dijagnoza u bazi te je prva iteracija (koristim PRVI MINIMALNI ID povijesti bolesti)
                    if($brojacAzuriranihRedaka < $brojSekundarnaBaza && $brojacIteracija == 1){
                        //Kreiram upit kojim ću unijeti ID recepta u tablicu "povijest_bolesti"
                        $sqlUpdate ="UPDATE povijest_bolesti pb SET pb.idRecept = ?,pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ? 
                                    WHERE idPovijestBolesti = ?";
                        //Kreiranje prepared statementa
                        $stmtUpdate = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmtUpdate,$sqlUpdate)){
                            $response["success"] = "false";
                            $response["message"] = "Došlo je do pogreške!";
                            $response["idRecept"] = null;
                        }
                        //Ako je prepared statement u redu
                        else{
                            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                            mysqli_stmt_bind_param($stmtUpdate,"issi",$idRecept,$mkbSifraPrimarna,$mkb,$idMinPovijestBolesti);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmtUpdate);
                            //Povećavam broj ažuriranih redaka
                            $brojacAzuriranihRedaka++;
                        }
                    }
                    //Ako je broj obrađenih redaka manji od broja dijagnoza u bazi te NIJE prva iteracija
                    else if($brojacAzuriranihRedaka < $brojSekundarnaBaza && $brojacIteracija > 1){
                        
                        //Kreiram upit koji dohvaća SLJEDEĆI MINIMALNI ID povijesti bolesti za ovog pacijenta za ovu sesiju obrade
                        $sqlSljedeciMin = "SELECT pb.idPovijestBolesti FROM povijest_bolesti pb 
                                        WHERE TRIM(pb.mkbSifraPrimarna) = '$poslanaMKBSifra' 
                                        AND pb.mboPacijent = '$mboPacijent' 
                                        AND pb.idObradaLijecnik = '$poslaniIDObrada' 
                                        AND pb.tipSlucaj = '$poslaniTipSlucaj' 
                                        AND pb.vrijeme = '$poslanoVrijeme'
                                        AND pb.idPovijestBolesti > '$idMinPovijestBolesti' 
                                        LIMIT 1";
                        $resultSljedeciMin = $conn->query($sqlSljedeciMin);
                                
                        //Ako pacijent IMA evidentiranih povijesti bolesti
                        if ($resultSljedeciMin->num_rows > 0) {
                            while($rowSljedeciMin = $resultSljedeciMin->fetch_assoc()) {
                                //Dohvaćam povijesti bolesti sa SLJEDEĆIM MINIMALNIM ID-om
                                $idMinPovijestBolesti = $rowSljedeciMin['idPovijestBolesti'];
                            }
                        }
                        //Kreiram upit kojim ću unijeti ID recepta u tablicu "povijest_bolesti"
                        $sqlUpdate ="UPDATE povijest_bolesti pb SET pb.idRecept = ?,pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ? 
                                    WHERE pb.idPovijestBolesti = ?";
                        //Kreiranje prepared statementa
                        $stmtUpdate = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmtUpdate,$sqlUpdate)){
                            $response["success"] = "false";
                            $response["message"] = "Došlo je do pogreške!";
                            $response["idRecept"] = null;
                        }
                        //Ako je prepared statement u redu
                        else{
                            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                            mysqli_stmt_bind_param($stmtUpdate,"issi",$idRecept,$mkbSifraPrimarna,$mkb,$idMinPovijestBolesti);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmtUpdate);
                            //Povećavam broj ažuriranih redaka
                            $brojacAzuriranihRedaka++;
                            //Vraćanje uspješnog odgovora serveru
                            $response["success"] = "true";
                            $response["message"] = "Recept uspješno dodan!";
                            $response["idRecept"] = $pomIdRecept;
                        }
                    }
                    //Ako je broj ažuriranih redak JEDNAK broju sek. dijagnoza u bazi (npr. 2 == 2) I brojač iteracija JE VEĆI od broja sek. dijagnoza u bazi (npr. 3 > 2) 
                    //te da je broj sek. dijagnoza u BAZI VEĆI OD 0
                    if($brojacAzuriranihRedaka == $brojSekundarnaBaza && $brojacIteracija > $brojSekundarnaBaza && $brojSekundarnaBaza > 0){
                        //Prije nego što ubacim novi redak povijesti bolesti, dohvaćam redak koji sam ažurirao u prethodnom if uvjetu 
                        $sqlPovijestBolesti = "SELECT * FROM povijest_bolesti pb
                                            WHERE pb.idObradaLijecnik = '$poslaniIDObrada' 
                                            AND pb.mboPacijent = '$mboPacijent' 
                                            AND TRIM(pb.mkbSifraPrimarna) = '$poslanaMKBSifra' 
                                            AND pb.tipSlucaj = '$poslaniTipSlucaj' 
                                            AND pb.vrijeme = '$poslanoVrijeme'
                                            AND pb.idPovijestBolesti = 
                                            (SELECT MAX(pb2.idPovijestBolesti) FROM povijest_bolesti pb2 
                                            WHERE pb2.mboPacijent = '$mboPacijent' 
                                            AND pb2.idObradaLijecnik = '$poslaniIDObrada' 
                                            AND TRIM(pb2.mkbSifraPrimarna) = '$poslanaMKBSifra' 
                                            AND pb2.tipSlucaj = '$poslaniTipSlucaj' 
                                            AND pb2.vrijeme = '$poslanoVrijeme')";
                        $resultPovijestBolesti = $conn->query($sqlPovijestBolesti);

                        if ($resultPovijestBolesti->num_rows > 0) {
                            while($rowPovijestBolesti = $resultPovijestBolesti->fetch_assoc()) {
                                //Dohvaćam određene vrijednosti povijesti bolesti
                                $razlogDolaska = $rowPovijestBolesti['razlogDolaska'];
                                $anamneza = $rowPovijestBolesti['anamneza'];
                                $status = $rowPovijestBolesti['statusPacijent'];
                                $nalaz = $rowPovijestBolesti['nalaz'];
                                $tipSlucaj = $rowPovijestBolesti['tipSlucaj'];
                                $terapija = $rowPovijestBolesti['terapija'];
                                $preporukaLijecnik = $rowPovijestBolesti['preporukaLijecnik'];
                                $napomena = $rowPovijestBolesti['napomena'];
                                $datumPovijestiBolesti = $rowPovijestBolesti['datum'];
                                $narucen = $rowPovijestBolesti['narucen'];
                                $mboPacijent = $rowPovijestBolesti['mboPacijent'];
                                $vrijemePovijestiBolesti = $rowPovijestBolesti['vrijeme'];
                                $prosliPregled = $rowPovijestBolesti['prosliPregled'];
                                $bojaPregled = $rowPovijestBolesti['bojaPregled'];
                                $oznakaPov = $rowPovijestBolesti['oznaka'];
                            }
                        } 
                        //Kreiram upit za spremanje prvog dijela podataka u bazu
                        $sql = "INSERT INTO povijest_bolesti (razlogDolaska, anamneza, statusPacijent, 
                                nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                preporukaLijecnik, napomena, datum, narucen, mboPacijent,idObradaLijecnik, 
                                vrijeme,idRecept,prosliPregled,bojaPregled,oznaka) 
                                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                        //Kreiranje prepared statementa
                        $stmt = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmt,$sql)){
                            $response["success"] = "false";
                            $response["message"] = "Došlo je do pogreške!";
                            $response["idRecept"] = null;
                        }
                        //Ako je prepared statement u redu
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
                            mysqli_stmt_bind_param($stmt,"sssssssssssssisiiss",$razlogDolaska,$anamneza,$status,$nalaz,$mkbSifraPrimarna,$mkb,
                                                                        $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,
                                                                        $datumPovijestiBolesti,$narucen,$mboPacijent,$poslaniIDObrada,
                                                                        $vrijemePovijestiBolesti,$idRecept,$prosliPregled,$bojaPregled,$oznakaPov);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmt);

                            //Dohvaćam ID povijesti bolesti kojega sam upravo unio
                            $resultPovijestBolesti = mysqli_query($conn,"SELECT MAX(pb.idPovijestBolesti) AS ID FROM povijest_bolesti pb");
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
                                $response["message"] = "Došlo je do pogreške!";
                                $response["idRecept"] = null;
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
                                $response["idRecept"] = $pomIdRecept;
                            }
                        }
                    }
                }
                /**************************************** */
                //Ako su retci izbrisani, treba nadodati nove dijagnoze iz forme
                else if($brisanje == true){
                    //Kreiram upit za spremanje prvog dijela podataka u bazu
                    $sql = "INSERT INTO povijest_bolesti (razlogDolaska, anamneza, statusPacijent, 
                            nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                            preporukaLijecnik, napomena, datum, narucen, mboPacijent,idObradaLijecnik, 
                            vrijeme,idRecept,prosliPregled,bojaPregled,oznaka) 
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                    //Kreiranje prepared statementa
                    $stmt = mysqli_stmt_init($conn);
                    //Ako je statement neuspješan
                    if(!mysqli_stmt_prepare($stmt,$sql)){
                        $response["success"] = "false";
                        $response["message"] = "Došlo je do pogreške!";
                        $response["idRecept"] = null;
                    }
                    //Ako je prepared statement u redu
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
                        mysqli_stmt_bind_param($stmt,"sssssssssssssisiiss",$razlogDolaska,$anamneza,$status,$nalaz,$mkbSifraPrimarna,$mkb,
                                                                    $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,
                                                                    $datumPovijestiBolesti,$narucen,$mboPacijent,$poslaniIDObrada,
                                                                    $vrijemePovijestiBolesti,$idRecept,$prosliPregled,$bojaPregled,$oznakaPov);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmt);

                        //Dohvaćam ID povijesti bolesti kojega sam upravo unio
                        $resultPovijestBolesti = mysqli_query($conn,"SELECT MAX(pb.idPovijestBolesti) AS ID FROM povijest_bolesti pb");
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
                            $response["message"] = "Došlo je do pogreške!";
                            $response["idRecept"] = null;
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
                            $response["idRecept"] = $pomIdRecept;
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