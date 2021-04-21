<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class PovijestBolestiService{

    //Funkcija koja dohvaća zadnje uneseni ID povijesti bolesti
    function getIDPovijestBolesti($mboPacijent,$idObrada,$mkbSifraPrimarna){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];
          
        $sql = "SELECT pb.idPovijestBolesti,pb.bojaPregled FROM povijestBolesti pb 
            WHERE pb.mboPacijent = '$mboPacijent' 
            AND pb.idObradaLijecnik = '$idObrada' 
            AND TRIM(pb.mkbSifraPrimarna) = '$mkbSifraPrimarna' 
            AND pb.idPovijestBolesti = 
            (SELECT MAX(pb2.idPovijestBolesti) FROM povijestBolesti pb2 
            WHERE pb2.mboPacijent = '$mboPacijent' 
            AND pb2.idObradaLijecnik = '$idObrada' 
            AND TRIM(pb2.mkbSifraPrimarna) = '$mkbSifraPrimarna')";
        //Rezultat upita spremam u varijablu $resultMBO
        $result = mysqli_query($conn,$sql);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($result) > 0){
            //Idem redak po redak rezultata upita 
            while($row = mysqli_fetch_assoc($result)){
                $response[] = $row;
            }
        } 
        return $response;
    }

    //Kreiram funkciju koja će potvrditi povijest bolesti
    function potvrdiPovijestBolesti($idLijecnik,$idPacijent,$mboPacijent,$razlogDolaska,$anamneza,$status,
                                    $nalaz,$mkbPrimarnaDijagnoza,$mkbSifre,$tipSlucaj,
                                    $terapija,$preporukaLijecnik,$napomena,$idObrada, 
                                    $prosliPregled,$proslaBoja){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Kreiram prazno polje odgovora
        $response = [];
        //Trenutni datum
        $datum = date('Y-m-d');
        //Trenutno vrijeme za naručivanje
        $vrijeme = date('H:i:s');
        //Trenutno vrijeme za pregled
        $vrijemePregled = date("H:i:s");
        //Status pacijenta
        $statusObrada = "Aktivan";
        //Inicijaliziram polje random boja 
        $poljeBoja = ['#006400','#C71585','#BDB76B','#40E0D0','#000000','#4B0082','#48D1CC','#D2691E','#FF0000'];
        //Varijabla koja određuje je li pacijent naručen ili nije
        $narucen = NULL;
        //Označavam da slučajno generirana oznaka već postoji u bazi
        $ispravnaOznaka = false;
        while($ispravnaOznaka != true){
            //Generiram slučajni oznaku po kojom grupiram
            $oznaka = uniqid();
            //Kreiram upit koji provjerava postoji li već ova random generirana oznaka u bazi
            $sqlProvjeraOznaka = "SELECT pb.oznaka FROM povijestBolesti pb 
                                WHERE pb.oznaka = '$oznaka';";
            //Rezultat upita spremam u varijablu $resultProvjeraOznaka
            $resultProvjeraOznaka = mysqli_query($conn,$sqlProvjeraOznaka);
            //Ako se novo generirana oznaka NE NALAZI u bazi
            if(mysqli_num_rows($resultProvjeraOznaka) == 0){
                //Izlazim iz petlje
                $ispravnaOznaka = true;
            } 
        }
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
            $vrijeme = date("H:i:s", strtotime("-".(int)(date('i',strtotime($vrijeme)))." minutes", strtotime($vrijeme) ) );  
        }
        //Ako su minute vremena >= 15 && minute < 30, zaokruži na pola sata 
        else if( (int)(date('i',strtotime($vrijeme))) >= 15 && (int)(date('i',strtotime($vrijeme))) < 30){
            $vrijeme = date("H:i:s", strtotime("+".(30-(int)(date('i',strtotime($vrijeme))))." minutes",strtotime($vrijeme) ) );
        }
        //Ako su minute vremena > 30 && minute < 45, zaokruži na pola sata
        else if( (int)(date('i',strtotime($vrijeme))) > 30 && (int)(date('i',strtotime($vrijeme))) < 45){
            $vrijeme = date("H:i:s", strtotime("-".((int)(date('i',strtotime($vrijeme)))-30)." minutes", strtotime($vrijeme) ) );
        }
        //Ako su minute vremena >=45 && minute < 60, zaokruži na veći puni sat
        else if( (int)(date('i',strtotime($vrijeme))) >= 45 && (int)(date('i',strtotime($vrijeme))) < 60){
            $vrijeme = date("H:i:s", strtotime("+".(60-(int)(date('i',strtotime($vrijeme))))." minutes",strtotime($vrijeme) ) );
        }
        //Kreiram sql upit koji će provjeriti JE LI TRENUTNO AKTIVNI PACIJENT NARUČEN U OVO VRIJEME NA OVAJ DATUM
        $sqlCountNarucen = "SELECT COUNT(*) AS BrojNarucen FROM narucivanje n 
                            WHERE n.idPacijent = '$idPacijent' 
                            AND n.vrijemeNarucivanje = '$vrijeme' 
                            AND n.datumNarucivanje = '$datum'";
        //Rezultat upita spremam u varijablu $resultCountPacijent
        $resultCountNarucen = mysqli_query($conn,$sqlCountNarucen);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCountNarucen) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCountNarucen = mysqli_fetch_assoc($resultCountNarucen)){
                //Vrijednost rezultata spremam u varijablu $brojNarudzba
                $brojNarucen = $rowCountNarucen['BrojNarucen'];
            }
        }

        //Ako pacijent nije pronađen u listi naručenih za to vrijeme i za taj datum
        if($brojNarucen == 0){
            //Postavljam varijablu
            $narucen = "Ne";
        }
        else{
            $narucen = "Da";
        }
        //Ako pacijent nije aktivan u obradi, generiram RANDOM broj koji će predstavljati tu obradu
        if(empty($idObrada)){
            //Označavam da slučajno generirani ID obrade već postoji u bazi
            $ispravan = false;
            while($ispravan != true){
                //Generiram random broj
                $idObrada = rand(1000,10000);
                //Kreiram upit koji provjerava postoji li već ovaj random generirani broj u bazi za ovog pacijenta
                $sqlProvjeraObrada = "SELECT pb.idObradaLijecnik FROM povijestBolesti pb 
                                    WHERE pb.idObradaLijecnik = '$idObrada'";
                //Rezultat upita spremam u varijablu $resultProvjeraObrada
                $resultProvjeraObrada = mysqli_query($conn,$sqlProvjeraObrada);
                //Ako se novo generirani slučajni ID obrade NE NALAZI u bazi
                if(mysqli_num_rows($resultProvjeraObrada) == 0){
                    //Izlazim iz petlje
                    $ispravan = true;
                } 
            }
        }
        //Ako je novi slučaj
        if($tipSlucaj == "noviSlucaj"){
            /******************************** */
            //Provjera je li postoji već ova primarna dijagnoza u bazi
            $sqlProvjera = "SELECT TRIM(pb.mkbSifraPrimarna) AS mkbSifraPrimarna FROM povijestBolesti pb 
                            WHERE pb.idObradaLijecnik = '$idObrada' 
                            AND pb.mboPacijent IN 
                            (SELECT pacijent.mboPacijent FROM pacijent 
                            WHERE pacijent.idPacijent = '$idPacijent')";
            //Rezultat upita spremam u varijablu $resultProvjera
            $resultProvjera = mysqli_query($conn,$sqlProvjera);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($resultProvjera) > 0){
                //Idem redak po redak rezultata upita 
                while($rowProvjera = mysqli_fetch_assoc($resultProvjera)){
                    if($mkbPrimarnaDijagnoza == $rowProvjera['mkbSifraPrimarna']){
                        $response["success"] = "false";
                        $response["message"] = "Već ste unijeli ovu primarnu dijagnozu u ovoj sesiji obrade!";
                        return $response;
                    }
                }
            }
            /******************************** */
        }
        //Ako je polje sekundarnih dijagnoza prazno
        if(empty($mkbSifre)){
            //Kreiram upit za spremanje podataka u bazu
            $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                    nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                    preporukaLijecnik, napomena, datum, narucen, mboPacijent,idObradaLijecnik, 
                    vrijeme,prosliPregled,bojaPregled,oznaka) 
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            //Kreiranje prepared statementa
            $stmt = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmt,$sql)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement ne valja!";
            }
            //Ako je prepared statement u redu
            else{
                //Ako je slučaj novi:
                if(empty($prosliPregled)){
                    $prosliPregled = NULL;
                }
                //Ako je "proslaBoja" prazna, to znači da se generira novi slučaj
                if(empty($proslaBoja)){
                    //Na početku inicijaliziram broj pronađenih boja na 0 (u slučaju da ne postoji još ova obrada i boja)
                    $brojBoja = 0;
                    //Generiram random boju
                    $boja = $poljeBoja[array_rand($poljeBoja)];
                    //Tražim je li se novo generirana boja nalazi u bazi
                    $sql = "SELECT COUNT(*) AS brojBoja FROM povijestBolesti pb 
                            WHERE pb.idObradaLijecnik = '$idObrada' AND pb.bojaPregled = '$boja'";
                    $result = $conn->query($sql);
                    //Ako ima pronađenih rezultata za navedenu pretragu
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $brojBoja = $row['brojBoja'];
                        }
                    }
                    //Dok ne pronađem boju koja još ne postoji u bazi za ovu sesiju obrade
                    while($brojBoja != 0){
                        //Generiraj ponovno boju
                        $boja = $poljeBoja[array_rand($poljeBoja)];
                        //Ponovno traži
                        $sql = "SELECT COUNT(*) AS brojBoja FROM povijestBolesti pb 
                                WHERE pb.idObradaLijecnik = '$idObrada' AND pb.bojaPregled = '$boja'";
                        $result = $conn->query($sql);
                        //Ako ima pronađenih rezultata za navedenu pretragu
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $brojBoja = $row['brojBoja'];
                            }
                        }
                    }
                }
                //Ako "proslaBoja" nije prazna, to znači da je slučaj povezan
                else{
                    $boja = $proslaBoja;
                }
                $response[] = "Nova boja: ".$boja;
                //Postavljam sek. dijagnozu na NULL
                $sekDijagnoza = NULL;
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
                mysqli_stmt_bind_param($stmt,"sssssssssssssisiss",$razlogDolaska,$anamneza,$status,$nalaz,$mkbPrimarnaDijagnoza,$sekDijagnoza,
                                                $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen,$mboPacijent,$idObrada, 
                                                $vrijemePregled,$prosliPregled,$boja,$oznaka);
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

                    $response["success"] = "true";
                    $response["message"] = "Podatci uspješno dodani!";
                } 
            }
            return $response;
        }
        //Ako polje sekundarnih dijagnoza nije prazno
        else{
            //Inicijaliziram brojač iteracija da onemogućim unos različitih boja
            $brojac = 0;
            //Prolazim kroz polje sekundarnih dijagnoza i za svaku sekundarnu dijagnoze ubacivam novu n-torku u bazu
            foreach($mkbSifre as $mkb){  
                //Poveaćam brojač za 1
                $brojac++;
                //Kreiram upit za spremanje prvog dijela podataka u bazu
                $sql = "INSERT INTO povijestBolesti (razlogDolaska, anamneza, statusPacijent, 
                                                    nalaz, mkbSifraPrimarna, mkbSifraSekundarna, tipSlucaj, terapija,
                                                    preporukaLijecnik, napomena, datum, narucen, mboPacijent, 
                                                    idObradaLijecnik,vrijeme,prosliPregled,bojaPregled,oznaka) 
                                                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                //Kreiranje prepared statementa
                $stmt = mysqli_stmt_init($conn);
                //Ako je statement neuspješan
                if(!mysqli_stmt_prepare($stmt,$sql)){
                    $response["success"] = "false";
                    $response["message"] = "Prepared statement ne valja!";
                }
                //Ako je prepared statement u redu
                else{
                    //Ako je slučaj novi:
                    if(empty($prosliPregled)){
                        $prosliPregled = NULL;
                    }
                    //Ako je "proslaBoja" prazna, to znači da se generira novi slučaj
                    if(empty($proslaBoja)){
                        //Samo ako je prva iteracija
                        if($brojac == 1){
                            //Na početku inicijaliziram broj pronađenih boja na 0 (u slučaju da ne postoji još ova obrada i boja)
                            $brojBoja = 0;
                            //Generiram random boju
                            $boja = $poljeBoja[array_rand($poljeBoja)];
                            //Tražim je li se novo generirana boja nalazi u bazi
                            $sql = "SELECT COUNT(*) AS brojBoja FROM povijestBolesti pb 
                                    WHERE pb.idObradaLijecnik = '$idObrada' AND pb.bojaPregled = '$boja'";
                            $result = $conn->query($sql);
                            //Ako ima pronađenih rezultata za navedenu pretragu
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $brojBoja = $row['brojBoja'];
                                }
                            }
                            //Dok ne pronađem boju koja još ne postoji u bazi za ovu sesiju obrade
                            while($brojBoja != 0){
                                //Generiraj ponovno boju
                                $boja = $poljeBoja[array_rand($poljeBoja)];
                                //Ponovno traži
                                $sql = "SELECT COUNT(*) AS brojBoja FROM povijestBolesti pb 
                                        WHERE pb.idObradaLijecnik = '$idObrada' AND pb.bojaPregled = '$boja'";
                                $result = $conn->query($sql);
                                //Ako ima pronađenih rezultata za navedenu pretragu
                                if ($result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        $brojBoja = $row['brojBoja'];
                                    }
                                }
                            }
                        }
                    }
                    //Ako "proslaBoja" nije prazna, to znači da je slučaj povezan
                    else{
                        $boja = $proslaBoja;
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
                    mysqli_stmt_bind_param($stmt,"sssssssssssssisiss",$razlogDolaska,$anamneza,$status,$nalaz,$mkbPrimarnaDijagnoza,$mkb,
                                                                $tipSlucaj,$terapija,$preporukaLijecnik,$napomena,$datum,$narucen, 
                                                                $mboPacijent,$idObrada,$vrijemePregled,$prosliPregled,$boja,$oznaka);
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

                        $response["success"] = "true";
                        $response["message"] = "Podatci uspješno dodani!";
                    }
                }
            }
            return $response; 
        }
    }
}
?>