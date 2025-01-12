<?php
/**
 * (c) Copyright Ascensio System SIA 2023
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OnlineEditorsExamplePhp;

use Exception;
use OnlineEditorsExamplePhp\Helpers\ConfigManager;
use OnlineEditorsExamplePhp\Helpers\ExampleUsers;
use OnlineEditorsExamplePhp\Helpers\JwtManager;
use OnlineEditorsExamplePhp\Helpers\Users;

/**
 * Put log files into the log folder
 *
 * @param string  $msg
 * @param integer $logFileName
 *
 * @return void
 */
function sendlog($msg, $logFileName)
{
    $logsFolder = "logs/";
    if (!file_exists($logsFolder)) {  // if log folder doesn't exist, make it
        mkdir($logsFolder);
    }
    file_put_contents($logsFolder . $logFileName, $msg . PHP_EOL, FILE_APPEND);
}

/**
 * Create new uuid
 *
 * @return string
 */
function guid()
{
    if (function_exists('com_create_guid')) {
        return com_create_guid();
    }
    mt_srand((float) microtime() * 10000);  // optional for php 4.2.0 and up
    $charid = mb_strtoupper(md5(uniqid(rand(), true)));
    $hyphen = chr(45);  // "-"
    $uuid = chr(123)  // "{"
        .mb_substr($charid, 0, 8).$hyphen
        .mb_substr($charid, 8, 4).$hyphen
        .mb_substr($charid, 12, 4).$hyphen
        .mb_substr($charid, 16, 4).$hyphen
        .mb_substr($charid, 20, 12)
        .chr(125);  // "}"
    return $uuid;
}

/**
 * Get ip address
 *
 * @return string
 */
function getClientIp()
{
    $ipaddress = getenv('HTTP_CLIENT_IP') ?:
        getenv('HTTP_X_FORWARDED_FOR') ?:
            getenv('HTTP_X_FORWARDED') ?:
                getenv('HTTP_FORWARDED_FOR') ?:
                    getenv('HTTP_FORWARDED') ?:
                        getenv('REMOTE_ADDR') ?:
                            'Storage';

    $ipaddress = preg_replace("/[^0-9a-zA-Z.=]/", "_", $ipaddress);

    return $ipaddress;
}

/**
 * Get server url
 *
 * @param string  $forDocumentServer
 *
 * @return string
 */
function serverPath($forDocumentServer = null)
{
    $configManager = new ConfigManager();
    return $forDocumentServer && $configManager->getConfig("storagePath") != null
    && $configManager->getConfig("exampleUrl") != ""
        ? $configManager->getConfig("exampleUrl")
        : (getScheme() . '://' . $_SERVER['HTTP_HOST']);
}

/**
 * Get current user host address
 *
 * @param string  $userAddress
 *
 * @return string
 */
function getCurUserHostAddress($userAddress = null)
{
    $configManager = new ConfigManager();
    if ($configManager->getConfig("alone")) {
        if (empty($configManager->getConfig("storagePath"))) {
            return "Storage";
        }
        return "";
    }
    if (is_null($userAddress)) {
        $userAddress = getClientIp();
    }
    return preg_replace("[^0-9a-zA-Z.=]", '_', $userAddress);
}

/**
 * Get an internal file extension
 *
 * @param string  $filename
 *
 * @return string
 */
function getInternalExtension($filename)
{
    $ext = mb_strtolower('.' . pathinfo($filename, PATHINFO_EXTENSION));

    $configManager = new ConfigManager();
    if (in_array($ext, $configManager->getConfig("extsDocument"))) {
        return ".docx";
    }  // .docx for text document extensions
    if (in_array($ext, $configManager->getConfig("extsSpreadsheet"))) {
        return ".xlsx";
    }  // .xlsx for spreadsheet extensions
    if (in_array($ext, $configManager->getConfig("extsPresentation"))) {
        return ".pptx";
    }  // .pptx for presentation extensions
    return "";
}

/**
 * Get image url for templates
 *
 * @param string  $filename
 *
 * @return string
 */
