<?php

/**
 * Created by JetBrains PhpStorm.
 * User: vadim
 * Date: 03.06.13
 * Time: 20:09
 * To change this template use File | Settings | File Templates.
 */
class groupTestController
{
    public function __construct($method, $twig, $params)
    {
        global $bill_link;
        $this->dbConnect = $bill_link;
        $this->templater = $twig;
        $this->user = $_SESSION['user_id'];
        $method = $method . "Action";
        $this->$method($twig, $params);
    }

    public function listAction($twig, $params)
    {
        if ($this->getRole() == 1 or $this->getRole() == 2 or $this->getRole() == 3 or $this->getRole() == 0) {
            $template = $twig->loadTemplate('groups_test.html.twig');

            if ($this->getRole() == 2 or $this->getRole() == 3 or $this->getRole() == 0) {
                $where = " where parrent = {$_SESSION['user_id']}";
            } else {
                $where = '';
            }

            $groups = array();

            $query = mysqli_query($this->dbConnect, "select name, price, id from groups {$where}");
            if ($query) {
                while ($result = mysqli_fetch_assoc($query)) {
                    $q = mysqli_query(
                        $this->dbConnect,
                        "select p.id as pid,v.name as vendor, m.name as model, f.name as firmware, f.type, p.price, p.enabled, f.description from prices p, firmware f, models m, vendors v where p.fid = f.id and f.model_id = m.id and m.vendor_id = v.id and parrent_group = '{$result['id']}' order by v.name,m.name"
                    );
                    $price = array();
                    while ($r = mysqli_fetch_assoc($q)) {
                        if ($r['enabled'] == 1) {
                            $check = 'checked';
                        } else {
                            $check = '';
                        }
                        $price[] = array(
                            'vendor' => $r['vendor'],
                            'model' => $r['model'],
                            'firmware' => $r['firmware'],
                            'price' => $r['price'],
                            'pid' => $r['pid'],
                            'description' => $r['description'],
                            'enabled' => $check
                        );
                    }
                    $groups[] = array('id' => $result['id'], 'name' => $result['name'], 'price' => $price);
                }
            } else {
                throw new Exception("Error: mysqli error - " . mysqli_error($this->dbConnect));
            }

            if ($this->getRole() == 1 or $this->getRole() == 2 or $this->getRole() == 3 or $this->getRole() == 0) {
                if ($this->getRole() == 2 or $this->getRole() == 3 or $this->getRole() == 0) {
                    $left = " LEFT JOIN prices p on p.fid = f.id
                    LEFT JOIN users u on u.smena = p.parrent_group";
                    $where = "u.id = {$_SESSION['user_id']} and p.enabled = 1";
                    $fields = "p.price, p.enabled";
                } else {
                    $where = "f.enabled = 0 and v.id is not null";
                    $fields = "f.price, f.enabled";
                }



                $query = mysqli_query(
                    $this->dbConnect,
                    "
                select {$fields}, v.id, v.name as vname,m.name as mname,f.name as fname, f.`from`,f.type,f.id as fid, f.description
                  from firmware f
                  left join
                   models m on m.id = f.model_id left join
                   vendors v on v.id = m.vendor_id
                   {$left}
                   where
                   {$where}
                    order by v.name, m.name
                    "
                );
                if ($query) {
                    while ($result = mysqli_fetch_assoc($query)) {
                        $firmwares[] = array(
                            'id' => $result['id'],
                            'vendor' => $result['vname'],
                            'model' => $result['mname'],
                            'firmware' => $result['fname'],
                            'from' => $result['from'],
                            'fid' => $result['fid'],
                            'price' => $result['price'],
                            'enabled' => $result['enabled'],
                            'type' => $result['type'],
                            'description' => $result['description']
                        );
                    }
                    $firmwares = array();
                } else {
                    throw new Exception("Error: mysqli error - " . mysqli_error($this->dbConnect));
                }
            }

            $query = mysqli_query(
                $this->dbConnect,
                "select role, summa from users where id = '{$_SESSION['user_id']}'"
            );
            $result = mysqli_fetch_assoc($query);


            $query = mysqli_query($this->dbConnect, "select * from global_message");
            $r = mysqli_fetch_assoc($query);
            $globalMessage = $r['message'];

            echo $template->render(
                array(
                    'available_firmwares' => $firmwares,
                    'login' => $_SESSION['user_fio'],
                    'role' => $result['role'],
                    'summa' => $result['summa'],
                    'groups' => $groups,
                    'global_message' => $globalMessage
                )
            );
        } else {
            header("Location: /");
        }
    }

