<?php
//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';
//Importam bazu
$baza = new Baza();
$conn = $baza->spojiSBazom();

//Importam logout servis
$servis = new LogoutService();

//Ako je request metoda koja je aktivirala ovu skriptu "POST":
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    //Dohvaćam podatke koje je poslao frontend
	$postdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($postdata) && !empty($postdata)){
        //Pretvaram JSON u polje
        $request = json_decode($postdata);
        

        //Kreiram prazno polje
        $response = [];

        //Dohvaćam token i tip korisnika od frontenda
        $token = mysqli_real_escape_string($conn, trim($request->token));
        $tip = mysqli_real_escape_string($conn, trim($request->tip));

        //Pozivam metodu logout() iz servisa i njezin odgovor spremam u polje
        $response = $servis->logout($tip,$token);
        
        //Vraćam odgovor frontendu
        echo json_encode($response);
    }
}
?>