function getTemplateImageUrl($filename)
{
    $ext = mb_strtolower('.' . pathinfo($filename, PATHINFO_EXTENSION));
    $path = serverPath(true) . "/css/images/";

    $configManager = new ConfigManager();
    if (in_array($ext, $configManager->getConfig("extsDocument"))) {
        return $path . "file_docx.svg";
    }  // for text document extensions
    if (in_array($ext, $configManager->getConfig("extsSpreadsheet"))) {
        return $path . "file_xlsx.svg";
    }  // for spreadsheet extensions
    if (in_array($ext, $configManager->getConfig("extsPresentation"))) {
        return $path . "file_pptx.svg";
    }  // for presentation extensions
    return $path . "file_docx.svg";
}

/**
 * Get the document type
 *
 * @param string  $filename
 *
 * @return string
 */
function getDocumentType($filename)
{
    $ext = mb_strtolower('.' . pathinfo($filename, PATHINFO_EXTENSION));

    $configManager = new ConfigManager();
    if (in_array($ext, $configManager->getConfig("extsDocument"))) {
        return "word";
    }  // word for text document extensions
    if (in_array($ext, $configManager->getConfig("extsSpreadsheet"))) {
        return "cell";
    }  // cell for spreadsheet extensions
    if (in_array($ext, $configManager->getConfig("extsPresentation"))) {
        return "slide";
    }  // slide for presentation extensions
    return "word";
}

/**
 * Get the protocol
 *
 * @return string
 */
function getScheme()
{
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
}

/**
 * Get the storage path of the given file
 *
 * @param string  $fileName
 * @param string  $userAddress
 *
 * @return string
 */
function getStoragePath($fileName, $userAddress = null)
{
    $configManager = new ConfigManager();
    $storagePath = trim(
        str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $configManager->getConfig("storagePath")),
        DIRECTORY_SEPARATOR
    );
    if (!empty($storagePath) && !file_exists($storagePath) && !is_dir($storagePath)) {
        mkdir($storagePath);
    }

    if (realpath($storagePath) === $storagePath) {
        $directory = $storagePath;
    } else {
        $directory = __DIR__ . DIRECTORY_SEPARATOR . $storagePath;
    }

    if ($storagePath != "") {
        $directory = $directory  . DIRECTORY_SEPARATOR;

        // if the file directory doesn't exist, make it
        if (!file_exists($directory) && !is_dir($directory)) {
            mkdir($directory);
        }
    }

    if (realpath($storagePath) !== $storagePath) {
        $directory = $directory . getCurUserHostAddress($userAddress) . DIRECTORY_SEPARATOR;
    }

    if (!file_exists($directory) && !is_dir($directory)) {
        mkdir($directory);
    }
    sendlog("getStoragePath result: " . $directory . basename($fileName), "common.log");
    return realpath($storagePath) === $storagePath ? $directory . $fileName : $directory . basename($fileName);
}

/**
 * Get the path to the forcesaved file version
 *
 * @param string  $fileName
 * @param string  $userAddress
 * @param bool    $create
 *
 * @return string
 */
function getForcesavePath($fileName, $userAddress, $create)
{
    $configManager = new ConfigManager();
    $storagePath = trim(
        str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            $configManager->getConfig("storagePath")
        ),
        DIRECTORY_SEPARATOR
    );

    // create the directory to this file version
    if (realpath($storagePath) === $storagePath) {
        $directory = $storagePath . DIRECTORY_SEPARATOR;
    } else {
        $directory = __DIR__ . DIRECTORY_SEPARATOR . $storagePath . getCurUserHostAddress($userAddress) .
            DIRECTORY_SEPARATOR;
    }

    if (!is_dir($directory)) {
        return "";
    }

    // create the directory to the history of this file version
    $directory = $directory . $fileName . "-hist" . DIRECTORY_SEPARATOR;
    if (!$create && !is_dir($directory)) {
        return "";
    }

    if (!file_exists($directory) && !is_dir($directory)) {
        mkdir($directory);
    }
    $directory = $directory . $fileName;
    if (!$create && !file_exists($directory)) {
        return "";
    }

    return $directory;
}

