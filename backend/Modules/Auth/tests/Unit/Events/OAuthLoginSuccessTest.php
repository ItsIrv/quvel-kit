<?php

namespace Modules\Auth\Tests\Unit\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Mockery;
use Modules\Auth\Events\OAuthLoginSuccess;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(OAuthLoginSuccess::class)]
#[Group('auth-module')]
#[Group('auth-events')]
class OAuthLoginSuccessTest extends TestCase
{
    private string $nonce;
    private OAuthLoginSuccess $event;

    public function setUp(): void
    {
        parent::setUp();

        // Mocking a valid long nonce
        $this->nonce = 'nonce' . bin2hex(random_bytes(32));
        $this->event = new OAuthLoginSuccess($this->nonce);
    }

    /**
     * Test that OAuthLoginSuccess event implements ShouldBroadcast.
     */
    public function testEventImplementsShouldBroadcast(): void
    {
        // Assert
        $this->assertInstanceOf(ShouldBroadcast::class, $this->event);
    }

    /**
     * Test that OAuthLoginSuccess event broadcasts to the correct channel.
     */
    public function testEventBroadcastsToCorrectChannel(): void
    {
        // Arrange
        $expectedChannel = new Channel("auth.nonce.{$this->nonce}");

        // Assert
        $this->assertEquals([$expectedChannel], $this->event->broadcastOn());
    }

    /**
     * Test that OAuthLoginSuccess event broadcasts the correct payload.
     */
    public function testEventBroadcastsCorrectPayload(): void
    {
        // Assert
        $this->assertEquals(['success' => true], $this->event->broadcastWith());
    }

    /**
     * Test that OAuthLoginSuccess event has the correct broadcast name.
     */
    public function testEventHasCorrectName(): void
    {
        // Assert
        $this->assertEquals('oauth.success', $this->event->broadcastAs());
    }
}
