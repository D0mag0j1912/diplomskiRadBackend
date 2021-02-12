<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class DodajReceptService{
    //Funkcija koja dodava novi recept u bazu podataka
    function dodajRecept($mkbSifraPrimarna,$mkbSifraSekundarna,$osnovnaListaLijekDropdown,
                    $osnovnaListaLijekText,$dopunskaListaLijekDropdown,$dopunskaListaLijekText,
                    $osnovnaListaMagPripravakDropdown,$osnovnaListaMagPripravakText,$dopunskaListaMagPripravakDropdown,
                    $dopunskaListaMagPripravakText,$kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv,$brojPonavljanja,
                    $sifraSpecijalist,$idPacijent){
    //Dohvaćam bazu 
    $baza = new Baza();
    $conn = $baza->spojiSBazom();
    //Kreiram prazno polje odgovora
    $response = [];
    //Trenutni datum
    $datum = date('Y-m-d');
    //Trenutno vrijeme za naručivanje
    $vrijeme = date('H:i');
    //Ako nema sekundarnih dijagnoza
    if(empty($mkbSifraSekundarna)){
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
            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
            mysqli_stmt_bind_param($stmt,"ssssisissiiiss",$mkbSifraPrimarna,$prazna,$proizvod,$oblikJacinaPakiranjeLijek,
                                    $kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv, 
                                    $brojPonavljanja,$sifraSpecijalist,$idPacijent,$datum,$vrijeme);
            //Izvršavanje statementa
            mysqli_stmt_execute($stmt);

            //Vraćanje uspješnog odgovora serveru
            $response["success"] = "true";
            $response["message"] = "Recept uspješno dodan!";
        }
    }
    //Ako IMA MKB šifri sek. dijagnoza
    else{
        //Prolazim kroz svaku MKB šifru polja sekundarnih dijagnoza
        foreach($mkbSifraSekundarna as $mkb){
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
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmt,"ssssisissiiiss",$mkbSifraPrimarna,$mkb,$proizvod,$oblikJacinaPakiranjeLijek,
                        $kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv, 
                        $brojPonavljanja,$sifraSpecijalist,$idPacijent,$datum,$vrijeme);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmt);

                //Vraćanje uspješnog odgovora serveru
                $response["success"] = "true";
                $response["message"] = "Recept uspješno dodan!";
            }
        }
    }
    //Vraćam odgovor frontendu
    return $response;
    }
}
?>