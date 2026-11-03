<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $stmt = $conn->prepare("SELECT * FROM categorias ORDER BY nome ASC");
        $stmt->execute();
        $categorias = $stmt->fetchAll();
        echo json_encode(["success" => true, "data" => $categorias]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->nome)) {
            if (!empty($data->id) && $data->id > 0) {
                $stmt = $conn->prepare("UPDATE categorias SET nome = :nome, descricao = :descricao WHERE id = :id");
                $stmt->bindParam(":id", $data->id);
            } else {
                $stmt = $conn->prepare("INSERT INTO categorias (nome, descricao) VALUES (:nome, :descricao)");
            }
            $stmt->bindParam(":nome", $data->nome);
            $stmt->bindParam(":descricao", $data->descricao);
            $stmt->execute();
            echo json_encode(["success" => true, "message" => "Categoria salva com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Nome da categoria é obrigatório."]);
        }
        break;

    case 'DELETE':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM categorias WHERE id = :id");
                $stmt->bindParam(":id", $id);
                $stmt->execute();
                echo json_encode(["success" => true, "message" => "Categoria excluída com sucesso!"]);
            } catch(PDOException $e) {
                echo json_encode(["success" => false, "message" => "Não é possível excluir categoria com produtos vinculados."]);
            }
        }
        break;
}
?>