<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Obtener todos los préstamos
        $query = "SELECT 
                    p.ID_Prestamo as id,
                    p.Numero_Operacion as numeroOperacion,
                    CONCAT(u.Nombre, ' ', u.Apellido) as usuario,
                    l.Titulo as libro,
                    p.Fecha_prestamo as fechaRetiro,
                    p.Fecha_devolucion as fechaDevolucion,
                    p.Estado_devolucion as estado
                  FROM Prestamo p
                  INNER JOIN Usuario u ON p.FK_ID_Usuario = u.ID_Usuario
                  INNER JOIN Detalle_Prestamo dp ON p.ID_Prestamo = dp.FK_ID_Prestamo
                  INNER JOIN Libro l ON dp.FK_ID_Libro = l.ID_Ejemplar
                  WHERE p.Estado_devolucion != 'Devuelto'
                  ORDER BY p.Fecha_prestamo DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $prestamos = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $prestamos[] = $row;
        }
        
        echo json_encode($prestamos);
        break;
        
    case 'POST':
        // Crear nuevo préstamo
        $data = json_decode(file_get_contents("php://input"));
        
        // Generar número de operación único
        $numeroOp = "OP-" . date('Y') . "-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Iniciar transacción
        $db->beginTransaction();
        
        try {
            // Insertar préstamo
            $query = "INSERT INTO Prestamo 
                      (Numero_Operacion, Fecha_prestamo, Fecha_devolucion, Estado_devolucion, FK_ID_Usuario, FK_ID_Bibliotecario) 
                      VALUES (:numeroOp, :fechaPrestamo, :fechaDevolucion, 'Activo', :idUsuario, 1)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":numeroOp", $numeroOp);
            $stmt->bindParam(":fechaPrestamo", $data->fechaPrestamo);
            $stmt->bindParam(":fechaDevolucion", $data->fechaDevolucion);
            $stmt->bindParam(":idUsuario", $data->idUsuario);
            $stmt->execute();
            
            $idPrestamo = $db->lastInsertId();
            
            // Insertar detalle de préstamo
            $queryDetalle = "INSERT INTO Detalle_Prestamo (FK_ID_Prestamo, FK_ID_Libro, Cantidad) 
                            VALUES (:idPrestamo, :idLibro, 1)";
            $stmtDetalle = $db->prepare($queryDetalle);
            $stmtDetalle->bindParam(":idPrestamo", $idPrestamo);
            $stmtDetalle->bindParam(":idLibro", $data->idLibro);
            $stmtDetalle->execute();
            
            // Obtener datos del libro para la respuesta
            $queryLibro = "SELECT Titulo FROM Libro WHERE ID_Ejemplar = :idLibro";
            $stmtLibro = $db->prepare($queryLibro);
            $stmtLibro->bindParam(":idLibro", $data->idLibro);
            $stmtLibro->execute();
            $libro = $stmtLibro->fetch(PDO::FETCH_ASSOC);
            
            $db->commit();
            
            http_response_code(201);
            echo json_encode(array(
                "success" => true, 
                "message" => "Préstamo creado",
                "prestamo" => array(
                    "id" => $idPrestamo,
                    "numeroOperacion" => $numeroOp,
                    "libro" => $libro['Titulo'],
                    "fechaRetiro" => $data->fechaPrestamo,
                    "fechaDevolucion" => $data->fechaDevolucion,
                    "estado" => "Activo"
                )
            ));
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(array("success" => false, "message" => "Error al crear préstamo: " . $e->getMessage()));
        }
        break;
        
    case 'PUT':
        // Marcar como devuelto
        $data = json_decode(file_get_contents("php://input"));
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        
        if ($id) {
            $query = "UPDATE Prestamo 
                      SET Estado_devolucion = 'Devuelto'
                      WHERE ID_Prestamo = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $id);
            
            if($stmt->execute()) {
                echo json_encode(array("success" => true, "message" => "Préstamo actualizado"));
            } else {
                http_response_code(500);
                echo json_encode(array("success" => false, "message" => "Error al actualizar"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("success" => false, "message" => "ID no proporcionado"));
        }
        break;
}
?>