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

$mkbSifraPrimarna = "A00";
$mkbSifraSekundarna = [];
$osnovnaListaLijekDropdown = NULL;
$osnovnaListaLijekText = NULL;
$dopunskaListaLijekDropdown = NULL;
$dopunskaListaLijekText = NULL;
$osnovnaListaMagPripravakDropdown = NULL;
$osnovnaListaMagPripravakText = NULL;
$dopunskaListaMagPripravakDropdown = "Rp. Afloderm krema 20,0 Belobaza ad 100,0 M.D.S. krema";
$dopunskaListaMagPripravakText = NULL;
$kolicina = 1;
$doziranje = "3xtjedno";
$dostatnost = 10;
$hitnost = NULL;
$ponovljiv = NULL;
$brojPonavljanja = NULL;
//Funkcija koja dodava novi recept u bazu podataka
function dodajRecept($mkbSifraPrimarna,$mkbSifraSekundarna,$osnovnaListaLijekDropdown,
                    $osnovnaListaLijekText,$dopunskaListaLijekDropdown,$dopunskaListaLijekText,
                    $osnovnaListaMagPripravakDropdown,$osnovnaListaMagPripravakText,$dopunskaListaMagPripravakDropdown,
                    $dopunskaListaMagPripravakText,$kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv,$brojPonavljanja){
    //Dohvaćam bazu 
    $baza = new Baza();
    $conn = $baza->spojiSBazom();
    //Kreiram prazno polje odgovora
    $response = [];

    //Ako nema sekundarnih dijagnoza
    if(empty($mkbSifraSekundarna)){
        //Kreiram upit za dodavanje novog recepta u bazu
        $sql = "INSERT INTO recept (mkbSifraPrimarna,mkbSifraSekundarna,proizvod,oblikJacinaPakiranjeLijek, 
                        kolicina,doziranje,dostatnost,hitnost,ponovljiv,brojPonavljanja) VALUES 
                        (?,?,?,?,?,?,?,?,?,?)";
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
            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
            mysqli_stmt_bind_param($stmt,"ssssisissi",$mkbSifraPrimarna,$prazna,$proizvod,$oblikJacinaPakiranjeLijek,
                                            $kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv,$brojPonavljanja);
            //Izvršavanje statementa
            mysqli_stmt_execute($stmt);

            //Vraćanje uspješnog odgovora serveru
            $response["success"] = "true";
            $response["message"] = "Recept uspješno dodan!";
        }
    }
    //Vraćam odgovor frontendu
    return $response;
}

foreach(dodajRecept($mkbSifraPrimarna,$mkbSifraSekundarna,$osnovnaListaLijekDropdown,
$osnovnaListaLijekText,$dopunskaListaLijekDropdown,$dopunskaListaLijekText,
$osnovnaListaMagPripravakDropdown,$osnovnaListaMagPripravakText,$dopunskaListaMagPripravakDropdown,
$dopunskaListaMagPripravakText,$kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv,$brojPonavljanja) as $vanjsko){
    echo $vanjsko;
}
?>