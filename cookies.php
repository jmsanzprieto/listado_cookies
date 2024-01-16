<?php
// Analizamos si existen cookies activas antes de borrarlas
if ($_SERVER['REQUEST_METHOD'] === 'POST') :

    if (isset($_POST['eliminar_cookie'])) :
        $cookieNombre = $_POST['eliminar_cookie'];

        // Asegúrate de que el valor del campo eliminar_cookie está presente
        var_dump($_POST);

        // Establece la fecha de expiración en el pasado para eliminar la cookie
        setcookie($cookieNombre, '', time() - 3600, '/');
        
        // Vuelve a cargar la página para reflejar el cambio
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    endif;
endif;


// Llamada a la API de DeepL para traducir texto
function traducirTexto($texto, $idiomaDestino) {
    $apiKey = 'DEEPL_API_KEY'; // Reemplaza con tu clave de API de DeepL
    $url = 'https://api-free.deepl.com/v2/translate';
    
    $textoTraducido = '';

    $postData = [
        'text' => $texto,
        'target_lang' => $idiomaDestino,
        'auth_key' => $apiKey,
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($postData),
        ],
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response !== false) :
        $responseData = json_decode($response, true);
        if (isset($responseData['translations'][0]['text'])) :
            $textoTraducido = $responseData['translations'][0]['text'];
        endif;
    endif;

    return $textoTraducido;
}

// Verifica si hay cookies almacenadas
if (isset($_COOKIE) && !empty($_COOKIE)) :
    echo "<div class='container'>
            <table class='table table-hover'>
                <thead class='thead-dark'>
                    <tr>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Dominio</th>
                        <th>Descripción</th>
                        <th>Período de Retención</th>
                        <th>Gestionado por ...</th>
                        <th>Política de privacidad</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>
            <tbody>";

    // Lee el archivo CSV desde la URL de GitHub
    $csvData = file_get_contents('https://raw.githubusercontent.com/jkwakman/Open-Cookie-Database/master/open-cookie-database.csv');
    $csvRows = str_getcsv($csvData, "\n");

    // Itera a través de todas las cookies
    foreach ($_COOKIE as $nombre => $valor) :
        echo "<tr>";
        echo "<td>$nombre</td>";
        
        // Busca en el archivo CSV para obtener más información sobre la cookie
        $cookieInfo = buscarInformacionCookie($csvRows, $nombre);

        // Muestra la información adicional de la cookie
        if ($cookieInfo) :
            echo "<td class=''>" . traducirTexto($cookieInfo['Category'], 'es') . "</td>";
            echo "<td class=''>" . traducirTexto($cookieInfo['Domain'], 'es') . "</td>";
            echo "<td class=''>" . traducirTexto($cookieInfo['Description'], 'es') . "</td>";
            echo "<td class=''>" . traducirTexto($cookieInfo['Retention period'], 'es') . "</td>";
            echo "<td class=''>" . traducirTexto($cookieInfo['Data Controller'], 'es') . "</td>";
            echo "<td class=''>" . traducirTexto($cookieInfo['User Privacy & GDPR Rights Portals'], 'es') . "</td>";

            // Verifica si la categoría es diferente de "Functional" para incluir el botón de eliminar
            if ($cookieInfo['Category'] !== 'Functional') {
                // Botón de eliminar con estilo Bootstrap
                echo "<td>";
                echo "<form method='post' class='d-inline'>
                        <input type='hidden' name='eliminar_cookie' value='$nombre'>
                        <button type='submit' class='btn btn-danger'>Eliminar</button>
                      </form>";
                echo "</td>";
            } else {
                // Si la categoría es "Functional", coloca una celda vacía en lugar del botón de eliminar
                echo "<td></td>";
            }
        else :
            echo "<td colspan='8' class='text-danger'>Información no disponible</td>";
        endif;

        echo "</tr>";
    endforeach;
    echo "</tbody></table></div>";
else :
    echo "No se detectaron cookies.";
endif;

// Función para buscar información adicional de la cookie en el archivo CSV
function buscarInformacionCookie($csvRows, $nombreCookie) {
    foreach ($csvRows as $csvRow) :
        $data = str_getcsv($csvRow);
        if (isset($data[3])) : 
            $cookieKey = $data[3]; 

            // Verifica si el nombre de la cookie comienza con el prefijo del Cookie / Data Key name
            if (is_string($nombreCookie) && is_string($cookieKey) && strpos($nombreCookie, $cookieKey) === 0) :
                return [
                    'Cookie / Data Key name' => $cookieKey,
                    'Category' => isset($data[2]) ? $data[2] : '',
                    'Domain' => isset($data[4]) ? $data[4] : '',
                    'Description' => isset($data[5]) ? $data[5] : '',
                    'Retention period' => isset($data[6]) ? $data[6] : '',
                    'Data Controller' => isset($data[7]) ? $data[7] : '', 
                    'User Privacy & GDPR Rights Portals' => isset($data[8]) ? $data[8] : '', 
                    
                ];
            endif;
        endif;
    endforeach;
    return null;
}

