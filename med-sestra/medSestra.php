<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new MedSestraService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Punim polje sa vrijednostima polja iz funkcije
    $response = $servis->dohvatiOsobnePodatke();

    //Vraćam frontendu rezultat
    echo json_encode($response);
//Ako je zahtjev frontenda uključivao metodu PUT:
} else if($_SERVER['REQUEST_METHOD'] === 'PUT'){
    //Dohvaćam podatke koje je poslao frontend
	$putdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($putdata) && !empty($putdata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($putdata);

		//Deklariram prazno polje
        $response = [];

        //Ako se u JS objektu nalazi objekt "trenutna", to znači da se radi o ažuriranju lozinke za medicinsku sestru
        if($request->trenutna){
            $id = mysqli_real_escape_string($conn, trim($request->id));
            $trenutna = mysqli_real_escape_string($conn, trim($request->trenutna));
            $nova = mysqli_real_escape_string($conn, trim($request->nova));
            $potvrdaNova = mysqli_real_escape_string($conn, trim($request->potvrdaNova));

            //Ako ova metoda ne vraća null, odgovor metode spremam u polje odgovora (ako vraća null to znači da nema errora)
            if($servis->provjeraLozinka($id,$trenutna) != null){
                $response = $servis->provjeraLozinka($id,$trenutna);
            }
            //Ako metoda ne vraća null, odgovor metode spremam u polje odgovora (ako vraća null to znači da nema errora)
            else if($servis->jednakeLozinke($nova,$potvrdaNova) != null){
                $response = $servis->jednakeLozinke($nova,$potvrdaNova);
            }
            else{
                $response = $servis->azurirajLozinka($id,$nova);
            }
        }
        //Ako se ne nalazi, riječ je o ažuriranju osobnih podataka medicinske sestre
        else{
            //Dohvaćam pojedine atribute prijave iz polja
            $id = mysqli_real_escape_string($conn, trim($request->id));
            $email = mysqli_real_escape_string($conn, trim($request->email));
            $ime = mysqli_real_escape_string($conn, trim($request->ime));
            $prezime = mysqli_real_escape_string($conn, trim($request->prezime));
            $adresa = mysqli_real_escape_string($conn, trim($request->adresa));
            $specijalizacija = mysqli_real_escape_string($conn, trim($request->specijalizacija));

            //Punim polje sa odgovorom funkcije 
            $response = $servis->azurirajOsobnePodatke($id,$email,$ime,$prezime,$adresa,$specijalizacija);
        }
        //Šaljem odgovor frontendu
        echo json_encode($response);
    }   
}
?>