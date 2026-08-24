<?php

namespace App\Controllers;

use App\Models\Mazo;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MazoController
{
    private $mazoModel;

    public function __construct(Mazo $mazoModel)
    {
        $this->mazoModel = $mazoModel;
    }

    public function crearMazo(Request $request, Response $response) {
        try {
            $user = $request->getAttribute('user');
            $usuario_id = $user['id'];
            $data = $request->getParsedBody();
            $carta_id = $data['carta_id'] ?? null;
            $nombre = $data['nombre'] ?? null;
            $idUnicos = array_unique($carta_id);

            if ($nombre === null || $carta_id=== null) {
                $response->getBody()->write(json_encode(['error' => 'Faltan parámetros requeridos: nombre o cartas']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json'); // 400 Bad Request
            }
            
            foreach ($carta_id as $id){
                if ($id>30)
                {
                    $response->getBody()->write(json_encode(['error' => 'Valor incorrecto de ID']));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }
            }
            if (count($carta_id) == 5 ){
                if (count($idUnicos)!==5)
                {
                    $response->getBody()->write(json_encode(['error' => 'ID de cartas repetidos']));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }
            }
            else
            {
                $response->getBody()->write(json_encode(['error' => 'Cantidad incorrecta de cartas']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $mazo_id = $this->mazoModel->insertarMazo($usuario_id,$carta_id,$nombre);
            if ($mazo_id>0)
            {
                $response->getBody()->write(json_encode(['mazo_id' => $mazo_id, 'nombre' => $data['nombre']]));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json');;
            }
            else
            {
                $response->getBody()->write(json_encode(['error' => 'Limite de mazos por usuario']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        } catch (\PDOException $e) {
            error_log("Error de base de datos al crear mazo: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor al crear el mazo. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error general al crear mazo: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function eliminarMazo(Request $request, Response $response, array $args)
    {
        try {
            $user = $request->getAttribute('user');
            $idUsuario = $user['id'];
            $mazoId = $args['mazo'];

            // Validar que el mazo exista y pertenezca al usuario logueado
            if (!$this->mazoModel->perteneceAUsuario($mazoId, $idUsuario)) {
                $response->getBody()->write(json_encode(['error' => 'El mazo no pertenece al usuario logueado']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json'); 
            }

            // Validar si el mazo ha participado en alguna partida
            if($this->mazoModel->jugado($args['mazo'])) {
                $response->getBody()->write(json_encode(['error' => 'Mazo ya utilizado, no se puede eliminar']));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }

            $eliminado = $this->mazoModel->eliminar($mazoId);
            if ($eliminado === true)
            {
                $response->getBody()->write(json_encode(['exito' => 'Mazo eliminado']));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
            }
            elseif ($eliminado === false)
            {
                $response->getBody()->write(json_encode(['error' => 'No se pudo eliminar el mazo, id incorrecto']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
            else // $eliminado === null (error al eliminar las cartas)
            {
                $response->getBody()->write(json_encode(['error' => 'Error al eliminar el mazo.']));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }
        } catch (\PDOException $e) {
            error_log("Error de base de datos al eliminar mazo: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor al eliminar el mazo. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error general al eliminar mazo: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function modificarMazo(Request $request, Response $response, array $args)
    {
        try {
            $data = $request->getParsedBody();
            $nombre = $data['nombre'] ?? null;

            $mazo_id = $args['mazo'];

            $user =$request->getAttribute('user');
            $usuario_id = $user['id'];

            // Validar los datos necesarios
            if (!$nombre) {
                $response->getBody()->write(json_encode(['error' => 'Se debe proporcionar el nombre para realizar la modificacion.']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            if ($this->mazoModel->perteneceAUsuario($mazo_id,$usuario_id)){
                if($this->mazoModel->modificarNombre($nombre,$mazo_id))
                {
                    $response->getBody()->write(json_encode(['exito' => 'Nombre de mazo modificado']));
                    return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
                }
                else 
                {
                    $response->getBody()->write(json_encode(['error' => 'No se pudo modificar el nombre del mazo, id incorrecto']));
                    return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
                }
            }
            else 
            {
                $response->getBody()->write(json_encode(['error' => 'El mazo no pertenece al usuario logueado']));
                    return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }
        } catch (\PDOException $e) {
            error_log("Error de base de datos al modificar nombre del mazo: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor al modificar el nombre del mazo. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error general al modificar nombre del mazo: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function listarMazos(Request $request, Response $response, array $args)
    {
        try {
            $user = $request->getAttribute('user');
            $requestedId = (int) $args['id'];
            if ($user['id']==$requestedId)
            {
                $lista = $this->mazoModel->listaMazos($user['id']);
                if(empty($lista))
                {
                    $response->getBody()->write(json_encode(['lista de mazos del usuario logueado vacia' => $lista]));
                    return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
                }
                else
                {
                    $response->getBody()->write(json_encode(['lista de mazos del usuario logueado' => $lista]));
                    return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
                }
            }
            else
            {
                $response->getBody()->write(json_encode(['error' => 'Acceso invalido a la informacion de este usuario']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }
        } catch (\PDOException $e) {
            error_log("Error de base de datos al listar mazos: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor al listar los mazos. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error general al listar mazos: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }   

    public function listarCartas(Request $request, Response $response, array $args)
    {
        try {
            $params = $request->getQueryParams();
            $atributo = $params['atributo'] ?? null;
            $atributo_id = null;
            if ($atributo){
                $atributo_id = $this->mazoModel->obtenerAtributoId($atributo);
                if ($atributo_id === null) {
                    $response->getBody()->write(json_encode(['error' => 'El atributo especificado no existe.']));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }
            }
            else {
                $atributo_id = null;
            }
            $nombre = $params['nombre'] ?? null;
            $lista = $this->mazoModel->listaCartas($atributo_id,$nombre);
            if(empty($lista))
            {
                $response->getBody()->write(json_encode(['error' => 'No hay cartas que cumplan con los criterios']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            else
            {
                $response->getBody()->write(json_encode(['lista de cartas por criterios' => $lista]));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
            }
        } catch (\PDOException $e) {
            error_log("Error de base de datos al listar cartas: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor al listar las cartas. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error general al listar cartas: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    //Listar cartas de un mazo - AGREGADO PARA EL FRONT
    public function listarCartasDeUnMazo(Request $request, Response $response, array $args){
        try {
            $user = $request->getAttribute('user');
            $usuarioId = (int) $args['usuarioId'];
            $mazoId = (int) $args['mazoId'];

            if ($user['id'] !== $usuarioId) {
                $response->getBody()->write(json_encode(['error' => 'Acceso inválido']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            if (!$this->mazoModel->perteneceAUsuario($mazoId, $usuarioId)) {
                $response->getBody()->write(json_encode(['error' => 'El mazo no pertenece al usuario']));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }

            $cartas = $this->mazoModel->listaCartasMazo($mazoId);
            $response->getBody()->write(json_encode(['cartas' => $cartas]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log("Error al listar cartas del mazo: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

}