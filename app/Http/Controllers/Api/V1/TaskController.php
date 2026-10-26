<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\AssignAgentAction;
use App\Actions\CreateTaskAction;
use App\Actions\UpdateTaskStatusAction;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignAgentRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function __construct(
        protected CreateTaskAction      $createTaskAction,
        protected UpdateTaskStatusAction $updateTaskStatusAction,
        protected AssignAgentAction     $assignAgentAction,
    ) {}

    // ─── 1. List Tasks (role-based filter) ───────────────────────────────────

    /**
     * GET /api/v1/tasks
     * Returns tasks based on the authenticated user's role.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Task::class);

        $user  = $request->user();
        $query = Task::with(['service', 'customer', 'agent']);

        if ($user->isCustomer()) {
            // Customer sees only their own tasks
            $query->where('user_id', $user->id);
        } elseif ($user->isAgent() || $user->isProfessional()) {
            // Agent sees only assigned tasks
            $query->where('agent_id', $user->id);
        }
        // Staff (Admin, Operations, SuperAdmin) sees all tasks

        // Optional filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $tasks = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => TaskResource::collection($tasks->items()),
            'meta'    => [
                'current_page' => $tasks->currentPage(),
                'last_page'    => $tasks->lastPage(),
                'per_page'     => $tasks->perPage(),
                'total'        => $tasks->total(),
            ],
        ]);
    }

    // ─── 2. Show Single Task ─────────────────────────────────────────────────

    /**
     * GET /api/v1/tasks/{task}
     */
    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $task->load(['service', 'customer', 'agent', 'statusHistories.changedBy', 'documents']);

        return response()->json([
            'success' => true,
            'data'    => new TaskResource($task),
        ]);
    }

    // ─── 3. Create Task ──────────────────────────────────────────────────────

    /**
     * POST /api/v1/tasks
     * Customer creates a new task order.
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $this->authorize('create', Task::class);

        // Ensure the selected service is active
        $service = \App\Models\Service::findOrFail($request->service_id);
        if (!$service->is_active) {
            throw ValidationException::withMessages([
                'service_id' => ['নির্বাচিত সার্ভিসটি বর্তমানে সক্রিয় নেই।'],
            ]);
        }

        $task = $this->createTaskAction->execute(
            customer: $request->user(),
            data:     $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'টাস্ক সফলভাবে তৈরি হয়েছে।',
            'data'    => new TaskResource($task),
        ], 201);
    }

    // ─── 4. Update Task Status ────────────────────────────────────────────────

    /**
     * POST /api/v1/tasks/{task}/status
     * Agent or Staff updates task status.
     */
    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $task = $this->updateTaskStatusAction->execute(
            task:      $task,
            newStatus: $request->validated('status'),
            actor:     $request->user(),
            notes:     $request->validated('notes'),
        );

        return response()->json([
            'success' => true,
            'message' => 'টাস্কের স্ট্যাটাস আপডেট হয়েছে।',
            'data'    => new TaskResource($task),
        ]);
    }

    // ─── 5. Assign Agent ─────────────────────────────────────────────────────

    /**
     * POST /api/v1/tasks/{task}/assign
     * Staff assigns an agent to a task.
     */
    public function assignAgent(AssignAgentRequest $request, Task $task): JsonResponse
    {
        $this->authorize('assignAgent', $task);

        $agent = User::findOrFail($request->validated('agent_id'));

        $task = $this->assignAgentAction->execute(
            task:  $task,
            agent: $agent,
            actor: $request->user(),
        );

        return response()->json([
            'success' => true,
            'message' => "'{$agent->name}' কে এই টাস্কে assign করা হয়েছে।",
            'data'    => new TaskResource($task),
        ]);
    }

    // ─── 6. Cancel Task ──────────────────────────────────────────────────────

    /**
     * POST /api/v1/tasks/{task}/cancel
     * Customer cancels their own task; Staff can cancel any task.
     */
    public function cancel(Request $request, Task $task): JsonResponse
    {
        $this->authorize('cancel', $task);

        $user = $request->user();

        // Customers can only cancel if task is in a cancellable status
        if ($user->isCustomer() && !$task->isCancellableByCustomer()) {
            throw ValidationException::withMessages([
                'status' => ["'{$task->status}' অবস্থায় থাকা টাস্ক বাতিল করা যাবে না।"],
            ]);
        }

        $task = $this->updateTaskStatusAction->execute(
            task:      $task,
            newStatus: TaskStatus::CANCELLED->value,
            actor:     $user,
            notes:     $request->input('notes', 'টাস্ক বাতিল করা হয়েছে।'),
        );

        return response()->json([
            'success' => true,
            'message' => 'টাস্কটি বাতিল করা হয়েছে।',
            'data'    => new TaskResource($task),
        ]);
    }

    // ─── 7. Upload Document for Task ─────────────────────────────────────────

    /**
     * POST /api/v1/tasks/{task}/documents
     * Upload a document file for a specific task.
     */
    public function uploadDocument(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $request->validate([
            'file'          => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
            'document_type' => ['required', 'string', 'max:255'],
        ]);

        $file     = $request->file('file');
        $path     = $file->store("tasks/{$task->id}/documents", 'public');
        $fileName = $file->getClientOriginalName();
        $mimeType = $file->getClientMimeType();

        $document = $task->documents()->create([
            'user_id'       => $request->user()->id,
            'task_id'       => $task->id,
            'document_type' => $request->document_type,
            'file_name'     => $fileName,
            'storage_path'  => $path,
            'mime_type'     => $mimeType,
            'is_verified'   => false,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'ডকুমেন্ট আপলোড সফল হয়েছে।',
            'document' => [
                'id'            => $document->id,
                'document_type' => $document->document_type,
                'file_name'     => $document->file_name,
                'mime_type'     => $document->mime_type,
                'url'           => Storage::disk('public')->url($path),
                'is_verified'   => $document->is_verified,
                'uploaded_at'   => $document->created_at->toIso8601String(),
            ],
        ], 201);
    }

    // ─── 8. List Task Documents ───────────────────────────────────────────────

    /**
     * GET /api/v1/tasks/{task}/documents
     * List all documents uploaded for a task.
     */
    public function documents(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $documents = $task->documents()
            ->with('owner:id,name,role')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($doc) => [
                'id'            => $doc->id,
                'document_type' => $doc->document_type,
                'file_name'     => $doc->file_name,
                'mime_type'     => $doc->mime_type,
                'url'           => Storage::disk('public')->url($doc->storage_path),
                'is_verified'   => $doc->is_verified,
                'uploaded_by'   => $doc->owner ? [
                    'id'   => $doc->owner->id,
                    'name' => $doc->owner->name,
                    'role' => $doc->owner->role,
                ] : null,
                'uploaded_at'   => $doc->created_at->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data'    => $documents,
        ]);
    }
}
