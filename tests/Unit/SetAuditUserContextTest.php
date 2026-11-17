<?php

namespace Tests\Unit;

use App\Http\Middleware\SetAuditUserContext;
use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SetAuditUserContextTest extends TestCase
{
    public function test_mysql_audit_actor_is_set_for_the_request_and_cleared_afterward(): void
    {
        $user = new User;
        $user->setAttribute('id_user', 42);
        $request = Request::create('/');
        $request->setUserResolver(fn (): User => $user);

        $connection = \Mockery::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
        $connection->shouldReceive('statement')
            ->once()
            ->ordered()
            ->with('SET @app_user_id = 42')
            ->andReturn(true);
        $connection->shouldReceive('statement')
            ->once()
            ->ordered()
            ->with('SET @app_user_id = NULL')
            ->andReturn(true);
        DB::shouldReceive('connection')->once()->andReturn($connection);

        $response = (new SetAuditUserContext)->handle(
            $request,
            fn (): Response => new Response('ok')
        );

        $this->assertSame('ok', $response->getContent());
    }
}
