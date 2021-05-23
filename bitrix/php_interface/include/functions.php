<?php
/**
 * Author: ISOMAIN
 * Created: 21.05.2021
 * Product name: PhpStorm
 */

function dump($var)
{
    global $USER;
    if ($USER->isAdmin())
    {?>
        <pre style="font-size: 12px">
            <?print_r($var)?>
        </pre>
        <?
    }
}
?>