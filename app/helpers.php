<?php

/**
 * Recebe o cnpj em formato numérico e retorna formatado com pontos e barra
 */
if (!function_exists('formatarCnpjCpf')) {
    function formatarCnpjCpf($value)
    {
        $CPF_LENGTH = 11;
        $cnpj_cpf = preg_replace("/\D/", '', $value);

        if (strlen($cnpj_cpf) === $CPF_LENGTH) {
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
        }

        return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
    }
}

/**
 * Recebe uma string de data de NFE "d/m/Y - H:i:s" e apresenta "d/m/Y"
 *
 * @param String $value
 * @return String
 */
if (!function_exists('formatarDataNfe')) {
    function formatarDataNfe($value)
    {
        return explode('-', $value)[0];
    }
}

/**
 * Recebe uma string de data de NFE "d/m/Y - H:i:s" e apresenta "d/m/Y"
 *
 * @param String $value
 * @return String
 */
if (!function_exists('formatarEnDataNfe')) {
    function formatarEnDataNfe($value)
    {
        $data = trim(explode('-', $value)[0]);
        $parts = explode('/', $data);
        return $parts[2].'-'.$parts[1].'-'.$parts[0];
    }
}
