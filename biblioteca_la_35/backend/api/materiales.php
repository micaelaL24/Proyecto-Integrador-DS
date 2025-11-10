<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

// Directorio para guardar archivos
$uploadDir = "../uploads/materiales/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

switch($method) {
    case 'GET':
        if (isset($_GET['download'])) {
            // Descargar archivo
            $id = $_GET['download'];
            
            $query = "SELECT Nombre_Archivo, Ruta_Archivo FROM Material_Didactico WHERE ID_Material = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $material = $stmt->fetch(PDO::FETCH_ASSOC);
                $filepath = $material['Ruta_Archivo'];
                
                if (file_exists($filepath)) {
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename="' . $material['Nombre_Archivo'] . '"');
                    readfile($filepath);
                    exit();
                } else {
                    http_response_code(404);
                    echo json_encode(array("success" => false, "message" => "Archivo no encontrado"));
                }
            } else {
                http_response_code(404);
                echo json_encode(array("success" => false, "message" => "Material no encontrado"));
            }
        } else {
            // Obtener todos los materiales
            $query = "SELECT 
                        m.ID_Material as id,
                        m.Titulo as titulo,
                        m.Materia as materia,
                        m.Descripcion as descripcion,
                        CONCAT(u.Nombre, ' ', u.Apellido) as profesor,
                        m.Fecha_Subida as fecha
                      FROM Material_Didactico m
                      INNER JOIN Usuario u ON m.FK_ID_Profesor = u.ID_Usuario
                      ORDER BY m.Fecha_Subida DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $materiales = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $materiales[] = $row;
            }
            
            echo json_encode($materiales);
        }
        break;
        
    case 'POST':
        // Subir nuevo material
        if (isset($_FILES['archivo']) && isset($_POST['titulo']) && 
            isset($_POST['materia']) && isset($_POST['idProfesor'])) {
            
            $file = $_FILES['archivo'];
            $titulo = $_POST['titulo'];
            $materia = $_POST['materia'];
            $descripcion = $_POST['descripcion'];
            $idProfesor = $_POST['idProfesor'];
            
            // Validar que sea PDF
            $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($fileType != "pdf") {
                http_response_code(400);
                echo json_encode(array("success" => false, "message" => "Solo se permiten archivos PDF"));
                exit();
            }
            
            // Generar nombre único
            $nombreArchivo = uniqid() . "_" . basename($file['name']);
            $rutaArchivo = $uploadDir . $nombreArchivo;
            
            if (move_uploaded_file($file['tmp_name'], $rutaArchivo)) {
                $query = "INSERT INTO Material_Didactico 
                          (Titulo, Materia, Descripcion, Nombre_Archivo, Ruta_Archivo, FK_ID_Profesor, Fecha_Subida) 
                          VALUES (:titulo, :materia, :descripcion, :nombreArchivo, :rutaArchivo, :idProfesor, CURDATE())";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(":titulo", $titulo);
                $stmt->bindParam(":materia", $materia);
                $stmt->bindParam(":descripcion", $descripcion);
                $stmt->bindParam(":nombreArchivo", $nombreArchivo);
                $stmt->bindParam(":rutaArchivo", $rutaArchivo);
                $stmt->bindParam(":idProfesor", $idProfesor);
                
                if($stmt->execute()) {
                    http_response_code(201);
                    echo json_encode(array("success" => true, "message" => "Material subido exitosamente"));
                } else {
                    http_response_code(500);
                    echo json_encode(array("success" => false, "message" => "Error al guardar en BD"));
                }
            } else {
                http_response_code(500);
                echo json_encode(array("success" => false, "message" => "Error al subir archivo"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("success" => false, "message" => "Datos incompletos"));
        }
        break;
        
    case 'DELETE':
        // Eliminar material
        $id = isset($_GET['id']) ? $_GET['id'] : die();
        
        // Obtener ruta del archivo para eliminarlo
        $query = "SELECT Ruta_Archivo FROM Material_Didactico WHERE ID_Material = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $material = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Eliminar de la BD
            $queryDelete = "DELETE FROM Material_Didactico WHERE ID_Material = :id";
            $stmtDelete = $db->prepare($queryDelete);
            $stmtDelete->bindParam(":id", $id);
            
            if($stmtDelete->execute()) {
                // Eliminar archivo físico
                if (file_exists($material['Ruta_Archivo'])) {
                    unlink($material['Ruta_Archivo']);
                }
                echo json_encode(array("success" => true, "message" => "Material eliminado"));
            } else {
                http_response_code(500);
                echo json_encode(array("success" => false, "message" => "Error al eliminar"));
            }
        } else {
            http_response_code(404);
            echo json_encode(array("success" => false, "message" => "Material no encontrado"));
        }
        break;
}
?>