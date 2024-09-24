<?php

// Colors
$colors = [
    "black" => "30",
    "red" => "31",
    "green" => "32",
    "yellow" => "33",
    "blue" => "34",
    "magenta" => "35",
    "cyan" => "36",
    "white" => "37",
];

// Background colors
$bgColors = [
    "black" => "40",
    "red" => "41",
    "green" => "42",
    "yellow" => "43",
    "blue" => "44",
    "magenta" => "45",
    "cyan" => "46",
    "white" => "47",
];

    
function text($text, $color = "", $bgColor = "") {
    global $colors, $bgColors;

    $colorCode = "";
    $bgColorCode = "";

    // Si une couleur de texte est définie
    if (!empty($color) && isset($colors[$color])) {
        $colorCode = "\033[" . $colors[$color] . "m";
    }

    // Si une couleur de fond est définie
    if (!empty($bgColor) && isset($bgColors[$bgColor])) {
        $bgColorCode = "\033[" . $bgColors[$bgColor] . "m";
    }

    // Applique les couleurs et réinitialise à la fin
    $reset = "\033[0m";
    return $colorCode . $bgColorCode . $text . $reset;
}