/**
 * Get the path to the file history
 *
 * @param string  $storagePath
 *
 * @return string
 */
function getHistoryDir($storagePath)
{
    $directory = $storagePath . "-hist";
    // if the history directory doesn't exist, make it
    if (!file_exists($directory) && !is_dir($directory)) {
        mkdir($directory);
    }
    return $directory;
}

/**
 * Get the path to the specified file version
 *
 * @param string  $histDir
 * @param string  $version
 *
 * @return string
 */
function getVersionDir($histDir, $version)
{
    return $histDir . DIRECTORY_SEPARATOR . $version;
}

/**
 * Get a number of the last file version from the history directory
 *
 * @param string $histDir
 *
 * @return int
 */
function getFileVersion($histDir)
{
    if (!file_exists($histDir) || !is_dir($histDir)) {
        return 1;
    }  // check if the history directory exists

    $cdir = scandir($histDir);
    $ver = 1;
    foreach ($cdir as $key => $fileName) {
        if (!in_array($fileName, [".", ".."])) {
            if (is_dir($histDir . DIRECTORY_SEPARATOR . $fileName)) {
                $ver++;
            }
        }
    }
    return $ver;
}

/**
 * Get all the stored files from the folder
 *
 * @return array
 */
function getStoredFiles()
{
    $configManager = new ConfigManager();
    $storagePath = trim(str_replace(
        ['/', '\\'],
        DIRECTORY_SEPARATOR,
        $configManager->getConfig("storagePath")
    ), DIRECTORY_SEPARATOR);
    if (!empty($storagePath) && !file_exists($storagePath) && !is_dir($storagePath)) {
        mkdir($storagePath);
    }

    if (realpath($storagePath) === $storagePath) {
        $directory = $storagePath;
    } else {
        $directory = __DIR__ . DIRECTORY_SEPARATOR . $storagePath;
    }

    // get the storage path and check if it exists
    $result = [];
    if ($storagePath != "") {
        $directory = $directory . DIRECTORY_SEPARATOR;

        if (!file_exists($directory) && !is_dir($directory)) {
            return $result;
        }
    }

    if (realpath($storagePath) !== $storagePath) {
        $directory = $directory . getCurUserHostAddress() . DIRECTORY_SEPARATOR;
    }

    if (!file_exists($directory) && !is_dir($directory)) {
        return $result;
    }

    $cdir = scandir($directory);  // get all the files and folders from the directory
    $result = [];
    foreach ($cdir as $key => $fileName) {  // run through all the file and folder names
        if (!in_array($fileName, [".", ".."])) {
            if (!is_dir($directory . DIRECTORY_SEPARATOR . $fileName)) {  // if an element isn't a directory
                $ext = mb_strtolower('.' . pathinfo($fileName, PATHINFO_EXTENSION));
                $dat = filemtime($directory . DIRECTORY_SEPARATOR . $fileName);  // get the time of element modification
                $result[$dat] = (object) [  // and write the file to the result
                    "name" => $fileName,
                    "documentType" => getDocumentType($fileName),
                    "canEdit" => in_array($ext, $configManager->getConfig("docServEdited")),
                    "isFillFormDoc" => in_array($ext, $configManager->getConfig("docServFillforms")),
                ];
            }
        }
    }
    ksort($result);  // sort files by the modification date
    return array_reverse($result);
}

/**
 * Get the virtual path
 *
 * @param string $forDocumentServer
 *
 * @return string
 */
function getVirtualPath($forDocumentServer)
{
    $configManager = new ConfigManager();
    $storagePath = trim(str_replace(
        ['/', '\\'],
        '/',
        $configManager->getConfig("storagePath")
    ), '/');
    $storagePath = $storagePath != "" ? $storagePath . '/' : "";

    if (realpath($storagePath) === $storagePath) {
        $virtPath = serverPath($forDocumentServer) . '/' . $storagePath . '/';
    } else {
        $virtPath = serverPath($forDocumentServer) . '/' . $storagePath . getCurUserHostAddress() . '/';
    }
    sendlog("getVirtualPath virtPath: " . $virtPath, "common.log");
    return $virtPath;
}

