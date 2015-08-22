<?php

namespace NewupPlayground\Disco;

use Exception;
use DOMDocument;

class Wikipedia
{

    /**
     * Gets the redirect location for a given URI known to redirect.
     *
     * @param $uri
     * @return bool|string
     */
    private function getRedirectLocation($uri)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);

        if ($data !== false && preg_match('#Location: (.*)#', $data, $location)) {
            return trim($location[1]);
        }

        return false;
    }

    /**
     * Gets the content for a given URI.
     *
     * @param $uri
     * @return bool|mixed
     */
    private function fetchWebData($uri)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);

        if ($data === false) {
            return false;
        }

        return $data;
    }

    /**
     * Gets a random Wikipedia article's information.
     *
     * @param null $location
     * @return array|bool
     */
    public function random($location = null)
    {
        if ($location == null) {
            $articleLocation = $this->getRedirectLocation('https://en.wikipedia.org/wiki/Special:Random');
        } else {
            $articleLocation = $location;
        }

        if ($articleLocation !== false) {
            if ($document = $this->fetchWebData($articleLocation)) {
                libxml_use_internal_errors(true);
                with($dom = new DOMDocument)->loadHTML($document);
                libxml_clear_errors();
                $title = $dom->getElementsByTagName('title');
                if ($title->length) {
                    return [
                        $articleLocation,
                        rtrim($title->item(0)->textContent, ' - Wikipedia, the free encyclopedia')
                    ];
                }
            }
        }

        return false;
    }

    /**
     * Gets a slighlty less random Wikipedia article's information.
     *
     * Will not return an article that has been returned before.
     *
     * @return array|bool
     */
    public function uniquelyRandom()
    {
        $location = $this->getRedirectLocation('https://en.wikipedia.org/wiki/Special:Random');

        if ($this->inHistory($location)) {
            return $this->uniquelyRandom();
        } else {
            $this->addToHistory($location);
            return $this->random($location);
        }
    }

    /**
     * Adds a location to the history.
     *
     * @param $location
     */
    private function addToHistory($location)
    {
        file_put_contents(__DIR__.'/history', $location, FILE_APPEND);
    }

    /**
     * Checks if a given location is in the history.
     *
     * @param $location
     * @return bool
     */
    private function inHistory($location)
    {
        if (!file_exists(__DIR__.'/history')) {
            touch(__DIR__.'/history');
        }

        $handle = fopen(__DIR__.'/history', 'r');
        $inHistory = false; // init as false
        while (($buffer = fgets($handle)) !== false) {
            if (strpos($buffer, $location) !== false) {
                $inHistory = true;
                break;
            }
        }
        fclose($handle);

        return $inHistory;
    }

}