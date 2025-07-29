<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TireJobOrderTaskDetail;
use App\Models\Tool;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:tasks {--shift= : The shift to schedule tasks for (pagi or malam)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedules pending tasks based on tool availability and selected shift.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shift = $this->option('shift');
        if (!in_array($shift, ['pagi', 'malam'])) {
            $this->error('Invalid shift specified. Use --shift=pagi or --shift=malam.');
            return Command::FAILURE;
        }

        $now = Carbon::now();
        $schedulingStartTime = $this->getSchedulingStartTime($now, $shift);

        // 1. Reset tasks yang relevan
        TireJobOrderTaskDetail::where('status', 'scheduled')
            ->where('total_duration_calculated', '>', 0)
            ->update([
                'start_time' => null,
                'end_time' => null,
                'status' => 'pending',
            ]);

        // 2. Inisialisasi ketersediaan alat
        $toolAvailability = $this->initializeToolAvailability($schedulingStartTime);

        // 3. Ambil semua task yang pending, prioritaskan berdasarkan due date job order
        $tasksByJobOrder = TireJobOrderTaskDetail::where('status', 'pending')
            ->where('total_duration_calculated', '>', 0)
            ->with(['task.tools', 'tireJobOrder'])
            // Praktik yang baik: join untuk sorting berdasarkan due_date job order
            ->select('tire_job_order_task_details.*')
            ->join('tire_job_orders', 'tire_job_order_task_details.tire_job_order_id', '=', 'tire_job_orders.id')
            // ->orderBy('tire_job_orders.due_date', 'asc')
            ->orderBy('tire_job_order_task_details.tire_job_order_id', 'asc')
            ->orderBy('tire_job_order_task_details.order', 'asc')
            ->get()
            ->groupBy('tire_job_order_id');

        // 4. Inisialisasi "ready queue"
        $readyQueue = collect();
        foreach ($tasksByJobOrder as $tasks) {
            if ($tasks->isNotEmpty()) {
                $readyQueue->push($tasks->first());
            }
        }

        // 5. Inisialisasi variabel tracking
        $jobOrderLastEndTime = [];
        $scheduledCount = 0;
        $totalTaskCount = $tasksByJobOrder->flatten()->count();

        if ($totalTaskCount === 0) {
            $this->info('No pending tasks to schedule.');
            return Command::SUCCESS;
        }

        $this->info("Starting task scheduling for {$totalTaskCount} tasks...");
        $progressBar = $this->output->createProgressBar($totalTaskCount);
        $progressBar->start();

        DB::transaction(function () use (
            &$readyQueue,
            &$tasksByJobOrder,
            &$toolAvailability,
            &$jobOrderLastEndTime,
            &$scheduledCount,
            $totalTaskCount,
            $schedulingStartTime,
            $shift,
            $progressBar
        ) {
            while ($scheduledCount < $totalTaskCount && $readyQueue->isNotEmpty()) {
                $bestCandidate = null;
                $bestCandidateResult = null;
                $bestCandidateKey = null;

                // Cari task terbaik dari ready queue
                foreach ($readyQueue as $key => $taskDetail) {
                    $earliestConsideredStart = $schedulingStartTime->copy();
                    if (isset($jobOrderLastEndTime[$taskDetail->tire_job_order_id])) {
                        $earliestConsideredStart = $earliestConsideredStart->max(
                            $jobOrderLastEndTime[$taskDetail->tire_job_order_id]
                        );
                    }

                    // Cek ketersediaan tanpa mengubah array utama
                    $tempToolAvailability = unserialize(serialize($toolAvailability)); // Deep copy yang efektif
                    $candidateResult = $this->findEarliestAvailableTime(
                        $taskDetail->task->tools,
                        $tempToolAvailability,
                        $taskDetail->total_duration_calculated,
                        $earliestConsideredStart,
                        $shift
                    );

                    if ($candidateResult !== null) {
                        $candidateStartTime = $candidateResult['start_time'];
                        // Aturan Prioritas: 1. Waktu mulai tercepat, 2. ID Job Order terendah
                        if (
                            $bestCandidate === null ||
                            $candidateStartTime->lessThan($bestCandidateResult['start_time']) ||
                            ($candidateStartTime->equalTo($bestCandidateResult['start_time']) &&
                                $taskDetail->tire_job_order_id < $bestCandidate->tire_job_order_id)
                        ) {
                            $bestCandidate = $taskDetail;
                            $bestCandidateResult = $candidateResult;
                            $bestCandidateKey = $key;
                        }
                    }
                }

                if ($bestCandidate === null) {
                    // Tidak ada task yang bisa dijadwalkan, kemungkinan deadlock sumber daya
                    break;
                }

                // Jadwalkan task terbaik yang ditemukan
                $taskToSchedule = $bestCandidate;
                $startTime = $bestCandidateResult['start_time'];
                $chosenInstances = $bestCandidateResult['chosen_tools'];
                $endTime = $startTime->copy()->addMinutes($taskToSchedule->total_duration_calculated);

                // PERBAIKAN: Update ketersediaan alat secara eksplisit
                foreach ($chosenInstances as $toolName => $instanceKey) {
                    $toolAvailability[$toolName][$instanceKey] = $endTime->copy();
                }

                // Update database
                $taskToSchedule->update([
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'status' => 'scheduled',
                ]);

                // Update variabel tracking
                $jobOrderLastEndTime[$taskToSchedule->tire_job_order_id] = $endTime;
                $scheduledCount++;
                $progressBar->advance();

                // Hapus dari ready queue dan tambahkan task berikutnya dari job yang sama
                $readyQueue->forget($bestCandidateKey);
                $jobTasks = $tasksByJobOrder[$taskToSchedule->tire_job_order_id];
                $currentIndex = $jobTasks->search(fn($item) => $item->id === $taskToSchedule->id);

                if ($currentIndex !== false && isset($jobTasks[$currentIndex + 1])) {
                    $readyQueue->push($jobTasks[$currentIndex + 1]);
                }
            }
        });

        $progressBar->finish();
        $this->info('');

        $this->info("Task scheduling complete. Scheduled {$scheduledCount} of {$totalTaskCount} tasks.");
        if ($scheduledCount < $totalTaskCount) {
            $remaining = $totalTaskCount - $scheduledCount;
            $this->warn("{$remaining} tasks remain pending due to unavailability or scheduling constraints.");
        }
    }

    /**
     * KOREKSI: Menyederhanakan logika penentuan waktu mulai penjadwalan.
     */
    protected function getSchedulingStartTime(Carbon $now, string $shift): Carbon
    {
        if ($shift === 'pagi') {
            return $now->copy()->hour(8)->minute(0)->second(0);
        }

        // shift malam
        if ($now->hour < 5) {
            return $now->copy()->subDay()->hour(20)->minute(0)->second(0);
        }
        return $now->copy()->hour(20)->minute(0)->second(0);
    }

    protected function initializeToolAvailability(Carbon $initialAvailabilityTime): array
    {
        $tools = Tool::all();
        $availability = [];
        foreach ($tools as $tool) {
            $availability[$tool->name] = [];
            for ($i = 0; $i < $tool->quantity; $i++) {
                $availability[$tool->name][] = $initialAvailabilityTime->copy();
            }
        }
        return $availability;
    }

    /**
     * KOREKSI: Fungsi ini sekarang mengembalikan array [start_time, chosen_tools] atau null.
     * Logika penanganan waktu istirahat dan batas shift juga disederhanakan.
     *
     * @return array|null ['start_time' => Carbon, 'chosen_tools' => array] or null
     */
    protected function findEarliestAvailableTime(
        $requiredTools,
        &$toolAvailability,
        $taskDuration,
        $earliestConsideredStart,
        string $shift
    ): ?array {
        $currentSearchTime = $earliestConsideredStart->copy();
        $searchLimit = $currentSearchTime->copy()->addDays(14); // Batas pencarian 2 minggu

        while ($currentSearchTime->lessThan($searchLimit)) {
            // 1. Sesuaikan waktu ke slot kerja valid berikutnya (melompati istirahat & di luar shift)
            $currentSearchTime = $this->adjustToNextValidWorkTime($currentSearchTime, $taskDuration, $shift);

            // 2. Cek apakah semua alat yang dibutuhkan tersedia pada $currentSearchTime
            $canSchedule = true;
            $chosenToolInstances = [];
            $latestToolAvailableTime = $currentSearchTime->copy();

            foreach ($requiredTools as $tool) {
                if (empty($toolAvailability[$tool->name])) continue;

                $foundInstance = false;
                $bestInstanceKey = null;
                $earliestInstanceTime = null;

                // Cari instance alat yang tersedia paling awal
                foreach ($toolAvailability[$tool->name] as $instanceKey => $availableTime) {
                    if ($earliestInstanceTime === null || $availableTime->lessThan($earliestInstanceTime)) {
                        $earliestInstanceTime = $availableTime;
                    }
                    if ($availableTime->lessThanOrEqualTo($currentSearchTime)) {
                        $foundInstance = true;
                        $bestInstanceKey = $instanceKey;
                        break; // Ditemukan, gunakan instance ini
                    }
                }

                if ($foundInstance) {
                    $chosenToolInstances[$tool->name] = $bestInstanceKey;
                } else {
                    $canSchedule = false;
                    // Simpan waktu tersedia paling akhir dari semua alat yang dibutuhkan
                    // untuk melompat ke waktu tersebut di iterasi berikutnya.
                    $latestToolAvailableTime = $latestToolAvailableTime->max($earliestInstanceTime);
                }
            }

            // 3. Jika semua alat tersedia, kembalikan hasilnya
            if ($canSchedule) {
                return [
                    'start_time' => $currentSearchTime,
                    'chosen_tools' => $chosenToolInstances,
                ];
            }

            // 4. Jika tidak, lompat ke waktu relevan berikutnya
            $currentSearchTime = $latestToolAvailableTime;
        }

        return null; // Tidak ditemukan slot waktu
    }

    /**
     * Fungsi helper untuk menyesuaikan waktu ke slot kerja valid berikutnya.
     * Menangani waktu istirahat dan batas shift.
     */
    private function adjustToNextValidWorkTime(Carbon $time, int $duration, string $shift, bool $isFirstTask = false): Carbon
    {
        $adjustedTime = $time->copy();

        if ($isFirstTask) {
            if ($shift === 'pagi') {
                return $adjustedTime->copy()->hour(8)->minute(0)->second(0);
            } elseif ($shift === 'malam') {
                if ($adjustedTime->hour < 5) {
                    return $adjustedTime->copy()->subDay()->hour(20)->minute(0)->second(0);
                } else {
                    return $adjustedTime->copy()->hour(20)->minute(0)->second(0);
                }
            }
        }

        while (true) {
            $dayOfWeek = $adjustedTime->dayOfWeek;
            if ($dayOfWeek == Carbon::SUNDAY) {
                $adjustedTime->addDay()->hour(8)->minute(0);
                continue;
            }

            $breaks = [
                // ['08:00', '08:30'],
                // ['10:00', '10:15'],
                ['12:00', '13:00'],
                // ['15:00', '15:15'],
                ['16:30', '20:30'],
                // ['20:00', '20:30'],
                // ['22:00', '22:15'],
                ['00:00', '01:00'],
                // ['03:00', '03:15'],
                ['04:30', '08:30'],
            ];

            $conflict = false;
            $taskEnd = $adjustedTime->copy()->addMinutes($duration);
            foreach ($breaks as [$start, $end]) {
                $breakStart = Carbon::parse($adjustedTime->format('Y-m-d') . ' ' . $start);
                $breakEnd = Carbon::parse($adjustedTime->format('Y-m-d') . ' ' . $end);
                if (
                    $adjustedTime->between($breakStart, $breakEnd, false) ||
                    $taskEnd->between($breakStart, $breakEnd, false)
                ) {
                    $adjustedTime = $breakEnd;
                    $conflict = true;
                    break;
                }
            }

            if (!$conflict) break;
        }

        return $adjustedTime;
    }
}
