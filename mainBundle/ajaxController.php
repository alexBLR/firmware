<?php

/**
 * Created by JetBrains PhpStorm.
 * User: vadim
 * Date: 05.04.13
 * Time: 9:11
 * To change this template use File | Settings | File Templates.
 */
class ajaxController
{
    public function __construct($method, $twig, $params)
    {
        global $bill_link;
        $this->dbConnect = $bill_link;
        $this->templater = $twig;
        $method = $method . "Action";
        $this->$method($twig, $params);
    }

    public function mainAction($twig, $params)
    {
        $action = intval($_POST['action']);
        if (!empty($_POST['val'])) {
            $val = $_POST['val'];
        }
        $vendor = intval($_POST['vendor']);
        $model = intval($_POST['model']);
        $firmware = intval($_POST['firmware']);
        if (!empty($_POST['type'])) {
            $type = intval($_POST['type']);
        }

        $q = mysqli_query($this->dbConnect, "select * from settings");
        while ($r = mysqli_fetch_assoc($q)) {
            $settings[$r['id']]['username'] = $r['username'];
            $settings[$r['id']]['password'] = $r['password'];
        }

        switch ($action) {
            case 1:
                if (isset($val) and $val != '') {
                    $query = mysqli_query($this->dbConnect, "insert into vendors set name = '{$val}'");
                }
                break;
            case 2:
                if (isset($val) and $val != '') {
                    $query = mysqli_query(
                        $this->dbConnect,
                        "insert into models set name = '{$val}', vendor_id = '{$vendor}'"
                    );
                }
                break;
            case 3:
                if (isset($val) and $val != '') {
                    if ($_POST['serial'] == 'checked' and $_POST['crum'] != 'checked') {
                        $type = 1;
                    } elseif ($_POST['serial'] != 'checked' and $_POST['crum'] == 'checked') {
                        $type = 2;
                    } elseif ($_POST['serial'] == 'checked' and $_POST['crum'] == 'checked') {
                        $type = 3;
                    }
                    $query = mysqli_query(
                        $this->dbConnect,
                        "insert into firmware set name = '{$val}', model_id = '{$model}', type='{$type}', `from` = 1"
                    );

                }
                break;
            case 4:
                $query = mysqli_query(
                    $this->dbConnect,
                    "select m.id, m.name from models m, firmware f, groups g, prices p, users u where vendor_id = {$vendor} and m.id = f.model_id and p.fid = f.id and p.parrent_group = g.id and u.smena = g.id and u.id = '{$_SESSION['user_id']}' and p.enabled = 1 group by m.id order by m.name"
                );
                $disp = '0|Choose model;';
                while ($result = mysqli_fetch_assoc($query)) {
                    $disp .= "{$result['id']}|{$result['name']};";
                }
                echo $disp;
                break;
            case 5:
                $disp = '0|Choose firmware;';
                $query = mysqli_query(
                    $this->dbConnect,
                    "select f.id, f.name, f.type from firmware f, groups g, prices p, users u where f.model_id = '{$model}' and p.fid = f.id and p.parrent_group = g.id and u.smena = g.id and u.id = '{$_SESSION['user_id']}' and p.enabled = 1 order by f.name"
                );
                while ($result = mysqli_fetch_assoc($query)) {
                    if ($result['type'] == 1) {
                        $dop = 'SN';
                    } elseif ($result['type'] == 2) {
                        $dop = 'CRUM';
                    } else {
                        $dop = 'SN+CRUM';
                    }
                    $disp .= "{$result['id']}|{$result['name']} {$dop};";
                }
                echo $disp;
                break;
            case 6:
                header("Content-type: application/json");
                $query = mysqli_query(
                    $this->dbConnect,
                    "select f.type, f.model_id, m.name, f.from as mtype, f.name as fname,f.realModel, f.description from firmware f, models m where f.id = '{$firmware}' and f.model_id = m.id"
                );

                while ($result = mysqli_fetch_assoc($query)) {
                    $query = mysqli_query(
                        $this->dbConnect,
                        "select p.price from prices p, users u where p.parrent_group = u.smena and p.fid = {$firmware} and u.id = '{$_SESSION['user_id']}' and p.enabled = 1"
                    );
                    $r = mysqli_fetch_assoc($query);

                    $display['price'] = $r['price'];
                    if (!empty($result['realModel'])) {
                        $result['name'] = $result['realModel'];
                    }

                    if ($result['mtype'] == 4) {
                        $display['from'] = 4;
                        $display['snlength'] = 15;

                    } elseif ($result['mtype'] == 1 or $result['mtype'] == 3) {
                        if ($result['mtype'] == 1) {
                            $postdata = "username={$settings[1]['username']}&password={$settings[1]['password']}";
                        } else {
                            $postdata = "username={$settings[2]['username']}&password={$settings[2]['password']}";
                        }
                        echo $postdata;
                        $url1 = "http://www.korotron-online.net/Login";
                        $curl = curl_init();

                        curl_setopt($curl, CURLOPT_URL, $url1);
                        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
                        curl_setopt($curl, CURLOPT_HEADER, true);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
                        curl_setopt($curl, CURLOPT_POST, 1);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
                        $data = curl_exec($curl);
                        print_r($data);
                        preg_match_all('|Set-Cookie: (.*);|U', $data, $matches);
                        $cookies = implode(';', $matches[1]);

                        #Запрос на получение версии
                        $reffer = "http://www.korotron-online.net/Firmware";
                        $result['name'] = str_replace(" ", "%20", $result['name']);
                        $url = "http://www.korotron-online.net/Firmware/GetVersions/{$result['name']}";
                         echo $url;
                        $curl = curl_init();
                        curl_setopt($curl, CURLOPT_REFERER, $reffer);
                        curl_setopt($curl, CURLOPT_URL, $url);
                        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
                        curl_setopt($curl, CURLOPT_HEADER, false);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curl, CURLOPT_COOKIE, $cookies);
                        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
                        curl_setopt($curl, CURLOPT_POST, 1);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
                        $data = curl_exec($curl);
                        curl_close($curl);
                        $data = json_decode($data, true);
                        print_r($data);
                        $result['fname'] = explode(" ", $result['fname']);
                        foreach ($data as $val) {

                            $val['optionDisplay'] = explode(" ", $val['optionDisplay']);

                            //echo $val['optionDisplay'][0];
                            if ($val['optionDisplay'][2] == 9) {

                            }
                            // echo $result['fname'][0];
                            if ($result['fname'][1] == 9) {
                                if (strtoupper($val['optionDisplay'][0]) == strtoupper(
                                        $result['fname'][0]
                                    ) and $val['optionDisplay'][2] == 9
                                ) {
                                    // echo 1;
                                    $params['version'] = $val['optionValue'];
                                }
                            } else {
                                if (strtoupper($val['optionDisplay'][0]) == strtoupper(
                                        $result['fname'][0]
                                    ) and $val['optionDisplay'][2] != 9
                                ) {
                                    // echo 1;
                                    $params['version'] = $val['optionValue'];
                                }
                            }
                        }


                        $reffer = "http://www.korotron-online.net/Firmware";
                        $url = "http://www.korotron-online.net/Firmware/GetPrice/{$params['version']}";
                        // echo $url;
                        $curl = curl_init();
                        curl_setopt($curl, CURLOPT_REFERER, $reffer);
                        curl_setopt($curl, CURLOPT_URL, $url);
                        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
                        curl_setopt($curl, CURLOPT_HEADER, false);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curl, CURLOPT_COOKIE, $cookies);
                        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
                        curl_setopt($curl, CURLOPT_POST, 1);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
                        $data = curl_exec($curl);
                        curl_close($curl);
                        $data = json_decode($data, true);
                        $display['from'] = 1;
                        $display['comment'] = $data['comment_ru'];
                        $display['snlength'] = $data['sn_lenght'];
                        if ($firmware == 558 or $firmware == 562) {
                            $display['snlength'] = 9;
                        }
                    } else {
                        $postdata = "username={$settings[3]['username']}&password={$settings[3]['password']}";
                        $url = "http://firmware-online.com/";

                        #Запрос на авторизацию
                        $curl = curl_init();
                        curl_setopt($curl, CURLOPT_URL, $url);
                        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
                        curl_setopt($curl, CURLOPT_HEADER, true);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
                        curl_setopt($curl, CURLOPT_POST, 1);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
                        $data = curl_exec($curl);

                        preg_match_all('|Set-Cookie: (.*);|U', $data, $matches);
                        $cookies = implode(';', $matches[1]);

                        #Запрос на получение версии
                        $reffer = "http://firmware-online.com/";
                        $result['name'] = str_replace(" ", "%20", $result['name']);
                        $url = "http://firmware-online.com/Services/GetVersions/{$result['name']}";
                        //echo $url;
                        $curl = curl_init();
                        curl_setopt($curl, CURLOPT_REFERER, $reffer);
                        curl_setopt($curl, CURLOPT_URL, $url);
                        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
                        curl_setopt($curl, CURLOPT_HEADER, false);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curl, CURLOPT_COOKIE, $cookies);
                        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
                        curl_setopt($curl, CURLOPT_POST, 1);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
                        $data = curl_exec($curl);
                        curl_close($curl);
                        $data = json_decode($data, true);
                        //print_r($data);

                        foreach ($data as $val) {
                            $val['optionDisplay'] = explode(" ", $val['optionDisplay']);
                            //echo $val['optionDisplay'][0];
                            if (strtoupper($val['optionDisplay'][0]) == strtoupper(trim($result['fname']))) {
                                $params['version'] = $val['optionValue'];
                            }
                        }
                        $reffer = "http://firmware-online.com/";
                        $url = "http://firmware-online.com/Services/GetPrices/{$params['version']}";
                        $curl = curl_init();
                        curl_setopt($curl, CURLOPT_REFERER, $reffer);
                        curl_setopt($curl, CURLOPT_URL, $url);
                        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
                        curl_setopt($curl, CURLOPT_HEADER, false);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curl, CURLOPT_COOKIE, $cookies);
                        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
                        curl_setopt($curl, CURLOPT_POST, 1);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
                        $data = curl_exec($curl);
                        curl_close($curl);
                        $data = json_decode($data, true);


