<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);

//Получаем ид инфоблока из компонента
$iblockId = $arParams['IBLOCK_ID'];
?>

<div class="working_day">
    <form method="post" id="form">
        <p>Выберите дату:</p>
        <input type="date" name="calendar">
        <input type="text" hidden name="iblock" value="<?=$iblockId?>">
        <input type="text" hidden name="pathToTemplate" value="<?=$templateFolder?>">
        <button type="submit" id="btn">Проверить</button>
    </form>
</div>
<div class="result_form"></div>