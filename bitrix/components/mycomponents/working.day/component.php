<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

/** @global CIntranetToolbar $INTRANET_TOOLBAR */
global $INTRANET_TOOLBAR;

use Bitrix\Main\Context,
	Bitrix\Main\Type\DateTime,
	Bitrix\Main\Loader,
	Bitrix\Iblock;


if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 36000000;

$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);
if($arParams["IBLOCK_TYPE"] == '')
	$arParams["IBLOCK_TYPE"] = "news";
$arParams["IBLOCK_ID"] = trim($arParams["IBLOCK_ID"]);
$arParams["PARENT_SECTION"] = intval($arParams["PARENT_SECTION"]);
$arParams["INCLUDE_SUBSECTIONS"] = $arParams["INCLUDE_SUBSECTIONS"]!="N";
$arParams["SET_LAST_MODIFIED"] = $arParams["SET_LAST_MODIFIED"]==="Y";

if(!is_array($arParams["FIELD_CODE"]))
	$arParams["FIELD_CODE"] = array();
foreach($arParams["FIELD_CODE"] as $key=>$val)
	if(!$val)
		unset($arParams["FIELD_CODE"][$key]);

if(empty($arParams["PROPERTY_CODE"]) || !is_array($arParams["PROPERTY_CODE"]))
	$arParams["PROPERTY_CODE"] = array();
foreach($arParams["PROPERTY_CODE"] as $key=>$val)
	if($val==="")
		unset($arParams["PROPERTY_CODE"][$key]);

$arParams["CACHE_FILTER"] = $arParams["CACHE_FILTER"]=="Y";

