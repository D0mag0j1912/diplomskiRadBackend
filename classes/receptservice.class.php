<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class ReceptService{

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
                LIMIT 8";
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
                LIMIT 8";
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
                WHERE UPPER(l.zasticenoImeLijek) LIKE UPPER('%{$pretraga}%') 
                AND l.zasticenoImeLijek IS NOT NULL 
                AND l.oblikJacinaPakiranjeLijek IS NOT NULL 
                AND l.dddLijek IS NOT NULL 
                AND l.oznakaDopunskiLijek IS NOT NULL
                LIMIT 8";
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
                WHERE UPPER(l.zasticenoImeLijek) LIKE UPPER('%{$pretraga}%') 
                AND l.zasticenoImeLijek IS NOT NULL 
                AND l.oblikJacinaPakiranjeLijek IS NOT NULL 
                AND l.dddLijek IS NOT NULL 
                AND l.oznakaOsnovniLijek IS NOT NULL
                LIMIT 8";
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

    //Funkcija koja dohvaća pacijente na osnovu liječničke pretrage
    function dohvatiPacijentiPretraga($pretraga){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
 
        $sql = "SELECT p.imePacijent,p.prezPacijent, 
                DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS Datum,
                p.mboPacijent FROM pacijent p 
                WHERE UPPER(p.imePacijent) LIKE UPPER('%{$pretraga}%') 
                OR UPPER(p.prezPacijent) LIKE UPPER('%{$pretraga}%') OR UPPER(p.datRodPacijent) LIKE UPPER('%{$pretraga}%') 
                OR UPPER(p.mboPacijent) LIKE UPPER('%{$pretraga}%')
                ORDER BY p.prezPacijent ASC";
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
    //Funkcija koja dohvaća sve registrirane pacijente
    function dohvatiSvePacijente(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        //Kreiram sql upit koji će provjeriti koliko ima pacijenata u bazi podataka
        $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM pacijent";
        //Rezultat upita spremam u varijablu $resultCountPacijent
        $resultCountPacijent = mysqli_query($conn,$sqlCountPacijent);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCountPacijent) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCountPacijent = mysqli_fetch_assoc($resultCountPacijent)){
                //Vrijednost rezultata spremam u varijablu $brojPacijenata
                $brojPacijenata = $rowCountPacijent['BrojPacijent'];
            }
        }
        //Ako nema pronađenih pacijenata
        if($brojPacijenata == 0){
            $response["success"] = "false";
            $response["message"] = "Nema pronađenih pacijenata!";
        }
        //Ako ima pronađenih pacijenata
        else{
            //Kreiram upit koji dohvaća sve pacijente
            $sql = "SELECT p.imePacijent,p.prezPacijent, 
                    DATE_FORMAT(p.datRodPacijent,'%d.%m.%Y') AS Datum,
                    p.mboPacijent FROM pacijent p
                    ORDER BY p.prezPacijent ASC";
            
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }
        //Vraćam odgovor baze
        return $response;   
    }
}
?>