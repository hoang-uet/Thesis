<?php

namespace App\Services\Crawler;

class CrawlerCurlInit
{
    public function init()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.90 Safari/537.36");
        $cookie_shopee = 'cookie_shopee.txt';
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_shopee);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_shopee);

        $header = array(
            'x-csrftoken: ' . $this->csrftoken(),
            'x-requested-with: XMLHttpRequest',
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, false);
        curl_setopt($ch, CURLOPT_COOKIE, "csrftoken=" . $this->csrftoken());
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_exec($ch);

        return $ch;
    }
}
