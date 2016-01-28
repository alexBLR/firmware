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
        // логин в системе onpay
        'onpay_login' => 'fwfix_ru',
        // секретный код вашего интернет ресурса.
        // Этот код указывается в вашем кабинете в настройках
        'private_code' => '5sP3C4eX9r',
        // URL куда следует вернуться после выполнения первого шага оплаты
        'url_success' => 'http://93.85.94.218:82/',
        // флаг - использовать таблицу балансов пользователей,
        // если установлен false, то метод data_update_user_balance
        // переопределять не надо, он не будет вызываться
        'use_balance_table' => true,
        // статус для неоплаченной операции в таблице operations
        'new_operation_status' => 0
    );
    return $arr[$name];
}

// Для работы системы необходимо сохранение заявок от пользователей на первом шаге и обработка
// при уведомлении системой onpay

// функция определения параметров платежной формы
// к примеру, если необходимо добавить e-mail пользователя, который совершает платеж, то
// добавляется строка к результату '&user_email=vasia@mail.ru'
function get_iframe_url_params($operation_id, $sum, $md5check) {
    return "pay_mode=fix&pay_for=$operation_id&price=$sum&currency=RUR&convert=yes&md5=$md5check&url_success=".get_constant('url_success');
}

// функция создания операции. Для дальнейшей обработки платежа используется ID созданной операции
function data_create_operation($sum) {
    $userid 			= 1; 											//Определяем ID пользователя, осуществляющего пополнение
    $type 				= "Внешняя"; 							//определяем тип операции
    $comment 			= "Пополнение счета"; 		//вводим комментарий операции
    $description 	= "через систему Onpay"; 	//дополнительный комментарий

    //создаем строку для вставки в базу данных
    $query = "INSERT INTO `operations` (`sum`,`user_id`, `status`, `type`, `comment`, `description`, `date`)
						VALUES('$sum', '$userid', ".get_constant('new_operation_status').", '$type', '$comment', '$description', NOW());";
    return mysql_query($query); //сохраняем данные в базу
}

// функция выборки неоплаченной операции по ID
function data_get_created_operation($id) {
    $query = "SELECT * FROM operations WHERE `id`='$id' and `status`=".get_constant('new_operation_status');
    return mysql_query($query);
}

// функция обновления статуса операции на оплаченную
function data_set_operation_processed($id) {
    $query = "UPDATE operations SET status=1 WHERE id='$id'";
    return mysql_query($query);
}

// обновление баланса пользователя
// если параметр use_balance_table установлен в false, то этот метод не вызывается
// $operation_id - ID в таблице operations, по нему можно получить ID пользователя
function data_update_user_balance($operation_id, $sum) {
    //Определяем ID пользователя, осуществляющего пополнение
    $operation = data_get_created_operation($operation_id);
    if (mysql_num_rows($operation) == 1) {
        $operation_row = mysql_fetch_assoc($operation);
        $userid = $operation_row["user_id"];

        //Обновляем данные по счету пользователя
        $query = "UPDATE balances SET sum=sum+$sum, date=NOW() WHERE id='$userid'";
        return mysql_query($query);
    } else {
        return false;
    }
}
/*==================================== Конец ==========================================*/

//функция проебразует число в число с плавающей точкой
function to_float($sum) {
    if (strpos($sum, ".")) {
        $sum = round($sum, 2);
    } else {
        $sum = $sum.".0";
    }
    return $sum;
}

//функция выдает ответ для сервиса onpay в формате XML на чек запрос
function answer($type, $code, $pay_for, $order_amount, $order_currency, $text) {
    $md5 = strtoupper(md5("$type;$pay_for;$order_amount;$order_currency;$code;".get_constant('private_code')));
    return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<result>\n<code>$code</code>\n<pay_for>$pay_for</pay_for>\n<comment>$text</comment>\n<md5>$md5</md5>\n</result>";
}

//функция выдает ответ для сервиса onpay в формате XML на pay запрос
function answerpay($type, $code, $pay_for, $order_amount, $order_currency, $text, $onpay_id) {
    $md5 = strtoupper(md5("$type;$pay_for;$onpay_id;$pay_for;$order_amount;$order_currency;$code;".get_constant('private_code')));
    return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<result>\n<code>$code</code>\n<comment>$text</comment>\n<onpay_id>$onpay_id</onpay_id>\n<pay_for>$pay_for</pay_for>\n<order_id>$pay_for</order_id>\n<md5>$md5</md5>\n</result>";
}

