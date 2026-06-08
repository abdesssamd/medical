<?php

namespace Tests\Feature\Modules\Billing;

use App\Models\User;
use Modules\Billing\Models\CashSession;
use Modules\Billing\Models\CashTransaction;
use Modules\Billing\Services\CashSessionService;
use Tests\TestCase;

class CashSessionTest extends TestCase
{
    private CashSessionService $cashService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cashService = app(CashSessionService::class);
        $this->user = User::factory()->create(['role' => 'secretary']);
    }

    public function test_can_open_cash_session()
    {
        $session = $this->cashService->openSession($this->user, 100.00);

        $this->assertDatabaseHas('cash_sessions', [
            'user_id' => $this->user->id,
            'initial_balance' => 100.00,
            'status' => 'open',
        ]);
    }

    public function test_cannot_open_session_when_already_open()
    {
        $this->cashService->openSession($this->user, 100.00);

        $this->expectException(\Exception::class);
        $this->cashService->openSession($this->user, 50.00);
    }

    public function test_can_record_transaction()
    {
        $session = $this->cashService->openSession($this->user, 100.00);

        $transaction = $this->cashService->recordTransaction(
            $session,
            50.00,
            'cash',
            $this->user
        );

        $this->assertDatabaseHas('cash_transactions', [
            'cash_session_id' => $session->id,
            'amount' => 50.00,
            'method' => 'cash',
        ]);

        $this->assertEquals(150.00, $session->fresh()->theoretical_total);
    }

    public function test_can_close_session()
    {
        $session = $this->cashService->openSession($this->user, 100.00);
        $this->cashService->recordTransaction($session, 50.00, 'cash', $this->user);

        $this->cashService->closeSession($session, 150.00);

        $session->refresh();
        $this->assertEquals('closed', $session->status);
        $this->assertEquals(0, $session->difference);
    }

    public function test_detects_cash_variance()
    {
        $session = $this->cashService->openSession($this->user, 100.00);
        $this->cashService->recordTransaction($session, 50.00, 'cash', $this->user);

        $this->cashService->closeSession($session, 145.00); // 5€ manquants

        $session->refresh();
        $this->assertEquals(-5.00, $session->difference);
    }

    public function test_exports_cash_journal()
    {
        $session = $this->cashService->openSession($this->user, 100.00);
        $this->cashService->recordTransaction($session, 50.00, 'cash', $this->user);

        $journal = $this->cashService->exportCashJournal($session, 'csv');

        $this->assertStringContainsString('JOURNAL DE CAISSE', $journal);
        $this->assertStringContainsString('100,00', $journal);
        $this->assertStringContainsString('50,00', $journal);
    }

    public function test_gets_cash_dashboard()
    {
        $this->cashService->openSession($this->user, 100.00);

        $dashboard = $this->cashService->getCashDashboard($this->user);

        $this->assertNotNull($dashboard['open_session']);
        $this->assertArrayHasKey('stats', $dashboard);
    }
}