/**
 * Get a file with meta information
 *
 * @param string $fileName
 * @param string $uid
 * @param string $uname
 * @param string $userAddress
 *
 * @return void
 */
function createMeta($fileName, $uid, $uname, $userAddress = null)
{
    $histDir = getHistoryDir(getStoragePath($fileName, $userAddress));  // get the history directory

    // turn the file information into the json format
    $json = [
        "created" => date("Y-m-d H:i:s"),
        "uid" => $uid,
        "name" => $uname,
    ];

    // write the encoded file information to the createdInfo.json file
    file_put_contents($histDir . DIRECTORY_SEPARATOR . "createdInfo.json", json_encode($json, JSON_PRETTY_PRINT));
}

/**
 * Get the file url
 *
 * @param string $file_name
 * @param string $forDocumentServer
 *
 * @return string
 */
function fileUri($file_name, $forDocumentServer = null)
{
    $uri = getVirtualPath($forDocumentServer) . rawurlencode($file_name);  // add encoded file name to the virtual path
    return $uri;
}

/**
 * Get file information
 *
 * @param string $fileId
 *
 * @return array|string
 */
function getFileInfo($fileId)
{
    $storedFiles = getStoredFiles();
    $result = [];
    $resultID = [];

    // run through all the stored files
    foreach ($storedFiles as $key => $value) {
        $result[$key] = (object) [  // write all the parameters to the map
            "version" => getFileVersion(getHistoryDir(getStoragePath($value->name))),
            "id" => getDocEditorKey($value->name),
            "contentLength" => number_format(filesize(getStoragePath($value->name)) / 1024, 2)." KB",
            "pureContentLength" => filesize(getStoragePath($value->name)),
            "title" => $value->name,
            "updated" => date(DATE_ATOM, filemtime(getStoragePath($value->name))),
        ];
        // get file information by its id
        if ($fileId != null) {
            if ($fileId == getDocEditorKey($value->name)) {
                $resultID[count($resultID)] = $result[$key];
            }
        }
    }

    if ($fileId != null) {
        if (count($resultID) != 0) {
            return $resultID;
        }
        return "File not found";
    }

    return $result;
}

/**
 * Get all the supported file extensions
 *
 * @return array
 */
function getFileExts()
{
    $configManager = new ConfigManager();
    return array_merge(
        $configManager->getConfig("docServViewd"),
        $configManager->getConfig("docServEdited"),
        $configManager->getConfig("docServConvert"),
        $configManager->getConfig("docServFillforms")
    );
}

/**
 * Get the correct file name if such a name already exists
 *
 * @param string $fileName
 * @param string $userAddress
 *
 * @return string
 */
function GetCorrectName($fileName, $userAddress = null)
{
    $path_parts = pathinfo($fileName);

    $ext = mb_strtolower($path_parts['extension']);
    $name = $path_parts['basename'];
    // get file name from the basename without extension
    $baseNameWithoutExt = mb_substr($name, 0, mb_strlen($name) - mb_strlen($ext) - 1);
    $name = $baseNameWithoutExt . "." . $ext;

    // if a file with such a name already exists in this directory
    for ($i = 1; file_exists(getStoragePath($name, $userAddress)); $i++) {
        $name = $baseNameWithoutExt . " (" . $i . ")." . $ext;  // add an index after its base name
    }
    return $name;
}

/**
 * Get document key
 *
 * @param string $fileName
 *
 * @return string
 */
function getDocEditorKey($fileName)
{
    // get document key by adding local file url to the current user host address
    $key = getCurUserHostAddress() . fileUri($fileName);
    $stat = filemtime(getStoragePath($fileName));  // get creation time
    $key = $key . $stat;  // and add it to the document key
    return generateRevisionId($key);  // generate the document key value
}

