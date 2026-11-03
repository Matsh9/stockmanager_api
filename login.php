<?php
http_response_code(200);
require_once 'db.php';

// Lê o corpo JSON enviado pelo Retrofit
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input);

if (!empty($data->email) && !empty($data->senha)) {
    try {
        $query = "SELECT id, nome, email, senha FROM usuarios WHERE email = :email LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":email", $data->email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            // Suporta senha direta ('123456') ou hash MD5
            if ($data->senha === $user['senha'] || md5($data->senha) === $user['senha']) {
                echo json_encode([
                    "success" => true,
                    "message" => "Login efetuado com sucesso!",
                    "data" => [
                        "id" => (int)$user['id'],
                        "nome" => $user['nome'],
                        "email" => $user['email']
                    ]
                ]);
                exit();
            }
        }
        
        echo json_encode(["success" => false, "message" => "E-mail ou senha incorretos."]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Erro no banco de dados: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Preencha o e-mail e a senha."]);
}
?>