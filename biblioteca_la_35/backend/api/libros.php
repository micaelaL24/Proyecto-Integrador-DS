<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Obtener todos los libros
        $query = "SELECT 
                    ID_Ejemplar as id,
                    ISBN as isbn,
                    Titulo as titulo,
                    Autor as autor,
                    Genero as genero,
                    Formato as formato,
                    Cantidad_Disponible as cantidadTotal,
                    Cantidad_Disponible as disponibles
                  FROM Libro 
                  ORDER BY Titulo";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $libros = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Calcular libros disponibles (restando los prestados)
            $queryPrestados = "SELECT COALESCE(SUM(dp.Cantidad), 0) as prestados
                              FROM Detalle_Prestamo dp
                              INNER JOIN Prestamo p ON dp.FK_ID_Prestamo = p.ID_Prestamo
                              WHERE dp.FK_ID_Libro = :idLibro 
                              AND p.Estado_devolucion != 'Devuelto'";
            $stmtPrestados = $db->prepare($queryPrestados);
            $stmtPrestados->bindParam(":idLibro", $row['id']);
            $stmtPrestados->execute();
            $prestados = $stmtPrestados->fetch(PDO::FETCH_ASSOC)['prestados'];
            
            $row['disponibles'] = max(0, $row['cantidadTotal'] - $prestados);
            $libros[] = $row;
        }
        
        echo json_encode($libros);
        break;
        
    case 'POST':
        // Crear nuevo libro
        $data = json_decode(file_get_contents("php://input"));
        
        $query = "INSERT INTO Libro (ISBN, Titulo, Autor, Genero, Formato, Cantidad_Disponible) 
                  VALUES (:isbn, :titulo, :autor, :genero, :formato, :cantidad)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":isbn", $data->isbn);
        $stmt->bindParam(":titulo", $data->titulo);
        $stmt->bindParam(":autor", $data->autor);
        $stmt->bindParam(":genero", $data->genero);
        $stmt->bindParam(":formato", $data->formato);
        $stmt->bindParam(":cantidad", $data->cantidadTotal);
        
        if($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array(
                "success" => true, 
                "message" => "Libro creado", 
                "id" => $db->lastInsertId()
            ));
        } else {
            http_response_code(500);
            echo json_encode(array("success" => false, "message" => "Error al crear libro"));
        }
        break;
        
    case 'PUT':
        // Actualizar libro
        $data = json_decode(file_get_contents("php://input"));
        
        $query = "UPDATE Libro 
                  SET ISBN = :isbn, Titulo = :titulo, Autor = :autor, 
                      Genero = :genero, Formato = :formato, Cantidad_Disponible = :cantidad
                  WHERE ID_Ejemplar = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $data->id);
        $stmt->bindParam(":isbn", $data->isbn);
        $stmt->bindParam(":titulo", $data->titulo);
        $stmt->bindParam(":autor", $data->autor);
        $stmt->bindParam(":genero", $data->genero);
        $stmt->bindParam(":formato", $data->formato);
        $stmt->bindParam(":cantidad", $data->cantidadTotal);
        
        if($stmt->execute()) {
            echo json_encode(array("success" => true, "message" => "Libro actualizado"));
        } else {
            http_response_code(500);
            echo json_encode(array("success" => false, "message" => "Error al actualizar"));
        }
        break;
        
    case 'DELETE':
        // Eliminar libro
        $id = isset($_GET['id']) ? $_GET['id'] : die();
        
        $query = "DELETE FROM Libro WHERE ID_Ejemplar = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        
        if($stmt->execute()) {
            echo json_encode(array("success" => true, "message" => "Libro eliminado"));
        } else {
            http_response_code(500);
            echo json_encode(array("success" => false, "message" => "Error al eliminar"));
        }
        break;
}
?>