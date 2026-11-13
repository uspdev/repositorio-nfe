<?php

namespace App\Services;

use App\Models\Nfe;
use Illuminate\Support\Collection;

class NfeEstatisticasService
{
    /**
     * Gera as estatísticas de emitentes e destinatários para o período informado.
     *
     * @param array<int, int|string>|string $anos
     * @return array{totaisPorEmit: Collection, totaisPorDest: Collection, anos: array<int, int|string>|string}
     */
    public function gerar(array|string $anos = []): array
    {
        $anosNormalizados = $this->normalizarAnos($anos);
        $vendas = $this->buscarNfes($anosNormalizados)
            ->filter(fn (Nfe $nfe) => $nfe->isVenda());

        return [
            'totaisPorEmit' => $this->agruparPorParticipante($vendas, 'emit'),
            'totaisPorDest' => $this->agruparPorParticipante($vendas, 'dest'),
            'anos' => $anosNormalizados,
        ];
    }

    /**
     * Normaliza o filtro de anos, aplicando o intervalo padrão dos últimos 5 anos.
     *
     * @param array<int, int|string>|string $anos
     * @return array<int, int|string>|string
     */
    private function normalizarAnos(array|string $anos): array|string
    {
        if ($anos === '*') {
            return '*';
        }

        if (!isset($anos[0])) {
            $anos[0] = now()->year - 5;
        }
        if (!isset($anos[1]) || $anos[1] < $anos[0]) {
            $anos[1] = now()->year;
        }

        return $anos;
    }

    /**
     * Busca as NF-es do período informado ordenadas pela data de emissão.
     *
     * @param array<int, int|string>|string $anos
     */
    private function buscarNfes(array|string $anos): Collection
    {
        return Nfe::when(is_array($anos), function ($query) use ($anos) {
            return $query->whereBetween('ano', [$anos[0], $anos[1]]);
        })
            ->orderBy('ide->dataemi', 'desc')
            ->get();
    }

    /**
     * Agrupa as NF-es de venda por participante ('emit' ou 'dest') e soma os totais.
     */
    private function agruparPorParticipante(Collection $vendas, string $tipo): Collection
    {
        return $vendas
            ->groupBy("{$tipo}.cnpj")
            ->map(function (Collection $grupo, string $cnpj) use ($tipo) {
                $nome = $grupo->pluck("{$tipo}.nome")->first() ?? '(sem nome)';

                return [
                    'cnpj' => $cnpj,
                    'nome' => $nome,
                    'total' => $grupo->sum(fn (Nfe $nfe) => (float) ($nfe->ide['total'] ?? 0)),
                    'quantidade' => $grupo->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }
}
