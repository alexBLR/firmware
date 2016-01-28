<?php
/**
 * Created by JetBrains PhpStorm.
 * User: vadim
 * Date: 16.10.12
 * Time: 16:27
 * To change this template use File | Settings | File Templates.
 */

$Routes = array(
    array(
        'match' => '/',
        'controller' => 'mainController:main'
    ),
    array(
        'match' => 'favicon.ico',
        'controller' => 'mainController:main'
    ),
    array(
        'match' => '/login/',
        'controller' => 'mainController:login'
    ),
    array(
        'match' => '/logs/',
        'controller' => 'logsController:list'
    ),
    array(
        'match' => '/logout/',
        'controller' => 'mainController:logout'
    ),
    array(
        'match' => '/apiajax/',
        'controller' => 'ajaxController:main'
    ),
    array(
        'match' => '/registration/',
        'controller' => 'mainController:registration'
    ),
    array(
        'match' => '/setRegistration/',
        'controller' => 'mainController:setRegistration'
    ),
    array(
        'match' => '/getPrices/',
        'controller' => 'mainController:getPrices'
    ),
    array(
        'match' => '/order/',
        'controller' => 'mainController:order'
    ),
    array(
        'match' => '/request/',
        'controller' => 'mainController:request'
    ),
    array(
        'match' => '/api/',
        'controller' => 'apiController:api'
    ),
    array(
        'match' => '/getFirmware1/',
        'controller' => 'mainController:getFirmware1'
    ),
    array(
        'match' => '/createFirmware/',
        'controller' => 'ajaxController:createFirmware'
    ),
    array(
        'match' => '/getFirmware/',
        'controller' => 'ajaxController:getFirmwareList'
    ),
    array(
        'match' => '/test/',
        'controller' => 'mainController:test'
    ),
    array(
        'match' => '/users/',
        'controller' => 'userController:list'
    ),
    array(
        'match' => '/users/AddBalance',
        'controller' => 'userController:AddBalance'
    ),
    array(
        'match' => '/users/setGroup',
        'controller' => 'userController:setGroup'
    ),
    array(
        'match' => '/users/deleteUser',
        'controller' => 'userController:deleteUser'
    ),
    array(
        'match' => '/users/setPassword',
        'controller' => 'userController:setPassword'
    ),
    array(
        'match' => '/users/addLogin',
        'controller' => 'userController:addLogin'
    ),
    array(
        'match' => '/groups/',
        'controller' => 'groupController:list'
    ),
    array(
        'match' => '/groups_test/',
        'controller' => 'groupTestController:list'
    ),
    array(
        'match' => '/groups/addGroup',
        'controller' => 'groupController:addGroup'
    ),
    array(
        'match' => '/groups/setPrice',
        'controller' => 'groupController:setPrice'
    ),
    array(
        'match' => '/groups/deleteGroup',
        'controller' => 'groupController:deleteGroup'
    ),
    array(
        'match' => '/groups/changePrice',
        'controller' => 'groupController:changePrice'
    ),
    array(
        'match' => '/groups/changeEnabled',
        'controller' => 'groupController:changeEnabled'
    ),
    array(
        'match' => '/groups/deleteFirmarePrice',
        'controller' => 'groupController:deleteFirmarePrice'
    ),
    array(
        'match' => '/groups/setMessage',
        'controller' => 'groupController:setMessage'
    ),
    array(
        'match' => '/groups/showPrice',
        'controller' => 'groupController:showPrice'
    ),
    array(
        'match' => '/groups/clonePrice',
        'controller' => 'groupController:clonePrice'
    ),
    array(
        'match' => '/users/setCredit',
        'controller' => 'userController:setCredit'
    ),
    array(
        'match' => '/users/deleteCredit',
        'controller' => 'userController:deleteCredit'
    ),
    array(
        'match' => '/users/getPay',
        'controller' => 'userController:getPay'
    ),
    array(
        'match' => '/users/setStatus',
        'controller' => 'userController:setStatus'
    ),
    array(
        'match' => '/firmwares/',
        'controller' => 'firmwareController:list'
    ),
    array(
        'match' => '/settings/',
        'controller' => 'settingsController:list'
    ),
    array(
        'match' => '/settings/save/',
        'controller' => 'settingsController:save'
    ),
    array(
        'match' => '/firmwares/saveFrom/',
        'controller' => 'firmwareController:saveFrom'
    ),
    array(
        'match' => '/firmwares/addManufacturer/',
        'controller' => 'firmwareController:addManufacturer'
    ),
    array(
        'match' => '/firmwares/addModel/',
        'controller' => 'firmwareController:addModel'
    ),
    array(
        'match' => '/firmwares/saveDescription/',
        'controller' => 'firmwareController:saveDescription'
    ),
    array(
        'match' => '/firmwares/addFirmware/',
        'controller' => 'firmwareController:addFirmware'
    )
);