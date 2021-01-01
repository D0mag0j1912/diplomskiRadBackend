<?php
/********************************* 
OVDJE SE NALAZE SVE BACKEND METODE ZA REGISTRACIJU
*/

//Importam autoloader koji će automatski importat klasu čiji tip objekta kreiram
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

class SignupService{

    //Metoda za provjeru je li postoji još jedan korisnik primljenog tipa u bazi
    function uopcePostojiKorisnik($tip){
        //Kreiram objekt tipa "Baza"
        $baza = new Baza();
        //Inicijaliziram prazno polje
        $response = [];

        if($tip == "lijecnik"){
            //Kreiram sql koji će provjeriti koliko je liječnika zasad registrirano. Ako je već jedan registriran, ne može više
            $sqlProvjeraCount = "SELECT COUNT(DISTINCT(a.idLijecnik)) AS BrojLijecnik FROM ambulanta a";
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
            $sqlProvjeraCount = "SELECT COUNT(DISTINCT(a.idMedSestra)) AS BrojMedSestra FROM ambulanta a";
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
            $response["message"] = "Vrijednosti lozinka moraju biti jednake!";
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
			$response["message"] = "Prepared statement korisnika ne valja!";
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
				$sqlMedSestra = "INSERT INTO med_sestra (imeMedSestra,prezMedSestra,adrMedSestra,datKreirMedSestra,nazSpecMedSestra,idKorisnik) VALUES (?,?,?,?,?,?)";
				//Kreiram prepared statment
				$stmtMedSestra = mysqli_stmt_init($conn);
				//Ako je prepared statment neuspješno izvršen
				if(!mysqli_stmt_prepare($stmtMedSestra,$sqlMedSestra)){
					$response["success"] = "false";
					$response["message"] = "Prepared statement med. sestre ne valja!";
						
				}
				else{
					//Izvršavam upit koji dohvaća ID korisnika koji odgovara unesenom email-u
					$resultKorisnik = mysqli_query($conn,"SELECT k.idKorisnik FROM korisnik k WHERE k.email = '" . mysqli_real_escape_string($conn, $korisnik->email) . "'"); 
					while($rowKorisnik = mysqli_fetch_array($resultKorisnik))
					{
						$idKorisnik = $rowKorisnik['idKorisnik'];
					}
					$trenutniDatum = date("Y-m-d h:i:sa");
					//Sve unesene vrijednosti medicinske sestre što je korisnik unio se stavljaju umjesto upitnika
					mysqli_stmt_bind_param($stmtMedSestra,"sssssi",$korisnik->ime,$korisnik->prezime,$korisnik->adresa,$trenutniDatum,$korisnik->specijalizacija,$idKorisnik);
					//Izvršavam statement
					mysqli_stmt_execute($stmtMedSestra);

					//Moram dohvatiti ID medicinske sestre iz tablice "med_sestra" da ga unesem u tablicu "ambulanta"
					
					$resultMedSestra = mysqli_query($conn,"SELECT m.idMedSestra FROM med_sestra m WHERE m.imeMedSestra = '" . mysqli_real_escape_string($conn, $korisnik->ime) . "' 
														AND m.prezMedSestra = '" . mysqli_real_escape_string($conn, $korisnik->prezime) . "' 
														AND m.adrMedSestra = '" . mysqli_real_escape_string($conn, $korisnik->adresa) . "'
														AND m.nazSpecMedSestra = '" . mysqli_real_escape_string($conn, $korisnik->specijalizacija) . "'"); 
					while($rowMedSestra = mysqli_fetch_array($resultMedSestra))
					{
						$idMedSestra = $rowMedSestra['idMedSestra'];
					}

					//Brojim n-torke u tablici "ambulanta"
					$sqlCount = "SELECT COUNT(*) AS BrojRedova FROM ambulanta a";
					//Dohvaćam rezultat ovog upita u polje
					$resultCount = mysqli_query($conn,$sqlCount);
					//Ako polje ima redaka u sebi
					if(mysqli_num_rows($resultCount) > 0){
					//Idem redak po redak rezultata upita 
						while($rowCount = mysqli_fetch_assoc($resultCount)){
							//Vrijednost rezultata spremam u varijablu $brojRecept
							$brojRedova = $rowCount['BrojRedova'];
						}
					}

					//Ako je broj n-torki 0, znači prazna je tablica:
					if($brojRedova == 0){
						//Kreiram upit za spremanje podataka u bazu podataka u tablicu "ambulanta"
						$sqlAmbulanta = "INSERT INTO ambulanta (idMedSestra) VALUES (?)";
						//Kreiram prepared statment
						$stmtAmbulanta = mysqli_stmt_init($conn);
						//Ako je prepared statment neuspješno izvršen
						if(!mysqli_stmt_prepare($stmtAmbulanta,$sqlAmbulanta)){
							$response["success"] = "false";
							$response["message"] = "Prepared statement ambulante ne valja!";
							
						}
						else{
							//Sve unesene vrijednosti medicinske sestre se stavljaju umjesto upitnika
							mysqli_stmt_bind_param($stmtAmbulanta,"i",$idMedSestra);
							//Izvršavam statement
							mysqli_stmt_execute($stmtAmbulanta);

							//Vraćanje uspješnog responsa
							$response["success"] = "true";
							$response["message"] = "Uspješno registrirana medicinska sestra!";
						}
					} else if($brojRedova == 1){
						//Kreiram upit za ažuriranje tablice "ambulanta"
						$sqlAmbulantaUpdate = "UPDATE ambulanta a SET a.idMedSestra = ?";
						//Kreiram prepared statement
						$stmtAmbulantaUpdate = mysqli_stmt_init($conn);
						//Ako je prepared statment neuspješno izvršen
						if(!mysqli_stmt_prepare($stmtAmbulantaUpdate,$sqlAmbulantaUpdate)){
							$response["success"] = "false";
							$response["message"] = "Prepared statement update ne valja!";
						}
						else{
							//Sve unesene vrijednosti medicinske sestre se stavljaju umjesto upitnika
							mysqli_stmt_bind_param($stmtAmbulantaUpdate,"i",$idMedSestra);
							//Izvršavam statement
							mysqli_stmt_execute($stmtAmbulantaUpdate);	

							//Vraćanje uspješnog responsa
							$response["success"] = "true";
							$response["message"] = "Uspješno registrirana medicinska sestra!";
						}
					}
					else{
						$response["success"] = "false";
						$response["message"] = "Previše korisnika je registrirano!";
					}
				}
			}
			if($korisnik->tip == "lijecnik"){
				
				//Kreiram upit za spremanje podataka u bazu podataka u tablicu "lijecnik"
				$sqlLijecnik = "INSERT INTO lijecnik (imeLijecnik,prezLijecnik,adrLijecnik,datKreirLijecnik,nazSpecLijecnik,idKorisnik) VALUES (?,?,?,?,?,?)";
				//Kreiram prepared statment
				$stmtLijecnik = mysqli_stmt_init($conn);
				//Ako je prepared statment neuspješno izvršen
				if(!mysqli_stmt_prepare($stmtLijecnik,$sqlLijecnik)){
					$response["success"] = "false";
					$response["message"] = "Prepared statement liječnika ne valja!";
						
				}
				else{
					//Izvršavam upit koji dohvaća ID liječnika koji odgovara unesenom username-u
					$resultKorisnik = mysqli_query($conn,"SELECT k.idKorisnik FROM korisnik k WHERE k.email = '" . mysqli_real_escape_string($conn, $korisnik->email) . "'"); 
					while($rowKorisnik = mysqli_fetch_array($resultKorisnik))
					{
						$idKorisnik = $rowKorisnik['idKorisnik'];
					}
					$trenutniDatum = date("Y-m-d h:i:sa");
					//Sve unesene vrijednosti liječnika što je korisnik unio se stavljaju umjesto upitnika
					mysqli_stmt_bind_param($stmtLijecnik,"sssssi",$korisnik->ime,$korisnik->prezime,$korisnik->adresa,$trenutniDatum,$korisnik->specijalizacija,$idKorisnik);
					//Izvršavam statement
					mysqli_stmt_execute($stmtLijecnik);

					//Moram dohvatiti ID liječnika iz tablice "lijecnik" da ga unesem u tablicu "ambulanta"
					
					$resultLijecnik = mysqli_query($conn,"SELECT l.idLijecnik FROM lijecnik l WHERE l.imeLijecnik = '" . mysqli_real_escape_string($conn, $korisnik->ime) . "' 
														AND l.prezLijecnik = '" . mysqli_real_escape_string($conn, $korisnik->prezime) . "' 
														AND l.adrLijecnik = '" . mysqli_real_escape_string($conn, $korisnik->adresa) . "'
														AND l.nazSpecLijecnik = '" . mysqli_real_escape_string($conn, $korisnik->specijalizacija) . "'"); 
					while($rowLijecnik = mysqli_fetch_array($resultLijecnik))
					{
						$idLijecnik = $rowLijecnik['idLijecnik'];
					}

					//Brojim n-torke u tablici "ambulanta"
					$sqlCount = "SELECT COUNT(*) AS BrojRedova FROM ambulanta a";
					//Dohvaćam rezultat ovog upita u polje
					$resultCount = mysqli_query($conn,$sqlCount);
					//Ako polje ima redaka u sebi
					if(mysqli_num_rows($resultCount) > 0){
					//Idem redak po redak rezultata upita 
						while($rowCount = mysqli_fetch_assoc($resultCount)){
							//Vrijednost rezultata spremam u varijablu $brojRecept
							$brojRedova = $rowCount['BrojRedova'];
						}
					}

					//Ako je broj n-torki 0, znači prazna je tablica:
					if($brojRedova == 0){
						//Kreiram upit za spremanje podataka u bazu podataka u tablicu "ambulanta"
						$sqlAmbulanta = "INSERT INTO ambulanta (idLijecnik) VALUES (?)";
						//Kreiram prepared statment
						$stmtAmbulanta = mysqli_stmt_init($conn);
						//Ako je prepared statment neuspješno izvršen
						if(!mysqli_stmt_prepare($stmtAmbulanta,$sqlAmbulanta)){
							$response["success"] = "false";
							$response["message"] = "Prepared statement ambulante lijecnika ne valja!";
							
						}
						else{
							//Sve unesene vrijednosti liječnika se stavljaju umjesto upitnika
							mysqli_stmt_bind_param($stmtAmbulanta,"i",$idLijecnik);
							//Izvršavam statement
							mysqli_stmt_execute($stmtAmbulanta);

							//Vraćanje uspješnog responsa
							$response["success"] = "true";
							$response["message"] = "Uspješno registriran liječnik!";
						}
					} else if($brojRedova == 1){
						//Kreiram upit za ažuriranje tablice "ambulanta"
						$sqlAmbulantaUpdate = "UPDATE ambulanta a SET a.idLijecnik = ?";
						//Kreiram prepared statement
						$stmtAmbulantaUpdate = mysqli_stmt_init($conn);
						//Ako je prepared statment neuspješno izvršen
						if(!mysqli_stmt_prepare($stmtAmbulantaUpdate,$sqlAmbulantaUpdate)){
							$response["success"] = "false";
							$response["message"] = "Prepared statement update ne valja!";
						}
						else{
							//Sve unesene vrijednosti liječnika se stavljaju umjesto upitnika
							mysqli_stmt_bind_param($stmtAmbulantaUpdate,"i",$idLijecnik);
							//Izvršavam statement
							mysqli_stmt_execute($stmtAmbulantaUpdate);	

							//Vraćanje uspješnog responsa
							$response["success"] = "true";
							$response["message"] = "Uspješno registriran liječnik!";
						}
					}
					else{
						$response["success"] = "false";
						$response["message"] = "Previše korisnika je registrirano!";
					}
				}
			}
        }
        return $response;   
    }
}
?>