<?php
/**
 * Author: ISOMAIN
 * Created: 22.05.2021
 * Product name: PhpStorm
 */

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock");

if (isset($_POST["calendar"]) && isset($_POST["iblock"])) {
    $iblock = $_POST["iblock"];

    // Текущая дата
    $toDay = date($DB->DateFormatToPHP(CSite::GetDateFormat("SHORT")), time());

    // Получаем из инфоблока праздничные дни
    $holidayDate = [];
    $arSelect = array("ID", "NAME", "DATE_ACTIVE_FROM", "PROPERTY_HOLIDAY");
    $arFilter = array("IBLOCK_ID" => IntVal($iblock), "ACTIVE_DATE" => "Y", "ACTIVE" => "Y");
    $res = CIBlockElement::GetList(array(), $arFilter, false, array("nPageSize" => 50), $arSelect);
    while ($ob = $res->GetNextElement()) {
        $arFields = $ob->GetFields();
        $holidayDate[] = $arFields['PROPERTY_HOLIDAY_VALUE'];
    }

    // Меняем ключ и значение для удобного поиска в массиве
    $flipHolidayDate = array_flip($holidayDate);
    $date = $_POST["calendar"];

    // Проверяем на выходные дни
    function isWeekend($date) {
        return (date('N', strtotime($date)) >= 6);
    }

    // Переформатирование даты
    $dateF = date('d.m.Y', strtotime($_POST["calendar"]));
    $findDate = $dateF;

    while (isWeekend($dateF) or array_key_exists($dateF, $flipHolidayDate) or (strtotime($dateF) < strtotime($toDay))){
        $dateF = date("d.m.Y", strtotime($dateF.'+ 1 days'));
        if(array_key_exists($dateF, $flipHolidayDate)){
            while (array_key_exists($dateF, $flipHolidayDate)){
                $dateF = date("d.m.Y", strtotime($dateF.'+ 1 days'));
                $findDate = $dateF;
            }
        }
        else{
            $findDate = $dateF;
        }
    }

    // Формируем массив для JSON ответа
    $result = array(
        'calendar' => $findDate,
    );

    echo json_encode($result);
}