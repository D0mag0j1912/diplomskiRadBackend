<?php
class Korisnik{
    //Inicijaliziram varijable pojedinih atributa korisnika
    public $tip;
    public $ime;
    public $prezime;
    public $email;
    public $adresa;
    public $specijalizacija;
    public $lozinka;
    
    //Definiram konstruktor koji će pri kreiranju objekta tipa "Korisnik", mu pridružiti određene vrijednosti
    function __construct($tip, $ime,$prezime,$email,$adresa,$specijalizacija,$lozinka){
        $this->tip = $tip;
        $this->ime = $ime;
        $this->prezime = $prezime;
        $this->email = $email;
        $this->adresa = $adresa;
        $this->specijalizacija = $specijalizacija;
        $this->lozinka = $lozinka;
    }

    //Metode za dohvat pojedinih atributa
    function getTip(){
        return $this->tipKorisnik;
    }
    function getIme(){
        return $this->ime;
    }
    function getPrezime(){
        return $this->prezime;
    }
    function getEmail(){
        return $this->email;
    }
    function getAdresa(){
        return $this->adresa;
    }
    function getSpecijalizacija(){
        return $this->specijalizacija;
    }
    function getLozinka(){
        return $this->lozinka;
    }
}
?>