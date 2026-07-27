<?php
declare(strict_types=1);
function somenteDigitos(string $valor): string { return preg_replace('/\D+/', '', $valor) ?? ''; }
function formatarCnpj(string $cnpj): string {
    $cnpj=somenteDigitos($cnpj);
    return strlen($cnpj)===14 ? substr($cnpj,0,2).'.'.substr($cnpj,2,3).'.'.substr($cnpj,5,3).'/'.substr($cnpj,8,4).'-'.substr($cnpj,12,2) : $cnpj;
}
function validarCnpj(string $cnpj): bool {
    $cnpj=somenteDigitos($cnpj);
    if(strlen($cnpj)!==14 || preg_match('/^(\d)\1{13}$/',$cnpj)) return false;
    $calc=static function(string $base,array $pesos): int {
        $soma=0; foreach($pesos as $i=>$peso){$soma+=((int)$base[$i])*$peso;}
        $resto=$soma%11; return $resto<2?0:11-$resto;
    };
    $d1=$calc(substr($cnpj,0,12),[5,4,3,2,9,8,7,6,5,4,3,2]);
    $d2=$calc(substr($cnpj,0,12).$d1,[6,5,4,3,2,9,8,7,6,5,4,3,2]);
    return $cnpj[12]===(string)$d1 && $cnpj[13]===(string)$d2;
}
