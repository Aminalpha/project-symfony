<?php

namespace App\Controller;

use Nzo\FileDownloaderBundle\FileDownloader\FileDownloader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class HomeController extends AbstractController
{
    /**
     * @var FileDownloader $fileDownloader
     */
    private $fileDownloader;
    public function __construct(FileDownloader $fileDownloader)
    {
        $this->fileDownloader = $fileDownloader;

        // without autowiring use: $this->get('nzo_file_downloader')
    }


    public function index()
    {
        return $this->render("index.html.twig");
    }

    /**
     * @Route("/sfiptv/{listeId}")
     * @param $listeId
     * @return Response
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function downloadAction($listeId)
    {
        set_time_limit(0);
        $isLocal = false;
        $id = '';
        $url = false;
        if (!empty($listeId)) {
            $id = $listeId;
            if ($id == '2019101006001') {
                $url = 'http://www.sansat.net:25461/get.php?username=YviwmvfIsPQrxTu&password=RRSJYMOGijtpwfN&output=ts&type=m3u_plus';
            }
            if ($id == '2') {
                //$url = 'http://mu01.v5iptv.com:8880/get.php?spm=a2g0s.imconversation.0.0.7f333e5f8BOR3M&username=HJLM424&password=bHP6ein2Ms&type=m3u_plus&output=ts';
                //downloadFromLocalAndExit($id);
                $isLocal = true;
                $url = false;

            } else if ('22') {
                $url = 'http://www.sansat.net:25461/get.php?username=YviwmvfIsPQrxTu&password=RRSJYMOGijtpwfN&output=ts&type=m3u_plus';
            }
        } else {
            $id = 'default';
            $isLocal = true;
            $url = false;
            //$url = 'http://mu01.v5iptv.com:8880/get.php?spm=a2g0s.imconversation.0.0.7f333e5f8BOR3M&username=BL12MA000905&password=58519412&type=m3u_plus&output=ts';
            //downloadFromLocalAndExit($id);
        }



        $fileName = $this->downloadFromServer($url, $id);

        if ($url == false){

            //$response->headers->set("Content-Length", filesize($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .'local'.DIRECTORY_SEPARATOR . $id . '.txt'));
            //$response->setContent(readfile($_SERVER['DOCUMENT_ROOT'].'/local/'.$id.'.txt'));
            $response = $this->fileDownloader->readfile($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .'local'.DIRECTORY_SEPARATOR . $id . '.txt', true);
        } else{
            //$response->headers->set("Content-Length", filesize($_SERVER['DOCUMENT_ROOT'] . '/temp/' . $fileName));
            //$response->setContent(readfile($_SERVER['DOCUMENT_ROOT'] . '/temp/' . $fileName));
            $response = $this->fileDownloader->readfile($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .'temp'.DIRECTORY_SEPARATOR . $fileName);
            $this->deleteFiles();
        }
        $response = $this->prepareHeader($id, $response);
        return $response;
    }

    /**
     * @param $id
     * @param $response
     * @return  Response
     */
    private function prepareHeader($id, $response)
    {
        $seconds_to_cache = 3600;
        $ts = gmdate("D, d M Y H:i:s", time() + ($seconds_to_cache)) . " GMT";


        $response->headers->set("Expires", $ts);
        $response->headers->set("Pragma", "cache");
        $response->headers->set("Cache-Control", "max-age=".($seconds_to_cache));
        $response->headers->set("Content-Description", "File Transfer");
        $response->headers->set("Content-Type", "application/octet-stream");
        $response->headers->set("Content-Disposition", "attachment;filename=$id-playlist.m3u");

        $response->headers->set("Content-Transfer-Encoding", "binary");
        return $response;
    }

    /**
     * @param $url
     * @param $id
     * @return string
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function downloadFromServer($url, $id)
    {
        $file = 'test' . $id;
        if ($url != false) {
            $httpClient = HttpClient::create();
            $response = $httpClient->request('GET', $url);
            $statusCode = $response->getStatusCode();
            if (!empty($statusCode) && $statusCode == 200) {
                $content = $response->getContent();
                $fp = fopen($_SERVER['DOCUMENT_ROOT'] . '/temp/' . $file, 'w+');
                fwrite($fp, $content);
                fclose($fp);
            }
        } else {
            $file = $id;
        }

        return $file;
    }

    /**
     */
    private function deleteFiles()
    {
        //$filesystem = new Filesystem();
        $files = glob($_SERVER['DOCUMENT_ROOT'] . '/temp/*');
        foreach ($files as $fl) { // iterate files
            if (is_file($fl))
                unlink($fl); // delete file
        }
    }
}
