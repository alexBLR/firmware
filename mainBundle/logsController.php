<?php

/**
 * Created by JetBrains PhpStorm.
 * User: vadim
 * Date: 04.07.13
 * Time: 9:53
 * To change this template use File | Settings | File Templates.
 */
class logsController
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
        if ($this->getRole() == 1 or $this->getRole() == 2 or $this->getRole() == 3) {
            $template = $twig->loadTemplate('logsList.html.twig');

            $types = array(1 => ' U', 2 => 'NU');
            if ($this->getRole() == 2 or $this->getRole() == 3) {
                $where = " and u.parrent = '{$this->user}'";
                if ($this->getRole() == 3) {
                    $query = mysqli_query(
                        $this->dbConnect,
                        "SELECT u.id FROM users u WHERE u.parrent = '{$this->user}'"
                    );
                    while ($result = mysqli_fetch_assoc($query)) {
                        $massIds[] = $result['id'];
                    }
                    $massIds[] = $this->user;
                    $where = " and (u.parrent in (" . implode(",", $massIds) . "))";
                }
            } else {
                $where = '';
            }
            $query = mysqli_query(
                $this->dbConnect,
                "select l.*, u.login,u.smena,l.from, l.status from logs l, users u where u.id = l.user_id {$where} order by date desc"
            );

            if ($query) {
                while ($result = mysqli_fetch_assoc($query)) {
                    $q = mysqli_query(
                        $this->dbConnect,
                        "select f.type from models m, firmware f where m.name='{$result['model']}' and m.id = f.model_id and f.name = '{$result['firmware']}' LIMIT 1"
                    );
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
                    $q = mysqli_query($this->dbConnect, "select price from groups g where g.id = '{$result['smena']}'");
                    $r = mysqli_fetch_assoc($q);
                    $logs[] = array(
                        'login' => $result['login'],
                        'date' => $result['date'],
                        'name' => $name,
                        'type' => $types[$result['type']],
                        'price' => $result['sum'],
                        'from' => $result['from'],
                        'status' => $result['status'],
                        'file' => $result['filename'] . ".zip"
                    );
                }
            }

            $query = mysqli_query(
                $this->dbConnect,
                "select count(*) as count from logs where user_id = '{$_SESSION['user_id']}'"
            );
            $result = mysqli_fetch_assoc($query);
            $firmwareCount = $result['count'];

            $query = mysqli_query(
                $this->dbConnect,
                "select sum(sum) as summaLogs from logs l left join users u on l.user_id = u.id where u.role in (0,2)"
            );
            $result = mysqli_fetch_assoc($query);
            $summaLogs = $result['summaLogs'];

            $query = mysqli_query(
                $this->dbConnect,
                "select role, summa, price from users c, groups g where c.id = '{$_SESSION['user_id']}' and g.id = c.smena"
            );
            $result = mysqli_fetch_assoc($query);

            echo $template->render(
                array(
                    'login' => $_SESSION['user_fio'],
                    'summa' => $result['summa'],
                    'summaLogs' => $summaLogs,
                    'firmwareCount' => $firmwareCount,
                    'role' => $result['role'],
                    'price' => $result['price'],
                    'logs' => $logs
                )
            );
        }
    }

    private function getRole()
    {
        $query = mysqli_query($this->dbConnect, "select role from users where id = '{$_SESSION['user_id']}'");
        $result = mysqli_fetch_assoc($query);

        return $result['role'];
    }
}