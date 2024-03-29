<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';
include('../../getMBO.php');

//Dohvaćam liječnički servis
$servis = new DodajReceptService();

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
        //Dekodiranje
        if($request->osnovnaListaLijekDropdown != null) {
            $osnovnaListaLijekDropdown = urldecode($request->osnovnaListaLijekDropdown);
        }
        else if($request->osnovnaListaLijekText != null){
            $osnovnaListaLijekText = urldecode($request->osnovnaListaLijekText);
        }
        else if($request->dopunskaListaLijekDropdown != null){
            $dopunskaListaLijekDropdown = urldecode($request->dopunskaListaLijekDropdown);
        }
        else if($request->dopunskaListaLijekText != null){
            $dopunskaListaLijekText = urldecode($request->dopunskaListaLijekText);
        }

        $mkbSifraPrimarna = mysqli_real_escape_string($conn, trim($request->mkbSifraPrimarna)); 
        $mkbSifraSekundarna = $request->mkbSifraSekundarna;
        foreach($mkbSifraSekundarna as $mkb){
            $mkb = mysqli_real_escape_string($conn, trim($mkb));
        }
        //Ako postoji ova varijabla $osnovnaListaLijekDropdown, uzmi nju, ako ne postoji uzmi $request..
        $osnovnaListaLijekDropdown = mysqli_real_escape_string($conn, trim(isset($osnovnaListaLijekDropdown) ? $osnovnaListaLijekDropdown : $request->osnovnaListaLijekDropdown));
        $osnovnaListaLijekText = mysqli_real_escape_string($conn, trim(isset($osnovnaListaLijekText) ? $osnovnaListaLijekText : $request->osnovnaListaLijekText));
        $dopunskaListaLijekDropdown = mysqli_real_escape_string($conn, trim(isset($dopunskaListaLijekDropdown) ? $dopunskaListaLijekDropdown : $request->dopunskaListaLijekDropdown));
        $dopunskaListaLijekText = mysqli_real_escape_string($conn, trim(isset($dopunskaListaLijekText) ? $dopunskaListaLijekText : $request->dopunskaListaLijekText));
        $osnovnaListaMagPripravakDropdown = mysqli_real_escape_string($conn, trim($request->osnovnaListaMagPripravakDropdown));
        $osnovnaListaMagPripravakText = mysqli_real_escape_string($conn, trim($request->osnovnaListaMagPripravakText));
        $dopunskaListaMagPripravakDropdown = mysqli_real_escape_string($conn, trim($request->dopunskaListaMagPripravakDropdown));
        $dopunskaListaMagPripravakText = mysqli_real_escape_string($conn, trim($request->dopunskaListaMagPripravakText));
        $kolicina = mysqli_real_escape_string($conn, trim($request->kolicina));
        $kolicina = (int)$kolicina;
        $doziranje = mysqli_real_escape_string($conn, trim($request->doziranje));
        $dostatnost = mysqli_real_escape_string($conn, trim($request->dostatnost));
        $dostatnost = (int)$dostatnost;
        $hitnost = mysqli_real_escape_string($conn, trim($request->hitnost));
        $ponovljiv = mysqli_real_escape_string($conn, trim($request->ponovljiv));
        $brojPonavljanja = mysqli_real_escape_string($conn, trim($request->brojPonavljanja));
        $sifraSpecijalist = mysqli_real_escape_string($conn, trim($request->sifraSpecijalist));
        if(!empty($brojPonavljanja)){
            $brojPonavljanja = (int)$brojPonavljanja;
        }
        if(!empty($sifraSpecijalist)){
            $sifraSpecijalist = (int)$sifraSpecijalist;
        }
        $idPacijent = mysqli_real_escape_string($conn, trim($request->idPacijent));
        $idPacijent = (int)$idPacijent;
        $idLijecnik = mysqli_real_escape_string($conn, trim($request->idLijecnik));
        $idLijecnik = (int)$idLijecnik;
        $poslanaPrimarna = mysqli_real_escape_string($conn, trim($request->poslanaPrimarna));
        $poslaniIDObrada = mysqli_real_escape_string($conn, trim($request->poslaniIDObrada));
        $poslaniIDObrada = (int)$poslaniIDObrada;
        $poslaniTipSlucaj =  mysqli_real_escape_string($conn, trim($request->poslaniTipSlucaj));
        $poslanoVrijeme =  mysqli_real_escape_string($conn, trim($request->poslanoVrijeme));
        $response = $servis->dodajRecept($mkbSifraPrimarna,$mkbSifraSekundarna,$osnovnaListaLijekDropdown,
                                        $osnovnaListaLijekText,$dopunskaListaLijekDropdown,$dopunskaListaLijekText,
                                        $osnovnaListaMagPripravakDropdown,$osnovnaListaMagPripravakText,$dopunskaListaMagPripravakDropdown,
                                        $dopunskaListaMagPripravakText,$kolicina,$doziranje,$dostatnost,$hitnost,$ponovljiv,$brojPonavljanja,
                                        $sifraSpecijalist,$idPacijent,getMBO($idPacijent),$idLijecnik,$poslanaPrimarna,$poslaniIDObrada,$poslaniTipSlucaj,$poslanoVrijeme);  
        echo json_encode($response);
    }
}
?>