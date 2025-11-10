<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->tipo) && !empty($data->nombre) && !empty($data->apellido) && 
    !empty($data->dni) && !empty($data->telefono) && !empty($data->usuario) && 
    !empty($data->contraseña)) {
    
    $tipo = $data->tipo;
    $nombre = $data->nombre;
    $apellido = $data->apellido;
    $dni = $data->dni;
    $telefono = $data->telefono;
    $usuario = $data->usuario;
    $contraseña = $data->contraseña;
    $curso = isset($data->curso) ? $data->curso : null;
    
    // Verificar si el usuario ya existe
    $checkQuery = "SELECT Usuario_Login FROM Usuario WHERE Usuario_Login = :usuario
                   UNION
                   SELECT Usuario_Login FROM Bibliotecario WHERE Usuario_Login = :usuario";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":usuario", $usuario);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "El nombre de usuario ya existe"));
        exit();
    }
    
    // Insertar nuevo usuario
    $query = "INSERT INTO Usuario (Nombre, Apellido, DNI, Telefono, Usuario_Login, Contraseña, Tipo_Usuario, Curso, Estado) 
              VALUES (:nombre, :apellido, :dni, :telefono, :usuario, :contraseña, :tipo, :curso, 'Activo')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":nombre", $nombre);
    $stmt->bindParam(":apellido", $apellido);
    $stmt->bindParam(":dni", $dni);
    $stmt->bindParam(":telefono", $telefono);
    $stmt->bindParam(":usuario", $usuario);
    $stmt->bindParam(":contraseña", $contraseña);
    $stmt->bindParam(":tipo", $tipo);
    $stmt->bindParam(":curso", $curso);
    
    if($stmt->execute()) {
        http_response_code(201);
        echo json_encode(array("success" => true, "message" => "Usuario registrado exitosamente"));
    } else {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Error al registrar usuario"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Datos incompletos"));
}
?>