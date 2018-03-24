<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';
  
$configuration = [
    'settings' => [
        'displayErrorDetails' => true,
    ],
];
$c = new \Slim\Container($configuration);
$app = new \Slim\App($c);
//app = new \Slim\App;
$app->get('/new-report', function (Request $request, Response $response, array $args) {
//echo 1; return $response;
//    $client = new \MCS\MWSClient([
//        'Marketplace_Id' => '',
//        'Seller_Id' => '',
//        'Access_Key_ID' => '',
    //    'Secret_Access_Key' => '',
    $client = new \MCS\MWSClient([
        'Marketplace_Id' => 'ATVPDKIKX0DER',
        'Seller_Id' => 'A61G3C2W760IY',
        'Access_Key_ID' => 'AKIAICYVBSJT74OWYIPA',
        'Secret_Access_Key' => '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',
        'MWSAuthToken' => 'amzn.mws.94ae1344-b97d-649c-da1d-2049abfb9292' // Optional. Only use this key if you are a third party user/developer
    ]);

    if ($client->validateCredentials()) {
        $report = (int) $client->RequestReport('_GET_MERCHANT_LISTINGS_ALL_DATA_');
    } else {
        $report = false;
    }

    $response->getBody()->write($report);
    return $response;
});

$app->get('/test', function($req, $res){ echo __DIR__; return $res;});

$app->post('/update-quantities', function(Request $request, Response $response) {
    $parsedBody = $request->getParsedBody();
    $reportId = $parsedBody['reportId'];
    $crawlerItems = $parsedBody['items'];

    try {
   //     $client = new \MCS\MWSClient([
   //         'Marketplace_Id' => '',
   //         'Seller_Id' => '',
   //         'Access_Key_ID' => '',
   //         'Secret_Access_Key' => '',
   //         'MWSAuthToken' => '' // Optional. Only use this key if you are a third party user/developer
   //     ]);
$client = new \MCS\MWSClient([
    'Marketplace_Id' => 'ATVPDKIKX0DER',
    'Seller_Id' => 'A61G3C2W760IY',
    'Access_Key_ID' => 'AKIAICYVBSJT74OWYIPA',
    'Secret_Access_Key' => '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',
    'MWSAuthToken' => 'amzn.mws.94ae1344-b97d-649c-da1d-2049abfb9292' // Optional. Only use this key if you are a third party user/developer
]);
        if ($client->validateCredentials()) {
            $report = $client->GetReport($reportId);

            if ($report === false) {
                echo 'false';
                return $response;
            }

            $newItemsArray = [];
            foreach ($crawlerItems as $item) {
                $item['quantity'] = $item['availability'] === 'out_of_stock' ? 0 : 5;
                $newItemsArray[$item['sku']] = $item;
            }

            $productsToUpdate = [];
            foreach ($report as $itemInAmazon) {
		if ($newItemsArray[$itemInAmazon['seller-sku']] &&  $newItemsArray[$itemInAmazon['seller-sku']]['send_to_amazon'] == 0) {
		    continue;
		}
                if ($itemInAmazon['quantity'] < 12 && $newItemsArray[$itemInAmazon['seller-sku']] && $newItemsArray[$itemInAmazon['seller-sku']]['quantity'] == 5) {
                    $productsToUpdate[$itemInAmazon['seller-sku']] = 12;
                } else if ($itemInAmazon['quantity'] > 0 && $newItemsArray[$itemInAmazon['seller-sku']] && $newItemsArray[$itemInAmazon['seller-sku']]['quantity'] == 0) {
                    $productsToUpdate[$itemInAmazon['seller-sku']] = 0;
                }
            }

            $result = $client->updateStock($productsToUpdate);
            echo json_encode($productsToUpdate);
            return $response;
        } else {
            echo 'false';
            return $response;
        }

    } catch (Exception $e) {}
    echo 'false';
    return $response;
});

$app->run();

