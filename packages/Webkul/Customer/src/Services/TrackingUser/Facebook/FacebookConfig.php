<?php
namespace Webkul\Customer\Services\TrackingUser\Facebook;

class FacebookConfig
{
    /**
     * @var string
     */
    private $fbPixelToken = '';

    /**
     * @var string
     */
    private $fbPixelID = '';

    /**
     * @var string
     */
    private $url = 'https://graph.facebook.com/v12.0';

    public function __construct()
    {
        $this->setFbPixelToken();
        $this->setFbPixelID();
    }

    /**
     * SET FB Pixel Token
     *
     * @return string
     */
    private function setFbPixelToken()
    {
        $this->fbPixelToken = config('robosto.FB_PIXEL_TOKEN');
    }

    /**
     * SET FB Pixel ID
     *
     * @return string
     */
    private function setFbPixelID()
    {
        $this->fbPixelID = config('robosto.FB_PIXEL_ID');
    }

    /**
     * GET FB Pixel Token
     *
     * @return string
     */
    public function getFbPixelToken()
    {
        return $this->fbPixelToken;
    }

    /**
     * @return string
     */
    public function baseURL()
    {
        return "{$this->url}/{$this->fbPixelID}/events";
    }
}