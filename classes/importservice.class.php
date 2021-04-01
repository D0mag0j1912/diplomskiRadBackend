<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class ImportService{

    //Funkcija koja dohvaća sve zdravstvene djelatnosti
    function dohvatiZdravstveneDjelatnosti(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 
        $sql = "SELECT TRIM(sifDjel) AS sifDjel, TRIM(nazivDjel) AS nazivDjel FROM zdr_djel";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća pacijente (IME+PREZIME)
    function dohvatiPacijente(){
        $response = [];

        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram sql upit koji će provjeriti postoji li pacijenata
        $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM pacijent p;";
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
        //Ako nema pronađenih pacijenata u obradi
        if($brojPacijenata == 0){
            $response["success"] = "false";
            $response["message"] = "Nema pacijenata!";
        }
        //Ako ima pacijenata
        else{
            $sql = "SELECT CONCAT(p.imePacijent,' ',p.prezPacijent,' ',p.mboPacijent) AS Pacijent FROM pacijent p;";

            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            }
        }
        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja dohvaća sve zdravstvene ustanove
    function dohvatiZdravstveneUstanove(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 
        $sql = "SELECT TRIM(idZdrUst) AS idZdrUst, TRIM(nazivZdrUst) AS nazivZdrUst,
                TRIM(adresaZdrUst) AS adresaZdrUst, TRIM(brojTelZdrUst) AS brojTelZdrUst, 
                TRIM(pbrZdrUst) AS pbrZdrUst FROM zdr_ustanova";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća sve zdravstvene radnike
    function dohvatiZdravstveneRadnike(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 
        $sql = "SELECT * FROM zdr_radnici";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća sve magistralne pripravke sa dopunske liste
    function dohvatiMagistralnePripravkeDopunskaLista(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 
        $sql = "SELECT DISTINCT(nazivMagPripravak) AS nazivMagPripravak FROM dopunskaListaMagistralnihPripravaka";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća sve magistralne pripravke sa osnovne liste
    function dohvatiMagistralnePripravkeOsnovnaLista(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 
        $sql = "SELECT * FROM osnovnaListaMagistralnihPripravaka 
                WHERE oznakaMagPripravak IS NOT NULL";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća sve lijekove sa dopunske liste
    function dohvatiLijekoviDopunskaLista(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 
        $sql = "SELECT * FROM dopunskaListaLijekova 
                WHERE zasticenoImeLijek IS NOT NULL 
                AND oblikJacinaPakiranjeLijek IS NOT NULL 
                AND dddLijek IS NOT NULL 
                AND oznakaDopunskiLijek IS NOT NULL";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća sve lijekove sa osnovne liste
    function dohvatiLijekoviOsnovnaLista(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 
        $sql = "SELECT * FROM osnovnaListaLijekova 
                WHERE zasticenoImeLijek IS NOT NULL 
                AND oblikJacinaPakiranjeLijek IS NOT NULL 
                AND dddLijek IS NOT NULL 
                AND oznakaOsnovniLijek IS NOT NULL";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća sve područne urede 
    function dohvatiPodrucneUrede(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];   
        
        $sql = "SELECT * FROM podrucni_ured";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response;   
    }

    //Funkcija koja dohvaća sve kategorije osiguranja
    function dohvatiKategorijeOsiguranja(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];   
        
        $sql = "SELECT * FROM kategorije_osiguranje";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response;   
    }

    //Funkcija koja dohvaća sve dijagnoze
    function dohvatiDijagnoze(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];   
        
        $sql = "SELECT TRIM(mkbSifra) AS mkbSifra, 
                TRIM(imeDijagnoza) AS imeDijagnoza FROM dijagnoze";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response;    
    }

    //Funkcija koja dohvaća sve države osiguranja
    function dohvatiDrzaveOsiguranja(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];   
        
        $sql = "SELECT * FROM drzave_osiguranje";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response; 
    }

    //Funkcija koja dohvaća sva mjesta
    function dohvatiMjesto(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];   
        
        $sql = "SELECT * FROM mjesto";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response; 
    }

    //Funkcija koja dohvaća sva bračna stanja
    function dohvatiBracnoStanje(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];   
        
        $sql = "SELECT * FROM bracno_stanje";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća sve radne statuse
    function dohvatiRadniStatus(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];   
        
        $sql = "SELECT * FROM radni_status";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća sve statuse pacijenta
    function dohvatiStatusPacijenta(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];   
        
        $sql = "SELECT * FROM status_pacijent";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response;
    }

    //Funkcija koja dohvaća sve participacije
    function dohvatiParticipacija(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];   
        
        $sql = "SELECT * FROM participacija";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        //Vraćam odgovor baze
        return $response;
    }
}
?>