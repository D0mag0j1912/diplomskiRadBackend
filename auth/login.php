<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php'; 

//Importam login servis da mogu pristupiti metodama servisa
$servis = new LoginService();

//Importam bazu da mogu pristupiti konekciji
$baza = new Baza();

//Spremam konekciju u $conn
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "POST":
if($_SERVER["REQUEST_METHOD"] === "POST"){
    //Dohvaćam podatke s frontenda
    $postdata = file_get_contents("php://input");
    //Ako ima podataka u $postdata
    if(isset($postdata) && !empty($postdata)){

        //Pretvaram dobiveni JSON objekt u PHP asocijativno polje
        $request = json_decode($postdata);

        //Kreiram prazno polje
        $response = [];

        //Dohvaćam pojedine atribute prijave iz polja
        $email = mysqli_real_escape_string($conn, trim($request->email));
        $lozinka = mysqli_real_escape_string($conn, trim($request->lozinka));
        
        //U polje dohvaćam povratnu vrijednost funkcije prijavaKorisnik()
        $response = $servis->prijavaKorisnik($email,$lozinka);

        //Vrati nazad frontendu
        echo json_encode($response);
    }
}
?>