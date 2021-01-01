<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Dohvaćam liječnički servis
$servis = new LijecnikService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    //Dohvaćam podatke koje je poslao frontend
	$postdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($postdata) && !empty($postdata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($postdata);

		//Deklariram prazno polje
        $response = [];

        //Dohvaćam pojedine atribute koje je frontend poslao
        $mbo = mysqli_real_escape_string($conn, trim($request->mbo));
        $ime = mysqli_real_escape_string($conn, trim($request->ime));
        $prezime = mysqli_real_escape_string($conn, trim($request->prezime));

        //Ako $mbo nije null
        if($mbo != null){
            //Obavlja se provjera formata MBO-a
            //Ako provjera nije vratila null, znači da je MBO neispravan
            if($servis->provjeriMBO($mbo) != null){
                $response = $servis->provjeriMBO($mbo);
                //Vrati odgovor frontendu
                echo json_encode($response);
            }
            //Ako je MBO ispravan 
            else{
                //Dohvati pacijente na račun MBO-a i vrati frontendu
                $response = $servis->dohvatiPacijente($mbo,$ime,$prezime);
                echo json_encode($response);
            }
        }
        //Ako je $mbo null
        else{
            //Dohvati pacijente na račun imena i prezimena i vrati frontendu
            $response = $servis->dohvatiPacijente($mbo,$ime,$prezime);
            echo json_encode($response);
        }
    }
//Ako je u zahtjev frontenda uključena metoda PUT:
} else if($_SERVER['REQUEST_METHOD'] === 'PUT'){
    //Dohvaćam podatke koje je poslao frontend
	$putdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($putdata) && !empty($putdata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($putdata);

		//Deklariram prazno polje
        $response = [];

        //Dohvaćam pojedine vrijednosti koje je poslao frontend
        $id = mysqli_real_escape_string($conn, trim($request->id));
        $ime = mysqli_real_escape_string($conn, trim($request->ime));
        $prezime = mysqli_real_escape_string($conn, trim($request->prezime));
        $email = mysqli_real_escape_string($conn, trim($request->email));
        $spol = mysqli_real_escape_string($conn, trim($request->spol));
        $starost = mysqli_real_escape_string($conn, trim($request->starost));

        $response = $servis->azurirajPacijenta($id,$ime,$prezime,$email,$spol,$starost);

        echo json_encode($response);
    } 
//Ako je u zahtjev frontenda uključena metoda GET:  
} else if($_SERVER['REQUEST_METHOD'] === 'GET'){

    //Kreiram prazno polje
    $response = [];

    //Ako je s frontenda poslan parametar ID pacijenta
    if(isset($_GET['id'])){
        //Uzmi tu vrijednosti ID-a i pretvori je u INTEGER
        $id = (int)$_GET['id'];
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->dohvatiPodatkePacijenta($id);
        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
//Ako je u zahtjev frontenda uključena metoda DELETE:
} else if($_SERVER['REQUEST_METHOD'] === "DELETE"){
    //Dohvaćam podatke koje je poslao frontend
	$deletedata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($deletedata) && !empty($deletedata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($deletedata);
        //Kreiram prazno polje
        $response = [];

        //Uzmi tu vrijednost ID-a i pretvori je u INTEGER
        $id = mysqli_real_escape_string($conn, trim($request->id));
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->obrisiPacijenta($id);
        //Vraćam odgovor frontendu
        echo json_encode($response);
    }
}
?>