import { chromium, test, expect } from "@playwright/test";

const baseURL = "http://127.0.0.1:8000";
const endPoint = "/api/tasks";

test.describe("Todo app API testing", () => {
    let page;

    test.beforeAll(async () => {
        const browser = await chromium.launch();
        page = await browser.newPage();
        await deleteAllTasks();
    });

    test.afterAll(async () => {
        await page.close();
    });

    test("should be able to add a todo", async () => {
        const response = await addTodo({
            title: "Buy milk",
            description: "Buy milk from the store",
        });

        expect(response).toBeDefined();
        expect(response.message).toBe("タスクが正常に作成されました");
    });

    test("should be able to get all todo", async () => {
        const response = await getAllTodos();

        expect(response).toBeDefined();
        console.log(response);
    });

    test("should be able to update a todo", async () => {
        const addResponse = await addTodo({
            title: "Buy milk",
            description: "Buy milk from the store",
        });

        expect(addResponse).toBeDefined();
        const todoId = addResponse.task.id;

        const updateResponse = await updateTodo(todoId, {
            title: "Buy juice",
            description: "Buy juice from the amazon",
        });

        expect(updateResponse).toBeDefined();
        expect(updateResponse.message).toBe("タスクが正常に更新されました");
    });

    async function deleteAllTasks() {
        await page.evaluate(async ({ baseURL, endPoint }) => {
            await fetch(baseURL + endPoint, {
                method: "DELETE",
                headers: { "Content-Type": "application/json" },
            });
        }, { baseURL, endPoint });
    }

    async function addTodo(todo) {
        return await page.evaluate(async ({ baseURL, endPoint, todo }) => {
            const response = await fetch(baseURL + endPoint, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(todo),
            });
            return response.json();
        }, { baseURL, endPoint, todo });
    }

    async function getAllTodos() {
        return await page.evaluate(async ({ baseURL, endPoint }) => {
            const response = await fetch(baseURL + endPoint);
            return response.json();
        }, { baseURL, endPoint });
    }

    async function updateTodo(todoId, updatedTodo) {
        return await page.evaluate(async ({ baseURL, endPoint, todoId, updatedTodo }) => {
            const response = await fetch(baseURL + endPoint + `/${todoId}`, {
                method: "PUT",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(updatedTodo),
            });
            return response.json();
        }, { baseURL, endPoint, todoId, updatedTodo });
    }
});
