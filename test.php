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

//Funkcija koja dohvaća maksimalnu dozu lijeka
function izracunajMaksimalnuDozu($lijek,$doza){
    //Dohvaćam bazu 
    $baza = new Baza();
    $conn = $baza->spojiSBazom();
    //Kreiram prazno polje
    $response = [];
    //Inicijalno postavljam da nisam pronašao lijek
    $pronasao = false;
    //Inicijalno postavljam brojač na 2
    $brojac = 2;
    //Dok nisam pronašao lijek
    while($pronasao !== true){
        //Splitam string da mu uzmem ime i oblik-jačinu-pakiranje (KREĆEM OD 2)
        $polje = explode(" ",$lijek,$brojac);
        //Dohvaćam oblik,jačinu i pakiranje lijeka
        $ojpLijek = array_pop($polje);
        //Dohvaćam ime lijeka
        $imeLijek = implode(" ", $polje);

        //Kreiram sql upit kojim provjeravam postoji li LIJEK u osnovnoj listi lijekova
        $sqlOsnovnaLista = "SELECT o.oblikJacinaPakiranjeLijek,o.dddLijek FROM osnovnalistalijekova o 
                        WHERE o.oblikJacinaPakiranjeLijek = '$ojpLijek' AND o.zasticenoImeLijek = '$imeLijek'";

        $resultOsnovnaLista = $conn->query($sqlOsnovnaLista);
        //Ako je lijek pronađen u OSNOVNOJ LISTI LIJEKOVA
        if ($resultOsnovnaLista->num_rows > 0) {
            while($rowOsnovnaLista = $resultOsnovnaLista->fetch_assoc()) {
                //Dohvaćam oblik, jačinu i pakiranje unesenog lijeka
                $oblikJacinaPakiranjeLijek = $rowOsnovnaLista['oblikJacinaPakiranjeLijek'];
                //Dohvaćam dnevno definiranu dozu tog lijeka
                $dddLijek = $rowOsnovnaLista['dddLijek'];
            }
            //Izračunaj dostatnost..
            $tableta = "";
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
                //Dohvaćam mjernu jedinicu OJP-a (g ili mg)
                $mjernaJedinicaOJP = array_pop($ojp);
                //Ako $tableta sadrži zarez npr. 30x3,5 g
                if(strpos($tableta,",") !== false){
                    //Mijenjam zarez sa točkom da dobijem float s kojim mogu računati
                    $tableta = str_replace(",",".",$tableta);
                    //Dohvaćam dozu jedne tablete
                    $mgJednaTableta = (float)substr($tableta,strpos($tableta,"x")+1,strlen($tableta));
                }
                //Ako $tableta ne sadrži zarez npr. 20x150 mg
                else{
                    //Dohvaćam dozu jedne tablete
                    $mgJednaTableta = (int)substr($tableta,strpos($tableta,"x")+1,strlen($tableta));
                }
                //Dohvaćam frekvenciju doziranja
                $frekvencijaDoziranje = (int)substr($doza,0,strpos($doza,"x"));
                //Dohvaćam period doziranja
                $periodDoziranje = substr($doza,strpos($doza,"x")+1,strlen($doza));
                //Dijelim dnevno definiranu dozu na njezin broj i mjernu jedinicu
                $poljeDDD = explode(" ",$dddLijek);
                //Dohvaćam mjernu jedinicu dnevno definirane doze
                $mjernaJedinicaDDD = array_pop($poljeDDD);
                //Dohvaćam broj dnevno definirane doze
                $dddBroj = implode(" ", $poljeDDD);
                //Zamjenjujem zarez sa točkom
                $dddBroj = str_replace(',', '.', $dddBroj);
                //Ako je mjerna jedinica DDD-a u "g" (u gramima), a mjerna jedinica OJP-a je "mg"
                if($mjernaJedinicaDDD == "g" && $mjernaJedinicaOJP == "mg"){
                    //Pretvaram dnevno definiranu dozu u mg (miligrame)
                    $dddBroj = (float)$dddBroj*1000;
                    //Računam maksimalni broj tableta u danu npr. (300mg / 150mg => 2 tablete)
                    $maxDoza = ($dddBroj)/($mgJednaTableta);
                    //Ako vrijednost max doze NIJE integer 
                    if(!is_int($maxDoza)){
                        $maxDoza = round($maxDoza);
                    }
                    //Ako je period doziranja "dnevno":
                    if($periodDoziranje == "dnevno"){
                        //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                        if($frekvencijaDoziranje > $maxDoza){
                            $response["success"]="false";
                            $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                            $response["maxDoza"]="Dnevno definirana doza: ".$maxDoza."xdnevno";
                        }
                        //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                        else{
                            $response["success"]="true";
                            $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                            $response["maxDoza"]="Dnevno definirana doza: ".$maxDoza."xdnevno";
                        }
                    }
                    //Ako je period doziranja "tjedno":
                    else if($periodDoziranje == "tjedno"){
                        //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                        if($frekvencijaDoziranje > ($maxDoza * 7)){
                            $response["success"]="false";
                            $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                            $response["maxDoza"]="Tjedno definirana doza: ".($maxDoza * 7)."xtjedno";
                        }
                        //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                        else{
                            $response["success"]="true";
                            $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                            $response["maxDoza"]="Tjedno definirana doza: ".($maxDoza * 7)."xtjedno";
                        }
                    }
                }
                //Ako je mjerna jedinica DDD-a "g" (u gramima),a mjerna jedinica OJP-a u "g" (gramima)
                else if($mjernaJedinicaDDD == "g" && $mjernaJedinicaOJP == "g"){
                    //Računam maksimalni broj tableta u danu npr. (300mg / 150mg => 2 tablete)
                    $maxDoza = ($dddBroj)/($mgJednaTableta);
                    //Ako vrijednost max doze NIJE integer 
                    if(!is_int($maxDoza)){
                        $maxDoza = round($maxDoza);
                    }
                    //Ako je period doziranja "dnevno":
                    if($periodDoziranje == "dnevno"){
                        //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                        if($frekvencijaDoziranje > $maxDoza){
                            $response["success"]="false";
                            $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                            $response["maxDoza"]="Dnevno definirana doza: ".$maxDoza."xdnevno";
                        }
                        //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                        else{
                            $response["success"]="true";
                            $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                            $response["maxDoza"]="Dnevno definirana doza: ".$maxDoza."xdnevno";
                        }
                    }
                    //Ako je period doziranja "tjedno":
                    else if($periodDoziranje == "tjedno"){
                        //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                        if($frekvencijaDoziranje > ($maxDoza * 7)){
                            $response["success"]="false";
                            $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                            $response["maxDoza"]="Tjedno definirana doza: ".($maxDoza * 7)."xtjedno";
                        }
                        //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                        else{
                            $response["success"]="true";
                            $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                            $response["maxDoza"]="Tjedno definirana doza: ".($maxDoza * 7)."xtjedno";
                        }
                    }
                }
                //Ako je mjerna jedinica DDD-a "mg" (u miligramima),a mjerna jedinica OJP-a u "mg" (miligramima)
                else if($mjernaJedinicaDDD == "mg" && $mjernaJedinicaOJP == "mg"){
                    //Računam maksimalni broj tableta u danu npr. (300mg / 150mg => 2 tablete)
                    $maxDoza = ($dddBroj)/($mgJednaTableta);
                    //Ako vrijednost max doze NIJE integer 
                    if(!is_int($maxDoza)){
                        $maxDoza = round($maxDoza);
                    }
                    //Ako je period doziranja "dnevno":
                    if($periodDoziranje == "dnevno"){
                        //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                        if($frekvencijaDoziranje > $maxDoza){
                            $response["success"]="false";
                            $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                            $response["maxDoza"]="Dnevno definirana doza: ".$maxDoza."xdnevno";
                        }
                        //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                        else{
                            $response["success"]="true";
                            $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                            $response["maxDoza"]="Dnevno definirana doza: ".$maxDoza."xdnevno";
                        }
                    }
                    //Ako je period doziranja "tjedno":
                    else if($periodDoziranje == "tjedno"){
                        //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                        if($frekvencijaDoziranje > ($maxDoza * 7)){
                            $response["success"]="false";
                            $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                            $response["maxDoza"]="Tjedno definirana doza: ".($maxDoza * 7)."xtjedno";
                        }
                        //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                        else{
                            $response["success"]="true";
                            $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                            $response["maxDoza"]="Tjedno definirana doza: ".($maxDoza * 7)."xtjedno";
                        }
                    }
                }
            }
            //Ako ojp lijeka NE ZAVRŠAVA NA mg ili g
            else{
                //Vraćam null
                return null;
            }
            //Završi petlju
            $pronasao = TRUE;
        }
        //Ako lijek NIJE PRONAĐEN u osnovnoj listi, tražim ga u dopunskoj
        else{
            //Kreiram sql upit kojim provjeravam postoji li LIJEK u DOPUNSKOJ LISTI lijekova
            $sqlDopunskaLista = "SELECT d.oblikJacinaPakiranjeLijek,d.dddLijek FROM dopunskalistalijekova d 
                            WHERE d.oblikJacinaPakiranjeLijek = '$ojpLijek' AND d.zasticenoImeLijek = '$imeLijek'";

            $resultDopunskaLista = $conn->query($sqlDopunskaLista);
            //Ako je lijek PRONAĐEN u DOPUNSKOJ LISTI lijekova
            if($resultDopunskaLista->num_rows > 0){
                while($rowDopunskaLista = $resultDopunskaLista->fetch_assoc()) {
                    //Dohvaćam oblik, jačinu i pakiranje unesenog lijeka
                    $oblikJacinaPakiranjeLijek = $rowDopunskaLista['oblikJacinaPakiranjeLijek'];
                    //Dohvaćam dnevno definiranu dozu
                    $dddLijek = $rowDopunskaLista['dddLijek'];
                }
                //Inicijaliziram tabletu na prazan string
                $tableta = "";
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
                    //Dohvaćam mjernu jedinicu OJP-a (g ili mg)
                    $mjernaJedinicaOJP = array_pop($ojp);
                    //Ako $tableta sadrži zarez npr. 30x3,5 g
                    if(strpos($tableta,",") !== false){
                        //Mijenjam zarez sa točkom da dobijem float s kojim mogu računati
                        $tableta = str_replace(",",".",$tableta);
                        //Dohvaćam dozu jedne tablete
                        $mgJednaTableta = (float)substr($tableta,strpos($tableta,"x")+1,strlen($tableta));
                    }
                    //Ako $tableta ne sadrži zarez npr. 20x150 mg
                    else{
                        //Dohvaćam dozu jedne tablete
                        $mgJednaTableta = (int)substr($tableta,strpos($tableta,"x")+1,strlen($tableta));
                    }
                    //Dohvaćam frekvenciju doziranja
                    $frekvencijaDoziranje = (int)substr($doza,0,strpos($doza,"x"));
                    //Dohvaćam period doziranja
                    $periodDoziranje = substr($doza,strpos($doza,"x")+1,strlen($doza));
                    //Dijelim dnevno definiranu dozu na njezin broj i mjernu jedinicu
                    $poljeDDD = explode(" ",$dddLijek);
                    //Dohvaćam mjernu jedinicu dnevno definirane doze
                    $mjernaJedinicaDDD = array_pop($poljeDDD);
                    //Dohvaćam broj dnevno definirane doze
                    $dddBroj = implode(" ", $poljeDDD);
                    //Zamjenjujem zarez sa točkom
                    $dddBroj = str_replace(',', '.', $dddBroj);
                    //Ako je mjerna jedinica DDD-a u "g" (u gramima), a mjerna jedinica OJP-a je "mg"
                    if($mjernaJedinicaDDD == "g" && $mjernaJedinicaOJP == "mg"){
                        //Pretvaram dnevno definiranu dozu u mg (miligrame)
                        $dddBroj = (float)$dddBroj*1000;
                        //Računam maksimalni broj tableta u danu npr. (300mg / 150mg => 2 tablete)
                        $maxDoza = ($dddBroj)/($mgJednaTableta);
                        //Ako vrijednost max doze NIJE integer 
                        if(!is_int($maxDoza)){
                            $maxDoza = round($maxDoza);
                        }
                        //Ako je period doziranja "dnevno":
                        if($periodDoziranje == "dnevno"){
                            //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                            if($frekvencijaDoziranje > $maxDoza){
                                $response["success"]="false";
                                $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]="Dnevno definirana doza: ".$maxDoza."xdnevno";
                            }
                            //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                            else{
                                $response["success"]="true";
                                $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]="Dnevno definirana doza: ".$maxDoza."xdnevno";
                            }
                        }
                        //Ako je period doziranja "tjedno":
                        else if($periodDoziranje == "tjedno"){
                            //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                            if($frekvencijaDoziranje > ($maxDoza * 7)){
                                $response["success"]="false";
                                $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]="Tjedno definirana doza: ".($maxDoza * 7)."xtjedno";
                            }
                            //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                            else{
                                $response["success"]="true";
                                $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]="Tjedno definirana doza: ".($maxDoza * 7)."xtjedno";
                            }
                        }
                    }
                    //Ako je mjerna jedinica DDD-a "g" (u gramima),a mjerna jedinica OJP-a u "g" (gramima)
                    else if($mjernaJedinicaDDD == "g" && $mjernaJedinicaOJP == "g"){
                        //Računam maksimalni broj tableta u danu npr. (300mg / 150mg => 2 tablete)
                        $maxDoza = ($dddBroj)/($mgJednaTableta);
                        //Ako vrijednost max doze NIJE integer 
                        if(!is_int($maxDoza)){
                            $maxDoza = round($maxDoza);
                        }
                        //Ako je period doziranja "dnevno":
                        if($periodDoziranje == "dnevno"){
                            //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                            if($frekvencijaDoziranje > $maxDoza){
                                $response["success"]="false";
                                $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]="Dnevno definirana doza: ".$maxDoza."xdnevno";
                            }
                            //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                            else{
                                $response["success"]="true";
                                $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]="Dnevno definirana doza: ".$maxDoza."xdnevno";
                            }
                        }
                        //Ako je period doziranja "tjedno":
                        else if($periodDoziranje == "tjedno"){
                            //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                            if($frekvencijaDoziranje > ($maxDoza * 7)){
                                $response["success"]="false";
                                $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]="Tjedno definirana doza: ".($maxDoza * 7)."xtjedno";
                            }
                            //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                            else{
                                $response["success"]="true";
                                $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]="Tjedno definirana doza: ".($maxDoza * 7)."xtjedno";
                            }
                        }
                    }
                    //Ako je mjerna jedinica DDD-a "mg" (u miligramima),a mjerna jedinica OJP-a u "mg" (miligramima)
                    else if($mjernaJedinicaDDD == "mg" && $mjernaJedinicaOJP == "mg"){
                        //Računam maksimalni broj tableta u danu npr. (300mg / 150mg => 2 tablete)
                        $maxDoza = ($dddBroj)/($mgJednaTableta);
                        //Ako vrijednost max doze NIJE integer 
                        if(!is_int($maxDoza)){
                            $maxDoza = round($maxDoza);
                        }
                        //Ako je period doziranja "dnevno":
                        if($periodDoziranje == "dnevno"){
                            //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                            if($frekvencijaDoziranje > $maxDoza){
                                $response["success"]="false";
                                $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]="Dnevno definirana doza: ".$maxDoza."xdnevno";
                            }
                            //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                            else{
                                $response["success"]="true";
                                $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]="Dnevno definirana doza: ".$maxDoza."xdnevno";
                            }
                        }
                        //Ako je period doziranja "tjedno":
                        else if($periodDoziranje == "tjedno"){
                            //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                            if($frekvencijaDoziranje > ($maxDoza * 7)){
                                $response["success"]="false";
                                $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]="Tjedno definirana doza: ".($maxDoza * 7)."xtjedno";
                            }
                            //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                            else{
                                $response["success"]="true";
                                $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]="Tjedno definirana doza: ".($maxDoza * 7)."xtjedno";
                            }
                        }
                    }
                }
                //Ako ojp lijeka NE ZAVRŠAVA NA mg ili g
                else{
                    //Vraćam null
                    return null;
                }
                //Završi petlju
                $pronasao = TRUE;
            }
        }
        //Inkrementiram brojač 
        $brojac++;
    }
    return $response;
}

$doza = "3xdnevno";
$lijek = "Colospa caps. retard 60x200 mg";
foreach(izracunajMaksimalnuDozu($lijek,$doza) as $element){
    echo $element;
}
?>