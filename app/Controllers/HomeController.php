<?php

namespace App\Controllers;

use Core\Controller;

class HomeController extends Controller {

    public function __construct() {
        parent::__construct();
    }

    public function main() {
        
        $this->load->view('inc/header', array(
            'pageTabTitle' => 'Alkane PHP'
        ));

        $this->load->view('home', array(
            'welcomeMessage' => 'It works! :-D'
        ));

        $this->load->view('inc/footer');

    }

}

