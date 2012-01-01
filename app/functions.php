<?php

function generateRandom($lenght = 8){
    $chars = array_merge(range("a", "z"), range("A","Z"), range(0,9), array("!","@","#","$","^","&","(",")"));
    shuffle($chars);

    $randKeys = array_rand($chars, $lenght);
    $pass = "";
    foreach($randKeys as $k){
        $pass .= $chars[$k];
    }
    return $pass;
}
    
function createSlug($string){
    return preg_replace("/[^A-Za-z0-9]/", "_", strtolower($string));
}