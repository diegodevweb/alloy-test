<?php

namespace App\Jobs;

use App\Models\Task;
use App\Services\TaskCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteCompletedTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $taskId;
    protected $cacheService;

    /**
     * Create a new job instance.
     */
    public function __construct(int $taskId)
    {
        $this->taskId = $taskId;
    }

    /**
     * Execute the job.
     */
    public function handle(TaskCacheService $cacheService): void
    {

        try {
            $task = Task::withTrashed()->find($this->taskId);

            if (!$task) {
                Log::warning("Tarefa com ID {$this->taskId} não encontrada para exclusão definitiva.");
                return;
            }

            if (!$task->finalizado) {
                Log::info("Tarefa {$this->taskId} não está mais finalizada. Cancelando exclusão automática.");
                return;
            }

            Log::info("Executando exclusão automática da tarefa finalizada: {$task->nome} (ID: {$this->taskId})");

            $task->forceDelete();

            $cacheService->invalidateTag('tasks');
            $cacheService->invalidateTag("task.{$this->taskId}");

            Log::info("Tarefa {$this->taskId} excluída definitivamente com sucesso.");

        } catch (\Exception $e) {
            Log::error("Erro ao excluir tarefa {$this->taskId}: " . $e->getMessage());

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Falha na exclusão automática da tarefa {$this->taskId}: " . $exception->getMessage());
    }
}
