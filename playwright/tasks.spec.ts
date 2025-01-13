import { test, expect } from "@playwright/test";

const endPoint = "http://127.0.0.1:8000/api/tasks";

interface ResponseTodoWithMessage {
    message: string;
    task: Task;
}

interface Task {
    id: number;
    title: string;
    description: string;
    created_at: string;
    updated_at: string;
}


test.describe("Todo app API testing", () => {

    let taskId: number;

    test.beforeAll(async ({ request }) => {
        await request.delete(endPoint);
    });

    test.beforeEach(async ({ request }) => {
        const response = await request.post(endPoint, {
            data: {
                title: "Buy milk",
                description: "Buy milk from the store",
            },
        });

        const newTodo: ResponseTodoWithMessage = await response.json();
        taskId = newTodo.task.id;
    });

    test.afterAll(async ({ request }) => {
        await request.delete(endPoint);
    });

    test("should be able to add a todo", async ({ request }) => {

        const response = await request.post(endPoint, {
            data: {
                title: "Buy milk",
                description: "Buy milk from the store",
            },
        });

        const newTodo: ResponseTodoWithMessage = await response.json();

        expect(newTodo).toBeDefined();
        expect(newTodo.message).toBe("タスクが正常に作成されました");
    });

    test("should be able to get all todo", async ({ request }) => {
        const response = await request.get(endPoint);

        const todos: Task[] = await response.json();

        expect(todos).toBeDefined();

        expect(todos[0]).toHaveProperty("id");
        expect(todos[0]).toHaveProperty("title");
        expect(todos[0]).toHaveProperty("description");

    });

    test("should be able to update a todo", async ({ request }) => {

        const response = await request.put(endPoint + `/${taskId}`, {
            data: {
                title: "Buy juice",
                description: "Buy juice from the amazon",
            },
        });

        const updateTodo: ResponseTodoWithMessage = await response.json();

        expect(updateTodo).toBeDefined();
        expect(updateTodo.message).toBe("タスクが正常に更新されました");
    });
});
