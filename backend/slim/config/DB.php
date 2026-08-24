<?php

class DB
{
    private static $connection;

    public static function getConnection()
    {
        if (!self::$connection) {
            $host = 'db';
            $dbname = 'seminariophp';
            $user = 'seminariophp';
            $pass = 'seminariophp';
            $port = $_ENV['DB_PORT_LOCAL'] ?? 3306; // El puerto de tu MySQL local (generalmente 3306)

            try {
                self::$connection = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Recomendado para seguridad y consistencia
                self::$connection->exec("SET time_zone = 'America/Argentina/Buenos_Aires'"); // Solucion a horario de vencimiento de token 
            } catch (PDOException $e) {
                die(json_encode(['error' => $e->getMessage()]));
            }
        }

        return self::$connection;
    }

    /**
     * Permite desreferenciar la conexión PDO.
     * Esto es útil para permitir que PHP la cierre al final del ciclo de vida de la petición.
     *
     * @param \PDO|null $connection La instancia de PDO a establecer (generalmente null para cerrar).
     */
    public static function setConnection(?\PDO $connection): void
    {
        self::$connection = $connection;
    }
}