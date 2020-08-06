<?php
require __DIR__ . '/vendor/autoload.php';
use QL\QueryList as QueryList;

ignore_user_abort(true);
$AFK_GUIDE_CODES_URL = "https://afk.guide/redemption-codes";

function curPageURL(): string
{
    $pageURL = 'http';
    if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
    } else {
        $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}

/**
 * @param string $html
 * @return string
 */
function getHtmlWithoutScripts(string $html): string
{
    return preg_replace("/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i", "", $html);
}

function retrieveCodes(): array
{
    global $AFK_GUIDE_CODES_URL;
    $result = QueryList::get($AFK_GUIDE_CODES_URL);
    $domTrs = $result->find('#wps-table tbody')->eq(0)->find('tr');

    $rows = $domTrs->map(function ($domTr) {
        $row = array(
            "code" => $domTr->find("code")->text(),
            "reward" => $domTr->find("td:last")->html(),
            "message" => $domTr->find("em")->text()
        );
        return $row;
    });

    return $rows->all();
}

function printRSS($data)
{
    global $AFK_GUIDE_CODES_URL;

    header("Content-Type: application/rss+xml; charset=utf-8");

    $calledUrl = curPageURL();

    $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
    $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
    $xml .= '<channel>' . "\n";
    $xml .= '<atom:link href="' . $calledUrl . '" rel="self" type="application/rss+xml" />' . "\n";
    $xml .= '<title>AFK Arena Redemption Codes</title>' . "\n";
    $xml .= '<link>' . $calledUrl . '</link>' . "\n";
    $xml .= '<description></description>' . "\n";

    foreach($data as $item) {
        $xml .= '<item>' . "\n";
        $xml .= '<title>' . $item["code"] . '</title>' . "\n";
        $xml .= '<link>' . $AFK_GUIDE_CODES_URL . '</link>' . "\n";
        $xml .= '<description>' . "\n";
        $xml .= '<![CDATA[';
        $xml .= '<p>';
        $xml .= '<span><big>Code: <strong>' . $item["code"] . '</strong></big></span>';
        $xml .= '<br/>';
        $xml .= '<span>Rewards: ' . $item["reward"] . '</span>';
        $xml .= '<br/>';
        $xml .= '<br/>';
        $xml .= '<span><i>' . $item["message"] . '</i></span>';
        $xml .= '</p>';
        $xml .= ']]>';
        $xml .= '</description>' . "\n";
        // $xml .= '<pubDate>' . RssUtils::timestampToRFC822($lastSiteUpdate->updateTS) . '</pubDate>' . "\n";
        $xml .= '<guid isPermaLink="false">' . $item["code"] . '</guid>' . "\n"; // unique ID
        $xml .= '</item>' . "\n";
    }

    $xml .= '</channel>' . "\n";
    $xml .= '</rss>';
    echo $xml;

}

$data = retrieveCodes();
printRSS($data);