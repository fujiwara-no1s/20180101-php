<?php
require_once("MyCurl.class.php");

define("LOGIN_URL","https://premier.no1s.biz/users/login");
define("ADMIN_URL","https://premier.no1s.biz/admin");
define("CSV_FILE_NAME", "prodctus.csv");

/**
 *  convert html to dom to xpath
 */
function getXpathObject(string $html) {
  $html = mb_convert_encoding($html,"utf8","auto");
  $dom = new DOMDocument();
  @$dom->loadHTML( mb_convert_encoding($html,'HTML-ENTITIES','UTF-8'));
  return new DOMXPath($dom);
}

/**
 * get nodeValue from given xpath
 */
function getValueByXpath(string $html, string $searchPath) {
  $xpath = getXpathObject($html);
  return $xpath->query($searchPath)->item(0)->nodeValue;
}

/**
 * get nodeList from given xpath
 */
function getNodeListByXpath(string $html, string $searchPath) {
  $xpath = getXpathObject($html);
  return $xpath->query($searchPath);
}

/**
 * get products array fronm nodeList
 */
function getProductByXpath(string $html, string $searchPath) {
  $nodeList = getNodeListByXpath($html,$searchPath);
  $products = [];
  foreach($nodeList as $node) {
    $product = [];
    foreach ($node->childNodes as $child) {
      if ($child->nodeType !== XML_ELEMENT_NODE) continue;
      $product[]= $child->nodeValue;
    }
    $products[] = $product;
  }
  return $products;
}

$loginParams = [
  "email" => "micky.mouse@no1s.biz",
  "password" => "micky",
];


// get csrf token at first
echo "Get csrf token...\n";

$params["method"] = "GET";

$myCurl = new MyCurl(LOGIN_URL,$params);
$myCurl->exec();

$html = $myCurl->getResponseBody();

// find csrf token in html
$searchPath= "//input[@name='_csrfToken']/@value";
$loginParams["_csrfToken"] = getValueByXpath($html,$searchPath);

// post to login
echo "Login to app...\n";
$params["method"] = "POST";
$myCurl = new MyCurl(LOGIN_URL,$params,$loginParams);
$myCurl->exec();

// get product page 1 to 3
$results = [];
foreach( range( 1, 3 ) as $pageNumber ) {
  echo "Get product page ${pageNumber}...\n";
  $productURL = ADMIN_URL ."?page=${pageNumber}";
  $params["method"] = "GET";
  // get each product page
  $myCurl = new MyCurl($productURL, $params);
  $myCurl->exec();
  $html = $myCurl->getResponseBody();
  $searchPath = "//table/tr[position() > 1]";
  $results = array_merge($results,getProductByXpath($html,$searchPath));
}

// make csv string
$resultString = "";
foreach($results as $product) {
  $resultString .= '"' . $product[0] . '",';
  $resultString .= '"' . $product[1] . '",';
  $resultString .= '"' . $product[2] . '",';
  $resultString .= "\n";
}

// write csv string to file
echo "Write data to " . CSV_FILE_NAME . "...\n";
if (file_exists(CSV_FILE_NAME)) unlink(CSV_FILE_NAME);
file_put_contents(CSV_FILE_NAME, $resultString);

// clean up curl
$myCurl->finish();
echo "Finished...\n";