function process_first_step() {
    $sum = $_REQUEST['sum'];
    $output = '';
    $err = '';

    if (is_numeric($sum)) { //проверяем являются ли введенные данные числом
        $result = data_create_operation($sum);
    } else {
        $err = 'В поле сумма не числовое значение';
    }
    //если данные в базу поместились, идем дальше.
    if ($result) {
        $number = mysql_insert_id(); //определяем id записи в бд
        $sumformd5 = to_float($sum); //преобразуем число к числу с плавающей точкой
        //создаем хеш данных для проверки безопасности
        $md5check = md5("fix;$sumformd5;RUR;$number;yes;".get_constant('private_code'));
        //создаем строчку для запроса
        $url = "http://secure.onpay.ru/pay/".get_constant('onpay_login')."?".get_iframe_url_params($number, $sum, $md5check);
        //вывод формы onpay с заданными параметрами
        $output = '<iframe src="'.$url.'" width="300" height="500" frameborder=no scrolling=no></iframe>
	    					 <form method=post action="'.$_SERVER['HTTP_REFERER'].'"><input type="submit" value="Вернуться"></form>';
    } else {
        $err = empty($err) ? mysql_error() : $err;
        $output = "onpay script: Ошибка сохранения данных. (" . $err . ")";
    }
    return $output;
}

function process_api_request() {
    $rezult = '';
    $error = '';
    //проверяем чек запрос
    if ($_REQUEST['type'] == 'check') {
        //получаем данные, что нам прислал чек запрос
        $order_amount 	= $_REQUEST['order_amount'];
        $order_currency = $_REQUEST['order_currency'];
        $pay_for 				= $_REQUEST['pay_for'];
        $md5 						= $_REQUEST['md5'];
        //выдаем ответ OK на чек запрос
        $rezult = answer($_REQUEST['type'],0, $pay_for, $order_amount, $order_currency, 'OK');
    }

    //проверяем запрос на пополнение
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

        //производим проверки входных данных
        if (empty($onpay_id)) {$error .="Не указан id<br>";}
        else {if (!is_numeric(intval($onpay_id))) {$error .="Параметр не является числом<br>";}}
        if (empty($order_amount)) {$error .="Не указана сумма<br>";}
        else {if (!is_numeric($order_amount)) {$error .="Параметр не является числом<br>";}}
        if (empty($balance_amount)) {$error .="Не указана сумма<br>";}
        else {if (!is_numeric(intval($balance_amount))) {$error .="Параметр не является числом<br>";}}
        if (empty($balance_currency)) {$error .="Не указана валюта<br>";}
        else {if (strlen($balance_currency)>4) {$error .="Параметр слишком длинный<br>";}}
        if (empty($order_currency)) {$error .="Не указана валюта<br>";}
        else {if (strlen($order_currency)>4) {$error .="Параметр слишком длинный<br>";}}
        if (empty($exchange_rate)) {$error .="Не указана сумма<br>";}
        else {if (!is_numeric($exchange_rate)) {$error .="Параметр не является числом<br>";}}

        //если нет ошибок
        if (!$error) {
            if (is_numeric($pay_for)) {
                //Если pay_for - число
                $sum = floatval($order_amount);
                $rezult = data_get_created_operation($pay_for);
                if (mysql_num_rows($rezult) == 1) {
                    //создаем строку хэша с присланных данных
                    $md5fb = strtoupper(md5($_REQUEST['type'].";".$pay_for.";".$onpay_id.";".$order_amount.";".$order_currency.";".get_constant('private_code')));
                    //сверяем строчки хеша (присланную и созданную нами)
                    if ($md5fb != $md5) {
                        $rezult = answerpay($_REQUEST['type'], 8, $pay_for, $order_amount, $order_currency, 'Md5 signature is wrong. Expected '.$md5fb, $onpay_id);
                    } else {
                        $time = time();
                        $rezult_balance = get_constant('use_balance_table') ? data_update_user_balance($pay_for, $sum) : true;
                        $rezult_operation = data_set_operation_processed($pay_for);
                        //если оба запроса прошли успешно выдаем ответ об удаче, если нет, то о том что операция не произошла
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
                //Если pay_for - не правильный формат
                $rezult = answerpay($_REQUEST['type'], 11, $pay_for, $order_amount, $order_currency, 'Error in parameters data', $onpay_id);
            }
        } else {
            //Если есть ошибки
            $rezult = answerpay($_REQUEST['type'], 12, $pay_for, $order_amount, $order_currency, 'Error in parameters data: '.$error, $onpay_id);
        }
    }
    echo $rezult;
    return $rezult;
}
?>
