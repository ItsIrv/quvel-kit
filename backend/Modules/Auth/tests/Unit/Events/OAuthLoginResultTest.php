<?php

namespace Modules\Auth\Tests\Unit\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Modules\Auth\DTO\OAuthCallbackResult;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Events\OAuthLoginResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(OAuthLoginResult::class)]
#[Group('auth-module')]
#[Group('auth-events')]
class OAuthLoginResultTest extends TestCase
{
    private string $nonce;

    private OAuthLoginResult $event;

    private OAuthCallbackResult $result;

    protected function setUp(): void
    {
        parent::setUp();

        // Mocking a valid long nonce
        $this->nonce  = 'nonce' . bin2hex(random_bytes(32));
        $this->result = \Mockery::mock(OAuthCallbackResult::class);
        $this->event  = new OAuthLoginResult($this->nonce, $this->result);
    }

    /**
     * Test that OAuthLoginResult event implements ShouldBroadcast.
     */
    public function test_event_implements_should_broadcast(): void
    {
        // Assert
        $this->assertInstanceOf(ShouldBroadcast::class, $this->event);
    }

    /**
     * Test that OAuthLoginResult event broadcasts to the correct channel.
     */
    public function test_event_broadcasts_to_correct_channel(): void
    {
        // Arrange
        $expectedChannel = new Channel("auth.nonce.$this->nonce");

        // Assert
        $this->assertEquals([$expectedChannel], $this->event->broadcastOn());
    }

    /**
     * Test that OAuthLoginResult event broadcasts the correct payload.
     */
    public function test_event_broadcasts_correct_payload(): void
    {
        $this->result->shouldReceive('getStatus')->andReturn(OAuthStatusEnum::LOGIN_SUCCESS);

        // Assert
        $this->assertEquals(['status' => OAuthStatusEnum::LOGIN_SUCCESS->value], $this->event->broadcastWith());
    }

    /**
     * Test that OAuthLoginResult event has the correct broadcast name.
     */
    public function test_event_has_correct_name(): void
    {
        // Assert
        $this->assertEquals('oauth.result', $this->event->broadcastAs());
    }
}
