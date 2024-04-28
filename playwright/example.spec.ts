import { chromium, test, expect } from "@playwright/test";

const baseURL = "http://127.0.0.1:8000";
const endPoint = "/api/tasks";

test.describe("Todo app API testing", () => {
    test.beforeAll(async () => {
        const browser = await chromium.launch();
        const page = await browser.newPage();
        await page.evaluate(
            ({ baseURL, endPoint }) => {
                return fetch(baseURL + endPoint, {
                    method: "DELETE",
                    headers: { "Content-Type": "application/json" },
                }).then((res) => res.json());
            },
            { baseURL, endPoint },
        );
        await page.close();
    });

    test("should be able to add a todo", async ({ page }) => {
        const response = await page.evaluate(
            ({ baseURL, endPoint }) => {
                return fetch(baseURL + endPoint, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        title: "Buy milk",
                        description: "Buy milk from the store",
                    }),
                }).then((res) => res.json());
            },
            { baseURL, endPoint },
        );

        expect(response).toBeDefined();
        expect(response.message).toBe("タスクが正常に作成されました");
    });

    test("should be able to get all todo", async ({ page }) => {
        const response = await page.evaluate(
            ({ baseURL, endPoint }) => {
                return fetch(baseURL + endPoint).then((res) => res.json());
            },
            { baseURL, endPoint },
        );

        expect(response).toBeDefined();
        console.log(response);
    });

    test("should be able to update a todo", async ({ page }) => {
        const response = await page.evaluate(
            ({ baseURL, endPoint }) => {
                return fetch(baseURL + endPoint, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        title: "Buy milk",
                        description: "Buy milk from the store",
                    }),
                }).then((res) => res.json());
            },
            { baseURL, endPoint },
        );

        expect(response).toBeDefined();
        const todoId = response.task.id;
        const updateResponse = await page.evaluate(
            ({ baseURL, endPoint, todoId }) => {
                return fetch(baseURL + endPoint + `/${todoId}`, {
                    method: "PUT",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        title: "Buy juice",
                        description: "Buy juice from the amazon",
                    }),
                }).then((res) => res.json());
            },
            { baseURL, endPoint, todoId },
        );

        expect(updateResponse).toBeDefined();
        expect(updateResponse.message).toBe("タスクが正常に更新されました");
    });
});
