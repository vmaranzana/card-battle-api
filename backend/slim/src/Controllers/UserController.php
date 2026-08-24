<?php

namespace App\Controllers;

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    private $userModel;

    public function __construct(User $userModel)
    {
        $this->userModel = $userModel;
    }

    public function registro(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            $nombre = $data['nombre'] ?? null;
            $usuario = $data['usuario'] ?? null;
            $password = $data['password'] ?? null;

             // Verificar si los parámetros requeridos están presentes
            if ($nombre === null || $usuario === null || $password === null) {
                $response->getBody()->write(json_encode(['error' => 'Faltan parámetros requeridos: nombre, usuario o password.']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json'); // 400 Bad Request
            }

            // Primero, verificar si el usuario ya existe
            if ($this->userModel->getUserByUsername($usuario)) {
                $response->getBody()->write(json_encode(['error' => 'El nombre de usuario ya está en uso.']));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json'); // 409 Conflict
            }

            // Si el usuario no existe, proceder con las validaciones
            if (strlen($usuario) < 6 || strlen($usuario) > 20 || !ctype_alnum($usuario)) {
                $response->getBody()->write(json_encode(['error' => 'El nombre de usuario debe tener entre 6 y 20 caracteres alfanuméricos.']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            if (strlen($password) < 8 || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()\-_=+{};:\'",.<>\/?]).{8,}$/', $password)) {
                $response->getBody()->write(json_encode(['error' => 'La clave debe tener al menos 8 caracteres y contener mayúsculas, minúsculas, números y caracteres especiales.']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            if ($this->userModel->createUser($nombre, $usuario, $password)) {
                $response->getBody()->write(json_encode(['message' => 'Usuario registrado exitosamente.']));
                return $response->withStatus(201)->withHeader('Content-Type', 'application/json'); // 201 Created
            } else {
                $response->getBody()->write(json_encode(['error' => 'Error al registrar el usuario.']));
                return $response->withStatus(500)->withHeader('Content-Type', 'application/json'); // 500 Internal Server Error
            }
        } catch (\PDOException $e) {
            // Capturar excepciones de la base de datos (PDOExceptions)
            // Registrar el error para depuración (usando logs, por ejemplo)
            error_log("Error de base de datos al registrar usuario: " . $e->getMessage());
            // Devolver una respuesta de error genérica al cliente
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor al registrar el usuario. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json'); // 500 Internal Server Error
        } catch (\Exception $e) {
            // Capturar otras excepciones generales
            error_log("Error general al registrar usuario: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json'); // 500 Internal Server Error
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $request->getAttribute('user');
            $requestedId = (int) $args['id']; // Ahora obtenemos el 'id' del parámetro de la ruta

            if ($user['id'] !== $requestedId) { // Comparamos el ID del usuario logueado con el ID solicitado
                $response->getBody()->write(json_encode(['error' => 'No tienes permiso para editar este usuario.']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            $data = $request->getParsedBody();
            $nombre = $data['nombre'] ?? null;
            $password = $data['password'] ?? null;

            // Obtener los datos actuales del usuario
            $usuarioActual = $this->userModel->getUserById($user['id']);

            if ($nombre !== null || $password !== null) {
                // Validar la nueva contraseña si se proporciona
                if ($password !== null) {
                    // Validacion longitud y otras
                    if (strlen($password) < 8 || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()\-_=+{};:\'",.<>\/?]).{8,}$/', $password)) {
                        $response->getBody()->write(json_encode(['error' => 'La nueva clave debe tener al menos 8 caracteres y contener mayúsculas, minúsculas, números y caracteres especiales.']));
                        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                    }
                    // Validacion de si la contraseña es la misma
                    if ($password === $usuarioActual['password']){
                        $response->getBody()->write(json_encode(['error' => 'La nueva contraseña es igual a la anterior. Por favor, ingrese una contraseña diferente.']));
                        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                    }
                }

                if ($nombre !== null && $nombre === $usuarioActual['nombre']){
                    $response->getBody()->write(json_encode(['error' => 'El nombre ingresado es igual a la anterior. Por favor, ingrese un nombre diferente.']));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }

                if ($this->userModel->updateProfile($user['id'], $nombre, $password)) { // Usamos el ID del usuario logueado para la actualización
                    $response->getBody()->write(json_encode(['message' => 'Perfil actualizado exitosamente.']));
                    return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
                } else {
                    $response->getBody()->write(json_encode(['error' => 'Error al actualizar el perfil.']));
                    return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
                }
            } else {
                $response->getBody()->write(json_encode(['error' => 'Debe proporcionar al menos un campo para actualizar (nombre o password).']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        } catch (\PDOException $e) {
            error_log("Error de base de datos al actualizar perfil: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor al actualizar el perfil. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error general al actualizar perfil: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $request->getAttribute('user');
            $requestedId = (int) $args['id']; // Ahora obtenemos el 'id' del parámetro de la ruta

            if ($user['id'] !== $requestedId) { // Comparamos el ID del usuario logueado con el ID solicitado
                $response->getBody()->write(json_encode(['error' => 'No tienes permiso para ver la información de este usuario.']));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }

            $userData = [
                'nombre' => $user['nombre'],
                'usuario' => $user['usuario'],
            ];
            
            $response->getBody()->write(json_encode($userData));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (\PDOException $e) {
            error_log("Error de base de datos al obtener usuario: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor al obtener la información del usuario. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error general al obtener usuario: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}   