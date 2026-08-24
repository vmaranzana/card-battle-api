<?php

namespace App\Models;

use DB;
use PDO;

class Jugada
{
    public static function registrarJugada(int $partidaId, int $cartaIdA, ?int $cartaIdB = null, ?string $elUsuario = null): bool
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("INSERT INTO jugada (partida_id, carta_id_a, carta_id_b, el_usuario) VALUES (:partida_id, :carta_id_a, :carta_id_b, :el_usuario)");
        $stmt->bindParam(':partida_id', $partidaId);
        $stmt->bindParam(':carta_id_a', $cartaIdA);
        $stmt->bindParam(':carta_id_b', $cartaIdB);
        $stmt->bindParam(':el_usuario', $elUsuario);
        return $stmt->execute();
    }

    public static function obtenerNumeroJugadas(int $partidaId): int
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM jugada WHERE partida_id = :partida_id");
        $stmt->bindParam(':partida_id', $partidaId);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public static function obtenerJugadasPorPartida(int $partidaId): array
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT * FROM jugada WHERE partida_id = :partida_id");
        $stmt->bindParam(':partida_id', $partidaId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}