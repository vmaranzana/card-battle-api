<?php

namespace App\Models;

use DB;
use PDO;

class Partida
{
    public function usuarioTienePartidaEnCurso(int $idUsuario): bool
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM partida WHERE usuario_id = :usuario_id AND estado = 'en_curso'");
        $stmt->bindParam(':usuario_id', $idUsuario);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function crear($idUsuario, $idMazo): ?int 
    {
        $db = DB::getConnection();

        $fecha = date('Y-m-d H:i:s');
        $estado = "en_curso";

        $stmt = $db->prepare("INSERT INTO partida (usuario_id, fecha, mazo_id, estado) VALUES (:idUsuario, :fecha, :idMazo, :estado)");

        $stmt->bindParam(':idUsuario', $idUsuario);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->bindParam(':idMazo', $idMazo);
        $stmt->bindParam(':estado', $estado);

        if ($stmt->execute()) {
            return (int) $db->lastInsertId(); //Retorna el último id de la tabla
        }

        return null;
    }
    
    public function perteneceAUsuario($idPartida, $idUsuario): bool 
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT id FROM partida WHERE id = :idPartida AND usuario_id = :idUsuario");
        $stmt->bindParam(':idPartida', $idPartida);
        $stmt->bindParam(':idUsuario', $idUsuario);
        $stmt->execute();
    
        return $stmt->fetchColumn() > 0;
    }
  
    public static function obtenerPartidaPorIdYUsuario(int $partidaId, int $usuarioId): ?array
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT id, mazo_id, estado FROM partida WHERE id = :id AND usuario_id = :usuario_id");
        $stmt->bindParam(':id', $partidaId);
        $stmt->bindParam(':usuario_id', $usuarioId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null; // Devuelve el array o null si no lo encuentra.
    }
    
    public static function actualizarGanadorPartida(int $partidaId, string $elUsuario): bool
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("UPDATE partida SET el_usuario = :el_usuario, estado = 'finalizada' WHERE id = :id");
        $stmt->bindParam(':el_usuario', $elUsuario);
        $stmt->bindParam(':id', $partidaId);
        return $stmt->execute();
    }

    public static function obtenerEstadisticasPorUsuario(): array
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("
        SELECT
            u.usuario,
            p.usuario_id,
            SUM(CASE WHEN p.el_usuario = 'gano' AND p.estado = 'finalizada' THEN 1 ELSE 0 END) as partidas_ganadas,
            SUM(CASE WHEN p.el_usuario = 'perdio' AND p.estado = 'finalizada' THEN 1 ELSE 0 END) as partidas_perdidas,
            SUM(CASE WHEN p.el_usuario = 'empato' AND p.estado = 'finalizada' THEN 1 ELSE 0 END) as partidas_empatadas
        FROM partida p
        JOIN usuario u ON u.id = p.usuario_id
        GROUP BY p.usuario_id;
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}