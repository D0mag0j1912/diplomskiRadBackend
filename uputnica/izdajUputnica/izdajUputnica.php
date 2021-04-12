<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';
include('../../getMBO.php');
//Dohvaćam liječnički servis
$servis = new IzdajUputnica();

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

        //Dekodiram potrebna polja
        $molimTraziSe = urldecode($request->molimTraziSe);
        //Dekodiranje
        if($request->napomena != null) {
            $napomena = urldecode($request->napomena);
        }
        
        $idZdrUst = mysqli_real_escape_string($conn, trim($request->idZdrUst));
        if(!empty($idZdrUst)){
            $idZdrUst = (int)$idZdrUst;
        }
        $sifDjel = mysqli_real_escape_string($conn, trim($request->sifDjel));
        $sifDjel = (int)$sifDjel;
        $idPacijent = mysqli_real_escape_string($conn, trim($request->idPacijent));
        $idPacijent = (int)$idPacijent;
        $sifraSpecijalist = mysqli_real_escape_string($conn, trim($request->sifraSpecijalist));
        if(!empty($sifraSpecijalist)){
            $sifraSpecijalist = (int)$sifraSpecijalist;
        }
        $mkbSifraPrimarna = mysqli_real_escape_string($conn, trim($request->mkbSifraPrimarna)); 
        $mkbSifraSekundarna = $request->mkbPolje;
        foreach($mkbSifraSekundarna as $mkb){
            $mkb = mysqli_real_escape_string($conn, trim($mkb));
        }
        $vrstaPregled = mysqli_real_escape_string($conn, trim($request->vrstaPregled));
        $molimTraziSe = mysqli_real_escape_string($conn, trim($molimTraziSe));
        //Ako postoji ova varijabla $napomena, uzmi nju, ako ne postoji uzmi $request..
        $napomena = mysqli_real_escape_string($conn, trim(isset($napomena) ? $napomena : $request->napomena));
        $idLijecnik = mysqli_real_escape_string($conn, trim($request->idLijecnik));
        $idLijecnik = (int)$idLijecnik;
        $poslanaPrimarna = mysqli_real_escape_string($conn, trim($request->poslanaPrimarna));
        $poslaniIDObrada = mysqli_real_escape_string($conn, trim($request->poslaniIDObrada));
        $poslaniIDObrada = (int)$poslaniIDObrada;
        $poslaniTipSlucaj =  mysqli_real_escape_string($conn, trim($request->poslaniTipSlucaj));
        $poslanoVrijeme =  mysqli_real_escape_string($conn, trim($request->poslanoVrijeme)); 
        $response = $servis->dodajUputnicu($idZdrUst, $sifDjel, $idPacijent, getMBO($idPacijent), $sifraSpecijalist, 
                                        $mkbSifraPrimarna, $mkbSifraSekundarna, $vrstaPregled,
                                        $molimTraziSe, $napomena, $idLijecnik, $poslanaPrimarna,
                                        $poslaniIDObrada, $poslaniTipSlucaj, $poslanoVrijeme);  
        echo json_encode($response);
    }
}
?>