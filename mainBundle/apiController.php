<?php
/**
 * Created by JetBrains PhpStorm.
 * User: vadim
 * Date: 25.04.13
 * Time: 12:16
 * To change this template use File | Settings | File Templates.
 */

class apiController
{
    public function __construct($method, $twig, $params)
    {
        global $bill_link;
        $this->dbConnect = $bill_link;
        $this->templater = $twig;
        $method = $method . "Action";
        $this->$method($twig, $params);
    }

    public function apiAction()
    {
        process_api_request();
    }
}