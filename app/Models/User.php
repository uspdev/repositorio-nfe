<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use \Spatie\Permission\Traits\HasRoles;
    use \Uspdev\SenhaunicaSocialite\Traits\HasSenhaunica;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Obtém a sigla da unidade principal do usuário via Replicado (máximo 10 caracteres).
     */
    public function obterSiglaUnidade(): ?string
    {
        if (empty($this->codpes) || !class_exists(\Uspdev\Replicado\Pessoa::class)) {
            return null;
        }

        try {
            $vinculos = \Uspdev\Replicado\Pessoa::listarVinculosAtivos((int) $this->codpes, false);
            foreach ($vinculos as $vinculo) {
                $sigla = trim((string) ($vinculo['sglund'] ?? $vinculo['sglclg'] ?? ''));
                if ($sigla !== '') {
                    return substr($sigla, 0, 10);
                }

                if (!empty($vinculo['codundclg']) && class_exists(\Uspdev\Replicado\Estrutura::class)) {
                    $und = \Uspdev\Replicado\Estrutura::obterUnidade((int) $vinculo['codundclg']);
                    $sigla = trim((string) ($und['sglund'] ?? ''));
                    if ($sigla !== '') {
                        return substr($sigla, 0, 10);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Caso o Replicado esteja indisponível, retorna null sem interromper o fluxo
        }

        return null;
    }
}