/**
 * File uploading
 *
 * @param string $fileUri
 *
 * @throws Exception If file type is not supported or copy operation is unsuccessful
 *
 * @return null
 */
function doUpload($fileUri)
{
    $_fileName = GetCorrectName($fileUri);

    // check if file extension is supported by the editor
    $ext = mb_strtolower('.' . pathinfo($_fileName, PATHINFO_EXTENSION));
    if (!in_array($ext, getFileExts())) {
        throw new Exception("File type is not supported");
    }

    // check if the file copy operation is successful
    if (!@copy($fileUri, getStoragePath($_fileName))) {
        $errors = error_get_last();
        $err = "Copy file error: " . $errors['type'] . "<br />\n" . $errors['message'];
        throw new Exception($err);
    }

    return $_fileName;
}

/**
 * Generate an error code table
 *
 * @param string $errorCode Error code
 *
 * @throws Exception If error code is unknown
 *
 * @return null
 */
function processConvServResponceError($errorCode)
{
    $errorMessageTemplate = "Error occurred in the document service: ";
    $errorMessage = '';

    // add the error message to the error message template depending on the error code
    switch ($errorCode) {
        case -8:
            $errorMessage = $errorMessageTemplate . "Error document VKey";
            break;
        case -7:
            $errorMessage = $errorMessageTemplate . "Error document request";
            break;
        case -6:
            $errorMessage = $errorMessageTemplate . "Error database";
            break;
        case -5:
            $errorMessage = $errorMessageTemplate . "Incorrect password";
            break;
        case -4:
            $errorMessage = $errorMessageTemplate . "Error download error";
            break;
        case -3:
            $errorMessage = $errorMessageTemplate . "Error convertation error";
            break;
        case -2:
            $errorMessage = $errorMessageTemplate . "Error convertation timeout";
            break;
        case -1:
            $errorMessage = $errorMessageTemplate . "Error convertation unknown";
            break;
        case 0:  // if the error code is equal to 0, the error message is empty
            break;
        default:
            $errorMessage = $errorMessageTemplate . "ErrorCode = " . $errorCode;  // default value for the error message
            break;
    }

    throw new Exception($errorMessage);
}

/**
 * Translation key to a supported form.
 *
 * @param string $expected_key Expected key
 *
 * @return string key
 */
function generateRevisionId($expected_key)
{
    if (mb_strlen($expected_key) > 20) {
        $expected_key = crc32($expected_key);
    }  // if the expected key length is greater than 20, calculate the crc32 for it
    $key = preg_replace("[^0-9-.a-zA-Z_=]", "_", $expected_key);
    $key = mb_substr($key, 0, min([mb_strlen($key), 20]));  // the resulting key length is 20 or less
    return $key;
}

/**
 * Request for conversion to a service.
 *
 * @param string $document_uri         Uri for the document to convert
 * @param string $from_extension       Document extension
 * @param string $to_extension         Extension to which to convert
 * @param string $document_revision_id Key for caching on service
 * @param bool   $is_async             Perform conversions asynchronously
 * @param string   $filePass
 * @param string   $lang
 *
 * @return string request result of conversion
 */
