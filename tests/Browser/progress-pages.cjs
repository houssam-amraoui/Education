// Run against an isolated application with a published lesson and test accounts.
const { chromium } = require("playwright");
const assert = require("node:assert/strict");

(async () => {
    const { PROGRESS_TEST_URL: base, PROGRESS_TEST_LESSON: lesson, PROGRESS_TEST_PASSWORD: password,
        PROGRESS_TEST_MANAGER: manager, PROGRESS_TEST_LEARNER: learner, PROGRESS_TEST_MOBILE_LEARNER: mobileLearner,
        PROGRESS_TEST_REVIEW: review } = process.env;
    assert(base && lesson && password && manager && learner && mobileLearner && review);
    const executablePath = process.env.CHROME_PATH || "C:/Program Files/Google/Chrome/Application/chrome.exe";
    const browser = await chromium.launch({ executablePath, headless: true });

    async function layout(page) {
        await page.waitForLoadState("load");
        assert(await page.locator('link[rel="stylesheet"]').first().evaluate(link => Boolean(link.sheet)), page.url());
        assert.equal(await page.locator("h1").count(), 1, page.url());
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false, page.url());
    }

    async function signIn(page, email, destination) {
        await page.goto(base + destination);
        await page.waitForURL(base + "/login");
        await page.locator("#email").fill(email);
        await page.locator("#password").fill(password);
        await page.getByRole("button", { name: "Sign in", exact: true }).click();
        await page.waitForURL(base + destination);
        await layout(page);
    }

    try {
        for (const [width, email] of [[1440, learner], [320, mobileLearner]]) {
            const viewport = { width, height: 950 };
            const page = await browser.newPage({ viewport });
            const admin = await browser.newPage({ viewport });
            const errors = [];
            for (const tab of [page, admin]) tab.on("pageerror", error => errors.push(error.message));

            await signIn(page, email, "/dashboard");
            assert(await page.getByText("No learning paths selected.").isVisible());
            await page.getByRole("link", { name: "Change learning path" }).click();
            await layout(page);
            await page.getByRole("checkbox").first().check();
            await page.getByRole("button", { name: "Save learning paths" }).click();
            await page.waitForURL(base + "/dashboard");
            assert(await page.getByRole("status").getByText("Learning paths saved.").isVisible());

            await page.goto(base + lesson);
            await layout(page);
            await page.goto(base + "/dashboard");
            await layout(page);
            await page.getByRole("link", { name: "Continue learning" }).click();
            await page.getByRole("button", { name: "Mark as complete" }).click();
            await page.getByRole("status").getByText("Lesson marked as complete.").waitFor();
            assert.equal(await page.getByRole("button", { name: "Mark as complete" }).count(), 0);
            await page.goto(base + "/account/progress");
            await layout(page);
            await page.getByRole("link", { name: "Level detail" }).click();
            await layout(page);
            await page.getByRole("link", { name: "Full learning history" }).click();
            await layout(page);
            assert(await page.getByRole("row").filter({ hasText: "Present tense" })
                .getByRole("cell", { name: "Completed", exact: true }).isVisible());

            if (width === 320) {
                await page.locator("[data-language]").selectOption("ar");
                await page.waitForFunction(() => document.documentElement.dir === "rtl");
                await layout(page);
            }

            await signIn(admin, manager, "/admin/accounts/users");
            const studentRow = admin.getByRole("row").filter({ hasText: email });
            await studentRow.getByRole("link", { name: /Activity/ }).click();
            await layout(admin);
            assert(await admin.getByRole("cell", { name: "Completed", exact: true }).first().isVisible());
            await admin.goto(base + review);
            await layout(admin);
            assert(await admin.getByText("Completed attempt", { exact: true }).isVisible());
            assert.deepEqual(errors, []);
            await page.close();
            await admin.close();
        }
        console.log("Progress browser journeys passed at 1440px and 320px.");
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
