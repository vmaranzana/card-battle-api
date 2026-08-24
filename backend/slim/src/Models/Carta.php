<?php

namespace App\Models;

use DB;
use PDO;

class Carta
{
    public static function obtenerCartaPorId(int $cartaId): ?array
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT id, nombre, ataque, atributo_id FROM carta WHERE id = :id");
        $stmt->bindParam(':id', $cartaId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function verificaGanadorAtributo(int $atributoId1, int $atributoId2): bool
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT * FROM gana_a WHERE atributo_id = :attr1 AND atributo_id2 = :attr2");
        $stmt->bindParam(':attr1', $atributoId1);
        $stmt->bindParam(':attr2', $atributoId2);
        $stmt->execute();
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function obtenerCartasDelMazo($idMazo): array
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("
            SELECT mc.carta_id AS numero_carta, c.nombre
            FROM mazo_carta mc
            JOIN carta c ON mc.carta_id = c.id
            WHERE mc.mazo_id = :idMazo AND mc.estado = 'en_mano'
        ");
        $stmt->bindParam(':idMazo', $idMazo);
        $stmt->execute();

        //Retorna un arreglo con el id y nombre de las cartas del mazo
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerCartasDeLaPartida($idPartida): array 
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("
            SELECT mc.carta_id AS id, c.nombre, c.atributo_id, c.ataque, c.ataque_nombre
            FROM mazo_carta mc
            JOIN carta c ON mc.carta_id = c.id
            JOIN partida p ON mc.mazo_id = p.mazo_id
            WHERE p.id = :idPartida AND mc.estado = 'en_mano'
        ");
        $stmt->bindParam(':idPartida', $idPartida);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerCartasServidorDeLaPartida($idPartida): array
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("
            SELECT 
                mc.carta_id AS id, c.nombre, c.atributo_id, c.ataque_nombre, c.ataque
            FROM 
                mazo_carta mc
            JOIN 
                carta c ON mc.carta_id = c.id
            WHERE 
                mc.mazo_id = 1 AND mc.estado = 'en_mano'
            AND EXISTS (SELECT 1 FROM partida p WHERE p.id = :idPartida AND p.estado = 'en_curso');
        ");
        $stmt->bindParam(':idPartida', $idPartida, PDO::PARAM_INT);
        $stmt->execute();
            
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerNombreAtributoPorId(int $atributoId): ?array
    {
        $db = DB::getConnection();
        $stmt = $db->prepare("
            SELECT nombre
            FROM atributo
            WHERE id = :atributoId
        ");
        $stmt->bindParam(':atributoId', $atributoId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}