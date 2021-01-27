<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class ImportService{

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
        
        $sql = "SELECT * FROM dijagnoze";

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