                        preg_match_all("|<[^>]+>(.*)</[^>]+>|U", $data[0]['Comment'], $out, PREG_PATTERN_ORDER);
                        $display['from'] = 2;
                        if (!empty($out[1][1])) {
                            $display['comment'] = "Пример: " . $out[1][1];
                        }
                        $display['snlength'] = $data[0]['SnLenght'];
                    }
                    if (!empty($result['description'])) {
                        $display['comment'] = $result['description'];
                    }
                    if (empty($display['snlength'])) {
                        $display['snlength'] = 15;
                    }
                    if ($firmware == 558 or $firmware == 562 or $firmware == 930) {
                        $display['snlength'] = 9;
                    }

                    $display['type'] = $result['type'];
                    echo json_encode($display);
                }
                break;
            case 7:
                $query = mysqli_query($this->dbConnect, "select role from users where id = '{$_SESSION['user_id']}'");
                $result = mysqli_fetch_assoc($query);
                if ($result['role'] == 1) {
                    $idFirmware = $_POST['firmware'];
                    mysqli_query($this->dbConnect, "delete from firmware where id = '{$idFirmware}'");
                }
                break;
            case 8:
                $query = mysqli_query($this->dbConnect, "select role from users where id = '{$_SESSION['user_id']}'");
                $result = mysqli_fetch_assoc($query);
                if ($result['role'] == 1) {
                    $idModel = $_POST['model'];
                    mysqli_query($this->dbConnect, "delete from firmware where model_id = '{$idModel}'");
                    mysqli_query($this->dbConnect, "delete from models where id = '{$idModel}'");
                }
                break;
            default:
                break;
        }
        if ($query) {
            return 1;
        } else {
            throw new Exception("query not complite -" . mysqli_error($this->dbConnect));
        }
    }

    public function createFirmwareAction($twig, $params)
    {
        $_POST['serial'] = strtoupper($_POST['serial']);

        $query = mysqli_query(
            $this->dbConnect,
            "select role, summa, price, credit, summaCredit, smena, c.`parrent` from users c, groups g where c.id = '{$_SESSION['user_id']}' and g.id = c.smena"
        );

        $result = mysqli_fetch_assoc($query);
        $group = $result['smena'];
        $firmware = intval($_POST['firmware']);

        $query = mysqli_query(
            $this->dbConnect,
            "select * from prices where fid = '{$firmware}' and parrent_group = '{$group}'"
        );

        $res = mysqli_fetch_assoc($query);
        $price_id = $res['id'];
        $price = $res['price'];

        if ($result['parrent'] != 0) {
            $q = mysqli_query(
                $this->dbConnect,
                "select summa, credit, summaCredit, parrent, role from users where id = '{$result['parrent']}'"
            );
            $r = mysqli_fetch_assoc($q);
        }

        if ($price <= $result['summa'] or ($result['credit'] == 1 and abs($result['summa']) <= $result['summaCredit'] and $result['summa'] <= 0)
        ) {
            if ($r['credit'] == 1 and $r['summa'] <= 0 and $result['parrent'] != 0) {
                $r['summa'] = $r['summaCredit'] + $r['summa'];
            }
            if ($result['role'] == 0 and ($price >= $r['summa'])) {
                echo 1;
                exit();
            }

            if ($r['role'] == 2 and $r['parrent'] != 1) {
                $q = mysqli_query($this->dbConnect, "select summa, credit, summaCredit, parrent, role, id from users where id = '{$r['parrent']}'");
                $r = mysqli_fetch_assoc($q);
                if ($r['credit'] == 1 and $r['summa'] <= 0) {
                    $r['summa'] = $r['summaCredit'] + $r['summa'];
                }
                $id = $r['id'];
                if ($result['role'] == 0 and ($price >= $r['summa'])) {
                    echo 1;
                    exit();
                }

                $query = mysqli_query(
                    $this->dbConnect,
                    "select p.price from prices p left join users u on u.smena = p.parrent_group where u.id = {$r['id']} and p.fid = '{$firmware}'"
                );
                $r = mysqli_fetch_assoc($query);
                mysqli_query(
                    $this->dbConnect,
                    "update users set summa = summa - {$r['price']} where id = '{$id}'"
                );
            }

            mysqli_query(
                $this->dbConnect,
                "update users set summa = summa - {$price} where id = '{$_SESSION['user_id']}'"
            );


            $query = mysqli_query(
                $this->dbConnect,
                "select p.price from prices p left join users u on u.smena = p.parrent_group where u.id = {$result['parrent']} and p.fid = '{$firmware}'"
            );
            $r = mysqli_fetch_assoc($query);
            mysqli_query(
                $this->dbConnect,
                "update users set summa = summa - {$r['price']} where id = '{$result['parrent']}'"
            );

            $vendor = intval($_POST['vendor']);
            $model = intval($_POST['model']);
            $parrent_id = $result['parrent'];
            $parrent_price = $r['price'];

            $serial = $_POST['serial'];
            $crum = $_POST['crum'];
            $type = intval($_POST['type']);

            $query = mysqli_query($this->dbConnect, "select name from vendors where id = '{$vendor}'");
            $result = mysqli_fetch_assoc($query);
            $vendorName = $result['name'];

            $query = mysqli_query($this->dbConnect, "select name,type from models where id = '{$model}'");
            $result = mysqli_fetch_assoc($query);
            $modelName = $result['name'];


            $query = mysqli_query(
                $this->dbConnect,
                "select name, type, realModel, `from`, fixserv_id from firmware where id = '{$firmware}'"
            );
            $result = mysqli_fetch_assoc($query);
            $firmwareName = $result['name'];
            $firmwareType = $result['type'];
            $modelType = $result['from'];

            if (!empty($result['realModel'])) {
                $modelName = $result['realModel'];
            }

            $params = array(
                'vendor' => $vendorName,
                'model' => $modelName,
                'modelType' => $modelType,
                'firmware' => $firmwareName,
                'firmwareType' => $firmwareType,
                'serial' => $serial,
                'crum' => $crum,
                'type' => $type,
                'fixserv_id' => $result['fixserv_id'],
                'price' => $price,
                'price_id' => $price_id,
                'parrent_id' => $parrent_id,
                'parrent_price' => $parrent_price
            );
            if (empty($serial)) {
                $where = "and crum = '{$crum}'";
            } else {
                $where = "and serial = '{$serial}'";
            }
            $query = mysqli_query(
                $this->dbConnect,
                "select * from logs where user_id = '{$_SESSION['user_id']}' and firmware = '{$params['firmware']}' and model = '{$params['model']}' and vendor = '{$params['vendor']}' and type = '{$params['type']}' {$where} limit 1"
            );

            if (empty($serial)) {
                $math = preg_match("/[^0-9]/", $crum);
            } else {
                $math = 0;
            }

            if (1 == 1) {
                if ($modelType == 4) {
                    $this->getFirmware2Action($params);
                }
                if ($modelType == 1 or $modelType == 3) {
                    $this->getFirmwareAction($params);
                } elseif ($modelType == 2) {
                    if (empty($serial) and $math == 1) {
                        $params['modelType'] = 1;
                        $this->getFirmwareAction($params);
                    } else {
                        $this->getFirmware1Action($params);
                    }
                }
            } else {
            }
        } else {
            echo 1;
        }
    }

    public function getFirmware2Action($params)
    {

        $q = mysqli_query($this->dbConnect, "select * from settings");
        while ($r = mysqli_fetch_assoc($q)) {
            $settings[$r['id']]['username'] = $r['username'];
            $settings[$r['id']]['password'] = $r['password'];
        }
        $types = array(1 => 'upd', 2 => 'noupd');
        $typesFile = array(1 => 'U', 2 => 'NU');
        if (empty($params['serial'])) {
            $serialName = $params['crum'];
        } else {
            $serialName = $params['serial'];
        }

        if (empty($params['crum'])) {
            $params['crum'] = 'CRUM';
        }
        if (empty($params['serial'])) {
            $params['serial'] = 'S/N';
        }
        $params['from'] = 4;
        $url = 'http://fixserv.dynns.com/';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        preg_match('/src="([^"]+)"/', $data, $matches);

        $url = "{$matches[1]}/cgi-bin/module.exe?ID1={$settings[4]['username']}&ID2={$settings[4]['password']}&SN={$params['serial']}&CRM={$params['crum']}&MDL={$params['fixserv_id']}";
        #Запрос на авторизацию
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);
        $data = curl_exec($curl);
        $this->saveResults($params, 'email');
    }

    public function getFirmware1Action($params)
    {

        $q = mysqli_query($this->dbConnect, "select * from settings");
        while ($r = mysqli_fetch_assoc($q)) {
            $settings[$r['id']]['username'] = $r['username'];
            $settings[$r['id']]['password'] = $r['password'];
        }
        $types = array(1 => 'upd', 2 => 'noupd');
        $typesFile = array(1 => 'U', 2 => 'NU');
        if (!empty($params['crum'])) {
            $params['serial'] = $params['crum'];
        }
        if (empty($params['serial'])) {
            $serialName = $params['crum'];
        } else {
            $serialName = $params['serial'];
        }
        $firmawareName = str_replace(" ", "_", $params['firmware']);
        $firmawareName = str_replace(".", "", $firmawareName);
        $firmawareModel = str_replace(" ", "_", $params['model']);

        $filename = "FIX_" . $serialName . "_" . $firmawareModel . "_" . $firmawareName . "_" . $typesFile[$params['type']];


        $postdata = "username={$settings[3]['username']}&password={$settings[3]['password']}";
        $url = "http://firmware-online.com/";

        #Запрос на авторизацию
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $data = curl_exec($curl);

        preg_match_all('|Set-Cookie: (.*);|U', $data, $matches);
        $cookies = implode(';', $matches[1]);

        #Запрос на получение версии
        $reffer = "http://firmware-online.com/";
        $getModel = str_replace(" ", "%20", $params['model']);
        $url = "http://firmware-online.com/Services/GetVersions/{$getModel}";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_REFERER, $reffer);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_COOKIE, $cookies);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $data = curl_exec($curl);
        curl_close($curl);
        $data = json_decode($data, true);
        //print_r($data);
        $result['fname'] = explode(" ", $params['firmware']);
        foreach ($data as $val) {

            $val['optionDisplay'] = explode(" ", $val['optionDisplay']);

            //echo $val['optionDisplay'][0];
            if ($val['optionDisplay'][2] == 9) {

            }
            // echo $result['fname'][0];
            if ($result['fname'][1] == 9) {
                if (strtoupper($val['optionDisplay'][0]) == strtoupper(
                        $result['fname'][0]
                    ) and $val['optionDisplay'][2] == 9
                ) {
                    // echo 1;
                    $params['version'] = $val['optionValue'];
                }
            } else {
                if (strtoupper($val['optionDisplay'][0]) == strtoupper(
                        $result['fname'][0]
                    ) and $val['optionDisplay'][2] != 9
                ) {
                    // echo 1;
                    $params['version'] = $val['optionValue'];
                }
            }
        }

        if (!isset($params['serial'])) {
            $params['serial'] = $params['crum'];
        }
        //echo $params['version'];

        $postdata = "Manufacturer={$params['vendor']}&Model={$params['model']}&Ver={$params['version']}&Serial={$params['serial']}&type={$types[$params['type']]}";
        //echo $postdata;
        //echo $filename;
        $url = "http://firmware-online.com/Generator.cshtml";
        $reffer = "http://firmware-online.com/Generator.cshtml";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_REFERER, $reffer);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_COOKIE, $cookies);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $data = curl_exec($curl);
        curl_close($curl);
        //print_r($data);
        preg_match_all('|Location: (.*)|', $data, $matches);
        list($header, $body) = explode("\r\n\r\n", $data, 2);

        $postdata = '';
        $idDown = explode("/", $matches[1][0]);
        //print_r($idDown);
        $id = trim($idDown[2]);
        $url = "http://firmware-online.com/Services/DownloadFirmware/{$id}";
        $fp = fopen($filename . '.zip', 'w+b');
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FILE, $fp);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_COOKIE, $cookies);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $data = curl_exec($curl);
        //echo $data;
        preg_match_all('|Content-Disposition: (.*)|', $data, $matches);
        $response = curl_getinfo($curl);
        //print_r($response);
        curl_close($curl);
        file_put_contents($filename . '.zip', $data);
        $this->saveResults($params, $filename);
    }

    public function getFirmwareAction($params)
    {
        $q = mysqli_query($this->dbConnect, "select * from settings");
        while ($r = mysqli_fetch_assoc($q)) {
            $settings[$r['id']]['username'] = $r['username'];
            $settings[$r['id']]['password'] = $r['password'];
        }
        $types = array(1 => 'upd', 2 => 'noupd');
        $typesFile = array(1 => 'U', 2 => 'NU');
        if (empty($params['serial'])) {
            $serialName = $params['crum'];
        } else {
            $serialName = $params['serial'];
        }

        $firmawareName = str_replace(" ", "_", $params['firmware']);
        $firmawareName = str_replace(".", "", $firmawareName);
        $firmawareModel = str_replace(" ", "_", $params['model']);
        $filename = "FIX_" . $serialName . "_" . $firmawareModel . "_" . $firmawareName . "_" . $typesFile[$params['type']];

        if ($params['modelType'] == 1) {
            $postdata = "username={$settings[1]['username']}&password={$settings[1]['password']}";
        } else {
            $postdata = "username={$settings[2]['username']}&password={$settings[2]['password']}";
        }
        echo $postdata;
        $url1 = "http://www.korotron-online.net/Login";
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $data = curl_exec($curl);

        preg_match_all('|Set-Cookie: (.*);|U', $data, $matches);
        $cookies = implode(';', $matches[1]);

        #Запрос на получение версии
        $getModel = str_replace(" ", "%20", $params['model']);
        $reffer = "http://www.korotron-online.net/Firmware";
        $url = "http://www.korotron-online.net/Firmware/GetVersions/{$getModel}";
        // echo $url;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_REFERER, $reffer);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_COOKIE, $cookies);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $data = curl_exec($curl);
        curl_close($curl);
        //echo $data;
        $data = json_decode($data, true);

        //echo $params['firmware'];
        $result['fname'] = explode(" ", $params['firmware']);
        foreach ($data as $val) {

            $val['optionDisplay'] = explode(" ", $val['optionDisplay']);

            //echo $val['optionDisplay'][0];
            if ($val['optionDisplay'][2] == 9) {

            }
            // echo $result['fname'][0];
            if ($result['fname'][1] == 9) {
                if (strtoupper($val['optionDisplay'][0]) == strtoupper(
                        $result['fname'][0]
                    ) and $val['optionDisplay'][2] == 9
                ) {
                    // echo 1;
                    $params['version'] = $val['optionValue'];
                }
            } else {
                if (strtoupper($val['optionDisplay'][0]) == strtoupper(
                        $result['fname'][0]
                    ) and $val['optionDisplay'][2] != 9
                ) {
                    // echo 1;
                    $params['version'] = $val['optionValue'];
                }
            }
        }


        if (!isset($params['serial']) or empty($params['serial'])) {
            $params['serial'] = $params['crum'];
        }
        $postdata = "generate=1&manufacturer={$params['vendor']}&model={$params['model']}&version={$params['version']}&serial={$params['serial']}&crum={$params['crum']}&type={$types[$params['type']]}";
       /// echo $postdata;
//         exit();

        $url1 = "http://www.korotron-online.net/Firmware";
        $reffer = "http://www.korotron-online.net/Firmware";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_REFERER, $reffer);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_COOKIE, $cookies);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $data = curl_exec($curl);
        curl_close($curl);
