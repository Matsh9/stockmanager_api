<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $query = "SELECT p.*, c.nome as categoria_nome, f.nome as fornecedor_nome 
                  FROM produtos p 
                  LEFT JOIN categorias c ON p.categoria_id = c.id 
                  LEFT JOIN fornecedores f ON p.fornecedor_id = f.id 
                  ORDER BY p.nome ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $produtos = $stmt->fetchAll();
        
        // Formatar valores numéricos
        foreach ($produtos as &$p) {
            $p['id'] = (int)$p['id'];
            $p['preco'] = (float)$p['preco'];
            $p['quantidade'] = (int)$p['quantidade'];
            $p['quantidade_minima'] = (int)$p['quantidade_minima'];
            $p['categoria_id'] = (int)$p['categoria_id'];
            $p['fornecedor_id'] = (int)$p['fornecedor_id'];
        }
        echo json_encode(["success" => true, "data" => $produtos]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->nome) && isset($data->preco) && isset($data->quantidade)) {
            if (!empty($data->id) && $data->id > 0) {
                $stmt = $conn->prepare("UPDATE produtos SET nome = :nome, descricao = :descricao, preco = :preco, quantidade = :quantidade, quantidade_minima = :quantidade_minima, categoria_id = :categoria_id, fornecedor_id = :fornecedor_id WHERE id = :id");
                $stmt->bindParam(":id", $data->id);
            } else {
                $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, preco, quantidade, quantidade_minima, categoria_id, fornecedor_id) VALUES (:nome, :descricao, :preco, :quantidade, :quantidade_minima, :categoria_id, :fornecedor_id)");
            }
            $stmt->bindParam(":nome", $data->nome);
            $stmt->bindParam(":descricao", $data->descricao);
            $stmt->bindParam(":preco", $data->preco);
            $stmt->bindParam(":quantidade", $data->quantidade);
            $stmt->bindParam(":quantidade_minima", $data->quantidade_minima);
            $stmt->bindParam(":categoria_id", $data->categoria_id);
            $stmt->bindParam(":fornecedor_id", $data->fornecedor_id);
            $stmt->execute();
            echo json_encode(["success" => true, "message" => "Produto salvo com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Preencha os campos obrigatórios."]);
        }
        break;

    case 'DELETE':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM produtos WHERE id = :id");
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            echo json_encode(["success" => true, "message" => "Produto excluído com sucesso!"]);
        }
        break;
}
?>