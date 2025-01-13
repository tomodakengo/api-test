<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Auth Tasks",
 *     description="認証付きタスク管理API"
 * )
 */
class AuthTaskController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v2/tasks",
     *     summary="新規タスクを作成",
     *     tags={"Auth Tasks"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title"},
     *             @OA\Property(property="title", type="string", example="買い物に行く", maxLength=255),
     *             @OA\Property(property="description", type="string", example="牛乳を買う", maxLength=1000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="タスクが正常に作成されました",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="タスクが正常に作成されました"),
     *             @OA\Property(
     *                 property="task",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="買い物に行く"),
     *                 @OA\Property(property="description", type="string", example="牛乳を買う"),
     *                 @OA\Property(property="version", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="認証エラー"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="バリデーションエラー",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="title",
     *                     type="array",
     *                     @OA\Items(type="string", example="タイトルは必須です。")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="リクエスト制限を超過しました",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Too Many Attempts.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="内部サーバーエラー",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="タスクの作成に失敗しました"),
     *             @OA\Property(property="error", type="string", example="内部サーバーエラーが発生しました")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|min:1|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            return \DB::transaction(function () use ($request) {
                $task = new Task();
                $task->user_id = $request->user()->id;
                $task->title = $request->title;
                $task->description = $request->description;
                $task->save();

                return response()->json([
                    'message' => 'タスクが正常に作成されました',
                    'task' => $task
                ], 201);
            });
        } catch (\Exception $e) {
            \Log::error('タスク作成エラー: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'title' => $request->title
            ]);
            return response()->json([
                'message' => 'タスクの作成に失敗しました',
                'error' => '内部サーバーエラーが発生しました'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v2/tasks",
     *     summary="認証ユーザーのタスク一覧を取得",
     *     tags={"Auth Tasks"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="タスク一覧を取得",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="買い物に行く"),
     *                 @OA\Property(property="description", type="string", example="牛乳を買う"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="認証エラー"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $tasks = Task::where('user_id', $request->user()->id)->get();
        return response()->json($tasks);
    }

    /**
     * @OA\Get(
     *     path="/api/v2/tasks/{id}",
     *     summary="指定されたタスクの詳細を取得",
     *     tags={"Auth Tasks"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="タスクID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="タスクの詳細情報",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="買い物に行く"),
     *             @OA\Property(property="description", type="string", example="牛乳を買う"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="認証エラー"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="タスクが見つかりません"
     *     )
     * )
     */
    public function show(Request $request, $id)
    {
        $task = Task::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json($task);
    }

    /**
     * @OA\Put(
     *     path="/api/v2/tasks/{id}",
     *     summary="指定されたタスクを更新",
     *     tags={"Auth Tasks"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="タスクID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title"},
     *             @OA\Property(property="title", type="string", example="買い物に行く"),
     *             @OA\Property(property="description", type="string", example="牛乳を買う"),
     *             @OA\Property(property="version", type="integer", example=1, description="楽観的ロックのためのバージョン番号")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="タスクが正常に更新されました",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="タスクが正常に更新されました"),
     *             @OA\Property(
     *                 property="task",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="買い物に行く"),
     *                 @OA\Property(property="description", type="string", example="牛乳を買う"),
     *                 @OA\Property(property="version", type="integer", example=2),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="認証エラー"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="タスクが見つかりません"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="タスクは既に他のユーザーによって更新されています",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="タスクは既に他のユーザーによって更新されています")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="バリデーションエラー"
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="リクエスト制限を超過しました",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Too Many Attempts.")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'version' => 'required|integer',
        ]);

        $task = Task::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->where('version', $request->version)
            ->firstOrFail();

        try {
            \DB::transaction(function () use ($task, $request) {
                $task->title = $request->title;
                $task->description = $request->description;
                $task->version = $request->version + 1;
                $task->save();

                $cacheKey = "task_{$task->id}_user_{$request->user()->id}";
                \Cache::put($cacheKey, $task, now()->addMinutes(5));
            });

            return response()->json([
                'message' => 'タスクが正常に更新されました',
                'task' => $task
            ]);
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json([
                    'message' => 'タスクは既に他のユーザーによって更新されています',
                ], 409);
            }
            \Log::error('タスク更新エラー: ' . $e->getMessage(), [
                'task_id' => $id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'タスクの更新に失敗しました',
                'error' => '内部サーバーエラーが発生しました'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v2/tasks/{id}",
     *     summary="指定されたタスクを削除",
     *     tags={"Auth Tasks"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="タスクID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="タスクが正常に削除されました",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="タスクが正常に削除されました")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="認証エラー"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="タスクが見つかりません"
     *     )
     * )
     */
    public function destroy(Request $request, $id)
    {
        try {
            $task = Task::where('user_id', $request->user()->id)
                ->where('id', $id)
                ->firstOrFail();

            \DB::transaction(function () use ($task, $request) {
                $task->delete();  // ソフトデリート（Modelで設定）

                // キャッシュを削除
                $cacheKey = "task_{$task->id}_user_{$request->user()->id}";
                \Cache::forget($cacheKey);
            });

            return response()->json([
                'message' => 'タスクが正常に削除されました'
            ]);
        } catch (\Exception $e) {
            \Log::error('タスク削除エラー: ' . $e->getMessage(), [
                'task_id' => $id,
                'user_id' => $request->user()->id
            ]);
            return response()->json([
                'message' => 'タスクの削除に失敗しました',
                'error' => '内部サーバーエラーが発生しました'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v2/tasks",
     *     summary="認証ユーザーの全タスクを削除",
     *     tags={"Auth Tasks"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="全タスクが正常に削除されました",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="全タスクが正常に削除されました")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="認証エラー"
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="リクエスト制限を超過しました",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Too Many Attempts.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="内部サーバーエラー",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="タスクの一括削除に失敗しました"),
     *             @OA\Property(property="error", type="string", example="内部サーバーエラーが発生しました")
     *         )
     *     )
     * )
     */
    public function destroyAll(Request $request)
    {
        try {
            Task::where('user_id', $request->user()->id)->delete();
            return response()->json([
                'message' => '全タスクが正常に削除されました'
            ]);
        } catch (\Exception $e) {
            \Log::error('タスク一括削除エラー: ' . $e->getMessage(), [
                'user_id' => $request->user()->id
            ]);
            return response()->json([
                'message' => 'タスクの一括削除に失敗しました',
                'error' => '内部サーバーエラーが発生しました'
            ], 500);
        }
    }
} 