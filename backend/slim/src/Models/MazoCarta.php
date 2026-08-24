<?php

namespace App\Models;

use DB;
use PDO;

class MazoCarta
{
    public static function obtenerCartasEnManoPorMazo(int $mazoId): array
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT id, carta_id FROM mazo_carta WHERE mazo_id = :mazo_id AND estado = 'en_mano'");
        $stmt->bindParam(':mazo_id', $mazoId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerCartaEnManoPorCartaIdMazoId(int $cartaId, int $mazoId): ?array
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT mc.* FROM mazo_carta mc WHERE mc.carta_id = :carta_id AND mc.mazo_id = :mazo_id AND mc.estado = 'en_mano'");
        $stmt->bindParam(':carta_id', $cartaId);
        $stmt->bindParam(':mazo_id', $mazoId);
        $stmt->execute();
        $cartaEnMano = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cartaEnMano ?: null; // Devolver $cartaEnMano si es un array, null si es false
    }

    public static function actualizarEstadoCarta(int $mazoCartaId, string $estado): bool
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("UPDATE mazo_carta SET estado = :estado WHERE id = :id");
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':id', $mazoCartaId);
        return $stmt->execute();
    }

    public static function resetearEstadoMazo(int $mazoId): bool
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("UPDATE mazo_carta SET estado = 'en_mazo' WHERE mazo_id = :mazo_id AND estado = 'descartado'");
        $stmt->bindParam(':mazo_id', $mazoId);
        return $stmt->execute();
    }

}