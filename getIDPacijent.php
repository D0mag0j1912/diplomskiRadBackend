<?php
function getIDPacijent($mboPacijent){
    //Dohvaćam bazu 
    $baza = new Baza();
    $conn = $baza->spojiSBazom();
    //Kreiram upit za dohvaćanjem MBO-a pacijenta kojemu se upisiva povijest bolesti
    $sql = "SELECT p.idPacijent FROM pacijent p 
            WHERE p.mboPacijent = '$mboPacijent'";
    //Rezultat upita spremam u varijablu $result
    $result = mysqli_query($conn,$sql);
    //Ako rezultat upita ima podataka u njemu (znači nije prazan)
    if(mysqli_num_rows($result) > 0){
        //Idem redak po redak rezultata upita 
        while($row = mysqli_fetch_assoc($result)){
            //Vrijednost rezultata spremam u varijablu $mboPacijent
            $idPacijent = $row['idPacijent'];
        }
    }
    return $idPacijent;
}
?>