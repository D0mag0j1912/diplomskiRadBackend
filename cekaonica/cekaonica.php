<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new CekaonicaService();

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

        //Ako vraća null, nema errora
        if($servis->provjeraCekaonica($id) != null){
            $response = $servis->provjeraCekaonica($id);
        }
        else{
            //Punim polje sa odgovorom baze
            $response = $servis->dodajUCekaonicu($id);
        }
        //Šaljem nazad frontendu odgovor
        echo json_encode($response);
    }
}
//Ako zahtjev frontenda uključuje metodu GET:
else if($_SERVER["REQUEST_METHOD"] === "GET"){
    //Kreiram prazno polje
    $response = [];

    //Punim polje sa vrijednostima polja iz funkcije
    $response = $servis->dohvatiPacijenteCekaonica();

    //Vraćam frontendu rezultat
    echo json_encode($response);
}

?>