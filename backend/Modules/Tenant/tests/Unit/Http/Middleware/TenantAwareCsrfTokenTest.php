<?php

namespace Modules\Tenant\Tests\Unit\Http\Middleware;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Http\Middleware\TenantAwareCsrfToken;
use Modules\Tenant\Models\Tenant;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class TenantAwareCsrfTokenTest extends TestCase
{
    protected TenantAwareCsrfToken $middleware;
    protected MockInterface $mockTenantContext;
    protected MockInterface $mockApp;
    protected MockInterface $mockEncrypter;
    protected MockInterface $mockRequest;
    protected MockInterface $mockResponse;
    protected MockInterface $mockSession;
    protected MockInterface $mockTenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockApp = Mockery::mock(Application::class);
        $this->mockEncrypter = Mockery::mock(Encrypter::class);
        $this->mockTenantContext = Mockery::mock(TenantContext::class);
        $this->mockRequest = Mockery::mock(Request::class);
        $this->mockResponse = Mockery::mock(Response::class);
        $this->mockSession = Mockery::mock(SessionStore::class);
        $this->mockTenant = Mockery::mock(Tenant::class);

        $this->mockApp->shouldReceive('make')->with('encrypter')->andReturn($this->mockEncrypter);

        // Set up application container to return our mocked encrypter
        $this->mockApp->shouldReceive('get')->with('encrypter')->andReturn($this->mockEncrypter);

        // Mock the app() helper function
        $this->mockApp->shouldReceive('offsetGet')->with('encrypter')->andReturn($this->mockEncrypter);

        // Create the middleware with our mocked dependencies
        $this->middleware = new class($this->mockTenantContext, $this->mockApp, $this->mockEncrypter) extends TenantAwareCsrfToken {
            public function __construct(
                private readonly TenantContext $tenantContext,
                private $appContainer,
                private $encrypterInstance
            ) {
                parent::__construct($this->tenantContext);
                $this->container = $this->appContainer;
                $this->encrypter = $this->encrypterInstance;
            }

            // Expose protected methods for testing
            public function exposedGetCookieName(): string
            {
                return $this->getCookieName();
            }

            public function exposedNewCookie($request, $config)
            {
                return $this->newCookie($request, $config);
            }

            public function exposedGetTokenFromRequest($request): ?string
            {
                return $this->getTokenFromRequest($request);
            }
        };

        // Set up response headers
        $this->mockResponse->headers = Mockery::mock(ResponseHeaderBag::class);
        
        // Set up request headers
        $this->mockRequest->headers = Mockery::mock(HeaderBag::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[TestDox('Should use tenant-specific cookie name when tenant context is available')]
    public function testUsesTenantSpecificCookieNameWhenTenantContextIsAvailable(): void
    {
        // Arrange
        $this->mockTenant->shouldReceive('getAttribute')->with('public_id')->andReturn('tenant-123');
        $this->mockTenant->shouldReceive('__get')->with('public_id')->andReturn('tenant-123');
        
        $this->mockTenantContext->shouldReceive('has')
            ->once()
            ->andReturn(true);
            
        $this->mockTenantContext->shouldReceive('get')
            ->once()
            ->andReturn($this->mockTenant);

        // Act
        $cookieName = $this->middleware->exposedGetCookieName();

        // Assert
        $this->assertEquals('XSRF-TOKEN-tenant-123', $cookieName);
    }

    #[TestDox('Should use default cookie name when tenant context is not available')]
    public function testUsesDefaultCookieNameWhenTenantContextIsNotAvailable(): void
    {
        // Arrange
        $this->mockTenantContext->shouldReceive('has')
            ->once()
            ->andReturn(false);

        // Act
        $cookieName = $this->middleware->exposedGetCookieName();

        // Assert
        $this->assertEquals('XSRF-TOKEN', $cookieName);
    }

    #[TestDox('Should use default cookie name when tenant context has no tenant')]
    public function testUsesDefaultCookieNameWhenTenantContextHasNoTenant(): void
    {
        // Arrange
        // Create a new anonymous middleware instance specifically for this test
        $middleware = new class($this->mockTenantContext, $this->mockApp, $this->mockEncrypter) extends TenantAwareCsrfToken {
            public function exposedGetCookieName(): string
            {
                return $this->getCookieName();
            }
        };
        
        $this->mockTenantContext->shouldReceive('has')
            ->once()
            ->andReturn(true);
            
        $this->mockTenantContext->shouldReceive('get')
            ->once()
            ->andReturn(null);

        // Act
        $cookieName = $middleware->exposedGetCookieName();

        // Assert
        $this->assertEquals('XSRF-TOKEN', $cookieName);
    }

    #[TestDox('Should create cookie with tenant-specific name')]
    public function testCreatesCookieWithTenantSpecificName(): void
    {
        // Arrange
        $this->mockTenant->shouldReceive('getAttribute')->with('public_id')->andReturn('tenant-456');
        $this->mockTenant->shouldReceive('__get')->with('public_id')->andReturn('tenant-456');
        
        $this->mockTenantContext->shouldReceive('has')
            ->once()
            ->andReturn(true);
            
        $this->mockTenantContext->shouldReceive('get')
            ->once()
            ->andReturn($this->mockTenant);
            
        $this->mockRequest->shouldReceive('session')
            ->once()
            ->andReturn($this->mockSession);
            
        $this->mockSession->shouldReceive('token')
            ->once()
            ->andReturn('csrf-token-value');
            
        $config = [
            'lifetime' => 120,
            'path' => '/',
            'domain' => null,
            'secure' => true,
            'same_site' => 'lax',
            'partitioned' => false,
        ];

        // Act
        $cookie = $this->middleware->exposedNewCookie($this->mockRequest, $config);

        // Assert
        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertEquals('XSRF-TOKEN-tenant-456', $cookie->getName());
        $this->assertTrue(strpos($cookie->getValue(), 'csrf-token-value') !== false);
        $this->assertEquals('/', $cookie->getPath());
        $this->assertEquals(null, $cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertEquals('lax', $cookie->getSameSite());
        $this->assertFalse($cookie->isHttpOnly());
    }

    #[TestDox('Should get token from request input')]
    public function testGetsTokenFromRequestInput(): void
    {
        // Arrange
        $this->mockRequest->shouldReceive('input')
            ->with('_token')
            ->once()
            ->andReturn('input-token');
            
        $this->mockRequest->shouldReceive('header')
            ->with('X-CSRF-TOKEN')
            ->never();

        // Act
        $token = $this->middleware->exposedGetTokenFromRequest($this->mockRequest);

        // Assert
        $this->assertEquals('input-token', $token);
    }

    #[TestDox('Should use tenant-specific cookie name when tenant context is available')]
    public function testUsesTenantSpecificCookieNameWhenTenantContextIsAvailable(): void
    {
        // Arrange
        $this->mockTenant->shouldReceive('getAttribute')->with('public_id')->andReturn('tenant-123');
        $this->mockTenant->shouldReceive('__get')->with('public_id')->andReturn('tenant-123');
        
        $this->mockTenantContext->shouldReceive('has')
            ->once()
            ->andReturn(true);
            
        $this->mockTenantContext->shouldReceive('get')
            ->once()
            ->andReturn($this->mockTenant);

        // Act
        $cookieName = $this->middleware->exposedGetCookieName();

        // Assert
        $this->assertEquals('XSRF-TOKEN-tenant-123', $cookieName);
    }

    #[TestDox('Should use default cookie name when tenant context is not available')]
    public function testUsesDefaultCookieNameWhenTenantContextIsNotAvailable(): void
    {
        // Arrange
        $this->mockTenantContext->shouldReceive('has')
            ->once()
            ->andReturn(false);

        // Act
        $cookieName = $this->middleware->exposedGetCookieName();

        // Assert
        $this->assertEquals('XSRF-TOKEN', $cookieName);
    }

    #[TestDox('Should use default cookie name when tenant context has no tenant')]
    public function testUsesDefaultCookieNameWhenTenantContextHasNoTenant(): void
    {
        // Arrange
        // Create a new anonymous middleware instance specifically for this test
        $middleware = new class($this->mockTenantContext, $this->mockApp, $this->mockEncrypter) extends TenantAwareCsrfToken {
            public function exposedGetCookieName(): string
            {
                return $this->getCookieName();
            }
        };
        
        $this->mockTenantContext->shouldReceive('has')
            ->once()
            ->andReturn(true);
            
        $this->mockTenantContext->shouldReceive('get')
            ->once()
            ->andReturn(null);

        // Act
        $cookieName = $middleware->exposedGetCookieName();

        // Assert
        $this->assertEquals('XSRF-TOKEN', $cookieName);
    }

    #[TestDox('Should create cookie with tenant-specific name')]
    public function testCreatesCookieWithTenantSpecificName(): void
    {
        // Arrange
        $this->mockTenant->shouldReceive('getAttribute')->with('public_id')->andReturn('tenant-456');
        $this->mockTenant->shouldReceive('__get')->with('public_id')->andReturn('tenant-456');
        
        $this->mockTenantContext->shouldReceive('has')
            ->once()
            ->andReturn(true);
            
        $this->mockTenantContext->shouldReceive('get')
            ->once()
            ->andReturn($this->mockTenant);
            
        $this->mockRequest->shouldReceive('session')
            ->once()
            ->andReturn($this->mockSession);
            
        $this->mockSession->shouldReceive('token')
            ->once()
            ->andReturn('csrf-token-value');
            
        $config = [
            'lifetime' => 120,
            'path' => '/',
            'domain' => null,
            'secure' => true,
            'same_site' => 'lax',
            'partitioned' => false,
        ];

        // Act
        $cookie = $this->middleware->exposedNewCookie($this->mockRequest, $config);

        // Assert
        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertEquals('XSRF-TOKEN-tenant-456', $cookie->getName());
        $this->assertEquals('csrf-token-value', $cookie->getValue());
        $this->assertEquals('/', $cookie->getPath());
        $this->assertEquals(null, $cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertEquals('lax', $cookie->getSameSite());
        $this->assertFalse($cookie->isHttpOnly());
    }

    #[TestDox('Should get token from request input')]
    public function testGetsTokenFromRequestInput(): void
    {
        // Arrange
        $this->mockRequest->shouldReceive('input')
            ->with('_token')
            ->once()
            ->andReturn('input-token');
            
        $this->mockRequest->shouldReceive('header')
            ->with('X-CSRF-TOKEN')
            ->never();

        // Act
        $token = $this->middleware->exposedGetTokenFromRequest($this->mockRequest);

        // Assert
        $this->assertEquals('input-token', $token);
    }

    #[TestDox('Should get token from X-CSRF-TOKEN header')]
    public function testGetsTokenFromXCsrfTokenHeader(): void
    {
        // Arrange
        $this->mockRequest->shouldReceive('input')
            ->with('_token')
            ->once()
            ->andReturn(null);
            
        $this->mockRequest->shouldReceive('header')
            ->with('X-CSRF-TOKEN')
            ->once()
            ->andReturn('header-token');
            
        $this->mockRequest->shouldReceive('header')
            ->with('X-XSRF-TOKEN')
            ->never();

        // Act
        $token = $this->middleware->exposedGetTokenFromRequest($this->mockRequest);

        // Assert
        $this->assertEquals('header-token', $token);
    }

    #[TestDox('Should get token from X-XSRF-TOKEN header when other sources are empty')]
    public function testGetsTokenFromXXsrfTokenHeaderWhenOtherSourcesAreEmpty(): void
    {
        // Arrange
        // Create a new middleware instance specifically for this test
        $middleware = new class($this->mockTenantContext, $this->mockApp, $this->mockEncrypter) extends TenantAwareCsrfToken {
            public function exposedGetTokenFromRequest($request): ?string
            {
                return $this->getTokenFromRequest($request);
            }
        };
        
        $this->mockRequest->shouldReceive('input')
            ->with('_token')
            ->once()
            ->andReturn(null);
            
        $this->mockRequest->shouldReceive('header')
            ->with('X-CSRF-TOKEN')
            ->once()
            ->andReturn(null);
            
        $this->mockRequest->shouldReceive('header')
            ->with('X-XSRF-TOKEN')
            ->once()
            ->andReturn('encrypted-header-token');
            
        $this->mockEncrypter->shouldReceive('decrypt')
            ->with('encrypted-header-token', TenantAwareCsrfToken::serialized())
            ->once()
            ->andReturn('prefix|decrypted-header-token');

        // Act
        $token = $middleware->exposedGetTokenFromRequest($this->mockRequest);

        // Assert
        $this->assertEquals('decrypted-header-token', $token);
    }

    #[TestDox('Should handle decrypt exception from X-XSRF-TOKEN header')]
    public function testHandlesDecryptExceptionFromXXsrfTokenHeader(): void
    {
        // Arrange
        // Create a new middleware instance specifically for this test
        $middleware = new class($this->mockTenantContext, $this->mockApp, $this->mockEncrypter) extends TenantAwareCsrfToken {
            public function exposedGetTokenFromRequest($request): ?string
            {
                return $this->getTokenFromRequest($request);
            }
        };
        
        $this->mockRequest->shouldReceive('input')
            ->with('_token')
            ->once()
            ->andReturn(null);
            
        $this->mockRequest->shouldReceive('header')
            ->with('X-CSRF-TOKEN')
            ->once()
            ->andReturn(null);
            
        $this->mockRequest->shouldReceive('header')
            ->with('X-XSRF-TOKEN')
            ->once()
            ->andReturn('invalid-encrypted-token');
            
        $this->mockEncrypter->shouldReceive('decrypt')
            ->with('invalid-encrypted-token', TenantAwareCsrfToken::serialized())
            ->once()
            ->andThrow(new \Illuminate\Contracts\Encryption\DecryptException());
            
        $this->mockTenantContext->shouldReceive('has')
            ->once()
            ->andReturn(false);
            
        $this->mockRequest->shouldReceive('cookie')
            ->with('XSRF-TOKEN')
            ->once()
            ->andReturn(null);

        // Act
        $token = $middleware->exposedGetTokenFromRequest($this->mockRequest);

        // Assert
        $this->assertEquals('', $token);
    }

    #[TestDox('Should get token from tenant-specific cookie when other sources are empty')]
    public function testGetsTokenFromTenantSpecificCookieWhenOtherSourcesAreEmpty(): void
    {
        // Arrange
        // Create a new middleware instance specifically for this test
        $middleware = new class($this->mockTenantContext, $this->mockApp, $this->mockEncrypter) extends TenantAwareCsrfToken {
            public function exposedGetTokenFromRequest($request): ?string
            {
                return $this->getTokenFromRequest($request);
            }
        };
        
        $this->mockTenant->shouldReceive('getAttribute')->with('public_id')->andReturn('tenant-789');
        $this->mockTenant->shouldReceive('__get')->with('public_id')->andReturn('tenant-789');
        
        $this->mockRequest->shouldReceive('input')
            ->with('_token')
            ->once()
            ->andReturn(null);
            
        $this->mockRequest->shouldReceive('header')
            ->with('X-CSRF-TOKEN')
            ->once()
            ->andReturn(null);
            
        $this->mockRequest->shouldReceive('header')
            ->with('X-XSRF-TOKEN')
            ->once()
            ->andReturn(null);
            
        $this->mockTenantContext->shouldReceive('has')
            ->once()
            ->andReturn(true);
            
        $this->mockTenantContext->shouldReceive('get')
            ->once()
            ->andReturn($this->mockTenant);
            
        $this->mockRequest->shouldReceive('cookie')
            ->with('XSRF-TOKEN-tenant-789')
            ->once()
            ->andReturn('encrypted-cookie-token');
            
        $this->mockEncrypter->shouldReceive('decrypt')
            ->with('encrypted-cookie-token', TenantAwareCsrfToken::serialized())
            ->once()
            ->andReturn('prefix|decrypted-cookie-token');

        // Act
        $token = $middleware->exposedGetTokenFromRequest($this->mockRequest);

        // Assert
        $this->assertEquals('decrypted-cookie-token', $token);
    }

    #[TestDox('Should handle decrypt exception from tenant-specific cookie')]
    public function testHandlesDecryptExceptionFromTenantSpecificCookie(): void
    {
        // Arrange
        // Create a new middleware instance specifically for this test
        $middleware = new class($this->mockTenantContext, $this->mockApp, $this->mockEncrypter) extends TenantAwareCsrfToken {
            public function exposedGetTokenFromRequest($request): ?string
            {
                return $this->getTokenFromRequest($request);
            }
        };
        
        $this->mockTenant->shouldReceive('getAttribute')->with('public_id')->andReturn('tenant-abc');
        $this->mockTenant->shouldReceive('__get')->with('public_id')->andReturn('tenant-abc');
        
        $this->mockRequest->shouldReceive('input')
            ->with('_token')
            ->once()
            ->andReturn(null);
            
        $this->mockRequest->shouldReceive('header')
            ->with('X-CSRF-TOKEN')
            ->once()
            ->andReturn(null);
            
        $this->mockRequest->shouldReceive('header')
            ->with('X-XSRF-TOKEN')
            ->once()
            ->andReturn(null);
            
        $this->mockTenantContext->shouldReceive('has')
            ->once()
            ->andReturn(true);
            
        $this->mockTenantContext->shouldReceive('get')
            ->once()
            ->andReturn($this->mockTenant);
            
        $this->mockRequest->shouldReceive('cookie')
            ->with('XSRF-TOKEN-tenant-abc')
            ->once()
            ->andReturn('invalid-encrypted-cookie');
            
        $this->mockEncrypter->shouldReceive('decrypt')
            ->with('invalid-encrypted-cookie', TenantAwareCsrfToken::serialized())
            ->once()
            ->andThrow(new \Illuminate\Contracts\Encryption\DecryptException());

        // Act
        $token = $middleware->exposedGetTokenFromRequest($this->mockRequest);

        // Assert
        $this->assertEquals('', $token);
    }

    #[TestDox('Should return null when no token is found')]
    public function testReturnsNullWhenNoTokenIsFound(): void
    {
        // Arrange
        // Create a new middleware instance specifically for this test
        $middleware = new class($this->mockTenantContext, $this->mockApp, $this->mockEncrypter) extends TenantAwareCsrfToken {
            public function exposedGetTokenFromRequest($request): ?string
            {
                return $this->getTokenFromRequest($request);
            }
        };
        
        $this->mockRequest->shouldReceive('input')
            ->with('_token')
            ->once()
            ->andReturn(null);
            
        $this->mockRequest->shouldReceive('header')
            ->with('X-CSRF-TOKEN')
            ->once()
            ->andReturn(null);
            
        $this->mockRequest->shouldReceive('header')
            ->with('X-XSRF-TOKEN')
            ->once()
            ->andReturn(null);
            
        $this->mockTenantContext->shouldReceive('has')
            ->once()
            ->andReturn(false);
            
        $this->mockRequest->shouldReceive('cookie')
            ->with('XSRF-TOKEN')
            ->once()
            ->andReturn(null);

        // Act
        $token = $middleware->exposedGetTokenFromRequest($this->mockRequest);

        // Assert
        $this->assertNull($token);
    }
}

#[TestDox('Should get token from X-CSRF-TOKEN header')]
public function testGetsTokenFromXCsrftokenHeader(): void
{
    // Arrange
    // Create a new middleware instance specifically for this test
    $middleware = new class($this->mockTenantContext, $this->mockApp, $this->mockEncrypter) extends TenantAwareCsrfToken {
        public function exposedGetTokenFromRequest($request): ?string
        {
            return $this->getTokenFromRequest($request);
        }
    };
    
    $this->mockRequest->shouldReceive('input')
        ->with('_token')
        ->once()
        ->andReturn(null);
        
    $this->mockRequest->shouldReceive('header')
        ->with('X-CSRF-TOKEN')
        ->once()
        ->andReturn('token-from-x-csrf-token');
        
    $this->mockRequest->shouldReceive('header')
        ->with('X-XSRF-TOKEN')
        ->once()
        ->andReturn(null);
        
    $this->mockTenantContext->shouldReceive('has')
        ->once()
        ->andReturn(false);
        
    $this->mockRequest->shouldReceive('cookie')
        ->with('XSRF-TOKEN')
        ->once()
        ->andReturn(null);

    // Act
    $token = $middleware->exposedGetTokenFromRequest($this->mockRequest);

    // Assert
    $this->assertEquals('token-from-x-csrf-token', $token);
}

#[TestDox('Should get token from X-XSRF-TOKEN header')]
public function testGetsTokenFromXXsrfTokenHeader(): void
{
    // Arrange
    // Create a new middleware instance specifically for this test
    $middleware = new class($this->mockTenantContext, $this->mockApp, $this->mockEncrypter) extends TenantAwareCsrfToken {
        public function exposedGetTokenFromRequest($request): ?string
        {
            return $this->getTokenFromRequest($request);
        }
    };
    
    $this->mockRequest->shouldReceive('input')
        ->with('_token')
        ->once()
        ->andReturn(null);
        
    $this->mockRequest->shouldReceive('header')
        ->with('X-CSRF-TOKEN')
        ->once()
        ->andReturn(null);
        
    $this->mockRequest->shouldReceive('header')
        ->with('X-XSRF-TOKEN')
        ->once()
        ->andReturn('invalid-encrypted-token');
        
    $this->mockEncrypter->shouldReceive('decrypt')
        ->with('invalid-encrypted-token', TenantAwareCsrfToken::serialized())
        ->once()
        ->andThrow(new \Illuminate\Contracts\Encryption\DecryptException());
        
    $this->mockTenantContext->shouldReceive('has')
        ->once()
        ->andReturn(false);
        
    $this->mockRequest->shouldReceive('cookie')
        ->with('XSRF-TOKEN')
        ->once()
        ->andReturn(null);

    // Act
    $token = $middleware->exposedGetTokenFromRequest($this->mockRequest);

    // Assert
    $this->assertEquals('', $token);
}

#[TestDox('Should get token from tenant-specific cookie when other sources are empty')]
public function testGetsTokenFromTenantSpecificCookieWhenOtherSourcesAreEmpty(): void
{
    // Arrange
    // Create a new middleware instance specifically for this test
    $middleware = new class($this->mockTenantContext, $this->mockApp, $this->mockEncrypter) extends TenantAwareCsrfToken {
        public function exposedGetTokenFromRequest($request): ?string
        {
            return $this->getTokenFromRequest($request);
        }
    };
    
    $this->mockTenant->shouldReceive('getAttribute')->with('public_id')->andReturn('tenant-789');
    $this->mockTenant->shouldReceive('__get')->with('public_id')->andReturn('tenant-789');
    
    $this->mockRequest->shouldReceive('input')
        ->with('_token')
        ->once()
        ->andReturn(null);
        
    $this->mockRequest->shouldReceive('header')
        ->with('X-CSRF-TOKEN')
        ->once()
        ->andReturn(null);
        
    $this->mockRequest->shouldReceive('header')
        ->with('X-XSRF-TOKEN')
        ->once()
        ->andReturn(null);
        
    $this->mockTenantContext->shouldReceive('has')
        ->once()
        ->andReturn(true);
        
    $this->mockTenantContext->shouldReceive('get')
        ->once()
        ->andReturn($this->mockTenant);
        
    $this->mockRequest->shouldReceive('cookie')
        ->with('XSRF-TOKEN-tenant-789')
        ->once()
        ->andReturn('encrypted-cookie-token');
        
    $this->mockEncrypter->shouldReceive('decrypt')
        ->with('encrypted-cookie-token', TenantAwareCsrfToken::serialized())
        ->once()
        ->andReturn('prefix|decrypted-cookie-token');

    // Act
    $token = $middleware->exposedGetTokenFromRequest($this->mockRequest);

    // Assert
    $this->assertEquals('decrypted-cookie-token', $token);
}

#[TestDox('Should handle decrypt exception from tenant-specific cookie')]
public function testHandlesDecryptExceptionFromTenantSpecificCookie(): void
{
    // Arrange
    // Create a new middleware instance specifically for this test
    $middleware = new class($this->mockTenantContext, $this->mockApp, $this->mockEncrypter) extends TenantAwareCsrfToken {
        public function exposedGetTokenFromRequest($request): ?string
        {
            return $this->getTokenFromRequest($request);
        }
    };
    
    $this->mockTenant->shouldReceive('getAttribute')->with('public_id')->andReturn('tenant-abc');
    $this->mockTenant->shouldReceive('__get')->with('public_id')->andReturn('tenant-abc');
    
    $this->mockRequest->shouldReceive('input')
        ->with('_token')
        ->once()
        ->andReturn(null);
        
    $this->mockRequest->shouldReceive('header')
        ->with('X-CSRF-TOKEN')
        ->once()
        ->andReturn(null);
        
    $this->mockRequest->shouldReceive('header')
        ->with('X-XSRF-TOKEN')
        ->once()
        ->andReturn(null);
        
    $this->mockTenantContext->shouldReceive('has')
        ->once()
        ->andReturn(true);
        
    $this->mockTenantContext->shouldReceive('get')
        ->once()
        ->andReturn($this->mockTenant);
        
    $this->mockRequest->shouldReceive('cookie')
        ->with('XSRF-TOKEN-tenant-abc')
        ->once()
        ->andReturn('invalid-encrypted-cookie');
        
    $this->mockEncrypter->shouldReceive('decrypt')
        ->with('invalid-encrypted-cookie', TenantAwareCsrfToken::serialized())
        ->once()
        ->andThrow(new \Illuminate\Contracts\Encryption\DecryptException());

    // Act
    $token = $middleware->exposedGetTokenFromRequest($this->mockRequest);

    // Assert
    $this->assertEquals('', $token);
}

#[TestDox('Should return null when no token is found')]
public function testReturnsNullWhenNoTokenIsFound(): void
{
    // Arrange
    // Create a new middleware instance specifically for this test
    $middleware = new class($this->mockTenantContext, $this->mockApp, $this->mockEncrypter) extends TenantAwareCsrfToken {
        public function exposedGetTokenFromRequest($request): ?string
        {
            return $this->getTokenFromRequest($request);
        }
    };
    
    $this->mockRequest->shouldReceive('input')
        ->with('_token')
        ->once()
        ->andReturn(null);
        
    $this->mockRequest->shouldReceive('header')
        ->with('X-CSRF-TOKEN')
        ->once()
        ->andReturn(null);
        
    $this->mockRequest->shouldReceive('header')
        ->with('X-XSRF-TOKEN')
        ->once()
        ->andReturn(null);
        
    $this->mockTenantContext->shouldReceive('has')
        ->once()
        ->andReturn(false);
        
    $this->mockRequest->shouldReceive('cookie')
        ->with('XSRF-TOKEN')
        ->once()
        ->andReturn(null);

    // Act
    $token = $middleware->exposedGetTokenFromRequest($this->mockRequest);

    // Assert
    $this->assertNull($token);
}

#[TestDox('Should test serialized method returns correct value')]
public function testSerializedMethodReturnsCorrectValue(): void
{
    // Act
    $result = TenantAwareCsrfToken::serialized();

    // Assert
    $this->assertTrue($result);
    
    // Clean up Mockery after each test
    Mockery::close();
}
