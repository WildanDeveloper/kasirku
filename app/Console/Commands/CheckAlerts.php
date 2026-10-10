<?php

namespace App\Console\Commands;

use App\Services\AlertChecker;
use Illuminate\Console\Command;

class CheckAlerts extends Command
{
    protected $signature = 'kasirku:alerts';

    protected $description = 'Periksa stok menipis, shift menggantung, dan utang jatuh tempo lalu buat notifikasi';

    public function handle(AlertChecker $checker): int
    {
        $results = $checker->run();
        $total = array_sum($results);

        foreach ($results as $key => $count) {
            $this->line("  {$key}: {$count}");
        }

        $this->info($total === 0 ? 'Tidak ada alert baru.' : $total.' alert diproses.');

        return self::SUCCESS;
    }
}