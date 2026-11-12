// Run against an isolated, migrated application with a test administrator.
const { chromium } = require("playwright");
const assert = require("node:assert/strict");

(async () => {
    const base = process.env.ACCOUNT_TEST_URL;
    const adminEmail = process.env.ACCOUNT_TEST_ADMIN_EMAIL;
    const adminPassword = process.env.ACCOUNT_TEST_ADMIN_PASSWORD;
    assert(base && adminEmail && adminPassword, "Provide the isolated application URL and administrator fixture credentials.");
    const executablePath =
        process.env.CHROME_PATH ||
        "C:/Program Files/Google/Chrome/Application/chrome.exe";
    const browser = await chromium.launch({ executablePath, headless: true });
    const page = await browser.newPage();
    const errors = [];
    page.on("pageerror", (error) => errors.push(error.message));
    const run = Date.now();
    const password = "browser-test-password";

    async function layout() {
        assert.equal(await page.locator("h1").count(), 1);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false, page.url());
    }

    async function signIn(email, secret) {
        await page.locator("#email").fill(email);
        await page.locator("#password").fill(secret);
        await page.getByRole("button", { name: "Sign in", exact: true }).click();
    }

    async function signOut(width) {
        if (width < 1024) {
            await page.getByRole("button", { name: "Open navigation" }).click();
        }
        const region = width < 1024 ? page.locator("#mobile-navigation") : page.locator(".sidebar");
        await region.getByRole("button", { name: "Sign out" }).click();
        await page.waitForURL(base + "/account/login");
    }

    try {
        for (const width of [1440, 320]) {
            await page.setViewportSize({ width, height: 950 });
            const email = `learner-${run}-${width}@example.test`;
            const role = `Support ${run} ${width}`;
            const userList = base + "/admin/accounts/users";
            const roleList = base + "/admin/accounts/roles";

            await page.goto(base + "/account/register");
            await layout();
            await page.locator("#name").fill("Browser Learner");
            await page.locator("#email").fill(email);
            await page.locator("#password").fill(password);
            await page.locator("#password_confirmation").fill("different");
            await page.getByRole("button", { name: "Create account" }).click();
            await page.locator("[data-live-errors]:focus").waitFor();
            assert.equal(await page.locator("#email").inputValue(), email);
            assert.equal(await page.locator("#password").inputValue(), "");
            await page.locator("#password").fill(password);
            await page.locator("#password_confirmation").fill(password);
            await page.getByRole("button", { name: "Create account" }).click();
            await page.waitForURL(base + "/account/login");
            await layout();
            await signIn(email, "incorrect");
            await page.locator("[data-live-errors]:focus").waitFor();
            await signIn(email, password);
            await page.waitForURL(base + "/account");
            assert(await page.getByText(email, { exact: true }).isVisible());
            assert.equal((await page.goto(userList)).status(), 403);
            assert(await page.getByRole("heading", { name: "Access restricted" }).isVisible());
            await signOut(width);

            await page.goto(userList + "?search=nonexistent-account");
            await page.waitForURL(base + "/account/login");
            await signIn(adminEmail, adminPassword);
            await page.waitForURL(userList + "?search=nonexistent-account");
            assert(await page.getByRole("heading", { name: "No matching records" }).isVisible());
            await layout();
            await page.getByRole("link", { name: "Reset filters" }).click();
            await page.getByRole("link", { name: "Create user" }).click();
            await layout();
            const managedEmail = `managed-${run}-${width}@example.test`;
            await page.locator("#name").fill("Managed Account");
            await page.locator("#email").fill(managedEmail);
            await page.locator("#password").fill(password);
            await page.locator("#password_confirmation").fill(password);
            await page.getByRole("button", { name: "Save user" }).click();
            await page.waitForURL(/\/admin\/accounts\/users\/\d+\/edit$/);
            const editor = page.url();
            assert(await page.getByRole("status").getByText("Account created.").isVisible());
            await page.locator("#name").fill("Updated Account");
            await page.getByRole("button", { name: "Save user" }).click();
            await page.getByRole("status").getByText("Account updated.").waitFor();
            await layout();

            await page.goto(roleList);
            await layout();
            await page.getByRole("link", { name: "Create role" }).click();
            await layout();
            await page.locator("#name").fill(role);
            await page.getByLabel("Enter administration").check();
            await page.getByRole("button", { name: "Save role" }).click();
            await page.waitForURL(roleList);
            await page.getByRole("link", { name: "Edit " + role }).click();
            assert(await page.getByLabel("Enter administration").isChecked());
            await page.getByLabel("Enter administration").uncheck();
            await page.getByRole("button", { name: "Save role" }).click();
            await page.waitForURL(roleList);

            await page.goto(editor);
            await page.getByLabel(role, { exact: true }).check();
            await page.getByRole("button", { name: "Save roles" }).click();
            await page.getByRole("status").getByText("Roles updated.").waitFor();
            assert(await page.getByLabel(role, { exact: true }).isChecked());
            await page.getByLabel("Language").selectOption("ar");
            await page.locator('html[lang="ar"][dir="rtl"]').waitFor();
            await layout();
            await page.locator("[data-language]").selectOption("en");
            await page.locator('html[lang="en"]').waitFor();
            await signOut(width);
        }
        assert.deepEqual(errors, []);
        console.log("PASS: live registration, sessions, user and role editors, authorization, empty results, and RTL at 1440px and 320px.");
    } finally {
        await browser.close();
    }
})().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
