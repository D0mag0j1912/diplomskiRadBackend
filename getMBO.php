<?php 
//Funkcija koja vraća MBO pacijenta na osnovu njegovog ID-a
function getMBO($idPacijent){
    //Dohvaćam bazu 
    $baza = new Baza();
    $conn = $baza->spojiSBazom();
    //Kreiram upit za dohvaćanjem MBO-a pacijenta kojemu se upisiva povijest bolesti
    $sqlMBO = "SELECT p.mboPacijent AS MBO FROM pacijent p 
            WHERE p.idPacijent = '$idPacijent'";
    //Rezultat upita spremam u varijablu $resultMBO
    $resultMBO = mysqli_query($conn,$sqlMBO);
    //Ako rezultat upita ima podataka u njemu (znači nije prazan)
    if(mysqli_num_rows($resultMBO) > 0){
        //Idem redak po redak rezultata upita 
        while($rowMBO = mysqli_fetch_assoc($resultMBO)){
            //Vrijednost rezultata spremam u varijablu $mboPacijent
            $mboPacijent = $rowMBO['MBO'];
        }
    }
    return $mboPacijent;
}
?>