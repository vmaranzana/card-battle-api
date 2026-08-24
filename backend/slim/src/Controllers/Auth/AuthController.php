<?php

namespace App\Controllers\Auth;

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use DateTimeImmutable;

class AuthController
{
    private $userModel; 
    private $jwtSecret;

    public function __construct(User $userModel, string $jwtSecret)
    {
        $this->userModel = $userModel;
        $this->jwtSecret = $jwtSecret;

    }

    public function login(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $usuario = $data['usuario'] ?? '';
            $clave = $data['password'] ?? '';

            // Busco el usuario por el username en la BD
            $user = $this->userModel->getUserByUsername($usuario);

            // Verifico si el usuario existe.
            if (!$user) {
                $response->getBody()->write(json_encode(['error' => 'El usuario o la contraseña que ingresó no son válidos, vuelva a intentarlo.']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json'); // 401 Unauthorized
            }

            // Si la clave tiene un hash en BD utilizo password_verify
            // En este caso, comparto si la clave inyectada en el JSON es igual a la password en BD
            if ($clave !== $user['password']) {
                $response->getBody()->write(json_encode(['error' => 'El usuario o la contraseña que ingresó no son válidos, vuelva a intentarlo.']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json'); // 401 Unauthorized
            }

            // Creo el vencimiento del token
            $now = new DateTimeImmutable();
            $expire = $now->modify('+1 hour');
            $vencimientoToken = $expire->format('Y-m-d H:i:s');

            // Creo el token con la libreria JWT
            $payload = [
                'iat' => $now->getTimestamp(),         // Issued at
                'exp' => $expire->getTimestamp(),        // Expiration time
                'sub' => $user['id'],                  // User identifier
                'user' => $user['usuario'],
            ];
            $jwt = JWT::encode($payload, $this->jwtSecret, 'HS256');

            // Actualizar token y vencimiento en la base de datos
            $this->userModel->updateToken($user['id'], $jwt, $vencimientoToken);

            $response->getBody()->write(json_encode(['token' => $jwt]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json'); // 200 OK

        } catch (\PDOException $e) {
            error_log("Error de base de datos al iniciar sesión: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor al iniciar sesión. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Firebase\JWT\JWTException $e) {
            error_log("Error al generar el token JWT: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor al generar el token de autenticación. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error general al iniciar sesión: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}