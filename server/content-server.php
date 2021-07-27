<?php
include './ArchiveManager.php';

$langs  =  file_get_contents(__DIR__.'/languages.json');
$langs  =  json_decode($langs, true);
$unzipper =  new ArchiveManager($langs);

if(isset($_REQUEST['name'])  && isset($_REQUEST['index']) ){
    $content  =  $unzipper->downloadZipItem($_REQUEST['name'],$_REQUEST['index']);
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename={$content['fileName']}");
    header("Content-Type: application/octet-stream");
    header("Content-Transfer-Encoding: binary");
    header("Content-length:".strlen($content['content']));
   echo $content['content'];
   exit;
}


if(isset($_GET['archive'])){
    $archiveContent  =  $unzipper->unzip(__DIR__.'/files/'.$_GET['archive']);
    $feedback['archiveContent'] = $archiveContent;
    echo json_encode( $feedback) ;
}



if(isset($_GET['sub-list'])){
    $content =  $unzipper->getzipEntryContent(
        $_GET['zip-name'],
        $_GET['file-index'],
        $_GET['file-type'],
        $_GET['file-name'],
        $_GET['file-path']
    );
    echo json_encode($content) ;

}





?>