<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use OpenApi\Annotations as OA;


/**
 * @OA\Info(
 *     title="TODO API",
 *     version="1.0.0",
 *     description="This is a TODO API for managing tasks.",
 *     @OA\Contact(
 *         email="example@example.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 */
class TaskController extends Controller
{
    /**
     * @OA\Schema(
     *     schema="Task",
     *     title="Task",
     *     required={"id", "title", "description", "created_at", "updated_at"},
     *     @OA\Property(property="id", type="integer", format="int64", example=1),
     *     @OA\Property(property="title", type="string", example="Task 1"),
     *     @OA\Property(property="description", type="string", example="Description of Task 1"),
     *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-04-27T12:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-04-27T12:00:00Z")
     * )
     * 
     * @OA\Post(
     *     path="/api/tasks",
     *     summary="タスク作成",
     *     tags={"Tasks"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="新しいタスクの情報",
     *         @OA\JsonContent(
     *             required={"title"},
     *             @OA\Property(property="title", type="string", example="New Task"),
     *             @OA\Property(property="description", type="string", example="Description of New Task")
     *         )
     *     ),
    *     @OA\Response(
    *         response=201,
    *         description="タスクが正常に作成されました",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="タスクが正常に作成されました"),
    *             @OA\Property(property="task", ref="#/components/schemas/Task")
    *         )
    *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $task = new Task();
        $task->title = $request->title;
        $task->description = $request->description;
        $task->save();

        return response()->json([
            'message' => 'タスクが正常に作成されました',
            'task' => $task
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/tasks",
     *     summary="タスク一覧取得",
     *     tags={"Tasks"},
     *     @OA\Response(
     *         response=200,
     *         description="タスクの一覧を取得します。",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Task")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $tasks = Task::all();
        return response()->json($tasks);
    }

    /**
     * @OA\Get(
     *     path="/api/tasks/{id}",
     *     summary="タスク詳細取得",
     *     tags={"Tasks"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="タスクのID",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="指定されたIDのタスクの詳細を取得します。",
     *         @OA\JsonContent(ref="#/components/schemas/Task")
     *     )
     * )
     */
    public function show($id)
    {
        $task = Task::findOrFail($id);
        return response()->json($task);
    }

    /**
     * @OA\Put(
     *     path="/api/tasks/{id}",
     *     summary="タスク更新",
     *     tags={"Tasks"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="タスクのID",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="更新するタスクの情報",
     *         @OA\JsonContent(
     *             required={"title"},
     *             @OA\Property(property="title", type="string", example="Updated Task"),
     *             @OA\Property(property="description", type="string", example="Description of Updated Task")
     *         )
     *     ),
    *     @OA\Response(
    *         response=200,
    *         description="タスクが正常に更新されました",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="タスクが正常に更新されました"),
    *             @OA\Property(property="task", ref="#/components/schemas/Task")
    *         )
    *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $task = Task::findOrFail($id);
        $task->title = $request->title;
        $task->description = $request->description;
        $task->save();

        return response()->json([
            'message' => 'タスクが正常に更新されました',
            'task' => $task
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/tasks/{id}",
     *     summary="タスク削除",
     *     tags={"Tasks"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="タスクのID",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="タスクが正常に削除されました",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="タスクが正常に削除されました")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();

        return response()->json(['message' => 'タスクが正常に削除されました']);
    }

    /**
     * @OA\Delete(
     *     path="/api/tasks",
     *     summary="全てのタスク削除",
     *     tags={"Tasks"},
     *     @OA\Response(
     *         response=200,
     *         description="全てのタスクが正常に削除されました",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="全てのタスクが正常に削除されました")
     *         )
     *     )
     * )
     */
    public function destroyAll()
    {
        Task::truncate();
        return response()->json(['message' => '全てのタスクが正常に削除されました']);
    }
}
