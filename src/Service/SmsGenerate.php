<?php
namespace App\Service;
class SmsGenerate {
    public function generateMatricule(){
        return uniqid();
    }
}
