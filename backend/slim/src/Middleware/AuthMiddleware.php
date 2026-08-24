<?php

namespace App\Middleware;

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use DateTimeImmutable;
use PDO;
use DB;

class AuthMiddleware    
{
    private $jwtSecret;
    private $db; 

    public function __construct(string $jwtSecret, PDO $db)
    {
        $this->jwtSecret = $jwtSecret;
        $this->db = $db;
    }

    public static function initialize(): self
    {
        $db = DB::getConnection();
        return new self($_ENV['JWT_SECRET'] ?? 'your-secret-key', $db);
    }

    public function __invoke(Request $request, Handler $handler): Response
    {
        $authorizationHeader = $request->getHeaderLine('Authorization');

        if (!$authorizationHeader) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Se requiere token de autenticación.']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        list(, $token) = explode(' ', $authorizationHeader);

        if (!$token) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token de autenticación inválido.']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));

            $now = new DateTimeImmutable();
            if ($decoded->exp < $now->getTimestamp()) {
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write(json_encode(['error' => 'El token ha expirado.']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            $userId = $decoded->sub;
            $userModel = new User($this->db);
            $user = $userModel->getUserById($userId);

            if (!$user) {
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write(json_encode(['error' => 'Usuario no encontrado.']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            $request = $request->withAttribute('user', $user);

            $response = $handler->handle($request);
            return $response;

        } catch (\Exception $e) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token de autenticación inválido.', 'details' => $e->getMessage()]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    }
}