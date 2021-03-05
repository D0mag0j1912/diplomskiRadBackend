<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class ReceptService{

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
                                $response["maxDoza"]=$maxDoza."xdnevno";
                            }
                            //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                            else{
                                $response["success"]="true";
                                $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]=$maxDoza."xdnevno";
                            }
                        }
                        //Ako je period doziranja "tjedno":
                        else if($periodDoziranje == "tjedno"){
                            //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                            if($frekvencijaDoziranje > ($maxDoza * 7)){
                                $response["success"]="false";
                                $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]=($maxDoza * 7)."xtjedno";
                            }
                            //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                            else{
                                $response["success"]="true";
                                $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]=($maxDoza * 7)."xtjedno";
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
                                $response["maxDoza"]=$maxDoza."xdnevno";
                            }
                            //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                            else{
                                $response["success"]="true";
                                $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]=$maxDoza."xdnevno";
                            }
                        }
                        //Ako je period doziranja "tjedno":
                        else if($periodDoziranje == "tjedno"){
                            //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                            if($frekvencijaDoziranje > ($maxDoza * 7)){
                                $response["success"]="false";
                                $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]=($maxDoza * 7)."xtjedno";
                            }
                            //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                            else{
                                $response["success"]="true";
                                $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]=($maxDoza * 7)."xtjedno";
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
                                $response["maxDoza"]=$maxDoza."xdnevno";
                            }
                            //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                            else{
                                $response["success"]="true";
                                $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]=$maxDoza."xdnevno";
                            }
                        }
                        //Ako je period doziranja "tjedno":
                        else if($periodDoziranje == "tjedno"){
                            //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                            if($frekvencijaDoziranje > ($maxDoza * 7)){
                                $response["success"]="false";
                                $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]=($maxDoza * 7)."xtjedno";
                            }
                            //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                            else{
                                $response["success"]="true";
                                $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                $response["maxDoza"]=($maxDoza * 7)."xtjedno";
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
                                    $response["maxDoza"]=$maxDoza."xdnevno";
                                }
                                //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                                else{
                                    $response["success"]="true";
                                    $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                    $response["maxDoza"]=$maxDoza."xdnevno";
                                }
                            }
                            //Ako je period doziranja "tjedno":
                            else if($periodDoziranje == "tjedno"){
                                //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                                if($frekvencijaDoziranje > ($maxDoza * 7)){
                                    $response["success"]="false";
                                    $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                                    $response["maxDoza"]=($maxDoza * 7)."xtjedno";
                                }
                                //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                                else{
                                    $response["success"]="true";
                                    $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                    $response["maxDoza"]=($maxDoza * 7)."xtjedno";
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
                                    $response["maxDoza"]=$maxDoza."xdnevno";
                                }
                                //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                                else{
                                    $response["success"]="true";
                                    $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                    $response["maxDoza"]=$maxDoza."xdnevno";
                                }
                            }
                            //Ako je period doziranja "tjedno":
                            else if($periodDoziranje == "tjedno"){
                                //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                                if($frekvencijaDoziranje > ($maxDoza * 7)){
                                    $response["success"]="false";
                                    $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                                    $response["maxDoza"]=($maxDoza * 7)."xtjedno";
                                }
                                //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                                else{
                                    $response["success"]="true";
                                    $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                    $response["maxDoza"]=($maxDoza * 7)."xtjedno";
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
                                    $response["maxDoza"]=$maxDoza."xdnevno";
                                }
                                //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                                else{
                                    $response["success"]="true";
                                    $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                    $response["maxDoza"]=$maxDoza."xdnevno";
                                }
                            }
                            //Ako je period doziranja "tjedno":
                            else if($periodDoziranje == "tjedno"){
                                //Ako je unesena frekvencija doziranja veća od maksimalnog dnevnog broja tableta
                                if($frekvencijaDoziranje > ($maxDoza * 7)){
                                    $response["success"]="false";
                                    $response["message"]="Doziranje je prešlo dnevno definiranu dozu!";
                                    $response["maxDoza"]=($maxDoza * 7)."xtjedno";
                                }
                                //Ako je dnevna frekvencija doziranja u redu, tj. ne prelazi max dnevnu dozu
                                else{
                                    $response["success"]="true";
                                    $response["message"]="Doziranje nije prešlo dnevno definiranu dozu!";
                                    $response["maxDoza"]=($maxDoza * 7)."xtjedno";
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

    //Funkcija koja dohvaća inicijalne dijagnoze u unosu novog recepta
    function dohvatiInicijalneDijagnoze($idPacijent){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje
        $response = [];

        //Kreiram upit kojim dohvaćam ZADNJE UNESENU primarnu dijagnozu povijesti bolesti za određenog pacijenta
        $sqlZadnjaPrimarna = "SELECT pb.mkbSifraPrimarna FROM povijestbolesti pb
                            WHERE pb.idPovijestBolesti = 
                            (SELECT MAX(pb2.idPovijestBolesti) FROM povijestbolesti pb2
                            WHERE pb.mboPacijent = pb2.mboPacijent 
                            AND pb2.idRecept IS NULL)
                            AND pb.mboPacijent IN 
                            (SELECT pacijent.mboPacijent FROM pacijent 
                            WHERE pacijent.idPacijent = '$idPacijent')";
        $resultZadnjaPrimarna = $conn->query($sqlZadnjaPrimarna);
        //Ako postoji primarna dijagnoza zabilježena u povijesti bolesti za OVOG PACIJENTA
        if($resultZadnjaPrimarna->num_rows > 0){
            while($rowZadnjaPrimarna = $resultZadnjaPrimarna->fetch_assoc()) {
                //Dohvaćam tu primarnu dijagnozu
                $mkbPrimarnaDijagnoza = $rowZadnjaPrimarna['mkbSifraPrimarna'];
            }
        }
        //Ako NE POSTOJI primarna dijagnoza zabilježena u povijesti bolesti za OVOG PACIJENTA
        else{
            //Vrati null
            return null;
        } 

        //Ako POSTOJI primarna dijagnoza, kreiram upit koji dohvaća sve njezine sekundarne dijagoze
        $sql = "SELECT DISTINCT(d.imeDijagnoza) AS NazivPrimarna, 
                IF(pb.mkbSifraSekundarna = NULL, NULL, (SELECT d2.imeDijagnoza FROM dijagnoze d2 WHERE d2.mkbSifra = pb.mkbSifraSekundarna)) AS NazivSekundarna 
                ,pb.idObradaLijecnik FROM povijestBolesti pb 
                JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna
                WHERE pb.mkbSifraPrimarna = '$mkbPrimarnaDijagnoza'";
        $result = $conn->query($sql);
        //Ako ima rezultata
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $response[] = $row;
            }
        }
        return $response;
    }

    //Funkcija koja izračunava dostatnost lijeka
    function izracunajDostatnost($lijek,$kolicina,$doza,$brojPonavljanja){
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
                    //Ako je period doziranja "dnevno":
                    if($periodDoziranje == "dnevno"){
                        //Ako je broj ponavljanja 0 tj. ako je recept običan:
                        if(empty($brojPonavljanja)){
                            //Računam dostatnost u danima
                            $dostatnost = ($kolicina * $brojTableta) / ($frekvencijaDoziranje);
                        }
                        //Ako je broj ponavljanja > 0, tj. ako je recept ponovljiv
                        else{
                            //Računam dostatnost u danima
                            $dostatnost = ($kolicina * $brojTableta) / ($frekvencijaDoziranje) * ($brojPonavljanja + 1);
                        }
                        //Ako vrijednost dostatnosti NIJE integer 
                        if(!is_int($dostatnost)){
                            $dostatnost = round($dostatnost);
                        }
                    }
                    //Ako je period doziranja "tjedno":
                    else if($periodDoziranje == "tjedno"){
                        //Ako je broj ponavljanja 0 tj. ako je recept običan:
                        if(empty($brojPonavljanja)){
                            //Računam dostatnost u danima
                            $dostatnost = ($kolicina * $brojTableta) / ($frekvencijaDoziranje) * 7;   
                        }
                        //Ako je broj ponavljanja > 0, tj. ako je recept ponovljiv:
                        else{
                            //Računam dostatnost u danima
                            $dostatnost = ($kolicina * $brojTableta) / ($frekvencijaDoziranje) * 7 * ($brojPonavljanja + 1);
                        }
                        //Ako vrijednost dostatnosti NIJE integer 
                        if(!is_int($dostatnost)){
                            $dostatnost = round($dostatnost);
                        }
                    }
                }
                //Ako ojp lijeka NE ZAVRŠAVA NA mg ili g
                else{
                    //Ako je broj ponavljanja 0 tj. ako je recept običan:
                    if(empty($brojPonavljanja)){
                        //Inicijalno postavi trajanje terapije na 30 dana
                        $dostatnost = 30 * ($kolicina);  
                    }
                    //Ako je broj ponavljanja > 0, tj. ako je recept ponovljiv:
                    else{
                        //Izračunaj dostatnost
                        $dostatnost = 30 * ($kolicina) * ($brojPonavljanja + 1);
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
                        //Ako je period doziranja "dnevno":
                        if($periodDoziranje == "dnevno"){
                            //Ako je broj ponavljanja 0 tj. ako je recept običan:
                            if(empty($brojPonavljanja)){
                                //Računam dostatnost u danima
                                $dostatnost = ($kolicina * $brojTableta) / ($frekvencijaDoziranje);
                            }
                            //Ako je broj ponavljanja > 0, tj. ako je recept ponovljiv
                            else{
                                //Računam dostatnost u danima
                                $dostatnost = ($kolicina * $brojTableta) / ($frekvencijaDoziranje) * ($brojPonavljanja + 1);
                            }
                            //Ako vrijednost dostatnosti NIJE integer 
                            if(!is_int($dostatnost)){
                                $dostatnost = round($dostatnost);
                            }
                        }
                        //Ako je period doziranja "tjedno":
                        else if($periodDoziranje == "tjedno"){
                            //Ako je broj ponavljanja 0 tj. ako je recept običan:
                            if(empty($brojPonavljanja)){
                                //Računam dostatnost u danima
                                $dostatnost = ($kolicina * $brojTableta) / ($frekvencijaDoziranje) * 7;   
                            }
                            //Ako je broj ponavljanja > 0, tj. ako je recept ponovljiv:
                            else{
                                //Računam dostatnost u danima
                                $dostatnost = ($kolicina * $brojTableta) / ($frekvencijaDoziranje) * 7 * ($brojPonavljanja + 1);
                            }
                            //Ako vrijednost dostatnosti NIJE integer 
                            if(!is_int($dostatnost)){
                                $dostatnost = round($dostatnost);
                            }
                        }
                    }
                    //Ako ojp lijeka NE ZAVRŠAVA NA mg ili g
                    else{
                        //Ako je broj ponavljanja 0 tj. ako je recept običan:
                        if(empty($brojPonavljanja)){
                            //Inicijalno postavi trajanje terapije na 30 dana
                            $dostatnost = 30 * ($kolicina);  
                        }
                        //Ako je broj ponavljanja > 0, tj. ako je recept ponovljiv:
                        else{
                            //Izračunaj dostatnost
                            $dostatnost = 30 * ($kolicina) * ($brojPonavljanja + 1);
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

    //Funkcija koja računa do kada vrijedi dostatnost nekog proizvoda
    function dohvatiDatumDostatnost($dostatnost){
        //Trenutni datum
        $datum = date('d.m.Y');
        $vrijediDo = date('d.m.Y', strtotime($datum . ' +'.$dostatnost.' day'));
        return $vrijediDo;
    }

    //Funkcija koja dohvaća informaciju ima li izabrani MAGISTRALNI PRIPRAVAK oznaku "RS":
    function dohvatiOznakaMagPripravak($magPripravak){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];
        //Definiram oznaku
        $oznaka = "RS";

        //Provjeravam postoji li izabrani lijek u OSNOVNOJ LISTI magistralnih pripravaka
        $sqlCountOsnovnaLista = "SELECT COUNT(*) AS BrojOsnovnaLista FROM osnovnalistamagistralnihpripravaka 
                                WHERE nazivMagPripravak = '$magPripravak'";
        //Rezultat upita spremam u varijablu $resultCountOsnovnaLista
        $resultCountOsnovnaLista = mysqli_query($conn,$sqlCountOsnovnaLista);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCountOsnovnaLista) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCountOsnovnaLista = mysqli_fetch_assoc($resultCountOsnovnaLista)){
                //Vrijednost rezultata spremam u varijablu $brojOsnovnaLista
                $brojOsnovnaLista= $rowCountOsnovnaLista['BrojOsnovnaLista'];
            }
        } 
        //Ako JE PRONAĐEN izabrani mag. pripravak u OSNOVNOJ LISTI
        if($brojOsnovnaLista > 0){
            //Kreiram upit koji će provjeriti je li izabrani MAGISTRALNI PRIPRAVAK ima oznaku RS
            $sqlCount = "SELECT COUNT(*) AS BrojRS FROM osnovnalistamagistralnihpripravaka
                        WHERE nazivMagPripravak = '$magPripravak' 
                        AND oznakaMagPripravak = '$oznaka'";
            //Rezultat upita spremam u varijablu $resultCount
            $resultCount = mysqli_query($conn,$sqlCount);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($resultCount) > 0){
                //Idem redak po redak rezultata upita 
                while($rowCount= mysqli_fetch_assoc($resultCount)){
                    //Vrijednost rezultata spremam u varijablu $brojRS
                    $brojRS = $rowCount['BrojRS'];
                }
            }
            //Ako magistralni pripravak IMA oznaku RS:
            if($brojRS > 0){
                $response["success"] = "true";
                $response["lista"] = "osnovna";
            }
            //Ako magistralni pripravak NEMA oznaku RS:
            else{
                $response["success"] = "false";
                $response["lista"] = "osnovna";
            }
        }
        //Ako NIJE PRONAĐEN izabrani mag. pripravak u OSNOVNOJ LISTI
        else{
            //Počinjem tražiti u DOPUNSKOJ LISTI
            $sqlCount = "SELECT COUNT(*) AS BrojRS FROM dopunskalistamagistralnihpripravaka
                        WHERE nazivMagPripravak = '$magPripravak' 
                        AND oznakaMagPripravak = '$oznaka'";
            //Rezultat upita spremam u varijablu $resultCount
            $resultCount = mysqli_query($conn,$sqlCount);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($resultCount) > 0){
                //Idem redak po redak rezultata upita 
                while($rowCount= mysqli_fetch_assoc($resultCount)){
                    //Vrijednost rezultata spremam u varijablu $brojRS
                    $brojRS = $rowCount['BrojRS'];
                }
            }
            //Ako magistralni pripravak IMA oznaku RS:
            if($brojRS > 0){
                $response["success"] = "true";
                $response["lista"] = "dopunska";
            }
            //Ako magistralni pripravak NEMA oznaku RS:
            else{
                $response["success"] = "false";
                $response["lista"] = "dopunska";
            }
        }
        return $response;
    }

    //Funkcija koja dohvaća informaciju ima li izabrani LIJEK oznaku "RS"
    function dohvatiOznakaLijek($lijek){
        //Kreiram objekt tipa "Baza"
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $brojac = 2;
        //Na početku označavam da nisam pronašao izabrani lijek
        $pronasao = FALSE;
        $oznaka = "RS";
        //Dok ga nisam pronašao
        while($pronasao !== TRUE){
            //Splitam string da mu uzmem ime i oblik-jačinu-pakiranje (KREĆEM OD 2)
            $polje = explode(" ",$lijek,$brojac);
            //Dohvaćam oblik,jačinu i pakiranje lijeka
            $ojpLijek = array_pop($polje);
            //Dohvaćam ime lijeka
            $imeLijek = implode(" ", $polje);
            //Provjeravam postoji li izabrani lijek u OSNOVNOJ LISTI lijekova
            $sqlCountOsnovnaLista = "SELECT COUNT(*) AS BrojOsnovnaLista FROM osnovnalistalijekova 
                    WHERE zasticenoImeLijek = '$imeLijek' 
                    AND oblikJacinaPakiranjeLijek = '$ojpLijek';";
            //Rezultat upita spremam u varijablu $resultCountOsnovnaLista
            $resultCountOsnovnaLista = mysqli_query($conn,$sqlCountOsnovnaLista);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($resultCountOsnovnaLista) > 0){
                //Idem redak po redak rezultata upita 
                while($rowCountOsnovnaLista = mysqli_fetch_assoc($resultCountOsnovnaLista)){
                    //Vrijednost rezultata spremam u varijablu $brojOsnovnaLista
                    $brojOsnovnaLista= $rowCountOsnovnaLista['BrojOsnovnaLista'];
                }
            } 
            //Ako je pronađen izabrani LIJEK u OSNOVNOJ LISTI lijekova
            if($brojOsnovnaLista > 0){
                //Završi petlju
                $pronasao = TRUE;
                //Kreiram upit koji će provjeriti je li izabrani LIJEK ima oznaku RS
                $sqlCount = "SELECT COUNT(*) AS BrojRS FROM osnovnalistalijekova 
                            WHERE zasticenoImeLijek = '$imeLijek' 
                            AND oblikJacinaPakiranjeLijek = '$ojpLijek' 
                            AND oznakaOsnovniLijek = '$oznaka'";
                //Rezultat upita spremam u varijablu $resultCount
                $resultCount = mysqli_query($conn,$sqlCount);
                //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                if(mysqli_num_rows($resultCount) > 0){
                    //Idem redak po redak rezultata upita 
                    while($rowCount= mysqli_fetch_assoc($resultCount)){
                        //Vrijednost rezultata spremam u varijablu $brojRS
                        $brojRS = $rowCount['BrojRS'];
                    }
                }
                //Ako lijek IMA oznaku RS:
                if($brojRS > 0){
                    $response["success"] = "true";
                    $response["lista"] = "osnovna";
                }
                //Ako lijek NEMA oznaku RS:
                else{
                    $response["success"] = "false";
                    $response["lista"] = "osnovna";
                }
            }
            //Provjeravam postoji li izabrani lijek u DOPUNSKOJ LISTI lijekova 
            $sqlCountDopunskaLista = "SELECT COUNT(*) AS BrojDopunskaLista FROM dopunskalistalijekova 
                    WHERE zasticenoImeLijek = '$imeLijek' 
                    AND oblikJacinaPakiranjeLijek = '$ojpLijek';";
            //Rezultat upita spremam u varijablu $resultCountDopunskaLista
            $resultCountDopunskaLista = mysqli_query($conn,$sqlCountDopunskaLista);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($resultCountDopunskaLista) > 0){
                //Idem redak po redak rezultata upita 
                while($rowCountDopunskaLista = mysqli_fetch_assoc($resultCountDopunskaLista)){
                    //Vrijednost rezultata spremam u varijablu $brojOsnovnaLista
                    $brojDopunskaLista = $rowCountDopunskaLista['BrojDopunskaLista'];
                }
            } 
            if($brojDopunskaLista > 0){
                //Završi petlju
                $pronasao = TRUE;
                //Kreiram upit koji će provjeriti je li izabrani LIJEK ima oznaku RS
                $sqlCount = "SELECT COUNT(*) AS BrojRS FROM dopunskalistalijekova 
                            WHERE zasticenoImeLijek = '$imeLijek' 
                            AND oblikJacinaPakiranjeLijek = '$ojpLijek' 
                            AND oznakaDopunskiLijek = '$oznaka'";
                //Rezultat upita spremam u varijablu $resultCount
                $resultCount = mysqli_query($conn,$sqlCount);
                //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                if(mysqli_num_rows($resultCount) > 0){
                    //Idem redak po redak rezultata upita 
                    while($rowCount= mysqli_fetch_assoc($resultCount)){
                        //Vrijednost rezultata spremam u varijablu $brojRS
                        $brojRS = $rowCount['BrojRS'];
                    }
                }
                //Ako lijek IMA oznaku RS:
                if($brojRS > 0){
                    $response["success"] = "true";
                    $response["lista"] = "dopunska";
                }
                //Ako lijek NEMA oznaku RS:
                else{
                    $response["success"] = "false";
                    $response["lista"] = "dopunska";
                }
            }
            //Povećavam brojač
            $brojac++;
        }
        return $response;
    }

    //Funkcija koja dohvaća cijene za MAGISTRALNI PRIPRAVAK sa DOPUNSKE LISTE
    function dohvatiCijenaMagPripravakDL($magPripravak){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];

        $sql = "SELECT d.cijenaMagPripravak,d.cijenaZavod,d.doplataMagPripravak FROM dopunskalistamagistralnihpripravaka d 
                WHERE d.nazivMagPripravak = '$magPripravak'";
        $result = $conn->query($sql);

        //Ako ima pronađenih rezultata za navedenu pretragu
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        return $response;
    }
    //Funkcija koja dohvaća cijene sa osnovu izabranog LIJEKA sa DOPUNSKE LISTE
    function dohvatiCijenaLijekDL($lijek){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
    
        //Kreiram prazno polje odgovora
        $response = [];
        //Inicijalno postavljam brojač na 2
        $brojac = 2;
        //Na početku označavam da nisam pronašao izabrani lijek
        $pronasao = FALSE;
        //Dok ga nisam pronašao
        while($pronasao !== TRUE){
            //Splitam string da mu uzmem ime i oblik-jačinu-pakiranje (KREĆEM OD 2)
            $polje = explode(" ",$lijek,$brojac);
            //Dohvaćam oblik,jačinu i pakiranje lijeka
            $ojpLijek = array_pop($polje);
            //Dohvaćam ime lijeka
            $imeLijek = implode(" ", $polje);
            $sql = "SELECT d.cijenaLijek,d.cijenaZavod,d.doplataLijek FROM dopunskalistalijekova d 
                    WHERE d.zasticenoImeLijek = '$imeLijek' 
                    AND d.oblikJacinaPakiranjeLijek = '$ojpLijek'";
            $result = $conn->query($sql);
        
            //Ako ima pronađenih rezultata za navedenu pretragu
            if ($result->num_rows > 0) {
                //Izađi iz petlje
                $pronasao = TRUE;
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
            //Inkrementiram brojač za 1
            $brojac++;
        }
        return $response;
    }

    //Funkcija koja dohvaća MAGISTRALNE PRIPRAVKE sa DOPUNSKE LISTE na osnovu liječničke pretrage
    function dohvatiMagPripravciDopunskaListaPretraga($pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        $sql = "SELECT DISTINCT(m.nazivMagPripravak) AS nazivMagPripravak FROM dopunskalistamagistralnihpripravaka m  
                WHERE UPPER(m.nazivMagPripravak) LIKE UPPER('%{$pretraga}%')
                LIMIT 7";
        $result = $conn->query($sql);

        //Ako ima pronađenih rezultata za navedenu pretragu
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Ako nema pronađenih rezultata za ovu pretragu
        else{
            $response["success"] = "false";
            $response["message"] = "Nema pronađenih rezultata za ključnu riječ: ".$pretraga;
        } 
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća MAGISTRALNE PRIPRAVKE sa OSNOVNE LISTE na osnovu liječničke pretrage
    function dohvatiMagPripravciOsnovnaListaPretraga($pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        $sql = "SELECT m.nazivMagPripravak FROM osnovnalistamagistralnihpripravaka m  
                WHERE UPPER(m.nazivMagPripravak) LIKE UPPER('%{$pretraga}%') 
                AND m.oznakaMagPripravak IS NOT NULL
                LIMIT 7";
        $result = $conn->query($sql);

        //Ako ima pronađenih rezultata za navedenu pretragu
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Ako nema pronađenih rezultata za ovu pretragu
        else{
            $response["success"] = "false";
            $response["message"] = "Nema pronađenih rezultata za ključnu riječ: ".$pretraga;
        } 
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća lijekove sa dopunske liste na osnovu liječničke pretrage
    function dohvatiLijekoviDopunskaListaPretraga($pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        $sql = "SELECT CONCAT(l.zasticenoImeLijek,' ',l.oblikJacinaPakiranjeLijek) AS zasticenoImeLijek,l.proizvodacLijek FROM dopunskalistalijekova l 
                WHERE CONCAT(l.zasticenoImeLijek,' ',l.oblikJacinaPakiranjeLijek) LIKE UPPER('%{$pretraga}%') 
                AND l.zasticenoImeLijek IS NOT NULL 
                AND l.oblikJacinaPakiranjeLijek IS NOT NULL 
                AND l.dddLijek IS NOT NULL 
                AND l.oznakaDopunskiLijek IS NOT NULL
                LIMIT 7";
        $result = $conn->query($sql);

        //Ako ima pronađenih rezultata za navedenu pretragu
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Ako nema pronađenih rezultata za ovu pretragu
        else{
            $response["success"] = "false";
            $response["message"] = "Nema pronađenih rezultata za ključnu riječ: ".$pretraga;
        } 
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća lijekove sa osnovne liste na osnovu liječničke pretrage
    function dohvatiLijekoviOsnovnaListaPretraga($pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        $sql = "SELECT CONCAT(l.zasticenoImeLijek,' ',l.oblikJacinaPakiranjeLijek) AS zasticenoImeLijek,l.proizvodacLijek FROM osnovnalistalijekova l 
                WHERE CONCAT(l.zasticenoImeLijek,' ',l.oblikJacinaPakiranjeLijek) LIKE UPPER('%{$pretraga}%') 
                AND l.zasticenoImeLijek IS NOT NULL 
                AND l.oblikJacinaPakiranjeLijek IS NOT NULL 
                AND l.dddLijek IS NOT NULL 
                AND l.oznakaOsnovniLijek IS NOT NULL
                LIMIT 7";
        $result = $conn->query($sql);

        //Ako ima pronađenih rezultata za navedenu pretragu
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Ako nema pronađenih rezultata za ovu pretragu
        else{
            $response["success"] = "false";
            $response["message"] = "Nema pronađenih rezultata za ključnu riječ: ".$pretraga;
        } 
        //Vraćam odgovor baze
        return $response;
    }
}
?>