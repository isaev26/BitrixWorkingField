<?
// Получаем все разделы и подразделы инфоболока 
function getSectionList($filter, $select)
{
    CModule::IncludeModule('iblock');
    $dbSection = CIBlockSection::GetList(
        array(
            'LEFT_MARGIN' => 'ASC',
        ),
        array_merge(
            array(
                'ACTIVE' => 'Y',
                'GLOBAL_ACTIVE' => 'Y'
            ),
            is_array($filter) ? $filter : array()
        ),
        false,
        array_merge(
            array(
                'ID',
                'IBLOCK_SECTION_ID'
            ),
            is_array($select) ? $select : array()
        )
    );

    while ($arSection = $dbSection->GetNext(true, false)) {

        $SID = $arSection['ID'];
        $PSID = (int)$arSection['IBLOCK_SECTION_ID'];

        $arLincs[$PSID]['CHILDS'][$SID] = $arSection;

        $arLincs[$SID] = &$arLincs[$PSID]['CHILDS'][$SID];
    }

    return array_shift($arLincs);
}

// Передаем id инфолоболка и нужные нам свойства
$arSections = getSectionList(array('IBLOCK_ID' => 10), array('NAME', 'SECTION_PAGE_URL'))["CHILDS"];
?>
