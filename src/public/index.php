<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

$app = new \Slim\App;
$app->get('/new-report', function (Request $request, Response $response, array $args) {
    $client = new \MCS\MWSClient([
        'Marketplace_Id' => '',
        'Seller_Id' => '',
        'Access_Key_ID' => '',
        'Secret_Access_Key' => '',
        'MWSAuthToken' => '' // Optional. Only use this key if you are a third party user/developer
    ]);

    if ($client->validateCredentials()) {
        $report = (int) $client->RequestReport('_GET_MERCHANT_LISTINGS_ALL_DATA_');
    } else {
        $report = false;
    }

    $response->getBody()->write($report);
    return $response;
});

$app->post('/update-quantities', function(Request $request, Response $response) {
    $parsedBody = $request->getParsedBody();
    $reportId = $parsedBody['reportId'];
    $crawlerItems = $parsedBody['items'];

    try {
        $client = new \MCS\MWSClient([
            'Marketplace_Id' => '',
            'Seller_Id' => '',
            'Access_Key_ID' => '',
            'Secret_Access_Key' => '',
            'MWSAuthToken' => '' // Optional. Only use this key if you are a third party user/developer
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
                if ($itemInAmazon['quantity'] < 10 && $newItemsArray[$itemInAmazon['seller-sku']] && $newItemsArray[$itemInAmazon['seller-sku']]['quantity'] == 5) {
                    $productsToUpdate[$itemInAmazon['seller-sku']] = 10;
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

