<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    echo json_encode(array("success" => false, "message" => "Error de conexión"));
    exit();
}

// Simular datos de registro
$testData = array(
    "tipo" => "estudiante",
    "nombre" => "Test",
    "apellido" => "Usuario",
    "dni" => "99999999",
    "telefono" => "1234567890",
    "usuario" => "test_" . time(), // Usuario único
    "contraseña" => "test123",
    "curso" => "5° A"
);

try {
    $query = "INSERT INTO Usuario (Nombre, Apellido, DNI, Telefono, Usuario_Login, Contraseña, Tipo_Usuario, Curso, Estado) 
              VALUES (:nombre, :apellido, :dni, :telefono, :usuario, :contraseña, :tipo, :curso, 'Activo')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":nombre", $testData['nombre']);
    $stmt->bindParam(":apellido", $testData['apellido']);
    $stmt->bindParam(":dni", $testData['dni']);
    $stmt->bindParam(":telefono", $testData['telefono']);
    $stmt->bindParam(":usuario", $testData['usuario']);
    $stmt->bindParam(":contraseña", $testData['contraseña']);
    $stmt->bindParam(":tipo", $testData['tipo']);
    $stmt->bindParam(":curso", $testData['curso']);
    
    if($stmt->execute()) {
        echo json_encode(array(
            "success" => true, 
            "message" => "Usuario de prueba registrado",
            "usuario" => $testData['usuario']
        ));
    } else {
        echo json_encode(array("success" => false, "message" => "Error al insertar"));
    }
} catch(PDOException $e) {
    echo json_encode(array(
        "success" => false, 
        "message" => "Error SQL: " . $e->getMessage()
    ));
}
?>