<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';
//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class CekaonicaService{

    //Funkcija koja briše pacijenta iz čekaonice
    function izbrisiPacijentaCekaonica($idPacijent,$idCekaonica){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        $sqlObrada = "DELETE FROM obrada 
                    WHERE obrada.idPacijent IN 
                    (SELECT cekaonica.idPacijent FROM cekaonica 
                    WHERE cekaonica.idPacijent = ? AND cekaonica.idCekaonica = ?);";

        //Kreiranje prepared statementa
        $stmtObrada = mysqli_stmt_init($conn);
        //Ako je statement neuspješan
        if(!mysqli_stmt_prepare($stmtObrada,$sqlObrada)){
            $response["success"] = "false";
            $response["message"] = "Prepared statement brisanja obrade ne valja!";
        }
        //Ako je prepared statement u redu
        else{
            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
            mysqli_stmt_bind_param($stmtObrada,"ii",$idPacijent,$idCekaonica);
            //Izvršavanje statementa
            mysqli_stmt_execute($stmtObrada);

            $sql = "DELETE FROM cekaonica 
                WHERE idCekaonica = ?";
        
            //Kreiranje prepared statementa
            $stmt = mysqli_stmt_init($conn);
            //Ako je statement neuspješan
            if(!mysqli_stmt_prepare($stmt,$sql)){
                $response["success"] = "false";
                $response["message"] = "Prepared statement brisanja čekaonice ne valja!";
            }
            //Ako je prepared statement u redu
            else{
                //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
                mysqli_stmt_bind_param($stmt,"i",$idCekaonica);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmt);

                $response["success"] = "true";
                $response["message"] = "Pacijent uspješno izbrisan!";
            }
        }

        return $response;
    }

    //Funkcija koja provjerava koliko još ima pacijenata u čekaonici
    function provjeriBrojCekaonica(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];

        //Kreiram sql upit koji će provjeriti koliko ima pacijenata u bazi podataka
        $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM cekaonica";
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
        //Vrati broj pacijenata
        return $brojPacijenata;
    }

    //Funkcija koja dodava pacijenta u čekaonicu
    function dodajUCekaonicu($id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Trenutni datum
        $datum = date('Y-m-d');
        //Trenutno vrijeme
        $vrijeme = date('H:i:s');
        //Početni status čekaonice
        $status = "Čeka na pregled";

        $sql = "INSERT INTO cekaonica(idPacijent,datumDodavanja,vrijemeDodavanja,statusCekaonica) VALUES (?,?,?,?)";
        //Kreiranje prepared statementa
        $stmt = mysqli_stmt_init($conn);
        //Ako je statement neuspješan
        if(!mysqli_stmt_prepare($stmt,$sql)){
            $response["success"] = "false";
            $response["message"] = "Prepared statement ne valja!";
        }
        //Ako je prepared statement u redu
        else{
            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
            mysqli_stmt_bind_param($stmt,"isss",$id,$datum,$vrijeme,$status);
            //Izvršavanje statementa
            mysqli_stmt_execute($stmt);

            $response["success"] = "true";
            $response["message"] = "Pacijent je uspješno dodan u čekaonicu!";
        }
        //Vraćam odgovor
        return $response;
    }

    //Funkcija koja dohvaća pacijente iz čekaonice
    function dohvatiPacijenteCekaonica(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Kreiram sql upit koji će provjeriti postoji li pacijenata u čekaonici
        $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM cekaonica";
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
        //Ako nema pronađenih pacijenata u čekaonici
        if($brojPacijenata == 0){
            $response["success"] = "false";
            $response["message"] = "Čekaonica je prazna!";
        }
        //Ako ima pacijenata u čekaonici
        else{
            //Kreiram upit koji dohvaća osobne podatke pacijenta
            $sql = "SELECT p.idPacijent,p.imePacijent,p.prezPacijent,c.idCekaonica,DATE_FORMAT(c.datumDodavanja,'%d.%m.%Y') AS DatumDodavanja,c.vrijemeDodavanja,c.statusCekaonica FROM pacijent p 
                    JOIN cekaonica c ON p.idPacijent = c.idPacijent 
                    ORDER BY c.statusCekaonica,c.datumDodavanja,c.vrijemeDodavanja DESC";

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

    //Funkcija koja provjerava status u čekaonici
    function provjeraCekaonica($id){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Status pacijenta u čekaonici
        $status = "Čeka na pregled";
        //Provjeravam je li postoji već taj pacijent u čekaonici (da čeka na pregled)
        $sql="SELECT c.idPacijent FROM cekaonica c 
                WHERE c.idPacijent = '$id' AND c.statusCekaonica = '$status'";
        
        $result = $conn->query($sql);

        if($result->num_rows > 0){
            $response["success"] = "false";
            $response["message"] = "Pacijent trenutno čeka na pregled!";
            return $response;
        }
        else{
            //Vraćam null
            return null;
        }
    }

    //Funkcija koja dohvaća 10 zadnjih pacijenata u čekaonici
    function dohvati10zadnjih(){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Kreiram sql upit koji će provjeriti postoji li pacijenata u čekaonici
        $sqlCountPacijent = "SELECT COUNT(*) AS BrojPacijent FROM cekaonica";
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
        //Ako nema pronađenih pacijenata u čekaonici
        if($brojPacijenata == 0){
            $response["success"] = "false";
            $response["message"] = "Čekaonica je prazna!";
        }
        //Ako ima pacijenata u čekaonici
        else{
            //Kreiram upit koji dohvaća osobne podatke pacijenta
            $sql = "SELECT p.idPacijent,p.imePacijent,p.prezPacijent,c.idCekaonica,DATE_FORMAT(c.datumDodavanja,'%d.%m.%Y') AS DatumDodavanja,c.vrijemeDodavanja,c.statusCekaonica FROM pacijent p 
                    JOIN cekaonica c ON p.idPacijent = c.idPacijent 
                    ORDER BY c.statusCekaonica,c.datumDodavanja,c.vrijemeDodavanja DESC 
                    LIMIT 10";

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

    //Funkcija koja dohvaća pacijente iz čekaonice po njihovom statusu u čekaonici
    function dohvatiPacijentaPoStatusu($statusi){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        //Ako polje nije prazno
        if(!empty($statusi)){
            foreach($statusi as $status){
        
                $sql = "SELECT p.idPacijent,p.imePacijent,p.prezPacijent,c.idCekaonica,DATE_FORMAT(c.datumDodavanja,'%d.%m.%Y') AS DatumDodavanja,c.vrijemeDodavanja,c.statusCekaonica FROM pacijent p 
                        JOIN cekaonica c ON p.idPacijent = c.idPacijent 
                        WHERE c.statusCekaonica = '$status'
                        ORDER BY c.statusCekaonica,c.datumDodavanja,c.vrijemeDodavanja DESC";
        
                $result = $conn->query($sql);
        
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $response[] = $row;
                    }
                }
            }
            return $response;
        }
    }
}
?>