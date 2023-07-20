<?php

function timestamp($timezone = null) {
    if ($timezone !== null) {
        date_default_timezone_set($timezone);
    }

    return date('Y-m-d H:i:s');
}

function sendJson(array $resp, int $httpCode = 200) {
    
    header('Content-Type: application/json; charset=UTF-8', true, $httpCode);
    
    echo json_encode($resp);
    
}

function sendJsonError(string $message, int $statusCode = 0) {
    sendJson(array(
        'status' => 'failed',
        'statusCode' => $statusCode,
        'message' => $message
    ));

    return false;
}

function sendJsonSuccess(string $message, int $statusCode = 200) {
    sendJson(array(
        'status' => 'success',
        'statusCode' => $statusCode,
        'message' => $message
    ));

    return true;
}

function postDataRetrieve() {

    $postBody = file_get_contents('php://input');

    $postBody = json_decode($postBody, true);

    if (is_array($postBody)) {
        return array_merge($postBody, $_POST);
    } else {
        return $_POST;
    }
    
}


