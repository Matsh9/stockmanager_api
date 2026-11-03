<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $query = "SELECT v.*, u.nome as usuario_nome 
                  FROM vendas v 
                  LEFT JOIN usuarios u ON v.usuario_id = u.id 
                  ORDER BY v.data_venda DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $vendas = $stmt->fetchAll();

        foreach ($vendas as &$v) {
            $v['id'] = (int)$v['id'];
            $v['usuario_id'] = (int)$v['usuario_id'];
            $v['valor_total'] = (float)$v['valor_total'];

            // Buscar os itens de cada venda
            $stmtItens = $conn->prepare("SELECT iv.*, p.nome as produto_nome FROM itens_venda iv LEFT JOIN produtos p ON iv.produto_id = p.id WHERE iv.venda_id = :venda_id");
            $stmtItens->bindParam(":venda_id", $v['id']);
            $stmtItens->execute();
            $itens = $stmtItens->fetchAll();
            foreach ($itens as &$item) {
                $item['id'] = (int)$item['id'];
                $item['venda_id'] = (int)$item['venda_id'];
                $item['produto_id'] = (int)$item['produto_id'];
                $item['quantidade'] = (int)$item['quantidade'];
                $item['preco_unitario'] = (float)$item['preco_unitario'];
                $item['subtotal'] = (float)$item['subtotal'];
            }
            $v['itens'] = $itens;
        }
        echo json_encode(["success" => true, "data" => $vendas]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->usuario_id) && !empty($data->itens) && is_array($data->itens)) {
            try {
                $conn->beginTransaction();

                $valorTotal = 0;
                foreach ($data->itens as $item) {
                    $valorTotal += ($item->quantidade * $item->preco_unitario);
                }

                // 1. Registra a venda
                $stmtVenda = $conn->prepare("INSERT INTO vendas (usuario_id, valor_total) VALUES (:usuario_id, :valor_total)");
                $stmtVenda->bindParam(":usuario_id", $data->usuario_id);
                $stmtVenda->bindParam(":valor_total", $valorTotal);
                $stmtVenda->execute();
                $vendaId = $conn->lastInsertId();

                // 2. Registra os itens e atualiza o estoque dos produtos
                foreach ($data->itens as $item) {
                    $subtotal = $item->quantidade * $item->preco_unitario;

                    $stmtItem = $conn->prepare("INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (:venda_id, :produto_id, :quantidade, :preco_unitario, :subtotal)");
                    $stmtItem->bindParam(":venda_id", $vendaId);
                    $stmtItem->bindParam(":produto_id", $item->produto_id);
                    $stmtItem->bindParam(":quantidade", $item->quantidade);
                    $stmtItem->bindParam(":preco_unitario", $item->preco_unitario);
                    $stmtItem->bindParam(":subtotal", $subtotal);
                    $stmtItem->execute();

                    // Atualiza a quantidade em estoque
                    $stmtEstoque = $conn->prepare("UPDATE produtos SET quantidade = quantidade - :qtd WHERE id = :prod_id");
                    $stmtEstoque->bindParam(":qtd", $item->quantidade);
                    $stmtEstoque->bindParam(":prod_id", $item->produto_id);
                    $stmtEstoque->execute();
                }

                $conn->commit();
                echo json_encode(["success" => true, "message" => "Venda registrada com sucesso!"]);
            } catch(Exception $e) {
                $conn->rollBack();
                echo json_encode(["success" => false, "message" => "Erro ao registrar venda: " . $e->getMessage()]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Dados da venda inválidos."]);
        }
        break;
}
?>