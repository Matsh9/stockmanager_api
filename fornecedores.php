<?php
http_response_code(200);
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $stmt = $conn->prepare("SELECT * FROM fornecedores ORDER BY nome ASC");
        $stmt->execute();
        $fornecedores = $stmt->fetchAll();
        echo json_encode(["success" => true, "data" => $fornecedores]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->nome)) {
            if (!empty($data->id) && $data->id > 0) {
                $stmt = $conn->prepare("UPDATE fornecedores SET nome = :nome, telefone = :telefone, email = :email, cnpj = :cnpj WHERE id = :id");
                $stmt->bindParam(":id", $data->id);
            } else {
                $stmt = $conn->prepare("INSERT INTO fornecedores (nome, telefone, email, cnpj) VALUES (:nome, :telefone, :email, :cnpj)");
            }
            $stmt->bindParam(":nome", $data->nome);
            $stmt->bindParam(":telefone", $data->telefone);
            $stmt->bindParam(":email", $data->email);
            $stmt->bindParam(":cnpj", $data->cnpj);
            $stmt->execute();
            echo json_encode(["success" => true, "message" => "Fornecedor salvo com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Nome do fornecedor é obrigatório."]);
        }
        break;

    case 'DELETE':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM fornecedores WHERE id = :id");
                $stmt->bindParam(":id", $id);
                $stmt->execute();
                echo json_encode(["success" => true, "message" => "Fornecedor excluído com sucesso!"]);
            } catch(PDOException $e) {
                echo json_encode(["success" => false, "message" => "Não foi possível excluir fornecedor vinculado a produtos."]);
            }
        }
        break;
}
?>