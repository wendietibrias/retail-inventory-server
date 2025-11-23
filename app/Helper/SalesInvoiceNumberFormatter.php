<?php

namespace App\Helper;

class SalesInvoiceNumberFormatter
{
    public static function formatter($type, $lastNumber)
    {
        if ($type === "PPN") {
            if ($lastNumber < 10) {
                return "0000$lastNumber";
            }
            if ($lastNumber >= 10 && $lastNumber < 100) {
                return "000$lastNumber";
            }
            if ($lastNumber >= 100 && $lastNumber < 1000) {
                return "00$lastNumber";
            }
            if ($lastNumber >= 1000 && $lastNumber < 10000) {
                return "00$lastNumber";
            }
            if ($lastNumber >= 10000 && $lastNumber < 100000) {
                return "0$lastNumber";
            }
            if ($lastNumber >= 100000) {
                return "$lastNumber";
            }
        } else {
            if ($lastNumber < 10) {
                return "000000000$lastNumber";
            }
            if ($lastNumber >= 10 && $lastNumber < 100) {
                return "00000000$lastNumber";
            }
            if ($lastNumber >= 100 && $lastNumber < 1000) {
                return "0000000$lastNumber";
            }
            if ($lastNumber >= 1000 && $lastNumber < 10000) {
                return "000000$lastNumber";
            }
            if ($lastNumber >= 10000 && $lastNumber < 100000) {
                return "00000$lastNumber";
            }
            if ($lastNumber >= 100000 && $lastNumber < 1000000) {
                return "0000$lastNumber";
            }
            if($lastNumber >= 1000000 && $lastNumber < 10000000){
                return "000$lastNumber";
            }
            if($lastNumber >= 10000000 && $lastNumber < 100000000){
                return "00$lastNumber";
            }
            if($lastNumber >= 100000000 && $lastNumber < 1000000000){
                return "0$lastNumber";
            }
            if($lastNumber >= 1000000000){
                return "$lastNumber";
            }
        }

    }
}