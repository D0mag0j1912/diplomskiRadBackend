<?php
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

class Uzorci {

    //Funkcija koja dohvaća podatke uputnice na osnovu njezinog ID-a
    function dohvatiPodatciUputnica($idUputnica){
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        $sql = "SELECT TRIM(zd.nazivDjel) AS nazivDjel, 
                CASE 
                    WHEN u.sifraSpecijalist IS NOT NULL THEN (SELECT CONCAT((SELECT DISTINCT(TRIM(zr.tipSpecijalist)) FROM zdr_radnici zr 
                                                                            WHERE zr.sifraSpecijalist = u.sifraSpecijalist),' [',u.sifraSpecijalist,']'))
                    WHEN u.sifraSpecijalist IS NULL THEN NULL
                END AS specijalist,
                u.mkbSifraPrimarna, d.imeDijagnoza AS nazivPrimarna, u.vrstaPregleda AS vrstaPregled FROM uputnica u 
                JOIN zdr_djel zd ON zd.sifDjel = u.sifDjel 
                JOIN dijagnoze d ON d.mkbSifra = u.mkbSifraPrimarna
                WHERE u.idUputnica = '$idUputnica'";
        //Rezultat upita spremam u varijablu $result
        $result = mysqli_query($conn,$sql);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($result) > 0){
            //Idem redak po redak rezultata upita 
            while($row = mysqli_fetch_assoc($result)){
                //Vrijednost rezultata spremam u varijablu $idUputnica
                $response[] = $row;
            }
        }
        return $response;
    }

    //Funkcija koja dohvaća zdr. ustanove za koje nisu još dodani uzorci
    function dohvatiUstanovaUzorci($idPacijent){
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];

        $sql = "SELECT u.idUputnica, TRIM(zu.nazivZdrUst) AS nazivZdrUst FROM zdr_ustanova zu 
                JOIN uputnica u ON u.idZdrUst = zu.idZdrUst 
                WHERE u.idPacijent = '$idPacijent' 
                AND u.idUputnica NOT IN 
                (SELECT uz.idUputnica FROM uzorci uz)
                AND u.idUputnica IN 
                (SELECT MIN(u2.idUputnica) FROM uputnica u2 
                WHERE u.oznaka = u2.oznaka)";
        //Rezultat upita spremam u varijablu $result
        $result = mysqli_query($conn,$sql);
        //Ako rezultat upita ima podataka u njemu (znači nije prazan)
        if(mysqli_num_rows($result) > 0){
            //Idem redak po redak rezultata upita 
            while($row = mysqli_fetch_assoc($result)){
                //Vrijednost rezultata spremam u varijablu $idUputnica
                $response[] = $row;
            }
        }
        //Ako nema zdr. ustanova 
        else{
            return null;
        }
        return $response;
    }
    //Funkcija koja sprema uzorke u bazu
    function spremiUzorke(  
        $idUputnica,
        $eritrociti,
        $hemoglobin,
        $hematokrit,
        $mcv,
        $mch,
        $mchc,
        $rdw,
        $leukociti,
        $trombociti,
        $mpv,
        $trombokrit,
        $pdw,
        $neutrofilniGranulociti,
        $monociti,
        $limfociti,
        $eozinofilniGranulociti,
        $bazofilniGranulociti,
        $retikulociti
    ){
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        $response = [];
        
        //Kreiram upit za dodavanje nove uputnice u bazu
        $sql = "INSERT INTO uzorci (idUputnica,eritrociti,hemoglobin,hematokrit,mcv,
                                    mch,mchc,rdw,leukociti,trombociti,mpv,trombokrit,
                                    pdw,neutrofilniGranulociti,monociti,limfociti,
                                    eozinofilniGranulociti,bazofilniGranulociti,
                                    retikulociti) VALUES 
                (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        //Kreiranje prepared statementa
        $stmt = mysqli_stmt_init($conn);
        //Ako je statement neuspješan
        if(!mysqli_stmt_prepare($stmt,$sql)){
            return null;
        }
        //Ako je prepared statement u redu
        else{
            //Zamjena parametara u statementu (umjesto ? se stavlja vrijednost)
            mysqli_stmt_bind_param($stmt,"ididddiddiddidddddi", $idUputnica, $eritrociti, $hemoglobin,
                                                            $hematokrit, $mcv, $mch, $mchc, $rdw, $leukociti,
                                                            $trombociti, $mpv, $trombokrit, $pdw, $neutrofilniGranulociti,
                                                            $monociti, $limfociti, $eozinofilniGranulociti, $bazofilniGranulociti,
                                                            $retikulociti);
            //Izvršavanje statementa
            mysqli_stmt_execute($stmt);
            //Vraćam uspješan odgovor
            $response["success"] = "true";
            $response["message"] = "Uzorci uspješno poslani!";
        }
        return $response;
    }
}
?>