    private function getRole()
    {
        $query = mysqli_query($this->dbConnect, "select role from users where id = '{$_SESSION['user_id']}'");
        $result = mysqli_fetch_assoc($query);

        return $result['role'];
    }

    public function addGroupAction($twig, $params)
    {
        if ($this->getRole() == 1 or $this->getRole() == 2 or $this->getRole() == 3) {
            $name = mysqli_escape_string($this->dbConnect, $_POST['name']);
            if (!empty($name)) {
                $field = '';
                if ($this->getRole() == 2 or $this->getRole() == 3) {
                    $field = ", parrent = {$this->user}";
                }
                $query = mysqli_query($this->dbConnect, "insert into groups set name = '{$name}' {$field}");
                $rezz = mysqli_query($this->dbConnect, "SELECT last_insert_id()");
                $lastId = mysqli_fetch_row($rezz);

                foreach ($_POST['fid'] as $key => $val) {
                    if (empty($_POST['enabled'][$val])) {
                        $enabl = 1;
                    } else {
                        $enabl = 0;
                    }
                    $query = mysqli_query(
                        $this->dbConnect,
                        "insert into prices set fid = '{$val}', parrent_group = '{$lastId[0]}', price = '{$_POST['price'][$val]}', enabled = '{$enabl}'"
                    );

                }

                if ($query) {
                    header("Location: /groups/");
                }
            } else {
                header("Location: /groups/");
            }
        }
    }

    public function setPriceAction($twig, $params)
    {
        if ($this->getRole() == 1 or $this->getRole() == 2 or $this->getRole() == 3) {
            $price = $_POST['price'];
            $idGroup = intval($_POST['groupId']);
            $where = '';
            if ($this->getRole() == 2 or $this->getRole() == 3) {
                $where = " and parrent = {$this->user}";
            }
            $query = mysqli_query(
                $this->dbConnect,
                "update groups set price = '{$price}' where id = {$idGroup} {$where}"
            );

            if ($query) {
                header("Location: /groups/");
            }
        }
    }

    public function deleteFirmarePriceAction($twig, $params)
    {
        if ($this->getRole() == 1 or $this->getRole() == 2 or $this->getRole() == 3) {
            $id = intval($_POST['id']);
            $query = mysqli_query($this->dbConnect, "delete from prices where id = {$id}");

            if ($query) {
                header("Location: /groups/");
            }
        }
    }

    public function changePriceAction($twig, $params)
    {
        if ($this->getRole() == 1 or $this->getRole() == 2 or $this->getRole() == 3) {
            $value = mysqli_real_escape_string($this->dbConnect, $_POST['value']);
            $id = intval($_POST['id']);

            $query = mysqli_query($this->dbConnect, "update prices set price = '{$value}' where id = {$id}");
            echo "update prices set price = '{$value}' where id = {$id}";
        }
    }

    public function changeEnabledAction($twig, $params)
    {
        if ($this->getRole() == 1 or $this->getRole() == 2 or $this->getRole() == 3) {
            $value = mysqli_real_escape_string($this->dbConnect, $_POST['value']);
            $id = intval($_POST['id']);

            $query = mysqli_query($this->dbConnect, "update prices set enabled = '{$value}' where id = {$id}");
        }
    }

    public function deleteGroupAction($twig, $params)
    {
        if ($this->getRole() == 1 or $this->getRole() == 2 or $this->getRole() == 3) {
            $price = $_POST['price'];
            $idGroup = intval($_POST['groupId']);
            $where = '';
            if ($this->getRole() == 2 or $this->getRole() == 3) {
                $where = " and parrent = {$this->user}";
            }
            $query = mysqli_query($this->dbConnect, "delete from groups where id = {$idGroup} {$where}");
            if ($query) {
                header("Location: /groups/");
            }
        }
    }

    public function setMessageAction($twig, $params) {
        if ($this->getRole() == 1) {
            $message = $_POST['message'];
            $q = mysqli_query($this->dbConnect, "select * from global_message");
            if (mysqli_num_rows($q) == 0) {
                $q = mysqli_query($this->dbConnect, "insert into global_message set message = '{$message}'");
            } else {
                $q = mysqli_query($this->dbConnect, "update global_message set message = '{$message}'");
            }
            if ($q) {
                header("Location: /groups/");
            }
        }
    }
}