if($arParams["DISPLAY_TOP_PAGER"] || $arParams["DISPLAY_BOTTOM_PAGER"])
{
	$arNavParams = array(
		"nPageSize" => $arParams["NEWS_COUNT"],
		"bDescPageNumbering" => $arParams["PAGER_DESC_NUMBERING"],
		"bShowAll" => $arParams["PAGER_SHOW_ALL"],
	);
	$arNavigation = CDBResult::GetNavParams($arNavParams);
	if($arNavigation["PAGEN"]==0 && $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"]>0)
		$arParams["CACHE_TIME"] = $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"];
}
else
{
	$arNavParams = array(
		"nTopCount" => $arParams["NEWS_COUNT"],
		"bDescPageNumbering" => $arParams["PAGER_DESC_NUMBERING"],
	);
	$arNavigation = false;
}

$arParams["USE_PERMISSIONS"] = $arParams["USE_PERMISSIONS"]=="Y";
if(!is_array($arParams["GROUP_PERMISSIONS"]))
	$arParams["GROUP_PERMISSIONS"] = array(1);

$bUSER_HAVE_ACCESS = !$arParams["USE_PERMISSIONS"];
if($arParams["USE_PERMISSIONS"] && isset($GLOBALS["USER"]) && is_object($GLOBALS["USER"]))
{
	$arUserGroupArray = $USER->GetUserGroupArray();
	foreach($arParams["GROUP_PERMISSIONS"] as $PERM)
	{
		if(in_array($PERM, $arUserGroupArray))
		{
			$bUSER_HAVE_ACCESS = true;
			break;
		}
	}
}

if($this->startResultCache(false, array(($arParams["CACHE_GROUPS"]==="N"? false: $USER->GetGroups()), $bUSER_HAVE_ACCESS, $arNavigation)))
{
	if(!Loader::includeModule("iblock"))
	{
		$this->abortResultCache();
		ShowError(GetMessage("IBLOCK_MODULE_NOT_INSTALLED"));
		return;
	}
	if(is_numeric($arParams["IBLOCK_ID"]))
	{
		$rsIBlock = CIBlock::GetList(array(), array(
			"ACTIVE" => "Y",
			"ID" => $arParams["IBLOCK_ID"],
		));
	}
	else
	{
		$rsIBlock = CIBlock::GetList(array(), array(
			"ACTIVE" => "Y",
			"CODE" => $arParams["IBLOCK_ID"],
			"SITE_ID" => SITE_ID,
		));
	}

	$arResult = $rsIBlock->GetNext();
	if (!$arResult)
	{
		$this->abortResultCache();
		Iblock\Component\Tools::process404(
			trim($arParams["MESSAGE_404"]) ?: GetMessage("T_NEWS_NEWS_NA")
			,true
			,$arParams["SET_STATUS_404"] === "Y"
			,$arParams["SHOW_404"] === "Y"
			,$arParams["FILE_404"]
		);
		return;
	}

	$arResult["USER_HAVE_ACCESS"] = $bUSER_HAVE_ACCESS;
	//SELECT
	$arSelect = array_merge($arParams["FIELD_CODE"], array(
		"ID",
		"IBLOCK_ID",
		"IBLOCK_SECTION_ID",
		"NAME",
		"ACTIVE_FROM",
		"TIMESTAMP_X",
		"DETAIL_PAGE_URL",
		"LIST_PAGE_URL",
		"DETAIL_TEXT",
		"DETAIL_TEXT_TYPE",
		"PREVIEW_TEXT",
		"PREVIEW_TEXT_TYPE",
		"PREVIEW_PICTURE",
	));
	$bGetProperty = !empty($arParams["PROPERTY_CODE"]);
	//WHERE
	$arFilter = array (
		"IBLOCK_ID" => $arResult["ID"],
		"IBLOCK_LID" => SITE_ID,
		"ACTIVE" => "Y",
		"CHECK_PERMISSIONS" => $arParams['CHECK_PERMISSIONS'] ? "Y" : "N",
	);

	if($arParams["CHECK_DATES"])
		$arFilter["ACTIVE_DATE"] = "Y";

	$PARENT_SECTION = CIBlockFindTools::GetSectionID(
		$arParams["PARENT_SECTION"],
		$arParams["PARENT_SECTION_CODE"],
		array(
			"GLOBAL_ACTIVE" => "Y",
			"IBLOCK_ID" => $arResult["ID"],
		)
	);

	if (
		$arParams["STRICT_SECTION_CHECK"]
		&& (
			$arParams["PARENT_SECTION"] > 0
			|| $arParams["PARENT_SECTION_CODE"] <> ''
		)
	)
	{
		if ($PARENT_SECTION <= 0)
		{
			$this->abortResultCache();
			Iblock\Component\Tools::process404(
				trim($arParams["MESSAGE_404"]) ?: GetMessage("T_NEWS_NEWS_NA")
				,true
				,$arParams["SET_STATUS_404"] === "Y"
				,$arParams["SHOW_404"] === "Y"
				,$arParams["FILE_404"]
			);
			return;
		}
	}

	if (!empty($arResult['ITEMS']))
	{
		$elementFilter = array(
			"IBLOCK_ID" => $arResult["ID"],
			"IBLOCK_LID" => SITE_ID,
			"ID" => $arResult["ELEMENTS"]
		);

		$obParser = new CTextParser;
		$iterator = CIBlockElement::GetList(array(), $elementFilter, false, false, $arSelect);
		$iterator->SetUrlTemplates($arParams["DETAIL_URL"], "", $arParams["IBLOCK_URL"]);
		while ($arItem = $iterator->GetNext())
		{
			$arButtons = CIBlock::GetPanelButtons(
				$arItem["IBLOCK_ID"],
				$arItem["ID"],
				0,
				array("SECTION_BUTTONS" => false, "SESSID" => false)
			);
			$arItem["EDIT_LINK"] = $arButtons["edit"]["edit_element"]["ACTION_URL"];
			$arItem["DELETE_LINK"] = $arButtons["edit"]["delete_element"]["ACTION_URL"];

			if ($arParams["PREVIEW_TRUNCATE_LEN"] > 0)
				$arItem["PREVIEW_TEXT"] = $obParser->html_cut($arItem["PREVIEW_TEXT"], $arParams["PREVIEW_TRUNCATE_LEN"]);

			if ($arItem["ACTIVE_FROM"] <> '')
				$arItem["DISPLAY_ACTIVE_FROM"] = CIBlockFormatProperties::DateFormat($arParams["ACTIVE_DATE_FORMAT"], MakeTimeStamp($arItem["ACTIVE_FROM"], CSite::GetDateFormat()));
			else
				$arItem["DISPLAY_ACTIVE_FROM"] = "";

			Iblock\InheritedProperty\ElementValues::queue($arItem["IBLOCK_ID"], $arItem["ID"]);

			$arItem["FIELDS"] = array();

			if ($bGetProperty)
			{
				$arItem["PROPERTIES"] = array();
			}
			$arItem["DISPLAY_PROPERTIES"] = array();

			if ($arParams["SET_LAST_MODIFIED"])
			{
				$time = DateTime::createFromUserTime($arItem["TIMESTAMP_X"]);
				if (
					!isset($arResult["ITEMS_TIMESTAMP_X"])
					|| $time->getTimestamp() > $arResult["ITEMS_TIMESTAMP_X"]->getTimestamp()
				)
					$arResult["ITEMS_TIMESTAMP_X"] = $time;
			}

			$id = (int)$arItem["ID"];
			$arResult["ITEMS"][$id] = $arItem;
		}
		unset($obElement);
		unset($iterator);

		if ($bGetProperty)
		{
			unset($elementFilter['IBLOCK_LID']);
			CIBlockElement::GetPropertyValuesArray(
				$arResult["ITEMS"],
				$arResult["ID"],
				$elementFilter
			);
		}
	}

	$this->setResultCacheKeys(array(
		"ID",
		"IBLOCK_TYPE_ID",
		"LIST_PAGE_URL",
		"NAV_CACHED_DATA",
		"NAME",
		"SECTION",
		"ELEMENTS",
		"IPROPERTY_VALUES",
		"ITEMS_TIMESTAMP_X",
	));
	$this->includeComponentTemplate();
}

if(isset($arResult["ID"]))
{
	$arTitleOptions = null;
	if($USER->IsAuthorized())
	{
		if(
			$APPLICATION->GetShowIncludeAreas()
			|| (is_object($GLOBALS["INTRANET_TOOLBAR"]) && $arParams["INTRANET_TOOLBAR"]!=="N")
			|| $arParams["SET_TITLE"]
		)
		{
			if(Loader::includeModule("iblock"))
			{
				$arButtons = CIBlock::GetPanelButtons(
					$arResult["ID"],
					0,
					$arParams["PARENT_SECTION"],
					array("SECTION_BUTTONS"=>false)
				);

				if($APPLICATION->GetShowIncludeAreas())
					$this->addIncludeAreaIcons(CIBlock::GetComponentMenu($APPLICATION->GetPublicShowMode(), $arButtons));

				if(
					is_array($arButtons["intranet"])
					&& is_object($INTRANET_TOOLBAR)
					&& $arParams["INTRANET_TOOLBAR"]!=="N"
				)
				{
					$APPLICATION->AddHeadScript('/bitrix/js/main/utils.js');
					foreach($arButtons["intranet"] as $arButton)
						$INTRANET_TOOLBAR->AddButton($arButton);
				}
			}
		}
	}

	$this->setTemplateCachedData($arResult["NAV_CACHED_DATA"]);

	$ipropertyExists = (!empty($arResult["IPROPERTY_VALUES"]) && is_array($arResult["IPROPERTY_VALUES"]));
	$iproperty = ($ipropertyExists ? $arResult["IPROPERTY_VALUES"] : array());

	if ($arParams["SET_LAST_MODIFIED"] && $arResult["ITEMS_TIMESTAMP_X"])
	{
		Context::getCurrent()->getResponse()->setLastModified($arResult["ITEMS_TIMESTAMP_X"]);
	}

	unset($iproperty);
	unset($ipropertyExists);

	return $arResult["ELEMENTS"];
}