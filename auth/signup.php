<?php
//Postavljanje vremenske zone
date_default_timezone_set('Europe/Zagreb');
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';

//Kreiram objekt tipa "SignupService"
$servis = new SignupService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "POST":
if($_SERVER["REQUEST_METHOD"] === "POST"){
	//Dohvaćam podatke koje je poslao frontend
	$postdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($postdata) && !empty($postdata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($postdata);

		//Deklariram prazno polje
		$response = [];
		//Sređivam pojedine atribute iz polja (tip, ime, prezime ..)
		$tip = mysqli_real_escape_string($conn, trim($request->tip));
		$ime = mysqli_real_escape_string($conn, trim($request->ime));
		$prezime = mysqli_real_escape_string($conn, trim($request->prezime));
		$email = mysqli_real_escape_string($conn, trim($request->email));
		$adresa = mysqli_real_escape_string($conn, trim($request->adresa));
		$specijalizacija = mysqli_real_escape_string($conn, trim($request->specijalizacija));
		$lozinka = mysqli_real_escape_string($conn, trim($request->lozinka));
		$ponovnoLozinka = mysqli_real_escape_string($conn, trim($request->ponovnoLozinka));

		//Kreiram objekt tipa "Korisnik"
		$korisnik = new Korisnik($tip,$ime,$prezime,$email,$adresa,$specijalizacija,$lozinka);

		//ODRADIT ĆE SAMO JEDAN UVJET, NAPUNIT POLJE I POSLAT POLJE FRONTENDU

		//Pozivam metodu za provjeru vrijednosti lozinka te ako nije null (ako vraća null to znači da nema errora)
		if($servis->provjeraLozinka($lozinka,$ponovnoLozinka) != null){
			//Punim polje $response odgovarajućom porukom
			$response = $servis->provjeraLozinka($lozinka,$ponovnoLozinka);
		}
		// (ako vraća null to znači da nema errora)
		else if($servis->uopcePostojiKorisnik($tip) != null){
			//Pozivam metodu iz servisa i njezinu povratnu vrijednost (polje), pridružujem polju $response
			$response = $servis->uopcePostojiKorisnik($tip);
		}
		// (ako vraća null to znači da nema errora)
		//Ako već postoji korisnik sa tim korisničkim imenom u bazi:
		else if($servis->vecPostoji($email) != null){
			//U array $result spremam polje $result koje sam stvorio u funkciji vecPostoji()
			$response = $servis->vecPostoji($email);
		}

		//Ako je sve u redu za sad:
		else{
			//Pozivam metodu koja sprema podatke u bazu i dohvaćam response od nje
			$response = $servis->insertUBazu($korisnik);
		}
		//Vrati frontendu JSON response
		echo json_encode($response);
	}
}
?>