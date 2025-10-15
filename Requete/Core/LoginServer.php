<?php

namespace Requete\Core;

use PDO;
use PDOException;

abstract class LoginServer
{
    private string $user = "root";
    private string $password = "root";
    private string $server = ""; //Adresse du serveur à utiliser
    private string $base = ""; //Nom de la base de donnée à utiliser

    protected function getPdo(): PDO
    {
        $dsn = "mysql:dbname=" . $this->base . ";host=" . $this->server;
        try {
            $pdo = new PDO($dsn, $this->user, $this->password);
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit();
        }
        return  $pdo;
    }
}