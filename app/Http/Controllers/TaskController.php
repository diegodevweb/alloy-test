<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Jobs\DeleteCompletedTask;
use App\Models\Task;
use App\Services\TaskCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    protected $cacheService;

    public function __construct(TaskCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'tasks.index.' . md5($request->getQueryString() ?? '');

        $tasks = $this->cacheService->remember($cacheKey, function () use ($request) {
            $query = Task::query();

            if ($request->has('status')) {
                switch ($request->status) {
                    case 'finalizada':
                        $query->finalizadas();
                        break;
                    case 'pendente':
                        $query->pendentes();
                        break;
                    case 'vencida':
                        $query->vencidas();
                        break;
                }
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                      ->orWhere('descricao', 'like', "%{$search}%");
                });
            }

            return $query->orderBy('created_at', 'desc')->get();
        }, ['tasks']);

        return response()->json([
            'success' => true,
            'data' => $tasks,
            'meta' => [
                'total' => $tasks->count(),
                'finalizadas' => $tasks->where('finalizado', true)->count(),
                'pendentes' => $tasks->where('finalizado', false)->count(),
            ]
        ]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = Task::create($request->validated());

        // Invalidar cache
        $this->cacheService->invalidateTag('tasks');

        return response()->json([
            'success' => true,
            'message' => 'Tarefa criada com sucesso!',
            'data' => $task->load([])
        ], 201);
    }

    public function show(Task $task): JsonResponse
    {
        $cacheKey = "tasks.show.{$task->id}";

        $taskData = $this->cacheService->remember($cacheKey, function () use ($task) {
            return $task;
        }, ['tasks', "task.{$task->id}"]);

        return response()->json([
            'success' => true,
            'data' => $taskData
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $task->update($request->validated());

        $this->cacheService->invalidateTag('tasks');
        $this->cacheService->invalidateTag("task.{$task->id}");

        return response()->json([
            'success' => true,
            'message' => 'Tarefa atualizada com sucesso!',
            'data' => $task->fresh()
        ]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        $this->cacheService->invalidateTag('tasks');
        $this->cacheService->invalidateTag("task.{$task->id}");

        return response()->json([
            'success' => true,
            'message' => 'Tarefa excluída com sucesso!'
        ]);
    }

    public function toggle(Task $task): JsonResponse
    {
        $wasCompleted = $task->finalizado;
        $task->finalizado = !$task->finalizado;
        $task->save();

        if (!$wasCompleted && $task->finalizado) {
            DeleteCompletedTask::dispatch($task->id)->delay(now()->addMinutes(10));
        }

        $this->cacheService->invalidateTag('tasks');
        $this->cacheService->invalidateTag("task.{$task->id}");

        $message = $task->finalizado
            ? 'Tarefa marcada como finalizada! Será excluída automaticamente em 10 minutos.'
            : 'Tarefa marcada como pendente!';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $task->fresh()
        ]);
    }
}
