<?php

namespace Tests\Feature;

use App\Models\Email;
use App\Models\Nfe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailsPruneCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_counts_old_emails_without_deleting(): void
    {
        $this->criarEmail(200, true);
        $this->criarEmail(190, false);
        $this->criarEmail(10, true);

        $this->artisan('emails:prune', ['--dry-run' => true])
            ->expectsOutputToContain('[dry-run] 2 e-mail(s) com mais de 180 dia(s) seriam removidos.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('emails', 3);
    }

    public function test_prune_removes_all_old_emails_regardless_of_status_and_keeps_nfes(): void
    {
        $antigoParsed = $this->criarEmail(200, true);
        $antigoUnparsed = $this->criarEmail(185, false);
        $recente = $this->criarEmail(30, true);

        $nfe = new Nfe();
        $nfe->chave = '35260500000000000100550010000000011000000010';
        $nfe->xml = '<xml/>';
        $nfe->prot = [];
        $nfe->emit = [];
        $nfe->dest = [];
        $nfe->ide = [];
        $nfe->consulta = [];
        $nfe->infadic = '';
        $nfe->ano = 2025;
        $nfe->email_id = $antigoParsed->id;
        $nfe->save();

        $this->artisan('emails:prune')
            ->expectsOutputToContain('2 e-mail(s) com mais de 180 dia(s) removidos.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('emails', ['id' => $antigoParsed->id]);
        $this->assertDatabaseMissing('emails', ['id' => $antigoUnparsed->id]);
        $this->assertDatabaseHas('emails', ['id' => $recente->id]);
        $this->assertDatabaseHas('nfes', ['id' => $nfe->id, 'email_id' => null]);
    }

    public function test_prune_respects_custom_days_option_and_rejects_invalid_days(): void
    {
        $this->criarEmail(45, true);
        $this->criarEmail(15, true);

        $this->artisan('emails:prune', ['--days' => 0])
            ->assertExitCode(1);

        $this->assertDatabaseCount('emails', 2);

        $this->artisan('emails:prune', ['--days' => 30])
            ->expectsOutputToContain('1 e-mail(s) com mais de 30 dia(s) removidos.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('emails', 1);
    }

    private function criarEmail(int $daysAgo, bool $parsed): Email
    {
        $email = new Email();
        $email->message_id = '<msg-' . uniqid() . '@exemplo.com>';
        $email->date = now()->subDays($daysAgo);
        $email->subject = 'Assunto teste';
        $email->to = 'nfe@eesc.usp.br';
        $email->from = 'fornecedor@exemplo.com';
        $email->raw_header = '';
        $email->raw_body = '';
        $email->status = ['parsed' => $parsed];
        $email->unidade = 'EESC';
        $email->created_at = now()->subDays($daysAgo);
        $email->updated_at = now()->subDays($daysAgo);
        $email->save();

        return $email;
    }
}
