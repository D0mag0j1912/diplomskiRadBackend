<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new ObradaService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako zahtjev frontenda uključuje metodu POST:
if($_SERVER["REQUEST_METHOD"] === "POST"){
    //Dohvaćam podatke koje je poslao frontend
	$postdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($postdata) && !empty($postdata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($postdata);

		//Deklariram prazno polje
        $response = [];

        //Dohvaćam ID koji je frontend poslao
        $id = mysqli_real_escape_string($conn, trim($request->id));
        //Dohvaćam tip korisnika koji je frontend poslao
        $tip = mysqli_real_escape_string($conn, trim($request->tip));

        //Ako vraća null, nema errora
        if($servis->provjeraObrada($tip) != null){
            $response = $servis->provjeraObrada($tip);
        }
        else{
            //Punim polje sa odgovorom baze
            $response = $servis->dodajUObradu($tip,$id);
        }
        //Šaljem nazad frontendu odgovor
        echo json_encode($response);
    }
}
//Ako zahtjev frontenda uključuje metodu GET:
else if($_SERVER["REQUEST_METHOD"] === "GET"){
    //Kreiram prazno polje
    $response = [];

    if(isset($_GET['tip'])){
        //Dohvaćam tip korisnika
        $tip = $_GET['tip'];
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->dohvatiPacijentObrada($tip);

        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
}
//Ako zahtjev frontenda uključuje metodu PUT:
else if($_SERVER["REQUEST_METHOD"] === "PUT"){
    //Dohvaćam podatke koje je poslao frontend
	$putdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($putdata) && !empty($putdata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($putdata);

		//Deklariram prazno polje
        $response = [];

        //Dohvaćam ID koji je frontend poslao
        $id = mysqli_real_escape_string($conn, trim($request->id));
        //Dohvaćam tip korisnika koji je frontend poslao
        $tip = mysqli_real_escape_string($conn, trim($request->tip));
        //Dohvaćam ID obrade koji je frontend poslao
        $idObrada = mysqli_real_escape_string($conn, trim($request->idObrada));

        $response = $servis->azurirajStatus($idObrada,$tip,$id);

        echo json_encode($response);
    }
}
?>