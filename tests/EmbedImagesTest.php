<?php


class EmbedImagesTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * @var Puz\Mail\AutoEmbed\ImagesToAttachments
     */
    protected $mock;

    public function setUp()
    {
        $eventDispatcher = new Swift_Events_SimpleEventDispatcher();

        $transporter = new Swift_Transport_NullTransport($eventDispatcher);

        $this->mailer = new Swift_Mailer($transporter);

        $this->mock = $this->getMockBuilder(\Puz\Mail\AutoEmbed\ImagesToAttachments::class)
            ->setMethods(array('sendPerformed'))
            ->getMock();

        $this->mailer->registerPlugin($this->mock);
    }

    public function tearDown()
    {
        $reflector = new ReflectionClass(\Puz\Mail\AutoEmbed\ImagesToAttachments::class);
        $property = $reflector->getProperty("tempFiles");
        $property->setAccessible(true);

        /** @var array $files */
        $files = $property->getValue($this->mock);

        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * @test
     */
    public function can_embed_local_image()
    {
        $imageFile = __DIR__. "/data/smallimage.png";

        $bodyBefore = "<img src='" . $imageFile . "'>";
        $message = new Swift_Message("Test", $bodyBefore);

        $this->mailer->send($message);


        $bodyAfter = $message->getBody();
        $this->assertNotEquals($bodyBefore, $bodyAfter, "Image was not inline embedded");

        $this->assertRegExp("/<img src='cid:/", $bodyAfter, "Missing Content-ID from sent message");

        $messageContent = (string) $message;

        $this->assertContains("Content-Type: image/png; name=smallimage.png", $messageContent);
        $this->assertContains("Content-Transfer-Encoding: base64", $messageContent);
        $this->assertContains("Content-Disposition: inline; filename=smallimage.png", $messageContent);

        // For some reason, swiftmailer returns 78 as max length, but the content is at max 76
        $this->assertContains(implode(PHP_EOL, str_split(base64_encode(file_get_contents($imageFile)), 76)), $messageContent);
    }

    /**
     * @test
     */
    public function can_embed_base64_image()
    {
        $base64 = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAMSURBVBhXY3growIAAycBLhVrvukAAAAASUVORK5CYII=";
        $imageFile = "data:image/png;base64,$base64";

        $bodyBefore = "<img src='" . $imageFile . "'>";
        $message = new Swift_Message("Test", $bodyBefore);

        $this->mailer->send($message);

        $bodyAfter = $message->getBody();
        $this->assertNotEquals($bodyBefore, $bodyAfter, "Image was not inline embedded");

        $this->assertRegExp("/<img src='cid:/", $bodyAfter, "Missing Content-ID from sent message");

        $messageContent = $message->toString(); // Will throw exception because the plugin will delete the temporary generated file after it has sent it's message

        $this->assertContains("Content-Type: image/png; name=", $messageContent);
        $this->assertContains("Content-Transfer-Encoding: base64", $messageContent);
        $this->assertContains("Content-Disposition: inline; filename=", $messageContent);

        // For some reason, swiftmailer returns 78 as max length, but the content is at max 76
        $this->assertContains(implode(PHP_EOL, str_split($base64, 76)), $messageContent);
    }

    /**
     * @test
     */
    public function can_embed_base64_jpeg_image()
    {
        $base64 = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAMSURBVBhXY3growIAAycBLhVrvukAAAAASUVORK5CYII=";
        $imageFile = "data:image/jpeg;base64,$base64";

        $bodyBefore = "<img src='" . $imageFile . "'>";
        $message = new Swift_Message("Test", $bodyBefore);

        $this->mailer->send($message);

        $bodyAfter = $message->getBody();
        $this->assertNotEquals($bodyBefore, $bodyAfter, "Image was not inline embedded");

        $this->assertRegExp("/<img src='cid:/", $bodyAfter, "Missing Content-ID from sent message");
        $messageContent = $message->toString(); // Will throw exception because the plugin will delete the temporary generated file after it has sent it's message

        $this->assertRegExp("/Content-Type: image\/jpeg; name=.*(?=\.jpg)/", $messageContent);
        $this->assertContains("Content-Transfer-Encoding: base64", $messageContent);
        $this->assertContains("Content-Disposition: inline; filename=", $messageContent);

        // For some reason, swiftmailer returns 78 as max length, but the content is at max 76
        $this->assertContains(implode(PHP_EOL, str_split($base64, 76)), $messageContent);
    }


    /**
     * @test
     */
    public function intact_message_with_invalid_base64()
    {
        $base64 = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAMSURBVBhXY3growIAAycBLhVrvukAAAAASUVORK5CYII=";

        $imageFile = "data:other/type;base64,$base64";

        $bodyBefore = "hei <img src='" . $imageFile . "'>";
        $message = new Swift_Message("Test", $bodyBefore);

        $this->mailer->send($message);

        $bodyAfter = $message->getBody();
        $this->assertEquals($bodyBefore, $bodyAfter, "Image was inline embedded");

        $this->assertNotRegExp("/<img src='cid:/", $bodyAfter, "Missing Content-ID from sent message");

        $messageContent = $message->toString();

        $this->assertNotContains("Content-Type: image/png; name=", $messageContent);
        $this->assertNotContains("Content-Transfer-Encoding: base64", $messageContent);
        $this->assertNotContains("Content-Disposition: inline; filename=", $messageContent);

        // For some reason, swiftmailer returns 78 as max length, but the content is at max 76
        $this->assertNotContains(implode(PHP_EOL, str_split($base64, 76)), $messageContent);

    }
}