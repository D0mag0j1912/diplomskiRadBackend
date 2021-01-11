<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';
//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class CekaonicaService{

    //Funkcija koja dohvaća naziv i šifru sekundarnih dijagnoza na osnovu šifre sek. dijagnoze
    function dohvatiNazivSifraPovijestBolesti($polje,$idObrada){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
        //Za svaku pojedinu šifru sekundarne dijagnoze iz polja, pronađi joj šifru i naziv iz baze
        foreach($polje as $mkbSifra){
            $sql = "SELECT DISTINCT(TRIM(pb.mkbSifraPrimarna)) AS mkbSifraPrimarna,d.mkbSifra,d.imeDijagnoza,pb.idPovijestBolesti FROM dijagnoze d 
                    JOIN povijestBolesti pb ON pb.mkbSifraSekundarna = d.mkbSifra
                    WHERE d.mkbSifra = '$mkbSifra' AND pb.idObrada = '$idObrada'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            } 
        }
        return $response;
    }

    //Funkcija koja dohvaća naziv i šifru sekundarnih dijagnoza na osnovu šifre sek. dijagnoze
    function dohvatiNazivSifraOpciPodatci($polje,$idObrada){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = [];
        //Za svaku pojedinu šifru sekundarne dijagnoze iz polja, pronađi joj šifru i naziv iz baze
        foreach($polje as $mkbSifra){
            $sql = "SELECT DISTINCT(TRIM(pr.mkbSifraPrimarna)) AS mkbSifraPrimarna,d.mkbSifra,d.imeDijagnoza,pr.idPregled FROM dijagnoze d 
                    JOIN pregled pr ON pr.mkbSifraSekundarna = d.mkbSifra
                    WHERE d.mkbSifra = '$mkbSifra' AND pr.idObrada = '$idObrada'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            } 
        }
        return $response;
    }

    //Funkcija koja dohvaća povijest bolesti za određeni ID obrade
    function dohvatiPovijestBolesti($idObrada){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        $sql = "SELECT pb.anamneza,pb.terapija,TRIM(pb.mkbSifraPrimarna) AS mkbSifraPrimarna,d.imeDijagnoza AS NazivPrimarna, 
                GROUP_CONCAT(DISTINCT pb.mkbSifraSekundarna SEPARATOR ' ') AS mkbSifraSekundarna FROM povijestbolesti pb 
                JOIN dijagnoze d ON d.mkbSifra = pb.mkbSifraPrimarna 
                WHERE pb.idObrada = '$idObrada'
                GROUP BY pb.mkbSifraPrimarna";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        return $response;
    }

    //Funkcija koja dohvaća opće podatke pregleda za određeni ID obrade
    function dohvatiOpcePodatke($idObrada){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        $sql = "SELECT TRIM(pr.mkbSifraPrimarna) AS mkbSifraPrimarna,d.imeDijagnoza AS NazivPrimarna, 
                GROUP_CONCAT(DISTINCT pr.mkbSifraSekundarna SEPARATOR ' ') AS mkbSifraSekundarna FROM pregled pr 
                JOIN dijagnoze d ON d.mkbSifra = pr.mkbSifraPrimarna 
                WHERE pr.idObrada = '$idObrada' 
                GROUP BY pr.mkbSifraPrimarna";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        return $response;
    }

    //Funkcija koja dohvaća ime, prezime i datum pregleda pacijenta
    function dohvatiImePrezimeDatum($idObrada){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 

        $sql = "SELECT p.imePacijent,p.prezPacijent,DATE_FORMAT(o.datumDodavanja,'%d.%m.%Y') AS Datum,o.idObrada FROM pacijent p 
                JOIN obrada o ON o.idPacijent = p.idPacijent 
                WHERE o.idObrada = '$idObrada'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        }
        return $response;
    }

    //Funkcija koja briše pacijenta iz čekaonice
    function izbrisiPacijentaCekaonica($idObrada,$idCekaonica){
        //Dohvaćam bazu 
        $baza = new Baza();
        $conn = $baza->spojiSBazom();

        //Kreiram prazno polje odgovora
        $response = []; 
        //Ako je ID obrade prazan
        if(empty($idObrada)){
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
        //Ako ID obrade nije prazan
        else{
            $sqlObrada = "DELETE FROM obrada 
                    WHERE idObrada = ?;";

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
                mysqli_stmt_bind_param($stmtObrada,"i",$idObrada);
                //Izvršavanje statementa
                mysqli_stmt_execute($stmtObrada);

                $sql = "DELETE FROM cekaonica 
                    WHERE idObrada = ?";
            
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
                    mysqli_stmt_bind_param($stmt,"i",$idObrada);
                    //Izvršavanje statementa
                    mysqli_stmt_execute($stmt);

                    $response["success"] = "true";
                    $response["message"] = "Pacijent uspješno izbrisan!";
                }
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
            $sql = "SELECT p.idPacijent,p.imePacijent,p.prezPacijent,c.idCekaonica,
                    DATE_FORMAT(c.datumDodavanja,'%d.%m.%Y') AS DatumDodavanja,c.vrijemeDodavanja,
                    c.statusCekaonica,c.idObrada FROM pacijent p 
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