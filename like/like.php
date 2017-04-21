<?php

/*
  Plugin Name: Like Plugin
  Plugin URI: http://localhost/wordpresss
  Description: Eklenti Açıklaması
  Version: Versiyon (0.1 gibi)
  Author: Ednuka
  Author URI:  http://localhost/wordpresss
  License: GNU
 */



/* Direkt Çağrıları Engelleyelim */
if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
    die('You are not allowed to call this page directly.');
}

function like_form() {
   
    $form = "<form action=''>"
           
            . "<button>Begen</button>"
            . "</form>";
    return $form;
}

function like_btn($content) {

    

    if (is_single()) {
        $buton = like_form();
        $content .= $buton;
    }
    return $content;
}

add_filter('the_content', 'like_btn');
?>