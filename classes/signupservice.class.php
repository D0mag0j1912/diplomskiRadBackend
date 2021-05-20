<?php
/********************************* 
OVDJE SE NALAZE SVE BACKEND METODE ZA REGISTRACIJU
*/
//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');
//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once BASE_PATH.'\includes\autoloader.inc.php';

class SignupService{

    //Metoda za provjeru je li postoji još jedan korisnik primljenog tipa u bazi
    function uopcePostojiKorisnik($tip){
        //Kreiram objekt tipa "Baza"
        $baza = new Baza();
        //Inicijaliziram prazno polje
        $response = [];

        if($tip == "lijecnik"){
            //Kreiram sql koji će provjeriti koliko je liječnika zasad registrirano. Ako je već jedan registriran, ne može više
            $sqlProvjeraCount = "SELECT COUNT(*) AS BrojLijecnik FROM lijecnik";
            //Dohvaćam rezultat ovog upita u polje
            $resultProvjeraCount = mysqli_query($baza->spojiSBazom(),$sqlProvjeraCount);
            //Ako polje ima redaka u sebi
            if(mysqli_num_rows($resultProvjeraCount) > 0){
                //Idem redak po redak rezultata upita 
                while($rowProvjeraCount = mysqli_fetch_assoc($resultProvjeraCount)){
                    //Vrijednost rezultata spremam u varijablu $brojLijecnik
                    $brojLijecnik = $rowProvjeraCount['BrojLijecnik'];
                }
            }
            //Ako ima već jedan registrirani liječnik:
            if($brojLijecnik == 1){
                //Punimo polje porukom
                $response["success"]="false";
                $response["message"]="Već postoji jedan registrirani liječnik!";
                //Vraćam puno polje
                return $response;
            }
            else{
                //Vraćam null
                return null;
            }
        }
        else{
            //Kreiram sql koji će provjeriti koliko je medicinskih sestara zasad registrirano. Ako je već jedna registrirana, ne može više
            $sqlProvjeraCount = "SELECT COUNT(*) AS BrojMedSestra FROM med_sestra";
            //Dohvaćam rezultat ovog upita u polje
            $resultProvjeraCount = mysqli_query($baza->spojiSBazom(),$sqlProvjeraCount);
            //Ako polje ima redaka u sebi
            if(mysqli_num_rows($resultProvjeraCount) > 0){
                //Idem redak po redak rezultata upita 
                while($rowProvjeraCount = mysqli_fetch_assoc($resultProvjeraCount)){
                    //Vrijednost rezultata spremam u varijablu $brojMedSestra
                    $brojMedSestra = $rowProvjeraCount['BrojMedSestra'];
                }
            }
            //Ako ima već jedna registrirana medicinska sestra:
            if($brojMedSestra == 1){
                //Punimo polje porukom
                $response["success"]="false";
                $response["message"]="Već postoji jedna registrirana medicinska sestra!";
                //Vraćam puno polje
                return $response;
            }
            else{
                //Vraćam null
                return null;
            }
        }
    }

    //Funkcija koja provjerava postoji li već korisnik sa određenim korisničkim imenom u bazi
    function vecPostoji($email){
        //Kreiram objekt tipa "Baza"
        $baza = new Baza();

        //Inicijalizacija polja
        $response = [];

        //Kreiram sql upit koji provjerava je li postoji već taj korisnik u bazi podataka
        $sql="SELECT k.email FROM korisnik k 
                WHERE k.email = '$email'";
        
        $result = $baza->spojiSBazom()->query($sql);

        if($result->num_rows > 0){
            $response["success"] = "false";
            $response["message"] = "Email je već u upotrebi!";
            return $response;
        }
        else{
            //Vraćam polje
            return null;
        }
    }

    function provjeraLozinka($lozinka, $ponovnoLozinka){
        
        //Ako vrijednost i tip lozinke != vrijednosti i tipu ponovne lozinke
        if($lozinka !== $ponovnoLozinka){
            $response["success"] = "false";
            $response["message"] = "Vrijednosti lozinki moraju biti jednake!";
            //Vraćam puno polje
            return $response;
        }
        else{
            //Vraćam null
            return null;
        }
    }

