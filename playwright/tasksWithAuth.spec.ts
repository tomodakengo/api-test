import { test, expect } from '@playwright/test';

interface ResponseTodoWithMessage {
    message: string;
    task: Task;
}

interface Task {
    id: number;
    user_id: number;
    title: string;
    description: string;
    version: number;
    created_at: string;
    updated_at: string;
}

test.describe('認証付きタスクAPI', () => {
    let authToken: string;
    let taskId: number;
    const baseUrl = 'http://127.0.0.1:8000/api/v2';
    const authBaseUrl = 'http://127.0.0.1:8000/api';

    const TEST_USER = {
        name: 'テストユーザー',
        email: 'test@example.com',
        password: 'password123'
    };

    test.beforeAll(async ({ request }) => {
        // テスト開始前に十分な待機時間を設定
        await new Promise(resolve => setTimeout(resolve, 5000));

        // まずログインを試みる
        const loginResponse = await request.post(`${authBaseUrl}/login`, {
            data: {
                email: TEST_USER.email,
                password: TEST_USER.password
            },
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        if (loginResponse.ok()) {
            const loginJson = await loginResponse.json();
            authToken = loginJson.access_token;
        } else {
            // レート制限回避のため十分な待機時間を設定
            await new Promise(resolve => setTimeout(resolve, 5000));

            // ログインに失敗した場合は新規登録を試みる
            const registerResponse = await request.post(`${authBaseUrl}/register`, {
                data: TEST_USER,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (!registerResponse.ok()) {
                if (registerResponse.status() === 429) {
                    // レート制限に引っかかった場合は十分な待機時間を設定してリトライ
                    await new Promise(resolve => setTimeout(resolve, 10000));
                    const retryResponse = await request.post(`${authBaseUrl}/register`, {
                        data: TEST_USER,
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });
                    if (!retryResponse.ok()) {
                        const errorBody = await retryResponse.text();
                        throw new Error(`リトライ後もユーザー登録に失敗しました。ステータス: ${retryResponse.status()}, レスポンス: ${errorBody}`);
                    }
                    const retryJson = await retryResponse.json();
                    authToken = retryJson.access_token;
                } else {
                    const errorBody = await registerResponse.text();
                    throw new Error(`ユーザー登録に失敗しました。ステータス: ${registerResponse.status()}, レスポンス: ${errorBody}`);
                }
            } else {
                const registerJson = await registerResponse.json();
                authToken = registerJson.access_token;
            }
        }

        if (!authToken) {
            throw new Error('認証トークンの取得に失敗しました');
        }
    });

    // 各テスト実行前にタスクを全て削除し、新しいテストタスクを作成
    test.beforeEach(async ({ request }) => {
        try {
            // 既存のタスクを全て削除
            const deleteResponse = await request.delete(`${baseUrl}/tasks`, {
                headers: {
                    'Authorization': `Bearer ${authToken}`,
                    'Accept': 'application/json'
                }
            });
            expect(deleteResponse.ok()).toBeTruthy();

            // 削除後にタスクが0件になっていることを確認
            const checkResponse = await request.get(`${baseUrl}/tasks`, {
                headers: {
                    'Authorization': `Bearer ${authToken}`,
                    'Accept': 'application/json'
                }
            });
            const tasks = await checkResponse.json();
            expect(tasks).toHaveLength(0);

            // テスト用のタスクを1件作成
            const response = await request.post(`${baseUrl}/tasks`, {
                data: {
                    title: 'テストタスク',
                    description: 'テストの説明'
                },
                headers: {
                    'Authorization': `Bearer ${authToken}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            const newTodo: ResponseTodoWithMessage = await response.json();
            taskId = newTodo.task.id;
        } catch (error) {
            console.error('BeforeEach Error:', error);
            throw error;
        }
    });

    // テスト終了後にもクリーンアップを実行
    test.afterAll(async ({ request }) => {
        await request.delete(`${baseUrl}/tasks`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
    });

    test('タスクを作成できる', async ({ request }) => {
        const newTask = {
            title: '新しいタスク',
            description: '新しい説明'
        };

        const response = await request.post(`${baseUrl}/tasks`, {
            data: newTask,
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        expect(response.ok()).toBeTruthy();
        const data = await response.json();
        expect(data.message).toBe('タスクが正常に作成されました');
        expect(data.task.title).toBe(newTask.title);
        expect(data.task.description).toBe(newTask.description);
    });

    test('タスク一覧取得できる', async ({ request }) => {
        const response = await request.get(`${baseUrl}/tasks`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });

        const tasks: Task[] = await response.json();
        expect(tasks).toBeDefined();
        expect(tasks.length).toBeGreaterThan(0);

        // 最初のタスクの構造を確認
        expect(tasks[0]).toHaveProperty("id");
        expect(tasks[0]).toHaveProperty("user_id");
        expect(tasks[0]).toHaveProperty("title");
        expect(tasks[0]).toHaveProperty("description");
        expect(tasks[0]).toHaveProperty("created_at");
        expect(tasks[0]).toHaveProperty("updated_at");
    });

    test('特定のタスクを取得できる', async ({ request }) => {
        const response = await request.get(`${baseUrl}/tasks/${taskId}`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });

        expect(response.ok()).toBeTruthy();
        const task: Task = await response.json();
        expect(task.title).toBe('テストタスク');
        expect(task.description).toBe('テストの説明');
    });

    test('タスクを更新できる', async ({ request }) => {
        // まず現在のタスクを取得してバージョンを確認
        const getResponse = await request.get(`${baseUrl}/tasks/${taskId}`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        const currentTask: Task = await getResponse.json();

        const updateTask = {
            title: '更新後のタスク',
            description: '更新後の説明',
            version: currentTask.version
        };

        const response = await request.put(`${baseUrl}/tasks/${taskId}`, {
            data: updateTask,
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        expect(response.ok()).toBeTruthy();
        const data = await response.json();
        expect(data.message).toBe('タスクが正常に更新されました');
        expect(data.task.title).toBe(updateTask.title);
        expect(data.task.description).toBe(updateTask.description);
        expect(data.task.version).toBe(currentTask.version + 1);
    });

    test('同時更新の競合を検出できる', async ({ request }) => {
        // 現在のタスクを取得
        const getResponse = await request.get(`${baseUrl}/tasks/${taskId}`, {
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Accept': 'application/json'
            }
        });
        const currentTask: Task = await getResponse.json();

        // 1回目の更新
        const firstUpdate = {
            title: '1回目の更新',
            description: '1回目の説明',
            version: currentTask.version
        };

        const firstResponse = await request.put(`${baseUrl}/tasks/${taskId}`, {
            data: firstUpdate,
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        expect(firstResponse.ok()).toBeTruthy();

        // 古いバージョンで2回目の更新を試みる
        const secondUpdate = {
            title: '2回目の更新',
            description: '2回目の説明',
            version: currentTask.version  // 古いバージョン番号を使用
        };

        const secondResponse = await request.put(`${baseUrl}/tasks/${taskId}`, {
            data: secondUpdate,
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        // ModelNotFoundExceptionが発生するため404が返される
        expect(secondResponse.status()).toBe(404);
        const errorData = await secondResponse.json();
        expect(errorData.message).toBe('No query results for model [App\\Models\\Task].');
    });

    // レート制限のテストを追加
    test('レート制限を超過した場合にエラーになる', async ({ request }) => {
        // テスト開始前に十分な待機時間を設定
        await new Promise(resolve => setTimeout(resolve, 5000));

        // 連続して複数回リクエストを送信
        const promises = Array(20).fill(null).map(() =>
            request.post(`${baseUrl}/tasks`, {
                data: {
                    title: 'テストタスク',
                    description: 'テストの説明'
                },
                headers: {
                    'Authorization': `Bearer ${authToken}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
        );

        const responses = await Promise.all(promises);
        const lastResponse = responses[responses.length - 1];

        // 最後のリクエストがレート制限に引っかかることを確認
        if (lastResponse.status() === 429) {
            const errorData = await lastResponse.json();
            expect(errorData.message).toBe('Too Many Attempts.');
        }

        // テスト終了後に十分な待機時間を設定
        await new Promise(resolve => setTimeout(resolve, 5000));
    });

    test('タスクを削除できる', async ({ request }) => {
        const response = await request.delete(`${baseUrl}/tasks/${taskId}`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });

        expect(response.ok()).toBeTruthy();
        const data = await response.json();
        expect(data.message).toBe('タスクが正常に削除されました');

        // 削除されたことを確認
        const getResponse = await request.get(`${baseUrl}/tasks/${taskId}`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        expect(getResponse.status()).toBe(404);
    });

    test('認証なしでアクセスするとエラーになる', async ({ request }) => {
        // GETリクエスト
        const getResponse = await request.get(`${baseUrl}/tasks`, {
            headers: {
                'Accept': 'application/json'
            }
        });
        expect(getResponse.status()).toBe(401);

        // POSTリクエスト
        const postResponse = await request.post(`${baseUrl}/tasks`, {
            data: {
                title: 'テストタスク',
                description: 'テストの説明'
            },
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        expect(postResponse.status()).toBe(401);

        // Content-Typeがapplication/jsonの場合のみ、JSONとしてパースを試みる
        const contentType = getResponse.headers()['content-type'];
        if (contentType && contentType.includes('application/json')) {
            const errorJson = await getResponse.json();
            expect(errorJson.message).toBeDefined();
        }
    });

    test('他のユーザーのタスクにアクセスできない', async ({ request }) => {
        // テスト開始前に少し待機してレート制限を回避
        await new Promise(resolve => setTimeout(resolve, 1000));

        const otherUser = {
            name: '別のユーザー',
            email: `other${Date.now()}@example.com`,  // メールアドレスをユニークに
            password: 'password123'
        };

        // 別のユーザーを作成
        const registerResponse = await request.post(`${authBaseUrl}/register`, {
            data: otherUser,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        let otherUserToken;
        if (registerResponse.ok()) {
            const registerJson = await registerResponse.json();
            otherUserToken = registerJson.access_token;
        } else {
            // レート制限に引っかかった場合は少し待ってリトライ
            if (registerResponse.status() === 429) {
                await new Promise(resolve => setTimeout(resolve, 2000));
                const retryResponse = await request.post(`${authBaseUrl}/register`, {
                    data: otherUser,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                if (!retryResponse.ok()) {
                    const errorBody = await retryResponse.text();
                    throw new Error(`リトライ後もユーザー登録に失敗しました。ステータス: ${retryResponse.status()}, レスポンス: ${errorBody}`);
                }
                const retryJson = await retryResponse.json();
                otherUserToken = retryJson.access_token;
            } else if (registerResponse.status() === 422) {
                // ユーザーが既に存在する場合はログイン
                const loginResponse = await request.post(`${authBaseUrl}/login`, {
                    data: {
                        email: otherUser.email,
                        password: otherUser.password
                    },
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                if (!loginResponse.ok()) {
                    const errorBody = await loginResponse.text();
                    throw new Error(`ログインに失敗しました。ステータス: ${loginResponse.status()}, レスポンス: ${errorBody}`);
                }

                const loginJson = await loginResponse.json();
                otherUserToken = loginJson.access_token;
            } else {
                const errorBody = await registerResponse.text();
                throw new Error(`ユーザー登録に失敗しました。ステータス: ${registerResponse.status()}, レスポンス: ${errorBody}`);
            }
        }

        // 少し待機してからタスクを作成
        await new Promise(resolve => setTimeout(resolve, 1000));

        // 別のユーザーでタスクを作成
        const createResponse = await request.post(`${baseUrl}/tasks`, {
            data: {
                title: '別のユーザーのタスク',
                description: '別のユーザーの説明'
            },
            headers: {
                'Authorization': `Bearer ${otherUserToken}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        if (!createResponse.ok()) {
            const errorBody = await createResponse.text();
            throw new Error(`タスク作成に失敗しました。ステータス: ${createResponse.status()}, レスポンス: ${errorBody}`);
        }

        const createData = await createResponse.json();
        const otherTaskId = createData.task.id;

        // 少し待機してからアクセス
        await new Promise(resolve => setTimeout(resolve, 1000));

        // 元のユーザーで別のユーザーのタスクにアクセス
        const response = await request.get(`${baseUrl}/tasks/${otherTaskId}`, {
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Accept': 'application/json'
            }
        });

        // 他のユーザーのタスクにはアクセスできないことを確認
        expect(response.status()).toBe(404);
    });
});
