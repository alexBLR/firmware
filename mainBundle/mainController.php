<?php
/**
 * Created by JetBrains PhpStorm.
 * User: vadim
 * Date: 16.10.12
 * Time: 16:07
 * To change this template use File | Settings | File Templates.
 */
class mainController
{

    public function __construct($method, $twig, $params)
    {
        global $bill_link;
        $this->dbConnect = $bill_link;
        $this->templater = $twig;
        $method = $method . "Action";
        $this->$method($twig, $params);
    }

    public function loginAction($twig, $params)
    {
        if (is_authorized()) {
            header("Location: /");
        } else {
            if (empty($_POST['confirm'])) {
                $_POST['confirm'] = 0;
            }
            if ($_POST['confirm'] == 1) {
                if (!empty($_POST['login'])) {
                    $login = mysqli_escape_string($this->dbConnect, $_POST['login']);
                    $password = md5($_POST['password']);
                    $query = mysqli_query($this->dbConnect, "select id, login, smena from users where login = '{$login}' and passwd = '{$password}'");
                    if (mysqli_num_rows($query) > 0) {
                        $result = mysqli_fetch_assoc($query);
                        $_SESSION['user_id'] = $result['id'];
                        $_SESSION['user_fio'] = $result['login'];
                        $_SESSION['user_smena'] = $result['smena'];
                        header("Location: /");
                    } else {
                        echo $twig->render('login.html.twig', array('error' => 'Bad credentials'));
                    }
                } else {
                    echo $twig->render('login.html.twig', array('error' => 'Login is empty'));
                }
            } else {
                echo $twig->render('login.html.twig');
            }
        }
    }

    public function mainAction($twig, $params)
    {
        if (is_authorized()) {
            $template = $twig->loadTemplate('main.html.twig');
            $query = mysqli_query($this->dbConnect, "select id, name from vendors order by name");
            if ($query) {
                while ($result = mysqli_fetch_assoc($query)) {
                    $vendors[] = array('id' => $result['id'], 'name' => $result['name']);
                }
            } else {
                throw new Exception("Error: mysql error - " . mysql_error());
            }
            $types = array(1 => ' U', 2 => 'NU', 3 => 'NU');
            $query = mysqli_query($this->dbConnect, "select * from logs where user_id = '{$_SESSION['user_id']}' order by date desc");

            $logs = array();

            if ($query) {
                while ($result = mysqli_fetch_assoc($query)) {
                    $q = mysqli_query($this->dbConnect, "select f.type from models m, firmware f where m.name='{$result['model']}' and m.id = f.model_id and f.name = '{$result['firmware']}' LIMIT 1");
                    $r = mysqli_fetch_assoc($q);
                    $name = $result['vendor'] . " " . $result['model'] . " " . $result['firmware'];
                    if ($r['type'] == 1) {
                        $name .= " Serial: " . $result['serial'];
                    } elseif ($r['type'] == 2) {
                        $name .= " Crum: " . $result['serial'];
                    } else {
                        if (empty($result['crums'])) {
                            $result['crums'] = '';
                        }
                        $name .= " Serial: " . $result['serial'] . " Crum: " . $result['crums'];
                    }
                    $result['email_links'] = str_replace("\n", "<br>",$result['email_links']);
                    $logs[] = array(
                        'date' => $result['date'],
                        'name' => $name,
                        'type' => $types[$result['type']],
                        'price' => $result['sum'],
                        'status' => $result['status'],
                        'email_links' => $result['email_links'],
                        'file' => $result['filename'] . ".zip",
                        'from' => $result['from'],
                        'status' => $result['status']
                    );
                }
            } else {
                throw new Exception("Error: mysql error - " . mysql_error());
            }

            $query = mysqli_query($this->dbConnect, "select count(*) as count from logs where user_id = '{$_SESSION['user_id']}'");
            $result = mysqli_fetch_assoc($query);
            $firmwareCount = $result['count'];

            $query = mysqli_query($this->dbConnect, "select role, summa, price, credit from users c left join groups g on c.smena = g.id where c.id = '{$_SESSION['user_id']}'");
            $result = mysqli_fetch_assoc($query);

            echo $template->render(array('login' => $_SESSION['user_fio'], 'summa' => $result['summa'], 'firmwareCount' => $firmwareCount, 'vendors' => $vendors, 'logs' => $logs, 'role' => $result['role'], 'price' => $result['price'], 'credit' => $result['credit']));
        } else {
            header("Location: /login/");
        }
    }

    public function logoutAction()
    {
        session_destroy();
        header("Location: /");
    }

    public function registrationAction($twig, $params)
    {
        if (is_authorized()) {
            header("Location: /");
        } else {
            $template = $twig->loadTemplate('registration.html.twig');
            echo $template->render(array());
        }
    }

    public function is_email($email)
    {
        return preg_match("/^([a-zA-Z0-9])+([\.a-zA-Z0-9_-])*@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)*\.([a-zA-Z]{2,6})$/", $email);
    }

    public function getPricesAction($twig, $params) {
        $template = $twig->loadTemplate('prices.html.twig');
        $query = mysqli_query($this->dbConnect, "select v.id, v.name as vname, m.name as mname, f.name as fname, f.`from`, f.type, f.id as fid, f.description from vendors v left join models m on v.id = m.vendor_id left join firmware f on m.id = f.model_id");
        if ($query) {
            while ($result = mysqli_fetch_assoc($query)) {
                $firmwares[] = array('vendor' => $result['vname'], 'model' => $result['mname'], 'firmware' => $result['fname']);
            }
        } else {
            throw new Exception("Error: mysql error - " . mysql_error());
        }

        echo $template->render(array('firmwares' => $firmwares));
    }


    public function setRegistrationAction($twig, $params)
    {
        if (is_authorized()) {
            header("Location: /");
        } else {
            $template = $twig->loadTemplate('registration.html.twig');
            if ($_POST['confirm'] == 1) {
                $login = $_POST['loginReg'];
                $passwd = md5($_POST['passwordReg']);
                $query = mysqli_query($this->dbConnect, "select id from users where login = '{$login}' limit 1");
                if ($this->is_email($login)) {
                    if (mysqli_num_rows($query) == 0) {
                        $query = mysqli_query( $this->dbConnect, "insert into users set login = '{$login}', passwd = '{$passwd}'");
                        header("Location: /");
                    } else {
                        echo $template->render(array('error' => 'User exists'));
                    }
                } else {
                    echo $template->render(array('error' => 'E-mail not valid'));
                }
            } else {
                echo $template->render(array());
            }

        }
    }
}
