<?php
/**
 * Created by JetBrains PhpStorm.
 * User: vadim
 * Date: 08.04.13
 * Time: 19:59
 * To change this template use File | Settings | File Templates.
 */
class Paypal {
    /**
     * ��������� ��������� �� �������
     * @var array
     */
    protected $_errors = array();

    /**
     * ������ API
     * �������� �������� �� ��, ��� ��� ��������� ����� ������������ ��������������� ������
     * @var array
     */
    protected $_credentials = array(
        'USER' => 'shadowofbuilder-facilitator_api1.gmail.com',
        'PWD' => '1365671841',
        'SIGNATURE' => 'AOB8BW.eG3UwAsC6DZPdEz0MK6yGAeMmtweNN72c52kE-2JTh2vJtW4t',
    );

    /**
     * ���������, ���� ����� ������������ ������
     * �������� ������� - https://api-3t.paypal.com/nvp
     * ��������� - https://api-3t.sandbox.paypal.com/nvp
     * @var string
     */
    protected $_endPoint = 'https://api-3t.sandbox.paypal.com/nvp';

    /**
     * ������ API
     * @var string
     */
    protected $_version = '74.0';

    /**
     * �������������� ������
     *
     * @param string $method ������ � ���������� ������ ��������
     * @param array $params �������������� ���������
     * @return array / boolean Response array / boolean false on failure
     */
    public function request($method,$params = array()) {
        $this -> _errors = array();
        if( empty($method) ) { // ���������, ������ �� ������ �������
            $this -> _errors = array('�� ������ ����� �������� �������');
            return false;
        }

        // ��������� ������ �������
        $requestParams = array(
            'METHOD' => $method,
            'VERSION' => $this -> _version
        ) + $this -> _credentials;

        // �������������� ������ ��� NVP
        $request = http_build_query($requestParams + $params);

        // ����������� cURL
        $curlOptions = array (
            CURLOPT_URL => $this -> _endPoint,
            CURLOPT_VERBOSE => 1,
            CURLOPT_SSL_VERIFYPEER => false,
           // CURLOPT_SSL_VERIFYHOST => 2,
           // CURLOPT_CAINFO => 'cacert.pem', // ���� �����������
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $request
        );

        $ch = curl_init();
        curl_setopt_array($ch,$curlOptions);

        // ���������� ��� ������, $response ����� ��������� ����� �� API
        $response = curl_exec($ch);

        // ���������, ���� �� ������ � ������������� cURL
        if (curl_errno($ch)) {
            $this -> _errors = curl_error($ch);
            curl_close($ch);
            return false;
        } else  {
            curl_close($ch);
            $responseArray = array();
            parse_str($response,$responseArray); // ��������� ������, ���������� �� NVP � ������
            return $responseArray;
        }
    }
}