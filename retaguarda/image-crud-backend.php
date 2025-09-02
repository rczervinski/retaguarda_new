<?php
/**
 * Backend CRUD para Imagens de Produtos
 * Gerencia imagens na pasta /upload (fora do diretório atual)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configurações para acessar pasta upload
// Estrutura: public_html/upload/ (upload está no mesmo nível do projeto)
$uploadDir = '../upload/';  // Upload está no mesmo nível do projeto

// Detectar URL base baseada no host
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $protocol . '://' . $host . '/upload/';

$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

// Função para log de debug
function logDebug($message, $data = null) {
    error_log("[ImageCRUD] $message" . ($data ? ': ' . json_encode($data) : ''));
}

// Função para listar imagens de um produto
function listProductImages($codigoGtin, $uploadDir, $baseUrl, $allowedExtensions) {
    $images = [];
    
    // Verificar imagem principal e secundárias (posições 1-4) + categoria (5)
    for ($position = 1; $position <= 5; $position++) {
        $found = false;
        
        foreach ($allowedExtensions as $ext) {
            if ($position === 1) {
                $filename = $codigoGtin . '.' . $ext;
            } elseif ($position === 5) {
                $filename = $codigoGtin . '_categoria.' . $ext;
            } else {
                $filename = $codigoGtin . '_' . $position . '.' . $ext;
            }
            
            $filepath = $uploadDir . $filename;
            
            if (file_exists($filepath)) {
                $images[] = [
                    'position' => $position,
                    'filename' => $filename,
                    'url' => $baseUrl . $filename,
                    'size' => filesize($filepath),
                    'modified' => filemtime($filepath),
                    'extension' => $ext,
                    'label' => $position === 5 ? 'Categoria' : 'Imagem ' . $position
                ];
                $found = true;
                break; // Parar na primeira extensão encontrada
            }
        }
    }
    
    return $images;
}

// Função para deletar imagem
function deleteImage($filename, $uploadDir) {
    $filepath = $uploadDir . $filename;
    
    if (!file_exists($filepath)) {
        return ['success' => false, 'message' => 'Arquivo não encontrado'];
    }
    
    // Validação de segurança
    if (!preg_match('/^[0-9]+(_[2-4]|_categoria)?\.(jpg|jpeg|png|gif|webp)$/i', $filename)) {
        return ['success' => false, 'message' => 'Nome de arquivo inválido'];
    }
    
    if (unlink($filepath)) {
        return ['success' => true, 'message' => 'Imagem deletada com sucesso'];
    } else {
        return ['success' => false, 'message' => 'Erro ao deletar arquivo'];
    }
}

// Função para fazer upload de imagem
function uploadImage($file, $codigoGtin, $position, $uploadDir, $allowedExtensions, $maxFileSize) {
    // Verificar se já existe imagem na posição (BLOQUEIO DE SOBREPOSIÇÃO)
    $existingImage = false;
    foreach ($allowedExtensions as $ext) {
        if ($position === 1) {
            $existingFile = $uploadDir . $codigoGtin . '.' . $ext;
        } elseif ($position === 5) {
            $existingFile = $uploadDir . $codigoGtin . '_categoria.' . $ext;
        } else {
            $existingFile = $uploadDir . $codigoGtin . '_' . $position . '.' . $ext;
        }

        if (file_exists($existingFile)) {
            $existingImage = true;
            break;
        }
    }

    if ($existingImage) {
        $positionName = $position === 5 ? 'da Categoria' : $position;
        return ['success' => false, 'message' => "Já existe uma imagem na posição $positionName. Exclua primeiro para adicionar uma nova."];
    }

    // Validações
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Erro no upload: ' . $file['error']];
    }

    if ($file['size'] > $maxFileSize) {
        return ['success' => false, 'message' => 'Arquivo muito grande. Máximo: ' . ($maxFileSize / 1024 / 1024) . 'MB'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        return ['success' => false, 'message' => 'Extensão não permitida. Use: ' . implode(', ', $allowedExtensions)];
    }
    
    // Determinar nome do arquivo
    if ($position === 1) {
        $filename = $codigoGtin . '.' . $extension;
    } elseif ($position === 5) {
        $filename = $codigoGtin . '_categoria.' . $extension;
    } else {
        $filename = $codigoGtin . '_' . $position . '.' . $extension;
    }
    
    $filepath = $uploadDir . $filename;
    
    // Mover arquivo
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true, 
            'message' => 'Upload realizado com sucesso',
            'filename' => $filename,
            'position' => $position
        ];
    } else {
        return ['success' => false, 'message' => 'Erro ao salvar arquivo'];
    }
}

// Processar requisição
try {
    $method = $_SERVER['REQUEST_METHOD'];
    $operation = $_GET['operation'] ?? $_POST['operation'] ?? '';
    
    logDebug("Requisição recebida", ['method' => $method, 'operation' => $operation]);
    
    switch ($operation) {
        case 'list':
            $codigoGtin = $_GET['codigo_gtin'] ?? '';
            
            if (empty($codigoGtin)) {
                throw new Exception('Código GTIN não fornecido');
            }
            
            $images = listProductImages($codigoGtin, $uploadDir, $baseUrl, $allowedExtensions);
            
            echo json_encode([
                'success' => true,
                'codigo_gtin' => $codigoGtin,
                'images' => $images,
                'total' => count($images),
                'upload_dir' => $uploadDir,
                'base_url' => $baseUrl
            ]);
            break;
            
        case 'delete':
            $filename = $_POST['filename'] ?? '';

            if (empty($filename)) {
                throw new Exception('Nome do arquivo não fornecido');
            }

            $result = deleteImage($filename, $uploadDir);
            echo json_encode($result);
            exit; // Garantir que não há redirecionamento
            break;
            
        case 'upload':
            $codigoGtin = $_POST['codigo_gtin'] ?? '';
            $position = (int)($_POST['position'] ?? 1);
            
            if (empty($codigoGtin)) {
                throw new Exception('Código GTIN não fornecido');
            }
            
            if ($position < 1 || $position > 5) {
                throw new Exception('Posição inválida. Use 1-5');
            }
            
            if (!isset($_FILES['image'])) {
                throw new Exception('Nenhum arquivo enviado');
            }
            
            $result = uploadImage($_FILES['image'], $codigoGtin, $position, $uploadDir, $allowedExtensions, $maxFileSize);
            echo json_encode($result);
            break;
            
        case 'info':
            // Informações do sistema
            echo json_encode([
                'success' => true,
                'upload_dir' => $uploadDir,
                'base_url' => $baseUrl,
                'allowed_extensions' => $allowedExtensions,
                'max_file_size' => $maxFileSize,
                'upload_dir_exists' => is_dir($uploadDir),
                'upload_dir_writable' => is_writable($uploadDir)
            ]);
            break;
            
        default:
            throw new Exception('Operação não suportada: ' . $operation);
    }
    
} catch (Exception $e) {
    logDebug("Erro", ['message' => $e->getMessage()]);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
