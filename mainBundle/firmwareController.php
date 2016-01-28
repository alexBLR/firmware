<?php
/**
 * Created by JetBrains PhpStorm.
 * User: vadim
 * Date: 29.07.13
 * Time: 20:18
 * To change this template use File | Settings | File Templates.
 */

class firmwareController {
    public function __construct($method, $twig, $params)
    {
        global $bill_link;
        $this->dbConnect = $bill_link;
        $this->templater = $twig;
        $method = $method . "Action";
        $this->$method($twig, $params);
    }

    private function getRole()
    {
        $query = mysqli_query($this->dbConnect, "select role from users where id = '{$_SESSION['user_id']}'");
        $result = mysqli_fetch_assoc($query);

        return $result['role'];
    }

    public function listAction($twig, $params) {
        $template = $twig->loadTemplate('firmware.html.twig');

        $query = mysqli_query($this->dbConnect, "select id, name from vendors order by name");
        if ($query) {
            while ($result = mysqli_fetch_assoc($query)) {
                $vendors[] = array('id' => $result['id'], 'name' => $result['name']);
            }
        } else {
            throw new Exception("Error: mysql error - " . mysql_error());
        }

        $query = mysqli_query($this->dbConnect, "select id, name from models order by name");
        if ($query) {
            while ($result = mysqli_fetch_assoc($query)) {
                $models[] = array('id' => $result['id'], 'name' => $result['name']);
            }
        } else {
            throw new Exception("Error: mysql error - " . mysql_error());
        }

        $query = mysqli_query($this->dbConnect, "select f.price, f.enabled, v.id, v.name as vname, m.name as mname, f.name as fname, f.`from`, f.type, f.id as fid, f.description, f.realModel, f.fixserv_id from vendors v left join models m on v.id = m.vendor_id left join firmware f on m.id = f.model_id order by vname, mname");
        if ($query) {
            while ($result = mysqli_fetch_assoc($query)) {
                $firmwares[] = array(
                    'id' => $result['id'],
                    'vendor' => $result['vname'],
                    'model' => $result['mname'],
                    'firmware' => $result['fname'],
                    'realModel' => $result['realModel'],
                    'from' => $result['from'],
                    'fixserv_id' => $result['fixserv_id'],
                    'fid' => $result['fid'],
                    'price' => $result['price'],
                    'enabled' => $result['enabled'],
                    'type' => $result['type'],
                    'description' => $result['description']
                );
            }
        } else {
            throw new Exception("Error: mysqli error - " . mysqli_error($this->dbConnect));
        }

        $query = mysqli_query($this->dbConnect, "select role, summa from users where id = '{$_SESSION['user_id']}'");
        $result = mysqli_fetch_assoc($query);

        echo $template->render(array('login' => $_SESSION['user_fio'], 'role' => $result['role'], 'summa' => $result['summa'], 'firmwares' => $firmwares, 'vendors' => $vendors, 'models' => $models));
    }

    public function saveFromAction($twig, $params) {
        if($this->getRole() == 1) {
            $fid = intval($_POST['fId']);
            $from = intval($_POST['from']);
            $fixserv = intval($_POST['fixserv_id']);
            $real = mysqli_escape_string($this->dbConnect,$_POST['realModel']);
            $price = mysqli_real_escape_string($this->dbConnect, $_POST['price']);
            $firmware = mysqli_escape_string($this->dbConnect, $_POST['firmware']);
            $desc = mysqli_escape_string($this->dbConnect, $_POST['description']);
            $type = intval($_POST['typeSerial']);
            if(empty($_POST['enabled'])) {
                $enabled = 0;
            } else {
                $enabled = intval($_POST['enabled']);
            }

            $query = mysqli_query($this->dbConnect, "update firmware set `from` = '{$from}', fixserv_id = {$fixserv}, `type` = '{$type}', `price` = '{$price}', `name` = '{$firmware}', `enabled` = '{$enabled}', description = '{$desc}', realModel = '{$real}' where id = {$fid}");

            if($query) {
                header("Location: /firmwares/");
            } else {
                throw new Exception("Error: mysqli error - " . mysqli_error($this->dbConnect));
            }
        }
    }

    public function addFirmwareAction($twig, $params) {
        if($this->getRole() == 1) {
            $from = intval($_POST['from']);
            $fixserv = intval($_POST['fixserv_id']);
            $real = mysqli_escape_string($this->dbConnect,$_POST['realModel']);
            $price = intval($_POST['price']);
            $model = intval($_POST['model']);
            $firmware = mysqli_escape_string($this->dbConnect, $_POST['firmware']);
            $type = intval($_POST['type']);
            if(empty($_POST['enabled'])) {
                $enabled = 0;
            } else {
                $enabled = intval($_POST['enabled']);
            }

            $query = mysqli_query($this->dbConnect, "insert into firmware set `from` = '{$from}', fixserv_id = {$fixserv}, `type` = '{$type}', `price` = '{$price}', `name` = '{$firmware}', `enabled` = '{$enabled}', model_id = '{$model}', realModel='{$real}'");
            $rezz = mysqli_query($this->dbConnect, "SELECT last_insert_id()");
            $lastId = mysqli_fetch_row($rezz);

            $q = mysqli_query($this->dbConnect, "select id from groups");
            while ($r = mysqli_fetch_assoc($q)) {
                mysqli_query($this->dbConnect, "insert into prices set fid = '{$lastId[0]}', parrent_group = '{$r['id']}', price='{$price}', enabled=0");
            }

            if($query) {
                header("Location: /firmwares/");
            } else {
                throw new Exception("Error: mysqli error - " . mysqli_error($this->dbConnect));
            }
        }
    }

    public function addManufacturerAction($twig, $params) {
        if($this->getRole() == 1) {
            $name = mysqli_escape_string($this->dbConnect, $_POST['new_manufacturer']);
            if (!empty($name)) {
                $query = mysqli_query($this->dbConnect, "insert into vendors set `name` = '{$name}'");
                if ($query) {
                    header("Location: /firmwares/");
                }
            }
        }
    }

    public function addModelAction($twig, $params) {
        if($this->getRole() == 1) {
            $name = mysqli_escape_string($this->dbConnect, $_POST['new_model']);
            $query = mysqli_query($this->dbConnect, "insert into model set `name` = '{$name}'");
            if (!empty($name)) {
                if ($query) {
                    header("Location: /firmwares/");
                }
            }
        }
    }

    public function saveDescriptionAction($twig, $params) {
        if($this->getRole() == 1) {
            $fid = intval($_POST['fId']);
            $description = mysqli_escape_string($this->dbConnect,$_POST['description']);
            $query = mysqli_query($this->dbConnect, "update firmware set `description` = '{$description}' where id = {$fid}");
            if($query) {
                header("Location: /firmwares/");
            } else {
                echo 1;
            }
        }
    }
}