    function insertUBazu(Korisnik $korisnik){
        //Kreiram objekt tipa "Baza"
        $baza = new Baza();
        $conn = $baza->spojiSBazom();
        //Inicijalizacija polja
        $response = [];

        //Kreiram upit za spremanje podataka u bazu podataka u tablicu "korisnik"
		$sqlKorisnik = "INSERT INTO korisnik (tip,email,pass) VALUES (?,?,?)";
		//Kreiram prepared statment
		$stmtKorisnik = mysqli_stmt_init($conn);
		//Ako je prepared statment neuspješno izvršen
		if(!mysqli_stmt_prepare($stmtKorisnik,$sqlKorisnik)){
			$response["success"] = "false";
			$response["message"] = "Došlo je do pogreške!";
		}
		//Ako je prepared statment uspješno izvršen
		else{
			//Hashiram password 
			$passwordHash = password_hash($korisnik->lozinka,PASSWORD_DEFAULT);
			//Uzima username i password što je korisnik unio i stavlja ga umjesto upitnika
			mysqli_stmt_bind_param($stmtKorisnik,"sss",$korisnik->tip,$korisnik->email,$passwordHash);
			//Izvršavam statement
			mysqli_stmt_execute($stmtKorisnik);

			if($korisnik->tip == "sestra"){
				
				//Kreiram upit za spremanje podataka u bazu podataka u tablicu "med_sestra"
				$sqlMedSestra = "INSERT INTO med_sestra (imeMedSestra,prezMedSestra,adrMedSestra,datKreirMedSestra,idKorisnik,idZdrUst,sifraSpecijalist) VALUES (?,?,?,?,?,?,?)";
				//Kreiram prepared statment
				$stmtMedSestra = mysqli_stmt_init($conn);
				//Ako je prepared statment neuspješno izvršen
				if(!mysqli_stmt_prepare($stmtMedSestra,$sqlMedSestra)){
					$response["success"] = "false";
					$response["message"] = "Došlo je do pogreške!";
						
				}
				else{
					//Izvršavam upit koji dohvaća ID korisnika koji odgovara unesenom email-u
					$resultKorisnik = mysqli_query($conn,"SELECT k.idKorisnik FROM korisnik k WHERE k.email = '" . mysqli_real_escape_string($conn, $korisnik->email) . "'"); 
					while($rowKorisnik = mysqli_fetch_array($resultKorisnik))
					{
						$idKorisnik = $rowKorisnik['idKorisnik'];
					}
					$trenutniDatum = date("Y-m-d");
					$idZdrUst = 258825880;

                    //Dohvaćam šifru specijalista koju ubacivam u tablicu "med_sestra"
                    $sqlSpecijalizacija = "SELECT zr.sifraSpecijalist FROM zdr_radnici zr 
                                        WHERE zr.tipSpecijalist = '$korisnik->specijalizacija'";
                    //Dohvaćam rezultat ovog upita u polje
                    $resultSpecijalizacija = mysqli_query($conn,$sqlSpecijalizacija);
                    //Ako polje ima redaka u sebi
                    if(mysqli_num_rows($resultSpecijalizacija) > 0){
                        //Idem redak po redak rezultata upita 
                        while($rowSpecijalizacija = mysqli_fetch_assoc($resultSpecijalizacija)){
                            //Vrijednost rezultata spremam u varijablu $sifraSpecijalist
                            $sifraSpecijalist = $rowSpecijalizacija['sifraSpecijalist'];
                        }
                    }
                    else{
                        $sifraSpecijalist = NULL;
                    }
					//Sve unesene vrijednosti medicinske sestre što je korisnik unio se stavljaju umjesto upitnika
					mysqli_stmt_bind_param($stmtMedSestra,"ssssiii",$korisnik->ime,$korisnik->prezime,$korisnik->adresa,$trenutniDatum,$idKorisnik,$idZdrUst,$sifraSpecijalist);
					//Izvršavam statement
					mysqli_stmt_execute($stmtMedSestra);
					$response["success"] = "true";
					$response["message"] = "Medicinska sestra je uspješno registrirana!";
				}
			}
			if($korisnik->tip == "lijecnik"){
				
				//Kreiram upit za spremanje podataka u bazu podataka u tablicu "lijecnik"
				$sqlLijecnik = "INSERT INTO lijecnik (imeLijecnik,prezLijecnik,adrLijecnik,datKreirLijecnik,idKorisnik,idZdrUst,sifraSpecijalist) VALUES (?,?,?,?,?,?,?)";
				//Kreiram prepared statment
				$stmtLijecnik = mysqli_stmt_init($conn);
				//Ako je prepared statment neuspješno izvršen
				if(!mysqli_stmt_prepare($stmtLijecnik,$sqlLijecnik)){
					$response["success"] = "false";
					$response["message"] = "Došlo je do pogreške!";
						
				}
				else{
					//Izvršavam upit koji dohvaća ID liječnika koji odgovara unesenom username-u
					$resultKorisnik = mysqli_query($conn,"SELECT k.idKorisnik FROM korisnik k WHERE k.email = '" . mysqli_real_escape_string($conn, $korisnik->email) . "'"); 
					while($rowKorisnik = mysqli_fetch_array($resultKorisnik))
					{
						$idKorisnik = $rowKorisnik['idKorisnik'];
					}
					$trenutniDatum = date("Y-m-d");
					$idZdrUst = 258825880;
                    //Dohvaćam šifru specijalista koju ubacivam u tablicu "med_sestra"
                    $sqlSpecijalizacija = "SELECT zr.sifraSpecijalist FROM zdr_radnici zr 
                                        WHERE zr.tipSpecijalist = '$korisnik->specijalizacija'";
                    //Dohvaćam rezultat ovog upita u polje
                    $resultSpecijalizacija = mysqli_query($conn,$sqlSpecijalizacija);
                    //Ako polje ima redaka u sebi
                    if(mysqli_num_rows($resultSpecijalizacija) > 0){
                        //Idem redak po redak rezultata upita 
                        while($rowSpecijalizacija = mysqli_fetch_assoc($resultSpecijalizacija)){
                            //Vrijednost rezultata spremam u varijablu $sifraSpecijalist
                            $sifraSpecijalist = $rowSpecijalizacija['sifraSpecijalist'];
                        }
                    }
                    else{
                        $sifraSpecijalist = NULL;
                    }
					//Sve unesene vrijednosti liječnika što je korisnik unio se stavljaju umjesto upitnika
					mysqli_stmt_bind_param($stmtLijecnik,"ssssiii",$korisnik->ime,$korisnik->prezime,$korisnik->adresa,$trenutniDatum,$idKorisnik,$idZdrUst,$sifraSpecijalist);
					//Izvršavam statement
					mysqli_stmt_execute($stmtLijecnik);

					$response["success"] = "true";
					$response["message"] = "Liječnik je uspješno registriran!";
				}
			}
        }
        return $response;   
    }
}
?>