<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new Uzorci();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je metoda koja je poslala zahtjev POST:
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    //Dohvaćam podatke koje je poslao frontend
	$postdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($postdata) && !empty($postdata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($postdata);

		//Deklariram prazno polje
        $response = [];

        $idUputnica = mysqli_real_escape_string($conn, trim($request->idUputnica));
        $idUputnica = (int)$idUputnica;
        $eritrociti = mysqli_real_escape_string($conn, trim($request->eritrociti));
        $eritrociti = (float)$eritrociti;
        $hemoglobin = mysqli_real_escape_string($conn, trim($request->hemoglobin));
        $hemoglobin = (int)$hemoglobin;
        $hematokrit = mysqli_real_escape_string($conn, trim($request->hematokrit));
        $hematokrit = (float)$hematokrit;
        $mcv = mysqli_real_escape_string($conn, trim($request->mcv));
        $mcv = (float)$mcv;
        $mch = mysqli_real_escape_string($conn, trim($request->mch));
        $mch = (float)$mch;
        $mchc = mysqli_real_escape_string($conn, trim($request->mchc));
        $mchc = (int)$mchc;
        $rdw = mysqli_real_escape_string($conn, trim($request->rdw));
        $rdw = (float)$rdw;
        $leukociti = mysqli_real_escape_string($conn, trim($request->leukociti));
        $leukociti = (float)$leukociti;
        $trombociti = mysqli_real_escape_string($conn, trim($request->trombociti));
        $trombociti = (int)$trombociti;
        $mpv = mysqli_real_escape_string($conn, trim($request->mpv));
        $mpv = (float)$mpv;
        $trombokrit = mysqli_real_escape_string($conn, trim($request->trombokrit));
        $trombokrit = (float)$trombokrit;
        $pdw = mysqli_real_escape_string($conn, trim($request->pdw));
        $pdw = (int)$pdw;
        $neutrofilniGranulociti = mysqli_real_escape_string($conn, trim($request->neutrofilniGranulociti));
        $neutrofilniGranulociti = (float)$neutrofilniGranulociti;
        $monociti = mysqli_real_escape_string($conn, trim($request->monociti));
        $monociti = (float)$monociti;
        $limfociti = mysqli_real_escape_string($conn, trim($request->limfociti));
        $limfociti = (float)$limfociti;
        $eozinofilniGranulociti = mysqli_real_escape_string($conn, trim($request->eozinofilniGranulociti));
        $eozinofilniGranulociti = (float)$eozinofilniGranulociti;
        $bazofilniGranulociti = mysqli_real_escape_string($conn, trim($request->bazofilniGranulociti));
        $bazofilniGranulociti = (float)$bazofilniGranulociti;
        $retikulociti = mysqli_real_escape_string($conn, trim($request->retikulociti));
        $retikulociti = (int)$retikulociti;

        $response = $servis->spremiUzorke(
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
        );

        echo json_encode($response);
    }
}
?>