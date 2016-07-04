<?php

namespace Puz\Mail\AutoEmbed;

use Swift_Events_SendListener;
use Swift_Events_SendEvent;
use Swift_Image;

class ImagesToAttachments implements Swift_Events_SendListener
{
    /**
     * @var \Swift_Message
     */
    protected $message;

    /**
     * @var bool
     */
    protected $saveImages = false;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $tempFiles = [];

    public function __construct($savePath = null)
    {
        // TODO: Make plugin able to save images
    }

    public function beforeSendPerformed(Swift_Events_SendEvent $event)
    {
        $this->message = $event->getMessage();

        // Simply attach all the images you can find
        $this->attachImages();
    }

    public function sendPerformed(Swift_Events_SendEvent $evt)
    {
        // Delete all the temp files after sending the message
        foreach ($this->tempFiles as $file) {
            unlink($file);
        }
    }

    protected function attachImages()
    {
        $images = explode("<img", $this->message->getBody());
        $imageCount = count($images);
        if ($imageCount > 1) {
            $contentType = $this->message->getContentType();
            if (strpos($contentType, "text/html") === false) {
                $this->message->setContentType("text/html; charset=utf-8");
            }

            for ($i = 1; $i < count($images); $i++) {
                $parts = explode(">", $images[$i], 2);

                $src = explode("src=", $parts[0], 2);
                $splitBy = $src[1][0];

                $data = explode($splitBy, $src[1], 3);

                // check if it is a data image
                if (strpos($data[1], "data") === 0) {
                    if (strpos($data[1], "data:image") === 0) {
                        // data image
                        $embed = $this->embedDataImage($data[1]);
                    } else {
                        // Not valid data image object, ignore it
                        $images[$i] = "<img" . $parts[0] . ">" . $parts[1];
                        continue;
                    }
                } else {
                    $embed = $this->embedImage($data[1]);
                }
                $img = "<img" . $src[0] . "src=" . $splitBy . $embed . $splitBy . $data[2] . ">";


                $images[$i] = $img . $parts[1];
            }

            $this->message->setBody(implode("", $images));
        }
    }

    protected function embedImage($src)
    {
        // TODO Is it from the interwebs?
        
        // Relative path attachments
        if( substr($src, 0, 1) == '/' ) {
            if( function_exists( 'public_path' ) ) {
                // Laravel-only, public path can be found from any sapi:
                $src = public_path( $src );
            } elseif ( php_sapi_name() !== 'cli' ) {
                // Standalone script; only from web:
                $src = $_SERVER['DOCUMENT_ROOT'] . '/' . $src;
            }
        }
        
        $embed = $this->message->embed(Swift_Image::fromPath($src));

        return $embed;
    }

    protected function embedDataImage($data)
    {
        // data:image/type;base64,
        $data = explode(";base64,", $data, 2);

        $ext = explode("image/", $data[0])[1];

        if ($ext == "jpeg") {
            $ext = "jpg";
        }

        $name = md5(microtime()) . "." . $ext;

        $filePath = $this->tempDir() . DIRECTORY_SEPARATOR . $name;
        file_put_contents($filePath, base64_decode($data[1]));

        $embed = $this->message->embed(Swift_Image::fromPath($filePath));

        $this->tempFiles[] = $filePath;

        return $embed;
    }

    protected function tempDir()
    {
        return ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
    }
}
