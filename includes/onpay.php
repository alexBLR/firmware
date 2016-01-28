<?php
/**
 * Created by JetBrains PhpStorm.
 * User: vadim
 * Date: 15.04.13
 * Time: 8:56
 * To change this template use File | Settings | File Templates.
 */

function get_constant($name) {
    $arr = array(
        // ����� � ������� onpay
        'onpay_login' => 'fwfix_ru',
        // ��������� ��� ������ �������� �������.
        // ���� ��� ����������� � ����� �������� � ����������
        'private_code' => '5sP3C4eX9r',
        // URL ���� ������� ��������� ����� ���������� ������� ���� ������
        'url_success' => 'http://93.85.94.218:82/',
        // ���� - ������������ ������� �������� �������������,
        // ���� ���������� false, �� ����� data_update_user_balance
        // �������������� �� ����, �� �� ����� ����������
        'use_balance_table' => true,
        // ������ ��� ������������ �������� � ������� operations
        'new_operation_status' => 0
    );
    return $arr[$name];
}

// ��� ������ ������� ���������� ���������� ������ �� ������������� �� ������ ���� � ���������
// ��� ����������� �������� onpay

// ������� ����������� ���������� ��������� �����
// � �������, ���� ���������� �������� e-mail ������������, ������� ��������� ������, ��
// ����������� ������ � ���������� '&user_email=vasia@mail.ru'
function get_iframe_url_params($operation_id, $sum, $md5check) {
    return "pay_mode=fix&pay_for=$operation_id&price=$sum&currency=RUR&convert=yes&md5=$md5check&url_success=".get_constant('url_success');
}

// ������� �������� ��������. ��� ���������� ��������� ������� ������������ ID ��������� ��������
function data_create_operation($sum) {
    $userid 			= 1; 											//���������� ID ������������, ��������������� ����������
    $type 				= "�������"; 							//���������� ��� ��������
    $comment 			= "���������� �����"; 		//������ ����������� ��������
    $description 	= "����� ������� Onpay"; 	//�������������� �����������

    //������� ������ ��� ������� � ���� ������
    $query = "INSERT INTO `operations` (`sum`,`user_id`, `status`, `type`, `comment`, `description`, `date`)
						VALUES('$sum', '$userid', ".get_constant('new_operation_status').", '$type', '$comment', '$description', NOW());";
    return mysql_query($query); //��������� ������ � ����
}

// ������� ������� ������������ �������� �� ID
function data_get_created_operation($id) {
    $query = "SELECT * FROM operations WHERE `id`='$id' and `status`=".get_constant('new_operation_status');
    return mysql_query($query);
}

// ������� ���������� ������� �������� �� ����������
function data_set_operation_processed($id) {
    $query = "UPDATE operations SET status=1 WHERE id='$id'";
    return mysql_query($query);
}

// ���������� ������� ������������
// ���� �������� use_balance_table ���������� � false, �� ���� ����� �� ����������
// $operation_id - ID � ������� operations, �� ���� ����� �������� ID ������������
function data_update_user_balance($operation_id, $sum) {
    //���������� ID ������������, ��������������� ����������
    $operation = data_get_created_operation($operation_id);
    if (mysql_num_rows($operation) == 1) {
        $operation_row = mysql_fetch_assoc($operation);
        $userid = $operation_row["user_id"];

        //��������� ������ �� ����� ������������
        $query = "UPDATE balances SET sum=sum+$sum, date=NOW() WHERE id='$userid'";
        return mysql_query($query);
    } else {
        return false;
    }
}
/*==================================== ����� ==========================================*/

//������� ����������� ����� � ����� � ��������� ������
function to_float($sum) {
    if (strpos($sum, ".")) {
        $sum = round($sum, 2);
    } else {
        $sum = $sum.".0";
    }
    return $sum;
}

//������� ������ ����� ��� ������� onpay � ������� XML �� ��� ������
function answer($type, $code, $pay_for, $order_amount, $order_currency, $text) {
    $md5 = strtoupper(md5("$type;$pay_for;$order_amount;$order_currency;$code;".get_constant('private_code')));
    return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<result>\n<code>$code</code>\n<pay_for>$pay_for</pay_for>\n<comment>$text</comment>\n<md5>$md5</md5>\n</result>";
}

//������� ������ ����� ��� ������� onpay � ������� XML �� pay ������
function answerpay($type, $code, $pay_for, $order_amount, $order_currency, $text, $onpay_id) {
    $md5 = strtoupper(md5("$type;$pay_for;$onpay_id;$pay_for;$order_amount;$order_currency;$code;".get_constant('private_code')));
    return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<result>\n<code>$code</code>\n<comment>$text</comment>\n<onpay_id>$onpay_id</onpay_id>\n<pay_for>$pay_for</pay_for>\n<order_id>$pay_for</order_id>\n<md5>$md5</md5>\n</result>";
}

