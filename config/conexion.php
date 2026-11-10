<?php
declare(strict_types=1);

/**
 * PASO 1 — Conexión a MySQL con PDO.
 * Ajusta las constantes según tu servidor (XAMPP: root sin clave por defecto).
 */
const DB_HOST   = 'sql101.infinityfree.com';
const DB_NAME   = 'if0_43407870_clinica';
const DB_USER   = 'if0_43407870';
const DB_PASS   = 'rikitecno123';
const DB_CHARSET = 'utf8mb4';

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
    }

    return $pdo;
}
