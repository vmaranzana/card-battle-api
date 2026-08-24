<?php

namespace App\Controllers;

use App\Models\Carta;
use App\Models\Jugada as JugadaModel;
use App\Models\MazoCarta;
use App\Models\Partida;
use App\Models\Mazo;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class JuegoController
{
    private $mazoModel;
    private $mazoCartaModel;
    private $jugadaModel;
    private $partidaModel;
    private $cartaModel;

    public function __construct(
        Mazo $mazoModel,
        MazoCarta $mazoCartaModel,
        JugadaModel $jugadaModel,
        Partida $partidaModel,
        Carta $cartaModel
    ) {
        $this->mazoModel = $mazoModel;
        $this->mazoCartaModel = $mazoCartaModel;
        $this->jugadaModel = $jugadaModel;
        $this->partidaModel = $partidaModel;
        $this->cartaModel = $cartaModel;
    }

    public function crearPartida(Request $request, Response $response, $args){
        try{
            $user = $request->getAttribute('user');
            $idUsuario = $user['id'];

            //Obtiene mazo_id pasado por postman
            $datos = $request->getParsedBody();
            $idMazo = $datos['mazo_id'] ?? null;

            // **VALIDACIÓN MEJORADA 1:** Un usuario no puede tener más de una partida en curso.
            if ($this->partidaModel->usuarioTienePartidaEnCurso($idUsuario)) {
                $response->getBody()->write(json_encode(['error' => 'Ya tienes una partida en curso. Finalízala antes de crear una nueva.']));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json'); // 409 Conflict
            }

            if(!$idMazo){
                $response->getBody()->write(json_encode(['error' => 'Debe proporcionar mazo_id']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            //Valida que el mazo pertenezca al usuario
            if(!$this->mazoModel->perteneceAUsuario($idMazo, $idUsuario)){
                $response->getBody()->write(json_encode(['error' => 'El mazo no pertenece al usuario logueado']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            //Crea partida para usuario logueado, con fecha actual y estado "en_curso"
            $idPartida = $this->partidaModel->crear($idUsuario, $idMazo);
            if(!$idPartida){
                $response->getBody()->write(json_encode(['error' => 'No se pudo crear la partida']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            //Actualiza el estado de las cartas a "en_mano"
            $this->mazoModel->actualizarCartasEnMano($idMazo);
            //Actualizo el estado de las cartas del server a "en_mano"
            $this->mazoModel->actualizarCartasEnMano(1);
            //Lista de las cartas del usuario -> devuelve su id y nombre
            $cartas = $this->cartaModel->obtenerCartasDelMazo($idMazo);

            $respuesta = [
                'id_partida' => $idPartida,
                'cartas' => $cartas
            ];

            $response->getBody()->write(json_encode($respuesta));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\PDOException $e) {
            error_log("Error de base de datos al crear la partida: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error general al crear la partida: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        
    }

    public function obtenerCartasEnManoServidor(Request $request, Response $response, array $args) {
        try {
            $partidaId = (int) $args['partidaId'];
            $usuarioLogueadoId = $request->getAttribute('user')['id']; // Todavía verificamos que haya un usuario loggeado
                
            $servidorId = 1; 

            //Valida que la partida pertenezca al usuario
            if (!$this->partidaModel->perteneceAUsuario($partidaId, $usuarioLogueadoId)) {
                $response->getBody()->write(json_encode(['error' => 'La partida no pertenece al usuario']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            // Obtener las cartas 'en_mano' del servidor para esta partida
            $cartasServidor = $this->cartaModel->obtenerCartasServidorDeLaPartida($partidaId);
            $cartasConAtributoNombre = [];
            foreach ($cartasServidor as $carta) {
                $atributo = $this->cartaModel->obtenerNombreAtributoPorId($carta['atributo_id']);
                $cartasConAtributoNombre[] = [
                    'id' => $carta['id'],
                    'nombre' => $carta['nombre'],
                    'atributo' => $atributo['nombre'] ?? null, // Incluir atributo para el reverso
                    'nombre_ataque' => $carta['ataque_nombre'] ?? null,
                    'puntos_ataque' => $carta['ataque'] ?? null,
                    // 'imageUrl' => $carta['url_imagen_carta'] ?? null
                ];
            }

            $response->getBody()->write(json_encode(['cartas_servidor' => $cartasConAtributoNombre]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\PDOException $e) {
            error_log("Error de base de datos al obtener cartas del servidor: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor (BD). Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error general al obtener cartas del servidor: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function obtenerCartasEnMano(Request $request, Response $response, $args){

        try{
            $usuarioId = (int) $args['usuario'];
            $partidaId = (int) $args['partida'];
    
            $user = $request->getAttribute('user');
            $usuarioLogueadoId = $user['id'];

            //Verifico que los datos que se piden sean del usuario logueado
            if ($usuarioId !== $usuarioLogueadoId) {
                $response->getBody()->write(json_encode(['error' => 'No tienes permiso para ver la información de este usuario.']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            //Valida que la partida pertenezca al usuario
            if (!$this->partidaModel->perteneceAUsuario($partidaId, $usuarioId)) {
                $response->getBody()->write(json_encode(['error' => 'La partida no pertenece al usuario']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            // Lista las cartas 'en_mano' del usuario
            $cartas = $this->cartaModel->obtenerCartasDeLaPartida($partidaId);
            $cartasConAtributoNombre = [];
            // Obtener el nombre del atributo para cada carta
            foreach ($cartas as $carta) {
                $atributo = $this->cartaModel->obtenerNombreAtributoPorId($carta['atributo_id']);
                $cartasConAtributoNombre[] = [
                    'id' => $carta['id'], 
                    'nombre' => $carta['nombre'], 
                    'atributo' => $atributo['nombre'] ?? null,
                    'nombre_ataque' => $carta['ataque_nombre'] ?? null, 
                    'puntos_ataque' => $carta['ataque'] ?? null, 
                    //'imageUrl' => $carta['url_imagen_carta'] ?? null 
                ];
            }

            $response->getBody()->write(json_encode(['cartas' => $cartasConAtributoNombre]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\PDOException $e) {
            error_log("Error de base de datos al obtener las cartas: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error general al obtener las cartas: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        
    }

    private function jugadaServidor(): ?int
    {
        // Obtengo las cartas del server
        $mazoIdServidor =  1;
        $cartasEnManoServidor = $this->mazoCartaModel->obtenerCartasEnManoPorMazo($mazoIdServidor);

        if (empty($cartasEnManoServidor)) {
            return null; // El servidor no tiene cartas en mano
        }

        // Eligo una carta aleatoria de las cartas en mano
        $indiceAleatorio = array_rand($cartasEnManoServidor);
        $cartaJugadaInfo = $cartasEnManoServidor[$indiceAleatorio];

        // Actualizar el estado de la carta a "descartado"
        $this->mazoCartaModel->actualizarEstadoCarta($cartaJugadaInfo['id'], 'descartado');
        
        // Devuelvo la carta elegida aleatoriamente
        return $cartaJugadaInfo['carta_id'];
    }

    public function realizarJugada(Request $request, Response $response): Response
    {
        try {
            $userId = $request->getAttribute('user')['id'];
            $data = $request->getParsedBody();
            $cartaJugadaUsuarioId = $data['carta_jugada'] ?? null;
            $partidaId = $data['id_partida'] ?? null;

            // Validar los datos necesarios
            if (!$cartaJugadaUsuarioId || !$partidaId) {
                $error = ['error' => 'Se deben proporcionar la carta jugada y el ID de la partida.'];
                $response->getBody()->write(json_encode($error));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // Validar el tipo de los datos 
            if (!is_int($partidaId) || $partidaId <= 0) {
                $response->getBody()->write(json_encode(['error' => 'El ID de la partida no es del tipo valido.']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            } elseif (!is_int($cartaJugadaUsuarioId) || $cartaJugadaUsuarioId <= 0) {
                $response->getBody()->write(json_encode(['error' => 'El ID de la carta jugada no es del tipo valido.']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // Obtener información de la partida
            $partida = $this->partidaModel->obtenerPartidaPorIdYUsuario($partidaId, $userId);
            
            // Valida que la partida sea del usuario
            if (!$partida) {
                $response->getBody()->write(json_encode(['error' => 'La partida no pertenece al usuario.']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            // Valida el estado de la partida obtenida
            if ($partida['estado'] !== 'en_curso') {
                $response->getBody()->write(json_encode(['error' => 'La partida no está en curso.']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $mazoIdUsuario = $partida['mazo_id'];
            $mazoIdServidor = 1; // ID del mazo del servidor

            // Verificar si la carta del usuario está en su mano
            $cartaEnManoUsuario = $this->mazoCartaModel->obtenerCartaEnManoPorCartaIdMazoId($cartaJugadaUsuarioId, $mazoIdUsuario);
            if (!$cartaEnManoUsuario) {
                $error = ['error' => 'La carta jugada no está en tu mazo o ya la utilizaste, intenta con otra carta.'];
                $response->getBody()->write(json_encode($error));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // Obtener la jugada del servidor (y marcar su carta como descartada)
            $cartaIdServidor = $this->jugadaServidor($partidaId, $mazoIdServidor);

            if ($cartaIdServidor !== null) {
                // Determinar el ganador de la jugada ANTES de registrarla
                $resultadoJugadaInfo = $this->determinarGanadorJugada($cartaJugadaUsuarioId, $cartaIdServidor);

                // Registrar la jugada del usuario y del servidor con el resultado
                $this->jugadaModel->registrarJugada($partidaId, $cartaJugadaUsuarioId, $cartaIdServidor, $resultadoJugadaInfo['resultado']);
                $this->mazoCartaModel->actualizarEstadoCarta($cartaEnManoUsuario['id'], 'descartado');
            
                // Verificar si es la quinta jugada
                $numeroJugada = $this->jugadaModel->obtenerNumeroJugadas($partidaId);
                $ganadorJuego = "Partida en curso";
                if ($numeroJugada == 5) {
                    $ganadorJuego = $this->determinarGanadorJuego($partidaId, $userId);
                    $this->partidaModel->actualizarGanadorPartida($partidaId, $ganadorJuego);

                    // Actualizar el estado de las cartas descartadas del usuario y el servidor ('descartado'=>'en_mazo')
                    $this->mazoCartaModel->resetearEstadoMazo($mazoIdUsuario);
                    $this->mazoCartaModel->resetearEstadoMazo($mazoIdServidor); 
                }

                $dataRespuesta = [
                    'carta_servidor' => $cartaIdServidor,
                    'ataque_usuario' => $resultadoJugadaInfo['ataque_usuario'],
                    'ataque_servidor' => $resultadoJugadaInfo['ataque_servidor'],
                    'resultado_jugada' => $resultadoJugadaInfo['resultado'],
                    'ganador_juego' => $ganadorJuego
                ];

                $response->getBody()->write(json_encode($dataRespuesta));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

            } else {
                $error = ['error' => 'El servidor no pudo realizar una jugada.'];
                $response->getBody()->write(json_encode($error));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

        } catch (\PDOException $e) {
            error_log("Error de base de datos al realizar jugada: " . $e->getMessage());
            $error = ['error' => 'Error interno del servidor al realizar la jugada. Por favor, inténtelo de nuevo más tarde.'];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error general al realizar jugada: " . $e->getMessage());
            $error = ['error' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.'];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
    
    private function determinarGanadorJugada(int $cartaUsuarioId, int $cartaServidorId): array
    {
        $cartaUsuario = $this->cartaModel->obtenerCartaPorId($cartaUsuarioId);
        $cartaServidor = $this->cartaModel->obtenerCartaPorId($cartaServidorId);

        $ataqueUsuario = $cartaUsuario['ataque'];
        $atributoUsuarioId = $cartaUsuario['atributo_id'];
        $ataqueServidor = $cartaServidor['ataque'];
        $atributoServidorId = $cartaServidor['atributo_id'];

        // Aplicar el bono de ataque por atributo
        if ($this->cartaModel->verificaGanadorAtributo($atributoUsuarioId, $atributoServidorId)){
            $ataqueUsuario *= 1.30; // +30% al ataque del usuario
        } elseif ($this->cartaModel->verificaGanadorAtributo($atributoServidorId, $atributoUsuarioId)) {
            $ataqueServidor *= 1.30; // +30% al ataque del servidor
        }

        $resultado = '';
        // Comparar los ataques
        if ($ataqueUsuario > $ataqueServidor) {
            $resultado = 'gano';
        } elseif ($ataqueUsuario < $ataqueServidor) {
            $resultado = 'perdio';
        } else {
            $resultado = 'empato';
        }

        return [
            'resultado' => $resultado,
            'ataque_usuario' => round($ataqueUsuario, 2), // Redondear a 2 decimales para mejor presentación
            'ataque_servidor' => round($ataqueServidor, 2), // Redondear a 2 decimales para mejor presentación
        ];
    }

    private function determinarGanadorJuego(int $partidaId, int $usuarioId): ?string
    {
        $jugadas = $this->jugadaModel->obtenerJugadasPorPartida($partidaId);
        $ganadasUsuario = 0;
        $ganadasServidor = 0;

        //NECESARIAS LA VALIDACIONES $jugada['carta_id_a'] !== null?
        foreach ($jugadas as $jugada) {
            if ($jugada['el_usuario'] === 'gano') {
                $ganadasUsuario++;
            } elseif ($jugada['el_usuario'] === 'perdio') {
                $ganadasServidor++;
            }
        }

        if ($ganadasUsuario > $ganadasServidor) {
            return 'gano';
        } elseif ($ganadasServidor > $ganadasUsuario) {
            return 'perdio';
        } else {
            return 'empato';
        }
    }

    public function obtenerEstadisticasPorUsuario(Request $request, Response $response)
    {
        try {
            $estadisticas = $this->partidaModel->obtenerEstadisticasPorUsuario();

            // Agregamos los cálculos necesarios antes de responder
            foreach ($estadisticas as &$jugador) {
                $total = $jugador['partidas_ganadas'] + $jugador['partidas_perdidas'] + $jugador['partidas_empatadas'];
                $jugador['total_partidas'] = $total;
                $jugador['winrate'] = $total > 0 ? round(($jugador['partidas_ganadas'] / $total) * 100, 2) : 0;
            }

            $response->getBody()->write(json_encode($estadisticas));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\PDOException $e) {
            error_log("Error de base de datos al obtener estadísticas por usuario: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor al obtener las estadísticas por usuario. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error general al obtener estadísticas por usuario: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Error interno del servidor. Por favor, inténtelo de nuevo más tarde.']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

}