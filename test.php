<?php
include('./backend-path.php');
require_once BASE_PATH.'\includes\autoloader.inc.php';

$servis = new OpciPodatciService();
//Kreiram prazno polje
$response = [];
//Kreiram objekt tipa "Baza"
$baza = new Baza();
$conn = $baza->spojiSBazom();
//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');


$tipKorisnik = "lijecnik";
$ids = [504,503,505,502];

//Funkcija koja provjerava jeli ima dva ili više pregleda iz iste grupacije
function provjeriIstuGrupaciju($tipKorisnik,$ids){
    //Dohvaćam bazu 
    $baza = new Baza();
    $conn = $baza->spojiSBazom();

    $response = [];

    //Ako je tip korisnika "lijecnik":
    if($tipKorisnik == "lijecnik"){
        foreach($ids as $idPregled){
            $sql = "SELECT * FROM povijestBolesti pb 
                    WHERE pb.idPovijestBolesti = '$idPregled'";
            //Rezultat upita spremam u varijablu $result
            $result = mysqli_query($conn,$sql);
            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
            if(mysqli_num_rows($result) > 0){
                //Idem redak po redak rezultata upita 
                while($row = mysqli_fetch_assoc($result)){
                    $mkbSifraPrimarna = $row['mkbSifraPrimarna'];
                    $idObradaLijecnik = $row['idObradaLijecnik'];
                    $datum = $row['datum'];
                    $vrijeme = $row['vrijeme'];
                    $mboPacijent = $row['mboPacijent'];
                    $tipSlucaj = $row['tipSlucaj'];
                    //Kreiram upit koji dohvaća MAX ID pregleda grupacije pregleda koji se trenutno gleda
                    $sqlIDS = "SELECT pb.idPovijestBolesti FROM povijestBolesti pb 
                            WHERE TRIM(pb.mkbSifraPrimarna) = '$mkbSifraPrimarna' 
                            AND pb.idObradaLijecnik = '$idObradaLijecnik'
                            AND pb.datum = '$datum' 
                            AND pb.vrijeme = '$vrijeme' 
                            AND pb.mboPacijent = '$mboPacijent' 
                            AND pb.tipSlucaj = '$tipSlucaj' 
                            AND pb.idPovijestBolesti != '$idPregled'";
                    //Rezultat upita spremam u varijablu $result
                    $resultIDS = mysqli_query($conn,$sqlIDS);
                    //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                    if(mysqli_num_rows($resultIDS) > 0){
                        //Idem redak po redak rezultata upita 
                        while($rowIDS = mysqli_fetch_assoc($resultIDS)){
                            //Kreiram upit koji dohvaća MAX ID pregleda grupacije pregleda koji se trenutno gleda
                            $sqlMAX = "SELECT pb.idPovijestBolesti FROM povijestBolesti pb 
                                    WHERE TRIM(pb.mkbSifraPrimarna) = '$mkbSifraPrimarna' 
                                    AND pb.idObradaLijecnik = '$idObradaLijecnik'
                                    AND pb.datum = '$datum' 
                                    AND pb.vrijeme = '$vrijeme' 
                                    AND pb.mboPacijent = '$mboPacijent' 
                                    AND pb.tipSlucaj = '$tipSlucaj' 
                                    AND pb.idPovijestBolesti = 
                                    (SELECT MAX(pb2.idPovijestBolesti) FROM povijestBolesti pb2 
                                    WHERE TRIM(pb2.mkbSifraPrimarna) = '$mkbSifraPrimarna' 
                                    AND pb2.idObradaLijecnik = '$idObradaLijecnik'
                                    AND pb2.datum = '$datum' 
                                    AND pb2.vrijeme = '$vrijeme' 
                                    AND pb2.mboPacijent = '$mboPacijent' 
                                    AND pb2.tipSlucaj = '$tipSlucaj')";
                            //Rezultat upita spremam u varijablu $result
                            $resultMAX = mysqli_query($conn,$sqlMAX);
                            //Ako rezultat upita ima podataka u njemu (znači nije prazan)
                            if(mysqli_num_rows($resultMAX) > 0){
                                //Idem redak po redak rezultata upita 
                                while($rowMAX = mysqli_fetch_assoc($resultMAX)){
                                    //Ako se pronađeni pregled iz baze nalazi već u pregledima koji su poslani sa frontenda [504,503,505] te je upravo on maksimalni u toj grupaciji
                                    if(in_array($rowIDS['idPovijestBolesti'],$ids) && $rowIDS['idPovijestBolesti'] == $rowMAX['idPovijestBolesti']){
                                        $response[] = $idPregled;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    return $response;
}

foreach(provjeriIstuGrupaciju($tipKorisnik,$ids) as $vanjski){
    echo $vanjski."\n";
} 
?>