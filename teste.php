
<?php

require_once "db.php";

echo json_encode([
    "success" => true,
    "message" => "StockManager conectado ao MySQL com sucesso!"
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

?>
