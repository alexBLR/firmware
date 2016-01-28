<?php

/**
 * Created by PhpStorm.
 * User: vadim
 * Date: 13.09.15
 * Time: 17:52
 */
class settingsController
{

    public function __construct($method, $twig, $params)
    {
        global $bill_link;
        $this->dbConnect = $bill_link;
        $this->templater = $twig;
        $method = $method . "Action";
        $this->$method($twig, $params);
    }

    public function listAction($twig, $params)
    {
        if ($this->getRole() == 1) {
            $template = $twig->loadTemplate('settings.html.twig');

            $query = mysqli_query(
                $this->dbConnect,
                "select role, summa, price from users c, groups g where c.id = '{$_SESSION['user_id']}' and g.id = c.smena"
            );
            $result = mysqli_fetch_assoc($query);

            $q = mysqli_query($this->dbConnect, "select * from settings");
            while ($r = mysqli_fetch_assoc($q)) {
                $data['id'] = $r['id'];
                $data['username'] = $r['username'];
                $data['password'] = $r['password'];
                $settings[] = $data;
            }
            echo $template->render(
                array(
                    'login' => $_SESSION['user_fio'],
                    'summa' => $result['summa'],
                    'role' => $result['role'],
                    'price' => $result['price'],
                    'settings' => $settings
                )
            );
        }
    }

    public function saveAction($twig, $params) {
        if ($this->getRole() == 1) {
            mysqli_query($this->dbConnect, "update settings set username = '{$_POST['username_1']}', password = '{$_POST['password_1']}' where id = 1");
            mysqli_query($this->dbConnect, "update settings set username = '{$_POST['username_2']}', password = '{$_POST['password_2']}' where id = 2");
            mysqli_query($this->dbConnect, "update settings set username = '{$_POST['username_3']}', password = '{$_POST['password_3']}' where id = 3");
        }
    }

    private function getRole()
    {
        $query = mysqli_query($this->dbConnect, "select role from users where id = '{$_SESSION['user_id']}'");
        $result = mysqli_fetch_assoc($query);

        return $result['role'];
    }
}