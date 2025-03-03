<?php

declare(strict_types=1);

namespace League\Tactician\Doctrine\Tests\DBAL;

use Doctrine\DBAL\Connection;
use League\Tactician\Doctrine\DBAL\PingConnectionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

final class PingConnectionMiddlewareTest extends TestCase
{
    /** @var Connection&MockObject */
    private $connection;

    private PingConnectionMiddleware $middleware;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);

        $this->middleware = new PingConnectionMiddleware($this->connection);
    }

    /**
     * @test
     */
    public function itShouldReconnectIfConnectionExpires(): void
    {
        $this->connection->expects(self::once())->method('ping')->willReturn(false);
        $this->connection->expects(self::once())->method('close');
        $this->connection->expects(self::once())->method('connect');

        $executed = 0;
        $next     = static function () use (&$executed): void {
            $executed++;
        };

        $this->middleware->execute(new stdClass(), $next);

        self::assertEquals(1, $executed);
    }

    /**
     * @test
     */
    public function itShouldNotReconnectIfConnectionIsStillAlive(): void
    {
        $this->connection->expects(self::once())->method('ping')->willReturn(true);
        $this->connection->expects(self::never())->method('close');
        $this->connection->expects(self::never())->method('connect');

        $executed = 0;
        $next     = static function () use (&$executed): void {
            $executed++;
        };

        $this->middleware->execute(new stdClass(), $next);

        self::assertEquals(1, $executed);
    }
}
