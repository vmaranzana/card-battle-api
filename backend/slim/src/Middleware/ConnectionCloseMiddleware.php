<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use DB; 

class ConnectionCloseMiddleware
{
    /**
     * Hace que el objeto sea invocable como una función.
     * Se ejecuta después de que el handler principal haya procesado la petición.
     *
     * @param Request $request PSR-7 request
     * @param RequestHandler $handler PSR-15 request handler
     * @return Response PSR-7 response
     */
    /*public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Procesa la petición a través del resto de la aplicación (rutas, controladores, otros middlewares)
        $response = $handler->handle($request);

        // Obtiene la instancia Singleton de la conexión a la base de datos
        $dbConnection = DB::getConnection();

        // Si la conexión es una instancia de PDO, la desreferencia para su cierre
        if ($dbConnection instanceof \PDO) {
            DB::setConnection(null); // Llama al método estático que agregaste en DB.php
        }

        // Retorna la respuesta generada por la aplicación
        return $response;
    }*/

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        error_log("ConnectionCloseMiddleware: Antes de manejar la petición."); // Log de inicio
        $response = $handler->handle($request);

        // Obtiene la instancia Singleton de la conexión a la base de datos
        $dbConnection = DB::getConnection();

        // Si la conexión es una instancia de PDO, la desreferencia para su cierre
        if ($dbConnection instanceof \PDO) {
            DB::setConnection(null);
            error_log("ConnectionCloseMiddleware: Conexión PDO desreferenciada para el cierre."); // Log de cierre
        } else {
            error_log("ConnectionCloseMiddleware: No se encontró una instancia de PDO activa para desreferenciar.");
        }

        error_log("ConnectionCloseMiddleware: Después de manejar la petición."); // Log de fin

        return $response;
    }
}