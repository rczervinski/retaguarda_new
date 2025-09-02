<?php

function process_image($file_input_name, $codigo_sufixo) {
    if (isset($_FILES[$file_input_name]) && strlen($_FILES[$file_input_name]['name']) > 2) {
        $uploaded_file = $_FILES[$file_input_name]['tmp_name'];
        $upl_img_properties = getimagesize($uploaded_file);

        if (!$upl_img_properties) {
            echo "Arquivo inválido: " . $_FILES[$file_input_name]['name'];
            return;
        }

        $new_file_name = $_POST['codigo'] . $codigo_sufixo;
        $folder_path = "../upload/";
        $image_type = $upl_img_properties[2];

        $image_type_id = null;
        switch ($image_type) {
            case IMAGETYPE_PNG:
                $image_type_id = imagecreatefrompng($uploaded_file);
                break;
            case IMAGETYPE_GIF:
                $image_type_id = imagecreatefromgif($uploaded_file);
                break;
            case IMAGETYPE_JPEG:
                $image_type_id = imagecreatefromjpeg($uploaded_file);
                break;
            case IMAGETYPE_WEBP:
                $image_type_id = imagecreatefromwebp($uploaded_file);
                break;
            default:
                echo "Por favor selecione imagem 'PNG', 'GIF', 'JPG' ou 'WEBP'.";
                return;
        }

        if ($image_type_id) {
            $target_layer = image_resize($image_type_id, $upl_img_properties[0], $upl_img_properties[1]);
            // Salva como webp
            imagewebp($target_layer, $folder_path . $new_file_name . ".webp");
            echo "Imagem enviada " . $new_file_name . ".webp<br>";
            imagedestroy($image_type_id);
            imagedestroy($target_layer);
        }
    }
}

function image_resize($image_type_id, $img_width, $img_height) {
    $target_width = 600;
    $target_height = 600;
    $target_layer = imagecreatetruecolor($target_width, $target_height);
    imagecopyresampled($target_layer, $image_type_id, 0, 0, 0, 0, $target_width, $target_height, $img_width, $img_height);
    return $target_layer;
}

process_image('image', '');
process_image('image2', '_2');
process_image('image3', '_3');
process_image('image4', '_4');

// Handle image5 with 'categoria' as name
if (isset($_FILES['image5']) && strlen($_FILES['image5']['name']) > 2) {
    $uploaded_file = $_FILES['image5']['tmp_name'];
    $upl_img_properties = getimagesize($uploaded_file);

    if ($upl_img_properties) {
        $new_file_name = $_POST['categoria']; // Using 'categoria' as file name
        $folder_path = "../upload/";
        $image_type = $upl_img_properties[2];

        $image_type_id = null;
        switch ($image_type) {
            case IMAGETYPE_PNG:
                $image_type_id = imagecreatefrompng($uploaded_file);
                break;
            case IMAGETYPE_GIF:
                $image_type_id = imagecreatefromgif($uploaded_file);
                break;
            case IMAGETYPE_JPEG:
                $image_type_id = imagecreatefromjpeg($uploaded_file);
                break;
            case IMAGETYPE_WEBP:
                $image_type_id = imagecreatefromwebp($uploaded_file);
                break;
        }

        if ($image_type_id) {
            $target_layer = image_resize($image_type_id, $upl_img_properties[0], $upl_img_properties[1]);
            imagewebp($target_layer, $folder_path . $new_file_name . ".webp");
            echo "Imagem enviada " . $new_file_name . ".webp<br>";
            imagedestroy($image_type_id);
            imagedestroy($target_layer);
        }
    }
}

?>