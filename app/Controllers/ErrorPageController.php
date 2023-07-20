<?php

namespace App\Controllers;

use Core\Controller;

class ErrorPageController extends Controller {

    public function __construct() {
        parent::__construct();
    } 

    public function main() {
        if (http_response_code() == 200) {
            $errCode = 404;
        } else {
            $errCode = (int)http_response_code();
        }

        $err = array();
        $err['errCode'] = $errCode;
        $err['errMsg'] = $this->getErrorMessage($errCode);

        // header
        header('HTTP/1.1 ' . $err['errCode'] . ' ' . $err['errMsg']);
        
        $this->errorView($err);

    }

    public function errorView(array $errInfo) {

        
        $this->load->view('inc/header', array(
            'pageTabTitle' => $errInfo['errCode'] . ' ' . $errInfo['errMsg']
        ));

        $this->load->view('errors/error', $errInfo);
        $this->load->view('inc/footer');
    }

    public function getErrorMessage(int $errCode) { 
        $errMessages = array(
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found'
        );
        return isset($errMessages[$errCode]) ? $errMessages[$errCode] : '';
    }

    public function noscript() {
        $this->load->view('errors/noscript');
    }

}

