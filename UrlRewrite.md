# ЧПУ Битрикс, обработка адресов для кастомной деталной страницы (UrlRewrite) 😎

## bitrix:news.list
"DETAIL_URL" => "#SITE_DIR#/services/#SECTION_CODE_PATH#/#ELEMENT_CODE#/",

## bitrix:news.detail
"ELEMENT_CODE" => "{$_REQUEST["ELEMENT_CODE"]}",
"ELEMENT_ID" => "",

## arUrlRewrite

array (
    'CONDITION' => '#^/services/(.*)/(.*)/(.*)/.*#',
    'RULE' => 'SECTION_CODE=$2&ELEMENT_CODE=$3',
    'ID' => 'bitrix:news.detail',
    'PATH' => '/services/detail_product.php',
    'SORT' => 100,
  ),
