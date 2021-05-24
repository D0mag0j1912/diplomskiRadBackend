<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';
//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class NarucivanjeService{

    //Funkcija koja vraća polje u kojemu se nalaze DATUMI I NAZIVI DANA ZA IZABRANI DATUM
    function dohvatiNovoStanjeDatumiNazivi($datum){
        $response = [];

        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        
        //Kreiram upit koji dohvaća sve DATUME I NAZIVE DANA TIH DATUMA 
        $sql = "SELECT DATE_FORMAT(datum,'%d.%m.%Y') AS Datum, 
                CASE 
                    WHEN nazivDana = 'Sunday' THEN 'Nedjelja'
                    WHEN nazivDana = 'Monday' THEN 'Ponedjeljak'
                    WHEN nazivDana = 'Tuesday' THEN 'Utorak'
                    WHEN nazivDana = 'Wednesday' THEN 'Srijeda'
                    WHEN nazivDana = 'Thursday' THEN 'Četvrtak'
                    WHEN nazivDana = 'Friday' THEN 'Petak'
                    WHEN nazivDana = 'Saturday' THEN 'Subota' 
                END AS NazivDana
                FROM datumi 
                WHERE YEARWEEK(datum,1) = YEARWEEK('$datum',1) AND 
                WEEKDAY(datum) IN(0,1,2,3,4)";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        
        return $response;
    }

    //Funkcija koja dohvaća NOVO STANJE TABLICE NAKON ODABIRA DATUMA
    function dohvatiNovoStanje($datum){
        $response = [];

        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        $sql = "SELECT DATE_FORMAT(v.vrijeme,'%H:%i') AS Vrijeme, 
                (SELECT CONCAT(p.imePacijent,' ',p.prezPacijent,' ',p.mboPacijent,' ',n.idNarucivanje,' ',vp.bojaPregled) FROM pacijent p 
                                            JOIN narucivanje n ON n.idPacijent = p.idPacijent 
                                            JOIN vremena v2 ON v2.vrijeme = n.vrijemeNarucivanje 
                                            JOIN datumi d ON d.datum = n.datumNarucivanje
                                            JOIN vrsta_pregled vp ON vp.idVrstaPregled = n.idVrstaPregled
                                            WHERE v2.vrijeme = v.vrijeme AND d.nazivDana = 'Monday' AND 
                                            YEARWEEK(d.datum,1) = YEARWEEK('$datum',1)) AS Ponedjeljak,  
                (SELECT CONCAT(p.imePacijent,' ',p.prezPacijent,' ',p.mboPacijent,' ',n.idNarucivanje,' ',vp.bojaPregled) FROM pacijent p 
                                            JOIN narucivanje n ON n.idPacijent = p.idPacijent 
                                            JOIN vremena v2 ON v2.vrijeme = n.vrijemeNarucivanje 
                                            JOIN datumi d ON d.datum = n.datumNarucivanje
                                            JOIN vrsta_pregled vp ON vp.idVrstaPregled = n.idVrstaPregled
                                            WHERE v2.vrijeme = v.vrijeme AND d.nazivDana = 'Tuesday' AND 
                                            YEARWEEK(d.datum,1) = YEARWEEK('$datum',1)) AS Utorak, 
                (SELECT CONCAT(p.imePacijent,' ',p.prezPacijent,' ',p.mboPacijent,' ',n.idNarucivanje,' ',vp.bojaPregled) FROM pacijent p 
                                            JOIN narucivanje n ON n.idPacijent = p.idPacijent 
                                            JOIN vremena v2 ON v2.vrijeme = n.vrijemeNarucivanje 
                                            JOIN datumi d ON d.datum = n.datumNarucivanje
                                            JOIN vrsta_pregled vp ON vp.idVrstaPregled = n.idVrstaPregled
                                            WHERE v2.vrijeme = v.vrijeme AND d.nazivDana = 'Wednesday' AND 
                                            YEARWEEK(d.datum,1) = YEARWEEK('$datum',1)) AS Srijeda,
                (SELECT CONCAT(p.imePacijent,' ',p.prezPacijent,' ',p.mboPacijent,' ',n.idNarucivanje,' ',vp.bojaPregled) FROM pacijent p 
                                            JOIN narucivanje n ON n.idPacijent = p.idPacijent 
                                            JOIN vremena v2 ON v2.vrijeme = n.vrijemeNarucivanje 
                                            JOIN datumi d ON d.datum = n.datumNarucivanje
                                            JOIN vrsta_pregled vp ON vp.idVrstaPregled = n.idVrstaPregled
                                            WHERE v2.vrijeme = v.vrijeme AND d.nazivDana = 'Thursday' AND 
                                            YEARWEEK(d.datum,1) = YEARWEEK('$datum',1)) AS Četvrtak,
                (SELECT CONCAT(p.imePacijent,' ',p.prezPacijent,' ',p.mboPacijent,' ',n.idNarucivanje,' ',vp.bojaPregled) FROM pacijent p 
                                            JOIN narucivanje n ON n.idPacijent = p.idPacijent 
                                            JOIN vremena v2 ON v2.vrijeme = n.vrijemeNarucivanje 
                                            JOIN datumi d ON d.datum = n.datumNarucivanje
                                            JOIN vrsta_pregled vp ON vp.idVrstaPregled = n.idVrstaPregled
                                            WHERE v2.vrijeme = v.vrijeme AND d.nazivDana = 'Friday' AND 
                                            YEARWEEK(d.datum,1) = YEARWEEK('$datum',1)) AS Petak
                FROM vremena v 
                LEFT JOIN narucivanje n ON n.vrijemeNarucivanje = v.vrijeme
                GROUP BY Vrijeme
                ORDER BY Vrijeme;"; 

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }

        //Vraćam response
        return $response;
    }

    //Funkcija koja vraća polje u kojemu se nalaze DATUMI I NAZIVI DANA 
    function dohvatiNaziveDana(){
        $response = [];

        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        
        //Kreiram upit koji dohvaća sve DATUME I NAZIVE DANA TIH DATUMA 
        $sql = "SELECT DATE_FORMAT(datum,'%d.%m.%Y') AS Datum, 
                CASE 
                    WHEN nazivDana = 'Sunday' THEN 'Nedjelja'
                    WHEN nazivDana = 'Monday' THEN 'Ponedjeljak'
                    WHEN nazivDana = 'Tuesday' THEN 'Utorak'
                    WHEN nazivDana = 'Wednesday' THEN 'Srijeda'
                    WHEN nazivDana = 'Thursday' THEN 'Četvrtak'
                    WHEN nazivDana = 'Friday' THEN 'Petak'
                    WHEN nazivDana = 'Saturday' THEN 'Subota' 
                END AS NazivDana
                FROM datumi 
                WHERE YEARWEEK(datum,1) = YEARWEEK(CURDATE(),1) AND 
                WEEKDAY(datum) IN(0,1,2,3,4)";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }

        return $response;
    }

    //Funkcija koja dohvaća sva vremena u danu
    function dohvatiSvaVremena(){
        $response = [];

        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        $sql = "SELECT DATE_FORMAT(v.vrijeme,'%H:%i') AS Vrijeme, 
                (SELECT CONCAT(p.imePacijent,' ',p.prezPacijent,' ',p.mboPacijent,' ',n.idNarucivanje,' ',vp.bojaPregled) FROM pacijent p 
                                            JOIN narucivanje n ON n.idPacijent = p.idPacijent 
                                            JOIN vremena v2 ON v2.vrijeme = n.vrijemeNarucivanje 
                                            JOIN datumi d ON d.datum = n.datumNarucivanje
                                            JOIN vrsta_pregled vp ON vp.idVrstaPregled = n.idVrstaPregled
                                            WHERE v2.vrijeme = v.vrijeme AND d.nazivDana = 'Monday' AND 
                                            YEARWEEK(d.datum,1) = YEARWEEK(CURDATE(),1)) AS Ponedjeljak,  
                (SELECT CONCAT(p.imePacijent,' ',p.prezPacijent,' ',p.mboPacijent,' ',n.idNarucivanje,' ',vp.bojaPregled) FROM pacijent p 
                                            JOIN narucivanje n ON n.idPacijent = p.idPacijent 
                                            JOIN vremena v2 ON v2.vrijeme = n.vrijemeNarucivanje 
                                            JOIN datumi d ON d.datum = n.datumNarucivanje
                                            JOIN vrsta_pregled vp ON vp.idVrstaPregled = n.idVrstaPregled
                                            WHERE v2.vrijeme = v.vrijeme AND d.nazivDana = 'Tuesday' AND 
                                            YEARWEEK(d.datum,1) = YEARWEEK(CURDATE(),1)) AS Utorak, 
                (SELECT CONCAT(p.imePacijent,' ',p.prezPacijent,' ',p.mboPacijent,' ',n.idNarucivanje,' ',vp.bojaPregled) FROM pacijent p 
                                            JOIN narucivanje n ON n.idPacijent = p.idPacijent 
                                            JOIN vremena v2 ON v2.vrijeme = n.vrijemeNarucivanje 
                                            JOIN datumi d ON d.datum = n.datumNarucivanje
                                            JOIN vrsta_pregled vp ON vp.idVrstaPregled = n.idVrstaPregled
                                            WHERE v2.vrijeme = v.vrijeme AND d.nazivDana = 'Wednesday' AND 
                                            YEARWEEK(d.datum,1) = YEARWEEK(CURDATE(),1)) AS Srijeda,
                (SELECT CONCAT(p.imePacijent,' ',p.prezPacijent,' ',p.mboPacijent,' ',n.idNarucivanje,' ',vp.bojaPregled) FROM pacijent p 
                                            JOIN narucivanje n ON n.idPacijent = p.idPacijent 
                                            JOIN vremena v2 ON v2.vrijeme = n.vrijemeNarucivanje 
                                            JOIN datumi d ON d.datum = n.datumNarucivanje
                                            JOIN vrsta_pregled vp ON vp.idVrstaPregled = n.idVrstaPregled
                                            WHERE v2.vrijeme = v.vrijeme AND d.nazivDana = 'Thursday' AND 
                                            YEARWEEK(d.datum,1) = YEARWEEK(CURDATE(),1)) AS Četvrtak,
                (SELECT CONCAT(p.imePacijent,' ',p.prezPacijent,' ',p.mboPacijent,' ',n.idNarucivanje,' ',vp.bojaPregled) FROM pacijent p 
                                            JOIN narucivanje n ON n.idPacijent = p.idPacijent 
                                            JOIN vremena v2 ON v2.vrijeme = n.vrijemeNarucivanje 
                                            JOIN datumi d ON d.datum = n.datumNarucivanje
                                            JOIN vrsta_pregled vp ON vp.idVrstaPregled = n.idVrstaPregled
                                            WHERE v2.vrijeme = v.vrijeme AND d.nazivDana = 'Friday' AND 
                                            YEARWEEK(d.datum,1) = YEARWEEK(CURDATE(),1)) AS Petak
                FROM vremena v 
                LEFT JOIN narucivanje n ON n.vrijemeNarucivanje = v.vrijeme
                GROUP BY Vrijeme
                ORDER BY Vrijeme;"; 

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }

        //Vraćam response
        return $response;
    }

    //Funkcija koja dohvaća podatke narudžbe pacijenta
    function dohvatiNarudzba($idNarudzba){
        $response = [];

        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        
        $sql = "SELECT n.*,CONCAT(p.imePacijent,' ',p.prezPacijent,' ',p.mboPacijent) AS Pacijent,
                v.nazivVrstaPregled FROM narucivanje n 
                JOIN vrsta_pregled v ON v.idVrstaPregled = n.idVrstaPregled
                JOIN pacijent p ON p.idPacijent = n.idPacijent 
                WHERE n.idNarucivanje = '$idNarudzba';";
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }

        return $response;
    }

    //Funkcija koja dohvaća RAZLIČITE vrste pregleda za DROPDOWN
    function dohvatiRazliciteVrstePregleda(){
        $response = [];

        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        $sql = "SELECT DISTINCT(nazivVrstaPregled) FROM vrsta_pregled;";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja ažurira narudžbu
    function azurirajNarudzbu($idNarudzba,$vrijeme,$vrstaPregleda,$datum,$ime,$prezime,$mbo,$napomena){
        $response = [];

        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        
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

        
        //Kreiram sql upit koji će provjeriti postoji li već naručeni pacijent za taj termin
        $sqlCountNarudzba = "SELECT COUNT(*) AS BrojNarudzba FROM narucivanje n 
                            WHERE n.datumNarucivanje = '$datum' AND n.vrijemeNarucivanje = '$vrijeme' 
                            AND n.idNarucivanje != '$idNarudzba'";
        //Rezultat upita spremam u varijablu $resultCountPacijent
        $resultCountNarudzba = mysqli_query($conn,$sqlCountNarudzba);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCountNarudzba) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCountNarudzba = mysqli_fetch_assoc($resultCountNarudzba)){
                //Vrijednost rezultata spremam u varijablu $brojNarudzba
                $brojNarudzba = $rowCountNarudzba['BrojNarudzba'];
            }
        }

        //Ako već postoji naručeni pacijent za taj termin
        if($brojNarudzba > 0){
            $response["success"] = "false";
            $response["message"] = "Taj termin je zauzet!";
        }
        //Ako je termin slobodan
        else{
            //Dohvaćam ID pacijenta za određeno ime, prezime i MBO pacijenta
            $resultPacijent = mysqli_query($conn,"SELECT p.idPacijent AS ID FROM pacijent p WHERE p.imePacijent = '" . mysqli_real_escape_string($conn, $ime) . "'
                                                AND p.prezPacijent = '" . mysqli_real_escape_string($conn, $prezime) . "' 
                                                AND p.mboPacijent = '" . mysqli_real_escape_string($conn, $mbo) . "'");
            //Ulazim u polje rezultata i idem redak po redak
            while($rowPacijent = mysqli_fetch_array($resultPacijent)){
                //Dohvaćam željeni ID pregleda
                $idPacijent = $rowPacijent['ID'];
            } 

            //Dohvaćam ID vrste pregleda
            $resultVrstaPregled = mysqli_query($conn,"SELECT vp.idVrstaPregled AS ID FROM vrsta_pregled vp WHERE vp.nazivVrstaPregled = '" . mysqli_real_escape_string($conn, $vrstaPregleda) . "'");
            //Ulazim u polje rezultata i idem redak po redak
            while($rowVrstaPregled = mysqli_fetch_array($resultVrstaPregled)){
                //Dohvaćam željeni ID vrste pregleda
                $idVrstaPregled = $rowVrstaPregled['ID'];
            } 

            //Kreiram upit koji će ažurirati tablicu "narucivanje"
            $sql = "UPDATE narucivanje SET 
                    idPacijent = ?,datumNarucivanje = ?, vrijemeNarucivanje = ?, napomenaNarucivanje = ?,idVrstaPregled = ? 
                    WHERE idNarucivanje = ?";
            //Kreiranje prepared statementa
            $stmt = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmt,$sql)){
                $response["success"] = "false";
                $response["message"] = "Došlo je do pogreške!";
            }
            //Ako je prepared statement u redu
            else{
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmt,"isssii",$idPacijent,$datum,$vrijeme,$napomena,$idVrstaPregled,$idNarudzba);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmt);

                $response["success"] = "true";
                $response["message"] = "Podatci uspješno ažurirani!";
            }
        }

        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja naručuje pacijenta 
    function naruciPacijenta($vrijeme,$vrstaPregleda,$datum,$ime,$prezime,$mbo,$napomena){
        $response = [];

        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
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

        //Kreiram sql upit koji će provjeriti postoji li već naručeni pacijent za taj termin
        $sqlCountNarudzba = "SELECT COUNT(*) AS BrojNarudzba FROM narucivanje n 
                            WHERE n.datumNarucivanje = '$datum' AND n.vrijemeNarucivanje = '$vrijeme'";
        //Rezultat upita spremam u varijablu $resultCountPacijent
        $resultCountNarudzba = mysqli_query($conn,$sqlCountNarudzba);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCountNarudzba) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCountNarudzba = mysqli_fetch_assoc($resultCountNarudzba)){
                //Vrijednost rezultata spremam u varijablu $brojNarudzba
                $brojNarudzba = $rowCountNarudzba['BrojNarudzba'];
            }
        }

        //Ako već postoji naručeni pacijent za taj termin
        if($brojNarudzba > 0){
            $response["success"] = "false";
            $response["message"] = "Taj termin je zauzet!";
        }
        //Ako ne postoji naručeni pacijent za taj termin
        else{
            //Dohvaćam ID pacijenta za određeno ime, prezime i MBO pacijenta
            $resultPacijent = mysqli_query($conn,"SELECT p.idPacijent AS ID FROM pacijent p WHERE p.imePacijent = '" . mysqli_real_escape_string($conn, $ime) . "'
                                                AND p.prezPacijent = '" . mysqli_real_escape_string($conn, $prezime) . "' 
                                                AND p.mboPacijent = '" . mysqli_real_escape_string($conn, $mbo) . "'");
            //Ulazim u polje rezultata i idem redak po redak
            while($rowPacijent = mysqli_fetch_array($resultPacijent)){
                //Dohvaćam željeni ID pregleda
                $idPacijent = $rowPacijent['ID'];
            } 

            //Dohvaćam ID vrste pregleda
            $resultVrstaPregled = mysqli_query($conn,"SELECT vp.idVrstaPregled AS ID FROM vrsta_pregled vp WHERE vp.nazivVrstaPregled = '" . mysqli_real_escape_string($conn, $vrstaPregleda) . "'");
            //Ulazim u polje rezultata i idem redak po redak
            while($rowVrstaPregled = mysqli_fetch_array($resultVrstaPregled)){
                //Dohvaćam željeni ID pregleda
                $idVrstaPregled = $rowVrstaPregled['ID'];
            }

            //Kreiram upit koji će unijeti novi zapis u tablicu "narucivanje"
            $sql = "INSERT INTO narucivanje (idPacijent,datumNarucivanje,vrijemeNarucivanje,napomenaNarucivanje,idVrstaPregled) 
                    VALUES (?,?,?,?,?)";
            //Kreiranje prepared statementa
            $stmt = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmt,$sql)){
                $response["success"] = "false";
                $response["message"] = "Došlo je do pogreške!";
            }
            //Ako je prepared statement u redu
            else{
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmt,"isssi",$idPacijent,$datum,$vrijeme,$napomena,$idVrstaPregled);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmt);

                $response["success"] = "true";
                $response["message"] = "Pacijent uspješno naručen!";
            } 
        }

        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja dohvaća datum za navedeni dan u tjednu
    function dohvatiDatum($danUTjednu,$datum){
        $response = [];

        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        switch($danUTjednu){
            case "Ponedjeljak":
                $danUTjednu = "Monday";
                break;
            case "Utorak":
                $danUTjednu = "Tuesday";
                break;
            case "Srijeda":
                $danUTjednu = "Wednesday";
                break;
            case "Četvrtak":
                $danUTjednu = "Thursday";
                break;
            case "Petak":
                $danUTjednu = "Friday";
                break;
        }

        $sql = "SELECT d.datum FROM datumi d 
                WHERE YEARWEEK(datum,1) = YEARWEEK('$datum',1) 
                AND WEEKDAY(datum) IN(0,1,2,3,4) AND d.nazivDana = '$danUTjednu';";
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja dohvaća vrijeme za navedeno VRIJEME (LOL :))
    function dohvatiVrijeme($vrijeme){
        $response = [];

        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        $sql = "SELECT v.vrijeme FROM vremena v WHERE v.vrijeme = '$vrijeme'";
        
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja OTKAZUJE NARUDŽBU
    function otkaziNarudzbu($idNarudzba){
        $response = [];

        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        
        //Prvo moram provjeriti je li postoji narudžba sa ovim ID-om

        //Kreiram sql upit koji će provjeriti postoji li već naručeni pacijent za taj termin
        $sqlCountNarudzba = "SELECT COUNT(*) AS BrojNarudzba FROM narucivanje n 
                            WHERE n.idNarucivanje = '$idNarudzba'";
        //Rezultat upita spremam u varijablu $resultCountPacijent
        $resultCountNarudzba = mysqli_query($conn,$sqlCountNarudzba);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($resultCountNarudzba) > 0){
            //Idem redak po redak rezultata upita 
            while($rowCountNarudzba = mysqli_fetch_assoc($resultCountNarudzba)){
                //Vrijednost rezultata spremam u varijablu $brojNarudzba
                $brojNarudzba = $rowCountNarudzba['BrojNarudzba'];
            }
        }

        //Ako narudžba NE POSTOJI
        if($brojNarudzba === 0){
            $response["success"] = "false";
            $response["message"] = "Narudžba ne postoji!";
        }
        //Ako narudžba POSTOJI
        else{
            
            //Kreiram upit koji će izbrisati narudžbu sa tim ID-em
            $sql = "DELETE FROM narucivanje 
                    WHERE idNarucivanje = ?";
            //Kreiranje prepared statementa
            $stmt = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmt,$sql)){
                $response["success"] = "false";
                $response["message"] = "Došlo je do pogreške!";
            }
            //Ako je prepared statement u redu
            else{
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmt,"i",$idNarudzba);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmt);

                $response["success"] = "true";
                $response["message"] = "Narudžba uspješno otkazana!";
            }
        }
        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja dohvaća današnji datum
    function dohvatiDanasnjiDatum(){
        //Trenutni datum
        $datum = date('Y-m-d');

        return $datum;
    }
}
?>