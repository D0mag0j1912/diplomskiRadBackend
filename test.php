<?php
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

$servis = new OpciPodatciService();
//Kreiram prazno polje
$response = [];
//Kreiram objekt tipa "Baza"
$baza = new Baza();
$conn = $baza->spojiSBazom();
//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

/* $vrijeme = date("H:i");

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

echo $vrijeme; */
$mkbSifraPrimarna = "A03";
$mkbSifraSekundarna = ["A00","A00.0"];
$osnovnaListaLijekDropdown = "";
$osnovnaListaLijekText = "";
$dopunskaListaLijekDropdown = "Dicetel tbl. film obl. 30x100 mg";
$dopunskaListaLijekText = "";
$osnovnaListaMagPripravakDropdown = "";
$osnovnaListaMagPripravakText = "";
$dopunskaListaMagPripravakDropdown = "";
$dopunskaListaMagPripravakText = "";
$kolicina = 1;
$doziranje = "2xdnevno";
$dostatnost = 15;
$hitnost = "hitno";
$ponovljiv = "obican";
$brojPonavljanja = "";
$sifraSpecijalist = "";
$idPacijent = 1;
$datumRecept = "2021-02-22";
$vrijemeRecept = "09:47:00";

//Funkcija koja dodava novi recept u bazu podataka
function azurirajRecept($mkbSifraPrimarna,$mkbSifraSekundarna,$osnovnaListaLijekDropdown,
                        $osnovnaListaLijekText,$dopunskaListaLijekDropdown,$dopunskaListaLijekText,
                        $osnovnaListaMagPripravakDropdown,$osnovnaListaMagPripravakText,$dopunskaListaMagPripravakDropdown,
                        $dopunskaListaMagPripravakText,$kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv,$brojPonavljanja,
                        $sifraSpecijalist,$idPacijent,$datumRecept,$vrijemeRecept){
    //Dohvaćam bazu 
    $baza = new Baza();
    $conn = $baza->spojiSBazom();
    //Kreiram prazno polje odgovora
    $response = [];
    //Trenutni datum
    $datum = date('Y-m-d');
    //Trenutno vrijeme
    $vrijeme = date('H:i');
    //Brojim koliko ima sekundarnih dijagnoza u formi 
    $brojacSekundarnaDijagnozaForma = count($mkbSifraSekundarna);
    //Inicijaliziram brojač na 0 isprva (on gleda na kojoj sam iteraciji tj. sek. dijagnozi trenutno)
    $brojacIteracija = 0;
    //Prolazim kroz svaku MKB šifru polja sekundarnih dijagnoza
    foreach($mkbSifraSekundarna as $mkb){
        //Povećavam brojač za 1
        $brojacIteracija++;
        //Kreiram sql upit koji će prebrojiti koliko ima SEKUNDARNIH DIJAGNOZA TRENUTNO U BAZI ZA ODREĐENU PRIMARNU DIJAGNOZU, ZA ODREĐENI DATUM, VRIJEME I PACIJENTA
        $sqlCountSekundarna = "SELECT COUNT(r.mkbSifraSekundarna) AS BrojSekundarna FROM recept r
                                WHERE r.mkbSifraPrimarna = '$mkbSifraPrimarna' AND r.datumRecept = '$datumRecept' 
                                AND r.vrijemeRecept = '$vrijemeRecept' AND r.idPacijent = '$idPacijent';";
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

        //Ako u bazi NEMA sek. dijagnoza, a u formi je JEDNA 
        if($brojSekundarnaBaza == 0 && $brojacSekundarnaDijagnozaForma == 1){
            //Kreiram upit za dodavanje novog recepta u bazu
            $sql = "UPDATE recept r SET r.mkbSifraPrimarna = ?, r.mkbSifraSekundarna = ?, r.proizvod = ?, 
                                        r.oblikJacinaPakiranjeLijek = ?, r.kolicina = ?, r.doziranje = ?,
                                        r.dostatnost = ?, r.hitnost = ?, r.ponovljiv = ?, r.brojPonavljanja = ?, 
                                        r.sifraSpecijalist = ?
                    WHERE r.idPacijent = ? AND r.datumRecept = ? AND r.vrijemeRecept = ?";
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
                mysqli_stmt_bind_param($stmt,"ssssisissiiiss",$mkbSifraPrimarna,$mkb,$proizvod,$oblikJacinaPakiranjeLijek,
                                                $kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv, 
                                                $brojPonavljanja,$sifraSpecijalist,$idPacijent,$datumRecept,$vrijemeRecept);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmt);
                //Vraćanje uspješnog odgovora serveru
                $response["success"] = "true";
                $response["message"] = "Recept uspješno ažuriran!";
            }
        }
        //Ako u bazi NEMA sek. dijagnoza, a u formi je VIŠE OD JEDNE sek. dijagnoze
        else if($brojSekundarnaBaza == 0 && $brojacSekundarnaDijagnozaForma > 1){
            //Ako se trenutno nalazim na prvoj iteraciji tj. sek.dijagnozi, nju dodaj u bazu AŽURIRANJEM POSTOJEĆEG RETKA U BAZI (SA NULL NA Axx)
            if($brojacIteracija == 1){
                //Kreiram upit za dodavanje novog recepta u bazu
                $sql = "UPDATE recept r SET r.mkbSifraPrimarna = ?, r.mkbSifraSekundarna = ?, r.proizvod = ?, 
                                            r.oblikJacinaPakiranjeLijek = ?, r.kolicina = ?, r.doziranje = ?,
                                            r.dostatnost = ?, r.hitnost = ?, r.ponovljiv = ?, r.brojPonavljanja = ?, 
                                            r.sifraSpecijalist = ?
                        WHERE r.idPacijent = ? AND r.datumRecept = ? AND r.vrijemeRecept = ?";
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
                    mysqli_stmt_bind_param($stmt,"ssssisissiiiss",$mkbSifraPrimarna,$mkb,$proizvod,$oblikJacinaPakiranjeLijek,
                                        $kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv, 
                                        $brojPonavljanja,$sifraSpecijalist,$idPacijent,$datumRecept,$vrijemeRecept); 
                }     
            }
            /* //Ako se trenutno nalazim na iteraciji većoj od 1, njih ostale koliko ih ima, DODAJ U BAZU
            if($brojacIteracija > 1){
                //Kreiram upit za dodavanje novog recepta u bazu
                $sqlInsert = "INSERT INTO recept (mkbSifraPrimarna,mkbSifraSekundarna,proizvod,oblikJacinaPakiranjeLijek, 
                                            kolicina,doziranje,dostatnost,hitnost,ponovljiv,brojPonavljanja, 
                                            sifraSpecijalist,idPacijent,datumRecept,vrijemeRecept) VALUES 
                                            (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                //Kreiranje prepared statementa
                $stmtInsert = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmtInsert,$sqlInsert)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement ne valja!";
                }
                else{
                    //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                    mysqli_stmt_bind_param($stmtInsert,"ssssisissiiiss",$mkbSifraPrimarna,$mkb,$proizvod,$oblikJacinaPakiranjeLijek,
                                                                $kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv, 
                                                                $brojPonavljanja,$sifraSpecijalist,$idPacijent,$datumRecept,$vrijemeRecept);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmtInsert);
                }  
            } */
            //Vraćanje uspješnog odgovora serveru
            $response["success"] = "true";
            $response["message"] = "Recept uspješno ažuriran!";  
        }
    }
    
}
foreach(azurirajRecept($mkbSifraPrimarna,$mkbSifraSekundarna,$osnovnaListaLijekDropdown,
                        $osnovnaListaLijekText,$dopunskaListaLijekDropdown,$dopunskaListaLijekText,
                        $osnovnaListaMagPripravakDropdown,$osnovnaListaMagPripravakText,$dopunskaListaMagPripravakDropdown,
                        $dopunskaListaMagPripravakText,$kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv,$brojPonavljanja,
                        $sifraSpecijalist,$idPacijent,$datumRecept,$vrijemeRecept) as $recept){
    foreach($recept as $r){
        echo $r;
    }
}

?>