function sendRequestToConvertService(
    $document_uri,
    $from_extension,
    $to_extension,
    $document_revision_id,
    $is_async,
    $filePass,
    $lang
) {
    if (empty($from_extension)) {
        $path_parts = pathinfo($document_uri);
        $from_extension = mb_strtolower($path_parts['extension']);
    }

    // if title is undefined, then replace it with a random guid
    $title = basename($document_uri);
    if (empty($title)) {
        $title = guid();
    }

    if (empty($document_revision_id)) {
        $document_revision_id = $document_uri;
    }

    // generate document token
    $document_revision_id = generateRevisionId($document_revision_id);

    $configManager = new ConfigManager();
    $urlToConverter = $configManager->getConfig("docServSiteUrl").
        $configManager->getConfig("docServConverterUrl");

    $arr = [
        "async" => $is_async,
        "url" => $document_uri,
        "outputtype" => trim($to_extension, '.'),
        "filetype" => trim($from_extension, '.'),
        "title" => $title,
        "key" => $document_revision_id,
        "password" => $filePass,
        "region" => $lang,
    ];

    // add header token
    $headerToken = "";
    $jwtHeader = $configManager->getConfig("docServJwtHeader") ==
    "" ? "Authorization" : $configManager->getConfig("docServJwtHeader");

    $jwtManager = new JwtManager();
    if ($jwtManager->isJwtEnabled() && $jwtManager->tokenUseForRequest()) {
        $headerToken = $jwtManager->jwtEncode(["payload" => $arr]);
        $arr["token"] = $jwtManager->jwtEncode($arr);
    }

    $data = json_encode($arr);

    // request parameters
    $opts = ['http' => [
        'method' => 'POST',
        'timeout' => $configManager->getConfig("docServTimeout"),
        'header' => "Content-type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    (empty($headerToken) ? "" : $jwtHeader.": Bearer $headerToken\r\n"),
        'content' => $data,
    ],
    ];

    if (mb_substr($urlToConverter, 0, mb_strlen("https")) === "https") {
        if ($configManager->getConfig("docServVerifyPeerOff") === true) {
            $opts['ssl'] = ['verify_peer' => false, 'verify_peer_name' => false];
        }
    }

    $context = stream_context_create($opts);
    $response_data = file_get_contents($urlToConverter, false, $context);

    return $response_data;
}

/**
 * The method is to convert the file to the required format.
 *
 * Example:
 * string convertedDocumentUri;
 * getConvertedData("http://helpcenter.onlyoffice.com/content/GettingStarted.pdf",
 * ".pdf", ".docx", "http://helpcenter.onlyoffice.com/content/GettingStarted.pdf", false, out convertedDocumentUri);
 *
 * @param string $document_uri           Uri for the document to convert
 * @param string $from_extension         Document extension
 * @param string $to_extension           Extension to which to convert
 * @param string $document_revision_id   Key for caching on service
 * @param bool   $is_async               Perform conversions asynchronously
 * @param string $converted_document_uri Uri to the converted document
 * @param string $filePass               File pass
 * @param string $lang                   Language
 *
 * @throws Exception if an error occurs
 *
 * @return array percentage of completion of conversion and fileType from Convert service
 */
function getConvertedData(
    $document_uri,
    $from_extension,
    $to_extension,
    $document_revision_id,
    $is_async,
    &$converted_document_uri,
    $filePass,
    $lang
) {
    $converted_document_uri = "";
    $responceFromConvertService = sendRequestToConvertService(
        $document_uri,
        $from_extension,
        $to_extension,
        $document_revision_id,
        $is_async,
        $filePass,
        $lang
    );
    $json = json_decode($responceFromConvertService, true);

    // if an error occurs, then display an error message
    $errorElement = $json["error"] ?? "";
    if ($errorElement != null && $errorElement != "") {
        processConvServResponceError($errorElement);
    }

    $isEndConvert = $json["endConvert"];
    $percent = $json["percent"];
    $fileType = "";

    // if the conversion is completed successfully
    if ($isEndConvert != null && $isEndConvert == true) {
        // then get the file url
        $converted_document_uri = $json["fileUrl"];
        $fileType = $json["fileType"];
        $percent = 100;
    } elseif ($percent >= 100) { // otherwise, get the percentage of conversion completion
        $percent = 99;
    }

    return ["percent" => $percent, "fileType" => $fileType];
}

/**
 * Processing document received from the editing service.
 *
 * @param Response $document_response The result from editing service
 * @param string $response_uri      Uri to the converted document
 *
 * @throws Exception if an error occurs
 *
 * @return int percentage of completion of conversion
 */
