<?php
/**
 * This file contains helper functions for common usage.
 * @author Paulina Budzon <paulina.budzon@gmail.com>
 * @package frameworkCore
 */

/**
 * Generates random string consisting of letters, numbers and special characters of a given length.
 * @param int $lenght Desired length of the returned string.
 * @return string Random string. 
 */
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
    
/**
 * Creates "slug" (aka "index") from the given string - can be used to create SEO links.
 * @param string $string Input string.
 * @return string String containing only of letters and numbers, with all other characters replaced with "_".
 */
function createSlug($string){
    return preg_replace("/[^A-Za-z0-9]/", "_", strtolower($string));
}