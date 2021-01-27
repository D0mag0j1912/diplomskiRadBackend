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

$lijek = "Peptoran tbl. 20x150 mg";
$kolicina = 2;
$doza = "4xtjedno";


//Funkcija koja izračunava dostatnost lijeka
function izracunajDostatnost($lijek,$kolicina,$doza){
    //Dohvaćam bazu 
    $baza = new Baza();
    $conn = $baza->spojiSBazom();
    //Inicijalno postavljam brojač na 2
    $brojac = 2;
    //Inicijalno označavam da nisam pronašao lijek
    $pronasao = FALSE;
    //Deklariram oblik, jačinu i pakiranje lijeka na prazan string
    $oblikJacinaPakiranjeLijek = "";

    //Dok nisam pronašao lijek
    while($pronasao !== TRUE){
        //Splitam string da mu uzmem ime i oblik-jačinu-pakiranje (KREĆEM OD 2)
        $polje = explode(" ",$lijek,$brojac);
        //Dohvaćam oblik,jačinu i pakiranje lijeka
        $ojpLijek = array_pop($polje);
        //Dohvaćam ime lijeka
        $imeLijek = implode(" ", $polje);

        //Kreiram sql upit kojim provjeravam postoji li LIJEK u osnovnoj listi lijekova
        $sqlOsnovnaLista = "SELECT o.oblikJacinaPakiranjeLijek FROM osnovnalistalijekova o 
                        WHERE o.oblikJacinaPakiranjeLijek = '$ojpLijek' AND o.zasticenoImeLijek = '$imeLijek'";

        $resultOsnovnaLista = $conn->query($sqlOsnovnaLista);
        //Ako je lijek pronađen u OSNOVNOJ LISTI LIJEKOVA
        if ($resultOsnovnaLista->num_rows > 0) {
            while($rowOsnovnaLista = $resultOsnovnaLista->fetch_assoc()) {
                //Dohvaćam oblik, jačinu i pakiranje unesenog lijeka
                $oblikJacinaPakiranjeLijek = $rowOsnovnaLista['oblikJacinaPakiranjeLijek'];
            }
            //Izračunaj dostatnost..
            $tableta = "";
            $dostatnost = 0;
            //Ako ojp lijeka završava na "mg" ili na "g"
            if(substr($oblikJacinaPakiranjeLijek, -strlen("mg")) === "mg" || substr($oblikJacinaPakiranjeLijek, -strlen("g")) === "g"){
                //Dijelim oblik, jačinu i pakiranje na dijelove
                $ojp = explode(" ",$oblikJacinaPakiranjeLijek);
                //Prolazim kroz te dijelove stringa
                foreach($ojp as $element){
                    //Ako ijedan dio sadrži char "x":
                    if(strrpos($element,"x") !== false){
                        //Spremi taj dio stringa jer mi treba za izračun
                        $tableta = $element;
                    }
                }
                //Dohvaćam BROJ TABLETA/KAPSULA... 
                $brojTableta = (int)substr($tableta,0,strpos($tableta,"x"));
                //Dohvaćam frekvenciju doziranja
                $frekvencijaDoziranje = (int)substr($doza,0,strpos($doza,"x"));
                //Dohvaćam period doziranja
                $periodDoziranje = substr($doza,strpos($doza,"x")+1,strlen($doza));
                echo $brojTableta."\n";
                echo $frekvencijaDoziranje."\n";
                echo $periodDoziranje."\n";
                //Ako je period doziranja "dnevno":
                if($periodDoziranje == "dnevno"){
                    //Računam dostatnost u danima
                    $dostatnost = ($kolicina * $brojTableta) / ($frekvencijaDoziranje);
                }
                //Ako je period doziranja "tjedno":
                else if($periodDoziranje == "tjedno"){
                    //Računam dostatnost u danima
                    $dostatnost = ($kolicina * $brojTableta) / ($frekvencijaDoziranje) * 7;
                }
            }
            //Završi petlju
            $pronasao = TRUE;
        }
        //Ako lijek NIJE PRONAĐEN u osnovnoj listi, tražim ga u dopunskoj
        else{
            //Kreiram sql upit kojim provjeravam postoji li LIJEK u DOPUNSKOJ LISTI lijekova
            $sqlDopunskaLista = "SELECT d.oblikJacinaPakiranjeLijek FROM dopunskalistalijekova d 
                            WHERE d.oblikJacinaPakiranjeLijek = '$ojpLijek' AND d.zasticenoImeLijek = '$imeLijek'";

            $resultDopunskaLista = $conn->query($sqlDopunskaLista);
            //Ako je lijek PRONAĐEN u DOPUNSKOJ LISTI lijekova
            if($resultDopunskaLista->num_rows > 0){
                while($rowDopunskaLista = $resultDopunskaLista->fetch_assoc()) {
                    //Dohvaćam oblik, jačinu i pakiranje unesenog lijeka
                    $oblikJacinaPakiranjeLijek = $rowDopunskaLista['oblikJacinaPakiranjeLijek'];
                }
                //Izračunaj dostatnost..
                $tableta = "";
                $dostatnost = 0;
                //Ako ojp lijeka završava na "mg" ili na "g"
                if(substr($oblikJacinaPakiranjeLijek, -strlen("mg")) === "mg" || substr($oblikJacinaPakiranjeLijek, -strlen("g")) === "g"){
                    //Dijelim oblik, jačinu i pakiranje na dijelove
                    $ojp = explode(" ",$oblikJacinaPakiranjeLijek);
                    //Prolazim kroz te dijelove stringa
                    foreach($ojp as $element){
                        //Ako ijedan dio sadrži char "x":
                        if(strrpos($element,"x") !== false){
                            //Spremi taj dio stringa jer mi treba za izračun
                            $tableta = $element;
                        }
                    }
                    //Dohvaćam BROJ TABLETA/KAPSULA... 
                    $brojTableta = (int)substr($tableta,0,strpos($tableta,"x"));
                    //Dohvaćam frekvenciju doziranja
                    $frekvencijaDoziranje = (int)substr($doza,0,strpos($doza,"x"));
                    //Dohvaćam period doziranja
                    $periodDoziranje = substr($doza,strpos($doza,"x")+1,strlen($doza));
                    echo $brojTableta."\n";
                    echo $frekvencijaDoziranje."\n";
                    echo $periodDoziranje;
                    //Ako je period doziranja "dnevno":
                    if($periodDoziranje == "dnevno"){
                        //Računam dostatnost u danima
                        $dostatnost = ($kolicina * $brojTableta) / ($frekvencijaDoziranje);
                    }
                    //Ako je period doziranja "tjedno":
                    else if($periodDoziranje == "tjedno"){
                        //Računam dostatnost u danima
                        $dostatnost = ($kolicina * $brojTableta) / ($frekvencijaDoziranje) * 7;
                    }
                }
                //Završi petlju
                $pronasao = TRUE;
            }
        }
        //Inkrementiram brojač 
        $brojac++;
    }
    return $dostatnost;
}

echo izracunajDostatnost($lijek,$kolicina,$doza);
?>