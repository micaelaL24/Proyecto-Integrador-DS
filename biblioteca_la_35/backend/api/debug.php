<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo json_encode(array(
            "success" => true,
            "message" => "Backend funcionando correctamente",
            "database" => "Conectado"
        ));
    } else {
        echo json_encode(array(
            "success" => false,
            "message" => "Error de conexión a la base de datos"
        ));
    }
} catch (Exception $e) {
    echo json_encode(array(
        "success" => false,
        "error" => $e->getMessage()
    ));
}
?>