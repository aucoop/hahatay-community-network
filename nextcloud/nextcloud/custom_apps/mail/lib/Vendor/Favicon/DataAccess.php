<?php

namespace OCA\Mail\Vendor\Favicon;

/**
 * DataAccess is a wrapper used to read/write data locally or remotly
 * Aside from SOLID principles, this wrapper is also useful to mock remote resources in unit tests
 * Note: remote access warning are silenced because we don't care if a website is unreachable
 **/
class DataAccess
{
    public function retrieveUrl($url)
    {
        $this->setContext();
        return @file_get_contents($url);
    }

    public function retrieveHeader($url)
    {
        $this->setContext();
        $headers = @get_headers($url, 1);
        return is_array($headers) ? array_change_key_case($headers) : [];
    }

    public function saveCache($file, $data)
    {
        file_put_contents($file, $data);
    }

    public function readCache($file)
    {
        return file_get_contents($file);
    }

    private function setContext()
    {
        stream_context_set_default(
            [
                'http' => [
                    'method' => 'GET',
                    'follow_location' => 0,
                    'max_redirects' => 1,
                    'timeout' => 10,
                    'header' => 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:20.0; OCA\Mail\Vendor\Favicon; ' .
                                '+https://github.com/ArthurHoaro/favicon) Gecko/20100101 Firefox/32.0\r\n',
                ]
            ]
        );
    }
}