function getResponseUri($document_response, &$response_uri)
{
    $response_uri = "";
    $resultPercent = 0;

    if (!$document_response) {
        $errs = "Invalid answer format";
    }

    // if an error occurs, then display an error message
    $errorElement = $document_response->Error;
    if ($errorElement != null && $errorElement != "") {
        processConvServResponceError($document_response->Error);
    }

    $endConvert = $document_response->EndConvert;
    if ($endConvert != null && $endConvert == "") {
        throw new Exception("Invalid answer format");
    }

    // if the conversion is completed successfully
    if ($endConvert != null && mb_strtolower($endConvert) == true) {
        $fileUrl = $document_response->FileUrl;
        if ($fileUrl == null || $fileUrl == "") {
            throw new Exception("Invalid answer format");
        }

        // get the response file url
        $response_uri = $fileUrl;
        $resultPercent = 100;
    } else { // otherwise, get the percentage of conversion completion
        $percent = $document_response->Percent;

        if ($percent != null && $percent != "") {
            $resultPercent = $percent;
        }
        if ($resultPercent >= 100) {
            $resultPercent = 99;
        }
    }

    return $resultPercent;
}

/**
 * Get demo file name by the extension
 *
 * @param string $createExt
 * @param Users $user
 *
 * @return string
 */
function tryGetDefaultByType($createExt, $user)
{
    $sample = isset($_GET["sample"]) && $_GET["sample"];
    $demoName = ($sample ? "sample." : "new.") . $createExt;
    $demoPath = "assets" . DIRECTORY_SEPARATOR . ($sample ? "sample" : "new") . DIRECTORY_SEPARATOR;
    $demoFilename = GetCorrectName($demoName);

    if (!@copy(dirname(__FILE__) . DIRECTORY_SEPARATOR . $demoPath . $demoName, getStoragePath($demoFilename))) {
        sendlog("Copy file error to ". getStoragePath($demoFilename), "common.log");
        // Copy error!!!
    }

    // create demo file meta information
    createMeta($demoFilename, $user->id, $user->name);

    return $demoFilename;
}

/**
 * Get the callback url
 *
 * @param string $fileName
 *
 * @return string
 */
function getCallbackUrl($fileName)
{
    return serverPath(true) . '/'
        . "webeditor-ajax.php"
        . "?type=track"
        . "&fileName=" . urlencode($fileName)
        . "&userAddress=" . getClientIp();
}

/**
 * Get url to the created file
 *
 * @param string $fileName
 * @param string $uid
 * @param string $type
 *
 * @return string
 */
function getCreateUrl($fileName, $uid, $type)
{
    $ext = trim(getInternalExtension($fileName), '.');
    return serverPath(false) . '/'
        . "doceditor.php"
        . "?fileExt=" . $ext
        . "&user=" . $uid
        . "&type=" . $type;
}

/**
 * Get url for history download
 *
 * @param string $fileName
 * @param string $version
 * @param string $file
 * @param bool $isServer
 *
 * @return string
 */
function getHistoryDownloadUrl($fileName, $version, $file, $isServer = true)
{
    $userAddress = $isServer ? "&userAddress=" . getClientIp() : "";
    return serverPath($isServer) . '/'
        . "webeditor-ajax.php"
        . "?type=history"
        . "&fileName=" . urlencode($fileName)
        . "&ver=" . $version
        . "&file=" . urlencode($file)
        . $userAddress;
}

/**
 * Get url to download a file
 *
 * @param string $fileName
 * @param bool $isServer
 *
 * @return string
 */
function getDownloadUrl($fileName, $isServer = true)
{
    $userAddress = $isServer ? "&userAddress=" . getClientIp() : "";
    return serverPath($isServer) . '/'
        . "webeditor-ajax.php"
        . "?type=download"
        . "&fileName=" . urlencode($fileName)
        . $userAddress;
}

/**
 * Get document history
 *
 * @param string $filename
 * @param string $filetype
 * @param string $docKey
 * @param string $fileuri
 * @param bool $isEnableDirectUrl
 *
 * @return array
 */
