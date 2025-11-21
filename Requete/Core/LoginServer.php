<?php

namespace Requete\Core;

use PDO;
use PDOException;

class LoginServer
{
    private string $user = "root";
    private string $password = "root";
    private string $server = ""; //Adresse du serveur à utiliser
    private string $base = ""; //Nom de la base de donnée à utiliser

    public function getPdo(): PDO
    {
        $dsn = "mysql:dbname=" . $this->base . ";host=" . $this->server;
        return new PDO($dsn, $this->user, $this->password);
    }
}