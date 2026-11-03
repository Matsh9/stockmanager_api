<?php ob_start(); ini_set('display_errors', 0); error_reporting(0); http_response_code(200);
require_once 'db.php';
header("Content-Type: application/json; charset=UTF-8");
try { // 1. Total de Produtos
$stmtProd = $conn->query("SELECT COUNT(*) as total FROM produtos"); $rowProd = $stmtProd ? $stmtProd->fetch() : null; $totalProdutos = ($rowProd && isset($rowProd['total'])) ? (int)$rowProd['total'] : 0;
// 2. Total de Categorias
$stmtCat = $conn->query("SELECT COUNT(*) as total FROM categorias");
$rowCat = $stmtCat ? $stmtCat->fetch() : null;
$totalCategorias = ($rowCat && isset($rowCat['total'])) ? (int)$rowCat['total'] : 0;

// 3. Total de Vendas
$stmtVendas = $conn->query("SELECT COALESCE(SUM(valor_total), 0) as total_valor, COUNT(*) as qtd FROM vendas");
$vendasData = $stmtVendas ? $stmtVendas->fetch() : ['total_valor' => 0, 'qtd' => 0];
$totalVendas = (float)($vendasData['total_valor'] ?? 0);
$qtdVendas = (int)($vendasData['qtd'] ?? 0);

// 4. Produtos com Estoque Baixo
$stmtBaixo = $conn->query("SELECT p.*, c.nome as categoria_nome, f.nome as fornecedor_nome 
                      FROM produtos p 
                      LEFT JOIN categorias c ON p.categoria_id = c.id 
                      LEFT JOIN fornecedores f ON p.fornecedor_id = f.id 
                      WHERE p.quantidade <= p.quantidade_minima 
                      ORDER BY p.quantidade ASC");
$estoqueBaixo = $stmtBaixo ? $stmtBaixo->fetchAll() : [];

foreach ($estoqueBaixo as &$p) {
    $p['id'] = (int)$p['id'];
    $p['preco'] = (float)$p['preco'];
    $p['quantidade'] = (int)$p['quantidade'];
    $p['quantidade_minima'] = (int)$p['quantidade_minima'];
    $p['categoria_id'] = (int)$p['categoria_id'];
    $p['fornecedor_id'] = (int)$p['fornecedor_id'];
}

ob_end_clean();
echo json_encode([
    "success" => true,
    "data" => [
        "total_produtos" => $totalProdutos,
        "total_categorias" => $totalCategorias,
        "total_vendas" => $totalVendas,
        "quantidade_vendas" => $qtdVendas,
        "produtos_estoque_baixo" => $estoqueBaixo
    ]
]);
exit();
} catch (Exception $e) { ob_end_clean(); echo json_encode([ "success" => false, "message" => "Erro no banco de dados: " . $e->getMessage() ]); exit(); } ?>