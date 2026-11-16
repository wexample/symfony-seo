<?php

namespace Wexample\SymfonySeo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Wexample\SymfonySeo\EventSubscriber\RobotsHeaderSubscriber;

class RobotsHeaderSubscriberTest extends TestCase
{
    public function testAClosedAppMarksEveryResponse(): void
    {
        $response = $this->dispatch(true, new Response('<html></html>'));

        $this->assertSame('noindex, nofollow', $response->headers->get('X-Robots-Tag'));
    }

    public function testAFileIsMarkedAsAPageIs(): void
    {
        $response = $this->dispatch(true, new BinaryFileResponse(__FILE__));

        $this->assertSame('noindex, nofollow', $response->headers->get('X-Robots-Tag'));
    }

    public function testAnOpenAppAddsNothing(): void
    {
        $response = $this->dispatch(false, new Response());

        $this->assertFalse($response->headers->has('X-Robots-Tag'));
    }

    public function testAResponseKeepsTheHeaderItSetItself(): void
    {
        $response = $this->dispatch(true, new Response('', 200, ['X-Robots-Tag' => 'noindex']));

        $this->assertSame('noindex', $response->headers->get('X-Robots-Tag'));
    }

    public function testASubRequestIsLeftToTheMainOne(): void
    {
        $response = $this->dispatch(true, new Response(), HttpKernelInterface::SUB_REQUEST);

        $this->assertFalse($response->headers->has('X-Robots-Tag'));
    }

    private function dispatch(
        bool $disallowAll,
        Response $response,
        int $type = HttpKernelInterface::MAIN_REQUEST,
    ): Response {
        $event = new ResponseEvent($this->createStub(HttpKernelInterface::class), new Request(), $type, $response);
        (new RobotsHeaderSubscriber($disallowAll))->onResponse($event);

        return $event->getResponse();
    }
}
