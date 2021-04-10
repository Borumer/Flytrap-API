<?php

require __DIR__ . "/../../vendor/autoload.php";

use Flytrap\DBHandlers\FolderHandler;
use VarunS\PHPSleep\SimpleRest;

header("Access-Control-Allow-Headers: Authorization,authorization");
header("Access-Control-Expose-Headers: Authorization,authorization");
header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"] . "");
header("Access-Control-Allow-Credentials: true");
SimpleRest::handleRequestMethodValidation("GET", "OPTIONS");

$headers = apache_request_headers();

SimpleRest::handleHeaderValidation($headers, "authorization");

$folderHandler = new FolderHandler(SimpleRest::parseAuthorizationHeader($headers["authorization"]));

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $folderHandler->setFolderId($_GET['folder_id']);

        $response = [
            "root" => $folderHandler->getFolderInfo()
        ];

        $excludeAudio = $_GET['exclude_audio'] ?? false;
        $excludeFolder = $_GET['exclude_folder'] ?? false;

        switch (true) {
            // Both
            case !$excludeAudio && !$excludeFolder:
                $response['folder'] = $folderHandler->getFolderSubdirectories();
                $response['audio'] = $folderHandler->getFolderAudioFiles();

                if ($response['folder']['statusCode'] === $response['audio']['statusCode']) {
                    $response['statusCode'] = $response['folder']['statusCode'];
                } else {
                    $response['statusCode'] = 200;
                }

                break;
            
            // Folder ONLY
            case $excludeAudio && !$excludeFolder:
                $response['folder'] = $folderHandler->getFolderSubdirectories();
                $response["statusCode"] = $response['folder']['statusCode'];
                break;
            
            // Audio ONLY
            case $excludeFolder && !$excludeAudio:
                $response['audio'] = $folderHandler->getFolderAudioFiles();
                $response["statusCode"] = $response['audio']['statusCode'];
                break;
            
            // Neither
            default:
                $response["statusCode"] = 201;
                SimpleRest::setHttpHeaders($response["statusCode"]);
                break;

        }

        SimpleRest::setHttpHeaders($response["statusCode"]);
        echo json_encode($response);

        break;
}

?>