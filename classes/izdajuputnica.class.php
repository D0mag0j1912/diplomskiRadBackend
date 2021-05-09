<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');
class IzdajUputnica {

    //Funkcija koja dohvaća zadnje postavljene dijagnoze u povijesti bolesti
    function dohvatiInicijalneDijagnoze($idObrada,$mboPacijent){
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        //Kreiram upit koji dohvaća sporedne podatke povijest bolesti ZADNJEG RETKA (jer ako ovo ne napravim, vraćati će mi samo zadnju sek. dijagnozu)
        $sqlZadnjiRedak = "SELECT * FROM povijestBolesti pb
                        WHERE pb.idUputnica IS NULL 
                        AND pb.mboPacijent = '$mboPacijent' 
                        AND pb.idObradaLijecnik = '$idObrada'
                        AND pb.idPovijestBolesti = 
                        (SELECT MAX(pb2.idPovijestBolesti) FROM povijestbolesti pb2 
                        WHERE pb2.idUputnica IS NULL 
                        AND pb2.mboPacijent = '$mboPacijent' 
                        AND pb2.idObradaLijecnik = '$idObrada')";
        $resultZadnjiRedak = $conn->query($sqlZadnjiRedak);
        //Ako ima rezultata
        if($resultZadnjiRedak->num_rows > 0){
            while($rowZadnjiRedak = $resultZadnjiRedak->fetch_assoc()){
                $mkbSifraPrimarna = $rowZadnjiRedak['mkbSifraPrimarna'];
                $tipSlucaj = $rowZadnjiRedak['tipSlucaj'];
                $datum = $rowZadnjiRedak['datum'];
                $vrijeme = $rowZadnjiRedak['vrijeme'];
                $idObradaLijecnik = $rowZadnjiRedak['idObradaLijecnik'];
            }
        }
        //Ako pacijent nema još zapisanu povijest bolesti u ovoj sesiji obrade
        else{
            return null;
        }

        //Dohvaćam primarnu i sve sekundarne dijagnoze 
        $sql = "SELECT DISTINCT(TRIM(d.imeDijagnoza)) AS NazivPrimarna, 
                IF(pb.mkbSifraSekundarna = NULL, NULL, (SELECT TRIM(d2.imeDijagnoza) FROM dijagnoze d2 WHERE d2.mkbSifra = pb.mkbSifraSekundarna)) AS NazivSekundarna 
                ,pb.idObradaLijecnik,pb.tipSlucaj,pb.vrijeme,pb.datum FROM povijestBolesti pb 
                JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna
                WHERE TRIM(pb.mkbSifraPrimarna) = '$mkbSifraPrimarna' 
                AND pb.tipSlucaj = '$tipSlucaj' 
                AND pb.datum = '$datum' 
                AND pb.vrijeme = '$vrijeme' 
                AND pb.idObradaLijecnik = '$idObradaLijecnik'";
        $result = $conn->query($sql);
        //Ako ima rezultata
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $response[] = $row;
            }
        }
        return $response;
    }
    //Funkcija koja provjerava je li unesena povijest bolesti za ovu sesiju obrade
    function isUnesenaPovijestBolesti($idObrada, $mboPacijent){
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        $sql = "SELECT COUNT(pb.idPovijestBolesti) AS BrojPovijestBolesti FROM povijestBolesti pb 
                WHERE pb.idObradaLijecnik = '$idObrada' 
                AND pb.mboPacijent = '$mboPacijent' 
                AND pb.idUputnica IS NULL";
        //Rezultat upita spremam u varijablu $result
        $result = mysqli_query($conn,$sql);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($result) > 0){
            //Idem redak po redak rezultata upita 
            while($row = mysqli_fetch_assoc($result)){
                //Vrijednost rezultata spremam u varijablu $brojPovijestBolesti
                $brojPovijestBolesti = $row['BrojPovijestBolesti'];
            }
        }
        return $brojPovijestBolesti; 
    }

    //Funkcija koja dodava uputnicu
    function dodajUputnicu($idZdrUst, $sifDjel, $idPacijent, $mboPacijent, $sifraSpecijalist, 
                        $mkbSifraPrimarna, $mkbSifraSekundarna, $vrstaPregled,
                        $molimTraziSe, $napomenaUputnica, $idLijecnik, $poslanaMKBSifra,
                        $poslaniIDObrada, $poslaniTipSlucaj, $poslanoVrijeme){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];
        //Trenutni datum
        $datum = date('Y-m-d');
        //Trenutno vrijeme
        $vrijeme = date('H:i:s');
        //Spremam zdr. ustanove 
        $zdrUstanove = [19601964,25002503,21902194,47104716,19701977,47204729,49604961,356235629,359135919,999000420,358435846,389738972,258825880];
        //Označavam da slučajno generirana oznaka već postoji u bazi
        $ispravan = false;
        while($ispravan != true){
            //Generiram slučajni oznaku po kojom grupiram
            $oznaka = uniqid();
            //Kreiram upit koji provjerava postoji li već ova random generirana oznaka u bazi
            $sqlProvjeraOznaka = "SELECT u.oznaka FROM uputnica u 
                                WHERE u.oznaka = '$oznaka';";
            //Rezultat upita spremam u varijablu $resultProvjeraOznaka
            $resultProvjeraOznaka = mysqli_query($conn,$sqlProvjeraOznaka);
            //Ako se novo generirana oznaka NE NALAZI u bazi
            if(mysqli_num_rows($resultProvjeraOznaka) == 0){
                //Izlazim iz petlje
                $ispravan = true;
            } 
        }
        //Generiranje slučajne oznake za tablicu "nalaz"
        $ispravanNalaz = false;
        while($ispravanNalaz != true){
            //Generiram slučajni oznaku po kojom grupiram
            $oznakaNalaz = uniqid();
            //Kreiram upit koji provjerava postoji li već ova random generirana oznaka u bazi
            $sqlProvjeraOznakaNalaz = "SELECT n.oznaka FROM nalaz n 
                                    WHERE n.oznaka = '$oznakaNalaz';";
            //Rezultat upita spremam u varijablu $resultProvjeraOznaka
            $resultProvjeraOznakaNalaz = mysqli_query($conn,$sqlProvjeraOznakaNalaz);
            //Ako se novo generirana oznaka NE NALAZI u bazi
            if(mysqli_num_rows($resultProvjeraOznakaNalaz) == 0){
                //Izlazim iz petlje
                $ispravanNalaz = true;
            } 
        }

        //Gledam koliko ima sek. dijagnoza pregled u bazi gdje se dodava ID uputnice
        $sqlCountSekundarna = "SELECT COUNT(pb.mkbSifraSekundarna) AS BrojSekundarna FROM povijestBolesti pb
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
            //Kreiram upit za dodavanje nove uputnice u bazu
            $sql = "INSERT INTO uputnica (idZdrUst,sifDjel,idPacijent,sifraSpecijalist, 
                                        mkbSifraPrimarna,mkbSifraSekundarna,vrstaPregleda, 
                                        molimTraziSe,napomena,datum,vrijeme,oznaka) VALUES 
                                        (?,?,?,?,?,?,?,?,?,?,?,?)";
            //Kreiranje prepared statementa
            $stmt = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmt,$sql)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement ne valja!";
                $response["idUputnica"] = null;
            }
            //Ako je prepared statement u redu
            else{
                //Postavljam MKB šifru sekundarne dijagnoze na NULL
                $prazna = NULL;
                //Ako je prazna šifra specijalista koji je preporučio uputnicu
                if(empty($sifraSpecijalist)){
                    $sifraSpecijalist = NULL;
                }
                if(empty($napomenaUputnica)){
                    $napomenaUputnica = NULL;
                }
                if(empty($idZdrUst)){
                    $idZdrUst = NULL;
                }
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmt,"iiiissssssss", $idZdrUst, $sifDjel, $idPacijent, $sifraSpecijalist, 
                                                            $mkbSifraPrimarna, $prazna, $vrstaPregled, $molimTraziSe, 
                                                            $napomenaUputnica, $datum, $vrijeme, $oznaka);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmt);

                //Dohvaćam ZADNJE UNESENI ID uputnice
                $resultUputnica = mysqli_query($conn,"SELECT MAX(u.idUputnica) AS ID FROM uputnica u");
                //Ulazim u polje rezultata i idem redak po redak
                while($rowUputnica = mysqli_fetch_array($resultUputnica)){
                    //Dohvaćam željeni ID uputnice
                    $idUputnica = $rowUputnica['ID'];
                } 

                //Ako je broj trenutnih sek. dijagnoza u bazi povijesti bolesti 0 ILI 1
                if(($brojSekundarnaBaza == 0 || $brojSekundarnaBaza == 1)){
                    //Kreiram upit kojim ću unijeti ID uputnice u tablicu "povijestBolesti"
                    $sqlUpdate ="UPDATE povijestBolesti pb SET pb.idUputnica = ?, 
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
                        $response["message"] = "Prepared statement ne valja!";
                        $response["idUputnica"] = null;
                    }
                    //Ako je prepared statement u redu
                    else{
                        $prazna = NULL;
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmtUpdate,"isssisss",$idUputnica,$mkbSifraPrimarna,$prazna,$poslanaMKBSifra, 
                                                                $poslaniIDObrada,$mboPacijent,$poslanoVrijeme,$poslaniTipSlucaj);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmtUpdate);

                        //Spremam nalaz 
                        $sqlNalaz = "INSERT INTO nalaz (idUputnica, idPacijent, idSpecijalist, idZdrUst, sifDjel, 
                                                        mkbSifraPrimarna, mkbSifraSekundarna, misljenjeSpecijalist, 
                                                        komentarUzNalaz,datumNalaz, vrijemeNalaz, oznaka) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
                        //Kreiranje prepared statementa
                        $stmtNalaz = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmtNalaz,$sqlNalaz)){
                            $response["success"] = "false";
                            $response["message"] = "Prepared statement ne valja!";
                            $response["idUputnica"] = null;
                        }
                        else{
                            //MKB šifru sekundarne postavljam na NULL
                            $prazna = NULL;
                            //Generiram slučajni ID specijalista
                            $idSpecijalist = mt_rand(1,5);
                            //Ako je ustanova prazna
                            if(empty($idZdrUst)){
                                $idZdrUst = $zdrUstanove[array_rand($zdrUstanove,1)];
                            }
                            //Ako je vrsta pregleda == 'Dijagnostička pretraga'
                            if($vrstaPregled == 'Dijagnostička pretraga'){
                                //Stavljam dummy text
                                $komentarUzNalaz = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
                                $misljenjeSpecijalist = NULL;
                            }
                            else{
                                //Stavljam dummy text
                                $misljenjeSpecijalist = "Lorem Ipsum is simply dummy text of the printing and typesetting industry.";
                                $komentarUzNalaz = NULL;
                            }   
                            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                            mysqli_stmt_bind_param($stmtNalaz,"iiiiisssssss",$idUputnica, $idPacijent, $idSpecijalist,
                                                                        $idZdrUst, $sifDjel, $mkbSifraPrimarna, $prazna, 
                                                                        $misljenjeSpecijalist, $komentarUzNalaz, $datum, $vrijeme, $oznakaNalaz);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmtNalaz);
                            //Vraćanje uspješnog odgovora serveru
                            $response["success"] = "true";
                            $response["message"] = "Uputnica uspješno dodana!";
                            $response["idUputnica"] = $idUputnica;
                        }
                    } 
                }
                //Ako je broj sek. dijagnoza u bazi povijesti bolesti VEĆI OD 1 (npr. POVIJEST BOLESTI = A00-A01,A00-A02 UPUTNICA = A00-NULL)
                else if($brojSekundarnaBaza > 1){
                    //Brišem sve retke iz tablice ambulanta za ovu povijest bolesti
                    $sqlDeleteAmbulanta = "DELETE a FROM ambulanta a
                                        JOIN povijestbolesti pb ON pb.idPovijestBolesti = a.idPovijestBolesti 
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
                        $response["message"] = "Prepared statement ne valja!";
                        $response["idUputnica"] = null;
                    }
                    else{
                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                        mysqli_stmt_bind_param($stmtDeleteAmbulanta,"sisss",$poslanaMKBSifra,$poslaniIDObrada,$mboPacijent,$poslanoVrijeme,$poslaniTipSlucaj);
                        //Izvršavanje statementa
                        mysqli_stmt_execute($stmtDeleteAmbulanta);

                        //Prije nego što izbrišem redak povijesti bolesti, dohvaćam ga
                        $sqlPovijestBolesti = "SELECT * FROM povijestbolesti 
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
                        $sqlDelete = "DELETE FROM povijestBolesti 
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
                            $response["message"] = "Prepared statement ne valja!";
                            $response["idUputnica"] = null;
                        }
                        else{
                            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                            mysqli_stmt_bind_param($stmtDelete,"sisss",$poslanaMKBSifra,$poslaniIDObrada,$mboPacijent,$poslanoVrijeme,$poslaniTipSlucaj);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmtDelete);  
                            //Kreiram upit za dodavanje nove uputnice u bazu
                            $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                                    nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                    preporukaLijecnik, napomena, datum, narucen, mboPacijent,idObradaLijecnik, 
                                    vrijeme,idUputnica,prosliPregled, bojaPregled,oznaka) 
                                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                            //Kreiranje prepared statementa
                            $stmt = mysqli_stmt_init($conn);
                            //Ako je statement neuspješan
                            if(!mysqli_stmt_prepare($stmt,$sql)){
                                $response["success"] = "false";
                                $response["message"] = "Prepared statement ne valja!";
                                $response["idUputnica"] = null;
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
                                                                $narucen,$mboPacijent,$poslaniIDObrada,$vrijemePovijestiBolesti,$idUputnica, 
                                                                $prosliPregled,$bojaPregled,$oznakaPov);
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
                                    $response["idUputnica"] = null;
                                }
                                //Ako je prepared statement u redu
                                else{
                                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                    mysqli_stmt_bind_param($stmtAmbulanta,"iii",$idLijecnik,$idPacijent,$idPovijestBolesti);
                                    //Izvršavanje statementa
                                    mysqli_stmt_execute($stmtAmbulanta);
                                    
                                    //Spremam nalaz 
                                    $sqlNalaz = "INSERT INTO nalaz (idUputnica, idPacijent, idSpecijalist, idZdrUst, sifDjel, 
                                                                    mkbSifraPrimarna, mkbSifraSekundarna, misljenjeSpecijalist, 
                                                                    komentarUzNalaz ,datumNalaz, vrijemeNalaz, oznaka) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
                                    //Kreiranje prepared statementa
                                    $stmtNalaz = mysqli_stmt_init($conn);
                                    //Ako je statement neuspješan
                                    if(!mysqli_stmt_prepare($stmtNalaz,$sqlNalaz)){
                                        $response["success"] = "false";
                                        $response["message"] = "Prepared statement ne valja!";
                                        $response["idUputnica"] = null;
                                    }
                                    else{
                                        //MKB šifru sekundarne postavljam na NULL
                                        $prazna = NULL;
                                        //Generiram slučajni ID specijalista
                                        $idSpecijalist = mt_rand(1,5);
                                        //Ako je ustanova prazna
                                        if(empty($idZdrUst)){
                                            $idZdrUst = $zdrUstanove[array_rand($zdrUstanove,1)];
                                        }
                                        //Ako je vrsta pregleda == 'Dijagnostička pretraga'
                                        if($vrstaPregled == 'Dijagnostička pretraga'){
                                            //Stavljam dummy text
                                            $komentarUzNalaz = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
                                            $misljenjeSpecijalist = NULL;
                                        }
                                        else{
                                            //Stavljam dummy text
                                            $misljenjeSpecijalist = "Lorem Ipsum is simply dummy text of the printing and typesetting industry.";
                                            $komentarUzNalaz = NULL;
                                        }  
                                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                        mysqli_stmt_bind_param($stmtNalaz,"iiiiisssssss",$idUputnica, $idPacijent, $idSpecijalist,
                                                                                    $idZdrUst, $sifDjel, $mkbSifraPrimarna, $prazna, 
                                                                                    $misljenjeSpecijalist, $komentarUzNalaz, $datum, $vrijeme, $oznakaNalaz);
                                        //Izvršavanje statementa
                                        mysqli_stmt_execute($stmtNalaz);
                                        //Vraćanje uspješnog odgovora serveru
                                        $response["success"] = "true";
                                        $response["message"] = "Uputnica uspješno dodana!";
                                        $response["idUputnica"] = $idUputnica;
                                    }
                                }
                            } 
                        }
                    }
                }
            }
        }
        //Ako ima sekundarnih dijagnoza
        else{
            //Kreiram upit koji dohvaća MINIMALNI ID povijesti bolesti za određenog pacijenta i određenu sesiju obrade
            $sqlMin = "SELECT pb.idPovijestBolesti FROM povijestbolesti pb 
                    WHERE TRIM(pb.mkbSifraPrimarna) = '$poslanaMKBSifra' 
                    AND pb.idObradaLijecnik = '$poslaniIDObrada' 
                    AND pb.mboPacijent = '$mboPacijent' 
                    AND pb.tipSlucaj = '$poslaniTipSlucaj' 
                    AND pb.vrijeme = '$poslanoVrijeme'
                    AND pb.idPovijestBolesti = 
                    (SELECT MIN(pb2.idPovijestBolesti) FROM povijestbolesti pb2  
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
                                    JOIN povijestbolesti pb ON pb.idPovijestBolesti = a.idPovijestBolesti 
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
                    $response["message"] = "Prepared statement ne valja!";
                    $response["idUputnica"] = null;
                }
                else{
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmtDeleteAmbulanta,"sisss",$poslanaMKBSifra,$poslaniIDObrada,$mboPacijent,$poslaniTipSlucaj,$poslanoVrijeme);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmtDeleteAmbulanta);
                    //Prije nego što ubacim novi redak povijesti bolesti, dohvaćam redak koji sam ažurirao u prethodnom if uvjetu 
                    $sqlPovijestBolesti = "SELECT * FROM povijestbolesti 
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
                    $sqlDelete = "DELETE FROM povijestBolesti 
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
                        $response["message"] = "Prepared statement ne valja!";
                        $response["idUputnica"] = null;
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
                //Kreiram upit za dodavanje nove uputnice u bazu
                $sql = "INSERT INTO uputnica (idZdrUst,sifDjel,idPacijent,sifraSpecijalist, 
                                            mkbSifraPrimarna,mkbSifraSekundarna,vrstaPregleda, 
                                            molimTraziSe,napomena,datum,vrijeme, oznaka) VALUES 
                                            (?,?,?,?,?,?,?,?,?,?,?,?)";
                //Kreiranje prepared statementa
                $stmt = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmt,$sql)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement ne valja!";
                    $response["idUputnica"] = null;
                }
                //Ako je prepared statement u redu
                else{
                    if(empty($sifraSpecijalist)){
                        $sifraSpecijalist = NULL;
                    }
                    if(empty($napomenaUputnica)){
                        $napomenaUputnica = NULL;
                    }
                    if(empty($idZdrUst)){
                        $idZdrUst = NULL;
                    }
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmt,"iiiissssssss", $idZdrUst, $sifDjel, $idPacijent, $sifraSpecijalist, 
                                                                $mkbSifraPrimarna, $mkb, $vrstaPregled, $molimTraziSe, 
                                                                $napomenaUputnica, $datum, $vrijeme, $oznaka);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmt);
    
                    //Dohvaćam ZADNJE UNESENI ID uputnice
                    $resultUputnica = mysqli_query($conn,"SELECT MAX(u.idUputnica) AS ID FROM uputnica u");
                    //Ulazim u polje rezultata i idem redak po redak
                    while($rowUputnica = mysqli_fetch_array($resultUputnica)){
                        //Dohvaćam željeni ID uputnice
                        $idUputnica = $rowUputnica['ID'];
                        //Ako se gleda prva sekundarna dijagnoza
                        if($brojacIteracija == 1){
                            $prviIdUputnica = $idUputnica;
                        }
                    } 
                    //(BAZA = 0, FORMA = 1) ILI (BAZA = 1, FORMA = 1)
                    if($brojSekundarnaBaza <= $brojacSekundarnaForma && $brojacSekundarnaForma == 1){
                        //Kreiram upit kojim ću unijeti ID uputnice u tablicu "povijestBolesti"
                        $sqlUpdate ="UPDATE povijestBolesti pb SET pb.idUputnica = ?,pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ? 
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
                            $response["message"] = "Prepared statement ne valja!";
                            $response["idUputnica"] = null;
                        }
                        //Ako je prepared statement u redu
                        else{
                            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                            mysqli_stmt_bind_param($stmtUpdate,"isssisss",$idUputnica,$mkbSifraPrimarna,$mkb,$poslanaMKBSifra,
                                                                        $poslaniIDObrada,$mboPacijent,$poslaniTipSlucaj,$poslanoVrijeme);
                            //Izvršavanje statementa
                            mysqli_stmt_execute($stmtUpdate);

                            //Spremam nalaz 
                            $sqlNalaz = "INSERT INTO nalaz (idUputnica, idPacijent, idSpecijalist, idZdrUst, sifDjel, 
                                                            mkbSifraPrimarna, mkbSifraSekundarna, misljenjeSpecijalist, 
                                                            komentarUzNalaz ,datumNalaz, vrijemeNalaz, oznaka) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
                            //Kreiranje prepared statementa
                            $stmtNalaz = mysqli_stmt_init($conn);
                            //Ako je statement neuspješan
                            if(!mysqli_stmt_prepare($stmtNalaz,$sqlNalaz)){
                                $response["success"] = "false";
                                $response["message"] = "Prepared statement ne valja!";
                                $response["idUputnica"] = null;
                            }
                            else{
                                //Generiram slučajni ID specijalista
                                $idSpecijalist = mt_rand(1,5);
                                //Ako je ustanova prazna
                                if(empty($idZdrUst)){
                                    $idZdrUst = $zdrUstanove[array_rand($zdrUstanove,1)];
                                }
                                //Ako je vrsta pregleda == 'Dijagnostička pretraga'
                                if($vrstaPregled == 'Dijagnostička pretraga'){
                                    //Stavljam dummy text
                                    $komentarUzNalaz = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
                                    $misljenjeSpecijalist = NULL;
                                }
                                else{
                                    //Stavljam dummy text
                                    $misljenjeSpecijalist = "Lorem Ipsum is simply dummy text of the printing and typesetting industry.";
                                    $komentarUzNalaz = NULL;
                                }  
                                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                mysqli_stmt_bind_param($stmtNalaz,"iiiiisssssss",$idUputnica, $idPacijent, $idSpecijalist,
                                                                            $idZdrUst, $sifDjel, $mkbSifraPrimarna, $mkb, 
                                                                            $misljenjeSpecijalist, $komentarUzNalaz, $datum, $vrijeme, $oznakaNalaz);
                                //Izvršavanje statementa
                                mysqli_stmt_execute($stmtNalaz);

                                //Vraćanje uspješnog odgovora serveru
                                $response["success"] = "true";
                                $response["message"] = "Uputnica uspješno dodana!";
                                $response["idUputnica"] = $idUputnica;
                            }
                        } 
                    }
                    //npr. (BAZA = 1, FORMA = 2, BAZA = 2, FORMA = 2)
                    else if($brojSekundarnaBaza <= $brojacSekundarnaForma && $brojacSekundarnaForma > 1){
                        //Ako je broj sek. dijagnoza u bazi JEDNAK 0 te je prva iteracija (tj. prva dijagnoza forme)
                        if($brojSekundarnaBaza == 0 && $brojacIteracija == 1){
                            //Kreiram upit kojim ću unijeti ID uputnice u tablicu "povijestBolesti"
                            $sqlUpdate ="UPDATE povijestBolesti pb SET pb.idUputnica = ?,pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ? 
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
                                $response["message"] = "Prepared statement ne valja!";
                                $response["idUputnica"] = null;
                            }
                            //Ako je prepared statement u redu
                            else{
                                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                mysqli_stmt_bind_param($stmtUpdate,"isssisss",$idUputnica,$mkbSifraPrimarna,$mkb,$poslanaMKBSifra,
                                                                            $poslaniIDObrada,$mboPacijent,$poslaniTipSlucaj,$poslanoVrijeme);
                                //Izvršavanje statementa
                                mysqli_stmt_execute($stmtUpdate);

                                //Spremam nalaz 
                                $sqlNalaz = "INSERT INTO nalaz (idUputnica, idPacijent, idSpecijalist, idZdrUst, sifDjel, 
                                                                mkbSifraPrimarna, mkbSifraSekundarna, misljenjeSpecijalist, 
                                                                komentarUzNalaz,datumNalaz, vrijemeNalaz, oznaka) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
                                //Kreiranje prepared statementa
                                $stmtNalaz = mysqli_stmt_init($conn);
                                //Ako je statement neuspješan
                                if(!mysqli_stmt_prepare($stmtNalaz,$sqlNalaz)){
                                    $response["success"] = "false";
                                    $response["message"] = "Prepared statement ne valja!";
                                    $response["idUputnica"] = null;
                                }
                                else{
                                    //Generiram slučajni ID specijalista
                                    $idSpecijalist = mt_rand(1,5);
                                    //Ako je ustanova prazna
                                    if(empty($idZdrUst)){
                                        $idZdrUst = $zdrUstanove[array_rand($zdrUstanove,1)];
                                    }
                                    //Ako je vrsta pregleda == 'Dijagnostička pretraga'
                                    if($vrstaPregled == 'Dijagnostička pretraga'){
                                        //Stavljam dummy text
                                        $komentarUzNalaz = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
                                        $misljenjeSpecijalist = NULL;
                                    }
                                    else{
                                        //Stavljam dummy text
                                        $misljenjeSpecijalist = "Lorem Ipsum is simply dummy text of the printing and typesetting industry.";
                                        $komentarUzNalaz = NULL;
                                    }  
                                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                    mysqli_stmt_bind_param($stmtNalaz,"iiiiisssssss",$idUputnica, $idPacijent, $idSpecijalist,
                                                                                $idZdrUst, $sifDjel, $mkbSifraPrimarna, $mkb, 
                                                                                $misljenjeSpecijalist, $komentarUzNalaz, $datum, $vrijeme, $oznakaNalaz);
                                    //Izvršavanje statementa
                                    mysqli_stmt_execute($stmtNalaz);

                                    //Vraćanje uspješnog odgovora serveru
                                    $response["success"] = "true";
                                    $response["message"] = "Uputnica uspješno dodana!";
                                    $response["idUputnica"] = $prviIdUputnica;
                                }
                            } 
                        }
                        //Ako je broj sek. dijagnoza u BAZI JENDAK 0 te je n-ta iteracija (tj. n-ta dijagnoza forme)
                        else if($brojSekundarnaBaza == 0 && $brojacIteracija > 1){
                            //Prije nego što ubacim novi redak povijesti bolesti, dohvaćam redak koji sam ažurirao u prethodnom if uvjetu 
                            $sqlPovijestBolesti = "SELECT * FROM povijestbolesti pb
                                                WHERE pb.idObradaLijecnik = '$poslaniIDObrada' 
                                                AND pb.mboPacijent = '$mboPacijent' 
                                                AND TRIM(pb.mkbSifraPrimarna) = '$poslanaMKBSifra' 
                                                AND pb.tipSlucaj = '$poslaniTipSlucaj' 
                                                AND pb.vrijeme = '$poslanoVrijeme'
                                                AND pb.idPovijestBolesti = 
                                                (SELECT MAX(pb2.idPovijestBolesti) FROM povijestbolesti pb2 
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
                            $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                                    nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                    preporukaLijecnik, napomena, datum, narucen, mboPacijent,idObradaLijecnik, 
                                    vrijeme,idUputnica,prosliPregled,bojaPregled,oznaka) 
                                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                            //Kreiranje prepared statementa
                            $stmt = mysqli_stmt_init($conn);
                            //Ako je statement neuspješan
                            if(!mysqli_stmt_prepare($stmt,$sql)){
                                $response["success"] = "false";
                                $response["message"] = "Prepared statement ne valja!";
                                $response["idUputnica"] = null;
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
                                                                            $narucen,$mboPacijent,$poslaniIDObrada,$vrijemePovijestiBolesti,$idUputnica, 
                                                                            $prosliPregled,$bojaPregled,$oznakaPov);
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
                                    $response["idUputnica"] = null;
                                }
                                //Ako je prepared statement u redu
                                else{
                                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                    mysqli_stmt_bind_param($stmtAmbulanta,"iii",$idLijecnik,$idPacijent,$idPovijestBolesti);
                                    //Izvršavanje statementa
                                    mysqli_stmt_execute($stmtAmbulanta);

                                    //Spremam nalaz 
                                    $sqlNalaz = "INSERT INTO nalaz (idUputnica, idPacijent, idSpecijalist, idZdrUst, sifDjel, 
                                                                    mkbSifraPrimarna, mkbSifraSekundarna, misljenjeSpecijalist, 
                                                                    komentarUzNalaz,datumNalaz, vrijemeNalaz, oznaka) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
                                    //Kreiranje prepared statementa
                                    $stmtNalaz = mysqli_stmt_init($conn);
                                    //Ako je statement neuspješan
                                    if(!mysqli_stmt_prepare($stmtNalaz,$sqlNalaz)){
                                        $response["success"] = "false";
                                        $response["message"] = "Prepared statement ne valja!";
                                        $response["idUputnica"] = null;
                                    }
                                    else{
                                        //Ako je vrsta pregleda == 'Dijagnostička pretraga'
                                        if($vrstaPregled == 'Dijagnostička pretraga'){
                                            //Stavljam dummy text
                                            $komentarUzNalaz = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
                                            $misljenjeSpecijalist = NULL;
                                        }
                                        else{
                                            //Stavljam dummy text
                                            $misljenjeSpecijalist = "Lorem Ipsum is simply dummy text of the printing and typesetting industry.";
                                            $komentarUzNalaz = NULL;
                                        }  
                                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                        mysqli_stmt_bind_param($stmtNalaz,"iiiiisssssss",$idUputnica, $idPacijent, $idSpecijalist,
                                                                                    $idZdrUst, $sifDjel, $mkbSifraPrimarna, $mkb, 
                                                                                    $misljenjeSpecijalist, $komentarUzNalaz, $datum, $vrijeme, $oznakaNalaz);
                                        //Izvršavanje statementa
                                        mysqli_stmt_execute($stmtNalaz);

                                        //Vraćanje uspješnog odgovora serveru
                                        $response["success"] = "true";
                                        $response["message"] = "Uputnica uspješno dodana!";
                                        $response["idUputnica"] = $prviIdUputnica;
                                    }
                                }
                            }
                        }
                        //Ako je broj obrađenih redaka manji od broja dijagnoza u bazi te je prva iteracija (koristim PRVI MINIMALNI ID povijesti bolesti)
                        if($brojacAzuriranihRedaka < $brojSekundarnaBaza && $brojacIteracija == 1){
                            //Kreiram upit kojim ću unijeti ID uputnice u tablicu "povijestBolesti"
                            $sqlUpdate ="UPDATE povijestBolesti pb SET pb.idUputnica = ?,pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ? 
                                        WHERE idPovijestBolesti = ?";
                            //Kreiranje prepared statementa
                            $stmtUpdate = mysqli_stmt_init($conn);
                            //Ako je statement neuspješan
                            if(!mysqli_stmt_prepare($stmtUpdate,$sqlUpdate)){
                                $response["success"] = "false";
                                $response["message"] = "Prepared statement ne valja!";
                                $response["idUputnica"] = null;
                            }
                            //Ako je prepared statement u redu
                            else{
                                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                mysqli_stmt_bind_param($stmtUpdate,"issi",$idUputnica,$mkbSifraPrimarna,$mkb,$idMinPovijestBolesti);
                                //Izvršavanje statementa
                                mysqli_stmt_execute($stmtUpdate);

                                //Spremam nalaz 
                                $sqlNalaz = "INSERT INTO nalaz (idUputnica, idPacijent, idSpecijalist, idZdrUst, sifDjel, 
                                                                mkbSifraPrimarna, mkbSifraSekundarna, misljenjeSpecijalist, 
                                                                komentarUzNalaz,datumNalaz, vrijemeNalaz, oznaka) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
                                //Kreiranje prepared statementa
                                $stmtNalaz = mysqli_stmt_init($conn);
                                //Ako je statement neuspješan
                                if(!mysqli_stmt_prepare($stmtNalaz,$sqlNalaz)){
                                    $response["success"] = "false";
                                    $response["message"] = "Prepared statement ne valja!";
                                    $response["idUputnica"] = null;
                                }
                                else{
                                    //Generiram slučajni ID specijalista
                                    $idSpecijalist = mt_rand(1,5);
                                    //Ako je ustanova prazna
                                    if(empty($idZdrUst)){
                                        $idZdrUst = $zdrUstanove[array_rand($zdrUstanove,1)];
                                    }
                                    //Ako je vrsta pregleda == 'Dijagnostička pretraga'
                                    if($vrstaPregled == 'Dijagnostička pretraga'){
                                        //Stavljam dummy text
                                        $komentarUzNalaz = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
                                        $misljenjeSpecijalist = NULL;
                                    }
                                    else{
                                        //Stavljam dummy text
                                        $misljenjeSpecijalist = "Lorem Ipsum is simply dummy text of the printing and typesetting industry.";
                                        $komentarUzNalaz = NULL;
                                    }  
                                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                    mysqli_stmt_bind_param($stmtNalaz,"iiiiisssssss",$idUputnica, $idPacijent, $idSpecijalist,
                                                                                $idZdrUst, $sifDjel, $mkbSifraPrimarna, $mkb, 
                                                                                $misljenjeSpecijalist, $komentarUzNalaz, $datum, $vrijeme, $oznakaNalaz);
                                    //Izvršavanje statementa
                                    mysqli_stmt_execute($stmtNalaz);

                                    //Povećavam broj ažuriranih redaka
                                    $brojacAzuriranihRedaka++;
                                }
                            }
                        }
                        //Ako je broj obrađenih redaka manji od broja dijagnoza u bazi te NIJE prva iteracija
                        else if($brojacAzuriranihRedaka < $brojSekundarnaBaza && $brojacIteracija > 1){
                            
                            //Kreiram upit koji dohvaća SLJEDEĆI MINIMALNI ID povijesti bolesti za ovog pacijenta za ovu sesiju obrade
                            $sqlSljedeciMin = "SELECT pb.idPovijestBolesti FROM povijestbolesti pb 
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
                            //Kreiram upit kojim ću unijeti ID uputnice u tablicu "povijestBolesti"
                            $sqlUpdate ="UPDATE povijestBolesti pb SET pb.idUputnica = ?,pb.mkbSifraPrimarna = ?, pb.mkbSifraSekundarna = ? 
                                        WHERE pb.idPovijestBolesti = ?";
                            //Kreiranje prepared statementa
                            $stmtUpdate = mysqli_stmt_init($conn);
                            //Ako je statement neuspješan
                            if(!mysqli_stmt_prepare($stmtUpdate,$sqlUpdate)){
                                $response["success"] = "false";
                                $response["message"] = "Prepared statement ne valja!";
                                $response["idUputnica"] = null;
                            }
                            //Ako je prepared statement u redu
                            else{
                                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                mysqli_stmt_bind_param($stmtUpdate,"issi",$idUputnica,$mkbSifraPrimarna,$mkb,$idMinPovijestBolesti);
                                //Izvršavanje statementa
                                mysqli_stmt_execute($stmtUpdate);
                                //Spremam nalaz 
                                $sqlNalaz = "INSERT INTO nalaz (idUputnica, idPacijent, idSpecijalist, idZdrUst, sifDjel, 
                                                                mkbSifraPrimarna, mkbSifraSekundarna, misljenjeSpecijalist, 
                                                                komentarUzNalaz,datumNalaz, vrijemeNalaz, oznaka) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
                                //Kreiranje prepared statementa
                                $stmtNalaz = mysqli_stmt_init($conn);
                                //Ako je statement neuspješan
                                if(!mysqli_stmt_prepare($stmtNalaz,$sqlNalaz)){
                                    $response["success"] = "false";
                                    $response["message"] = "Prepared statement ne valja!";
                                    $response["idUputnica"] = null;
                                }
                                else{
                                    //Ako je vrsta pregleda == 'Dijagnostička pretraga'
                                    if($vrstaPregled == 'Dijagnostička pretraga'){
                                        //Stavljam dummy text
                                        $komentarUzNalaz = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
                                        $misljenjeSpecijalist = NULL;
                                    }
                                    else{
                                        //Stavljam dummy text
                                        $misljenjeSpecijalist = "Lorem Ipsum is simply dummy text of the printing and typesetting industry.";
                                        $komentarUzNalaz = NULL;
                                    }  
                                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                    mysqli_stmt_bind_param($stmtNalaz,"iiiiisssssss",$idUputnica, $idPacijent, $idSpecijalist,
                                                                                $idZdrUst, $sifDjel, $mkbSifraPrimarna, $mkb, 
                                                                                $misljenjeSpecijalist, $komentarUzNalaz, $datum, $vrijeme, $oznakaNalaz);
                                    //Izvršavanje statementa
                                    mysqli_stmt_execute($stmtNalaz);

                                    //Povećavam broj ažuriranih redaka
                                    $brojacAzuriranihRedaka++;
                                    //Vraćanje uspješnog odgovora serveru
                                    $response["success"] = "true";
                                    $response["message"] = "Uputnica uspješno dodana!";
                                    $response["idUputnica"] = $prviIdUputnica;
                                }
                            }
                        }
                        //Ako je broj ažuriranih redak JEDNAK broju sek. dijagnoza u bazi (npr. 2 == 2) I brojač iteracija JE VEĆI od broja sek. dijagnoza u bazi (npr. 3 > 2) 
                        //te da je broj sek. dijagnoza u BAZI VEĆI OD 0
                        if($brojacAzuriranihRedaka == $brojSekundarnaBaza && $brojacIteracija > $brojSekundarnaBaza && $brojSekundarnaBaza > 0){
                            //Prije nego što ubacim novi redak povijesti bolesti, dohvaćam redak koji sam ažurirao u prethodnom if uvjetu 
                            $sqlPovijestBolesti = "SELECT * FROM povijestbolesti pb
                                                WHERE pb.idObradaLijecnik = '$poslaniIDObrada' 
                                                AND pb.mboPacijent = '$mboPacijent' 
                                                AND TRIM(pb.mkbSifraPrimarna) = '$poslanaMKBSifra' 
                                                AND pb.tipSlucaj = '$poslaniTipSlucaj' 
                                                AND pb.vrijeme = '$poslanoVrijeme'
                                                AND pb.idPovijestBolesti = 
                                                (SELECT MAX(pb2.idPovijestBolesti) FROM povijestbolesti pb2 
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
                            $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                                    nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                    preporukaLijecnik, napomena, datum, narucen, mboPacijent,idObradaLijecnik, 
                                    vrijeme,idUputnica,prosliPregled,bojaPregled,oznaka) 
                                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                            //Kreiranje prepared statementa
                            $stmt = mysqli_stmt_init($conn);
                            //Ako je statement neuspješan
                            if(!mysqli_stmt_prepare($stmt,$sql)){
                                $response["success"] = "false";
                                $response["message"] = "Prepared statement ne valja!";
                                $response["idUputnica"] = null;
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
                                                                            $vrijemePovijestiBolesti,$idUputnica,$prosliPregled,$bojaPregled,$oznakaPov);
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
                                    $response["idUputnica"] = null;
                                }
                                //Ako je prepared statement u redu
                                else{
                                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                    mysqli_stmt_bind_param($stmtAmbulanta,"iii",$idLijecnik,$idPacijent,$idPovijestBolesti);
                                    //Izvršavanje statementa
                                    mysqli_stmt_execute($stmtAmbulanta);

                                    //Spremam nalaz 
                                    $sqlNalaz = "INSERT INTO nalaz (idUputnica, idPacijent, idSpecijalist, idZdrUst, sifDjel, 
                                                                    mkbSifraPrimarna, mkbSifraSekundarna, misljenjeSpecijalist, 
                                                                    komentarUzNalaz,datumNalaz, vrijemeNalaz, oznaka) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
                                    //Kreiranje prepared statementa
                                    $stmtNalaz = mysqli_stmt_init($conn);
                                    //Ako je statement neuspješan
                                    if(!mysqli_stmt_prepare($stmtNalaz,$sqlNalaz)){
                                        $response["success"] = "false";
                                        $response["message"] = "Prepared statement ne valja!";
                                        $response["idUputnica"] = null;
                                    }
                                    else{
                                        //Ako je vrsta pregleda == 'Dijagnostička pretraga'
                                        if($vrstaPregled == 'Dijagnostička pretraga'){
                                            //Stavljam dummy text
                                            $komentarUzNalaz = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
                                            $misljenjeSpecijalist = NULL;
                                        }
                                        else{
                                            //Stavljam dummy text
                                            $misljenjeSpecijalist = "Lorem Ipsum is simply dummy text of the printing and typesetting industry.";
                                            $komentarUzNalaz = NULL;
                                        }  
                                        //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                        mysqli_stmt_bind_param($stmtNalaz,"iiiiisssssss",$idUputnica, $idPacijent, $idSpecijalist,
                                                                                    $idZdrUst, $sifDjel, $mkbSifraPrimarna, $mkb, 
                                                                                    $misljenjeSpecijalist, $komentarUzNalaz,$datum, $vrijeme, $oznakaNalaz);
                                        //Izvršavanje statementa
                                        mysqli_stmt_execute($stmtNalaz);

                                        //Vraćanje uspješnog odgovora serveru
                                        $response["success"] = "true";
                                        $response["message"] = "Uputnica uspješno dodana!";
                                        $response["idUputnica"] = $prviIdUputnica;
                                    }
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
                                preporukaLijecnik, napomena, datum, narucen, mboPacijent,idObradaLijecnik, 
                                vrijeme,idUputnica,prosliPregled,bojaPregled,oznaka) 
                                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                        //Kreiranje prepared statementa
                        $stmt = mysqli_stmt_init($conn);
                        //Ako je statement neuspješan
                        if(!mysqli_stmt_prepare($stmt,$sql)){
                            $response["success"] = "false";
                            $response["message"] = "Prepared statement ne valja!";
                            $response["idUputnica"] = null;
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
                                                                        $vrijemePovijestiBolesti,$idUputnica,$prosliPregled,$bojaPregled,$oznakaPov);
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
                                $response["idUputnica"] = null;
                            }
                            //Ako je prepared statement u redu
                            else{
                                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                mysqli_stmt_bind_param($stmtAmbulanta,"iii",$idLijecnik,$idPacijent,$idPovijestBolesti);
                                //Izvršavanje statementa
                                mysqli_stmt_execute($stmtAmbulanta);
                                //Spremam nalaz 
                                $sqlNalaz = "INSERT INTO nalaz (idUputnica, idPacijent, idSpecijalist, idZdrUst, sifDjel, 
                                                                mkbSifraPrimarna, mkbSifraSekundarna, misljenjeSpecijalist, 
                                                                komentarUzNalaz ,datumNalaz, vrijemeNalaz, oznaka) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
                                //Kreiranje prepared statementa
                                $stmtNalaz = mysqli_stmt_init($conn);
                                //Ako je statement neuspješan
                                if(!mysqli_stmt_prepare($stmtNalaz,$sqlNalaz)){
                                    $response["success"] = "false";
                                    $response["message"] = "Prepared statement ne valja!";
                                    $response["idUputnica"] = null;
                                }
                                else{
                                    //Generiram slučajni ID specijalista
                                    $idSpecijalist = mt_rand(1,5);
                                    //Ako je ustanova prazna
                                    if(empty($idZdrUst)){
                                        $idZdrUst = $zdrUstanove[array_rand($zdrUstanove,1)];
                                    }
                                    //Ako je vrsta pregleda == 'Dijagnostička pretraga'
                                    if($vrstaPregled == 'Dijagnostička pretraga'){
                                        //Stavljam dummy text
                                        $komentarUzNalaz = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
                                        $misljenjeSpecijalist = NULL;
                                    }
                                    else{
                                        //Stavljam dummy text
                                        $misljenjeSpecijalist = "Lorem Ipsum is simply dummy text of the printing and typesetting industry.";
                                        $komentarUzNalaz = NULL;
                                    }  
                                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                                    mysqli_stmt_bind_param($stmtNalaz,"iiiiisssssss",$idUputnica, $idPacijent, $idSpecijalist,
                                                                                $idZdrUst, $sifDjel, $mkbSifraPrimarna, $mkb, 
                                                                                $misljenjeSpecijalist, $komentarUzNalaz, $datum, $vrijeme, $oznakaNalaz);
                                    //Izvršavanje statementa
                                    mysqli_stmt_execute($stmtNalaz);

                                    //Vraćanje uspješnog odgovora serveru
                                    $response["success"] = "true";
                                    $response["message"] = "Uputnica uspješno dodana!";
                                    $response["idUputnica"] = $prviIdUputnica;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $response;
    }
}   
?>