function process_first_step() {
    $sum = $_REQUEST['sum'];
    $output = '';
    $err = '';

    if (is_numeric($sum)) { //��������� �������� �� ��������� ������ ������
        $result = data_create_operation($sum);
    } else {
        $err = '� ���� ����� �� �������� ��������';
    }
    //���� ������ � ���� �����������, ���� ������.
    if ($result) {
        $number = mysql_insert_id(); //���������� id ������ � ��
        $sumformd5 = to_float($sum); //����������� ����� � ����� � ��������� ������
        //������� ��� ������ ��� �������� ������������
        $md5check = md5("fix;$sumformd5;RUR;$number;yes;".get_constant('private_code'));
        //������� ������� ��� �������
        $url = "http://secure.onpay.ru/pay/".get_constant('onpay_login')."?".get_iframe_url_params($number, $sum, $md5check);
        //����� ����� onpay � ��������� �����������
        $output = '<iframe src="'.$url.'" width="300" height="500" frameborder=no scrolling=no></iframe>
	    					 <form method=post action="'.$_SERVER['HTTP_REFERER'].'"><input type="submit" value="���������"></form>';
    } else {
        $err = empty($err) ? mysql_error() : $err;
        $output = "onpay script: ������ ���������� ������. (" . $err . ")";
    }
    return $output;
}

function process_api_request() {
    $rezult = '';
    $error = '';
    //��������� ��� ������
    if ($_REQUEST['type'] == 'check') {
        //�������� ������, ��� ��� ������� ��� ������
        $order_amount 	= $_REQUEST['order_amount'];
        $order_currency = $_REQUEST['order_currency'];
        $pay_for 				= $_REQUEST['pay_for'];
        $md5 						= $_REQUEST['md5'];
        //������ ����� OK �� ��� ������
        $rezult = answer($_REQUEST['type'],0, $pay_for, $order_amount, $order_currency, 'OK');
    }

    //��������� ������ �� ����������
    if ($_REQUEST['type'] == 'pay') {
        $onpay_id 					= $_REQUEST['onpay_id'];
        $pay_for 						= $_REQUEST['pay_for'];
        $order_amount 			= $_REQUEST['order_amount'];
        $order_currency			= $_REQUEST['order_currency'];
        $balance_amount 		= $_REQUEST['balance_amount'];
        $balance_currency 	= $_REQUEST['balance_currency'];
        $exchange_rate 			= $_REQUEST['exchange_rate'];
        $paymentDateTime 		= $_REQUEST['paymentDateTime'];
        $md5 								= $_REQUEST['md5'];

        //���������� �������� ������� ������
        if (empty($onpay_id)) {$error .="�� ������ id<br>";}
        else {if (!is_numeric(intval($onpay_id))) {$error .="�������� �� �������� ������<br>";}}
        if (empty($order_amount)) {$error .="�� ������� �����<br>";}
        else {if (!is_numeric($order_amount)) {$error .="�������� �� �������� ������<br>";}}
        if (empty($balance_amount)) {$error .="�� ������� �����<br>";}
        else {if (!is_numeric(intval($balance_amount))) {$error .="�������� �� �������� ������<br>";}}
        if (empty($balance_currency)) {$error .="�� ������� ������<br>";}
        else {if (strlen($balance_currency)>4) {$error .="�������� ������� �������<br>";}}
        if (empty($order_currency)) {$error .="�� ������� ������<br>";}
        else {if (strlen($order_currency)>4) {$error .="�������� ������� �������<br>";}}
        if (empty($exchange_rate)) {$error .="�� ������� �����<br>";}
        else {if (!is_numeric($exchange_rate)) {$error .="�������� �� �������� ������<br>";}}

        //���� ��� ������
        if (!$error) {
            if (is_numeric($pay_for)) {
                //���� pay_for - �����
                $sum = floatval($order_amount);
                $rezult = data_get_created_operation($pay_for);
                if (mysql_num_rows($rezult) == 1) {
                    //������� ������ ���� � ���������� ������
                    $md5fb = strtoupper(md5($_REQUEST['type'].";".$pay_for.";".$onpay_id.";".$order_amount.";".$order_currency.";".get_constant('private_code')));
                    //������� ������� ���� (���������� � ��������� ����)
                    if ($md5fb != $md5) {
                        $rezult = answerpay($_REQUEST['type'], 8, $pay_for, $order_amount, $order_currency, 'Md5 signature is wrong. Expected '.$md5fb, $onpay_id);
                    } else {
                        $time = time();
                        $rezult_balance = get_constant('use_balance_table') ? data_update_user_balance($pay_for, $sum) : true;
                        $rezult_operation = data_set_operation_processed($pay_for);
                        //���� ��� ������� ������ ������� ������ ����� �� �����, ���� ���, �� � ��� ��� �������� �� ���������
                        if ($rezult_operation && $rezult_balance) {
                            $rezult = answerpay($_REQUEST['type'], 0, $pay_for, $order_amount, $order_currency, 'OK', $onpay_id);
                        } else {
                            $rezult = answerpay($_REQUEST['type'], 9, $pay_for, $order_amount, $order_currency, 'Error in mechant database queries: operation or balance tables error', $onpay_id);
                        }
                    }
                } else {
                    $rezult = answerpay($_REQUEST['type'], 10, $pay_for, $order_amount, $order_currency, 'Cannot find any pay rows acording to this parameters: wrong payment', $onpay_id);
                }
            } else {
                //���� pay_for - �� ���������� ������
                $rezult = answerpay($_REQUEST['type'], 11, $pay_for, $order_amount, $order_currency, 'Error in parameters data', $onpay_id);
            }
        } else {
            //���� ���� ������
            $rezult = answerpay($_REQUEST['type'], 12, $pay_for, $order_amount, $order_currency, 'Error in parameters data: '.$error, $onpay_id);
        }
    }
    echo $rezult;
    return $rezult;
}
?>