//        echo $data;

        preg_match_all('|Location: (.*)|', $data, $matches);
        list($header, $body) = explode("\r\n\r\n", $data, 2);
//        print_r($matches);
        //echo $matches[1][0];
        $m = htmlspecialchars(trim($matches[1][0]));
        $m = explode("\r", $matches[1][0]);
        $postdata = '';

//exit();

        //открываем файловый дескриптор (куда сохранять файл)
        $fp = fopen($filename . '.zip', 'w+b');

        $idDown = explode("/", $matches[1][0]);
        $idDown[3] = trim($idDown[3]);
        $id = $idDown[3];
        $url = "http://www.korotron-online.net/Firmware/Download/$id/firmware.zip";
        //echo $url;
        //$reffer = "http://firmware-online.com/Firmware/Redirect/$id";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_REFERER, $reffer);
        curl_setopt($curl, CURLOPT_AUTOREFERER, false);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_NOBODY, false);
        curl_setopt($curl, CURLOPT_COOKIE, $cookies);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 600);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($curl, CURLOPT_FILE, $fp);
        $data = curl_exec($curl);
        $response = curl_getinfo($curl);
        curl_close($curl);
        fclose($fp);
        //preg_match_all('|Content-Length: (.*)|', $data, $matches);
        if (filesize($filename.'.zip') < 100000) {
            mysqli_query(
                $this->dbConnect,
                "update users set summa = summa + {$params['price']} where id = '{$_SESSION['user_id']}'"
            );
            mysqli_query(
                $this->dbConnect,
                "update users set summa = summa + {$params['parrent_price']} where id = '{$params['parrent_id']}'"
            );
            exit();
        }

        //file_put_contents($filename . '.zip', $data, LOCK_EX);


        if ($params['modelType'] == 3) {
            $zip = new ZipArchive;
            if ($zip->open($filename . '.zip') === true) {
                $count = $zip->numFiles;
                for ($i = 0; $i < $count; $i++) {
                    $stat = $zip->statIndex($i);

                    if (strstr($stat['name'], ".txt")) {
                        $zip->deleteIndex($i);
                        break;
                    }
                }
                $zip->close();
                echo 'ok!';
            } else {
                echo 'ошибка';
            }
        }

        $this->saveResults($params, $filename);
    }

    public function saveResults($params, $filename)
    {
        mysqli_query(
            $this->dbConnect,
            "insert into logs set
        user_id = '{$_SESSION['user_id']}',
        sum = '{$params['price']}',
        filename = '{$filename}',
        vendor = '{$params['vendor']}',
        model = '{$params['model']}',
        firmware = '{$params['firmware']}',
        version = '{$params['version']}',
        serial = '{$params['serial']}',
        crum = '{$params['crum']}',
        type = '{$params['type']}',
        date = '" . date("Y-m-d H:i:s", time()) . "',
        `from` = '{$params['from']}'
        "
        );
    }

    public function getFirmwareListAction($twig)
    {

        $template = $twig->loadTemplate('logs.html.twig');
        $query = mysqli_query(
            $this->dbConnect,
            "select * from logs where user_id = '{$_SESSION['user_id']}' order by date desc"
        );
        if ($query) {
            $types = array(1 => ' U', 2 => 'NU');
            while ($result = mysqli_fetch_assoc($query)) {
                $q = mysqli_query(
                    $this->dbConnect,
                    "select f.type from models m, firmware f where m.name='{$result['model']}' and m.id = f.model_id and f.name = '{$result['firmware']}'"
                );
                $r = mysqli_fetch_assoc($q);
                $name = $result['vendor'] . " " . $result['model'] . " " . $result['firmware'];
                if ($r['type'] == 1) {
                    $name .= " Serial: " . $result['serial'];
                } elseif ($r['type'] == 2) {
                    $name .= " Crum: " . $result['serial'];
                } else {
                    $name .= " Serial: " . $result['serial'] . " Crum: " . $result['crums'];
                }
                $q = mysqli_query(
                    $this->dbConnect,
                    "select price from groups g, users u where u.id = '{$result['user_id']}' and u.smena = g.id"
                );
                $r = mysqli_fetch_assoc($q);

                $logs[] = array(
                    'name' => $name,
                    'date' => $result['date'],
                    'price' => $result['sum'],
                    'email_links' => $result['email_links'],
                    'status' => $result['status'],
                    'from' => $result['from'],
                    'type' => $types[$result['type']],
                    'file' => $result['filename'] . ".zip"
                );
            }
        } else {
            throw new Exception("Error: mysqli error - " . mysqli_error($this->dbConnect));
        }
        echo $template->render(array('logs' => $logs));
    }

    public function deleteFirmwareAction($twig, $params)
    {

    }

}