function getHistory($filename, $filetype, $docKey, $fileuri, $isEnableDirectUrl)
{
    $configManager = new ConfigManager();
    $storagePath = $configManager->getConfig("storagePath");
    $histDir = getHistoryDir(getStoragePath($filename));  // get the path to the file history

    if (getFileVersion($histDir) > 0) {  // check if the file was modified (the file version is greater than 0)
        $curVer = getFileVersion($histDir);

        $hist = [];
        $histData = [];

        for ($i = 1; $i <= $curVer; $i++) {  // run through all the file versions
            $obj = [];
            $dataObj = [];
            $verDir = getVersionDir($histDir, $i);  // get the path to the file version

            // get document key
            $key = $i == $curVer ? $docKey : file_get_contents($verDir . DIRECTORY_SEPARATOR . "key.txt");
            $obj["key"] = $key;
            $obj["version"] = $i;

            if ($i == 1) {  // check if the version number is equal to 1
                // get meta data of this file
                $createdInfo = file_get_contents($histDir . DIRECTORY_SEPARATOR . "createdInfo.json");
                $json = json_decode($createdInfo, true);  // decode the meta data from the createdInfo.json file

                $obj["created"] = $json["created"];
                $obj["user"] = [
                    "id" => $json["uid"],
                    "name" => $json["name"],
                ];
            }

            $fileExe = mb_strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            $prevFileName = $verDir . DIRECTORY_SEPARATOR . "prev." . $filetype;
            $prevFileName = mb_substr($prevFileName, mb_strlen(getStoragePath("")));
            $dataObj["fileType"] = $fileExe;
            $dataObj["key"] = $key;

            $directUrl = $i == $curVer ? fileUri($filename, false) :
                getHistoryDownloadUrl($filename, $i, "prev.".$fileExe, false);
            $prevFileUrl = $i == $curVer ? $fileuri : getHistoryDownloadUrl($filename, $i, "prev.".$fileExe);
            if (realpath($storagePath) === $storagePath) {
                $prevFileUrl = $i == $curVer ? getDownloadUrl($filename) :
                    getHistoryDownloadUrl($filename, $i, "prev.".$fileExe);
                if ($isEnableDirectUrl) {
                    $directUrl = $i == $curVer ? getDownloadUrl($filename, false) :
                        getHistoryDownloadUrl($filename, $i, "prev.".$fileExe, false);
                }
            }

            $dataObj["url"] = $prevFileUrl;  // write file url to the data object
            if ($isEnableDirectUrl) {
                $dataObj["directUrl"] = $directUrl;  // write direct url to the data object
            }
            $dataObj["version"] = $i;

            if ($i > 1) {  // check if the version number is greater than 1 (the document was modified)
                $changes = json_decode(file_get_contents(getVersionDir($histDir, $i - 1) .
                    DIRECTORY_SEPARATOR . "changes.json"), true);  // get the path to the changes.json file
                $change = $changes["changes"][0];

                // write information about changes to the object
                $obj["changes"] = $changes ? $changes["changes"] : null;
                $obj["serverVersion"] = $changes["serverVersion"];
                $obj["created"] = $change ? $change["created"] : null;
                $obj["user"] = $change ? $change["user"] : null;

                $prev = $histData[$i - 2];  // get the history data from the previous file version
                // write information about previous file version to the data object
                $dataObj["previous"] = $isEnableDirectUrl ? [
                    "fileType" => $prev["fileType"],
                    "key" => $prev["key"],
                    "url" => $prev["url"],
                    "directUrl" => $prev["directUrl"],
                ] : [
                    "fileType" => $prev["fileType"],
                    "key" => $prev["key"],
                    "url" => $prev["url"],
                ];

                // write the path to the diff.zip archive with differences in this file version
                $dataObj["changesUrl"] = getHistoryDownloadUrl($filename, $i - 1, "diff.zip");
            }

            $jwtManager = new JwtManager();
            if ($jwtManager->isJwtEnabled()) {
                $dataObj["token"] = $jwtManager->jwtEncode($dataObj);
            }

            $hist[] = $obj;  // add object dictionary to the hist list
            $histData[$i - 1] = $dataObj;  // write data object information to the history data
        }

        // write history information about the current file version
        $out = [];
        array_push(
            $out,
            [
                "currentVersion" => $curVer,
                "history" => $hist,
            ],
            $histData
        );
        return $out;
    }
}
