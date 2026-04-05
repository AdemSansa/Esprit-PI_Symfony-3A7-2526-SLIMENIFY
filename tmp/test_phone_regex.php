<?php

function testPhone($phone, $regex) {
    return preg_match($regex, $phone) ? "VALID" : "INVALID";
}

$regex = '/^(\+216|00216)?[234579]\d{7}$/';

$testCases = [
    "20123456",
    "50123456",
    "98123456",
    "71123456",
    "44123456",
    "+21620123456",
    "0021650123456",
    "12345678",
    "2012345",
    "201234567",
];

foreach ($testCases as $phone) {
    echo "Phone: $phone => " . testPhone($phone, $regex) . "\n";
}
