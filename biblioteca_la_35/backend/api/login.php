<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->tipo) && !empty($data->usuario) && !empty($data->contraseña)) {
    
    $tipo = $data->tipo;
    $usuario = $data->usuario;
    $contraseña = $data->contraseña;
    
    if ($tipo === 'bibliotecaria') {
        $query = "SELECT ID_Bibl as id, Nombre, Apellido, 'bibliotecaria' as tipo 
                  FROM Bibliotecario 
                  WHERE Usuario_Login = :usuario AND Contraseña = :contraseña";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":usuario", $usuario);
        $stmt->bindParam(":contraseña", $contraseña);
    } else {
        $query = "SELECT ID_Usuario as id, Nombre, Apellido, Tipo_Usuario as tipo, Curso 
                  FROM Usuario 
                  WHERE Usuario_Login = :usuario AND Contraseña = :contraseña 
                  AND Tipo_Usuario = :tipo";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":usuario", $usuario);
        $stmt->bindParam(":contraseña", $contraseña);
        $stmt->bindParam(":tipo", $tipo);
    }
    
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "message" => "Login exitoso",
            "usuario" => array(
                "id" => $row['id'],
                "nombre" => $row['Nombre'] . ' ' . $row['Apellido'],
                "tipo" => $row['tipo'],
                "curso" => isset($row['Curso']) ? $row['Curso'] : null
            )
        ));
    } else {
        http_response_code(401);
        echo json_encode(array("success" => false, "message" => "Credenciales inválidas"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Datos incompletos"));
}
?>