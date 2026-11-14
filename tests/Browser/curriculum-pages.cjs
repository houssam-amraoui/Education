// Run against an isolated, migrated application with a curriculum manager.
const { chromium } = require("playwright");
const assert = require("node:assert/strict");

(async () => {
    const base = process.env.CURRICULUM_TEST_URL;
    const email = process.env.CURRICULUM_TEST_EMAIL;
    const password = process.env.CURRICULUM_TEST_PASSWORD;
    assert(base && email && password, "Provide the isolated application URL and curriculum manager credentials.");

    const executablePath = process.env.CHROME_PATH ||
        "C:/Program Files/Google/Chrome/Application/chrome.exe";
    const browser = await chromium.launch({ executablePath, headless: true });
    const run = Date.now();

    async function layout(page) {
        await page.waitForLoadState("load");
        assert.equal(await page.locator("h1").count(), 1, page.url());
        assert.equal(
            await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1),
            false,
            page.url(),
        );
    }

    try {
        for (const width of [1440, 320]) {
            const viewport = { width, height: 950 };
            const manager = await browser.newPage({ viewport });
            const guest = await browser.newPage({ viewport });
            const errors = [];
            for (const page of [manager, guest]) {
                page.on("pageerror", (error) => errors.push(error.message));
            }

            const title = "Browser English " + run + "-" + width;
            const levels = base + "/admin/curriculum/levels";
            const unitName = "Everyday English";
            const category = "Grammar and usage";
            const row = (name) => manager.getByRole("row").filter({ hasText: name });

            await manager.goto(levels);
            await manager.waitForURL(base + "/login");
            await manager.locator("#email").fill(email);
            await manager.locator("#password").fill(password);
            await manager.getByRole("button", { name: "Sign in", exact: true }).click();
            await manager.waitForURL(levels);
            await layout(manager);

            await manager.getByRole("link", { name: "Create learning path" }).first().click();
            await layout(manager);
            const description = "A persisted path for the browser check. " + "English".repeat(48);
            await manager.getByLabel("Description").fill(description);
            await manager.locator("form.stack").evaluate((form) => { form.noValidate = true; });
            await manager.getByRole("button", { name: "Save learning path" }).click();
            await manager.locator("[data-live-errors]:focus").waitFor();
            assert.equal(await manager.getByLabel("Description").inputValue(), description);

            await manager.getByLabel("Title").fill(title);
            await manager.getByRole("button", { name: "Save learning path" }).click();
            await manager.waitForURL(levels);
            assert(await row(title).getByText("Draft").isVisible());
            await guest.goto(base + "/paths");
            assert.equal(await guest.getByRole("heading", { name: title, exact: true }).count(), 0);

            await row(title).getByRole("button", { name: "Publish " + title }).click();
            await row(title).getByText("Published").waitFor();
            await row(title).getByRole("link", { name: "Edit " + title }).click();
            await manager.locator("main").getByRole("link", { name: "Units", exact: true }).click();
            await manager.getByRole("link", { name: "Create unit" }).first().click();
            assert.equal(await manager.locator("#level_id option:checked").textContent(), title);
            await manager.getByLabel("Title").fill(unitName);
            await manager.getByRole("button", { name: "Save unit" }).click();
            await row(unitName).getByRole("link", { name: "Edit " + unitName }).click();
            await manager.getByLabel("State", { exact: true }).selectOption("published");
            await manager.getByRole("button", { name: "Save state" }).click();
            await row(unitName).getByText("Published").waitFor();
            await row(unitName).getByRole("link", { name: "Edit " + unitName }).click();
            await manager.locator("main").getByRole("link", { name: "Categories", exact: true }).click();
            const categories = manager.url();
            await manager.getByRole("link", { name: "Create category" }).first().click();
            await layout(manager);
            assert.equal(await manager.locator("#unit_id option:checked").textContent(), unitName);
            assert.equal(await manager.getByLabel("Learning path", { exact: true }).inputValue(), title);
            await manager.getByLabel("Title").fill(category);
            await manager.getByRole("button", { name: "Save category" }).click();
            await manager.waitForURL(categories);
            await layout(manager);

            await guest.goto(base + "/home");
            await layout(guest);
            await guest.getByRole("link", { name: "Explore learning paths" }).click();
            await guest.getByLabel("Find in Learning paths").fill(title);
            await guest.getByRole("button", { name: "Search", exact: true }).click();
            await guest.getByRole("heading", { name: title, exact: true }).waitFor();
            const card = (name) => guest.locator(".card").filter({ has: guest.getByRole("heading", { name, exact: true }) });
            await card(title).getByRole("link", { name: "Explore" }).click();
            await layout(guest);
            await card("Units").getByRole("link", { name: "Explore" }).click();
            await card(unitName).getByRole("link", { name: "Explore" }).click();
            await card(category).getByRole("link", { name: "Explore" }).click();
            assert(await guest.getByRole("heading", { name: "No lessons yet" }).isVisible());
            const publicCategory = guest.url();
            const breadcrumb = guest.getByRole("navigation", { name: "Breadcrumb" });
            assert(await breadcrumb.getByRole("link", { name: title }).isVisible());
            assert(await breadcrumb.getByRole("link", { name: unitName }).isVisible());
            await layout(guest);

            if (width === 320) {
                await guest.waitForLoadState("load");
                const localeResponse = guest.waitForResponse((response) =>
                    response.url() === base + "/account/locale" && response.request().method() === "POST",
                );
                await guest.getByLabel("Language").selectOption("ar");
                assert.equal((await localeResponse).status(), 303);
                await guest.locator('html[dir="rtl"]').waitFor();
                assert.equal(guest.url(), publicCategory);
                await layout(guest);
            }

            await row(category).getByRole("button", { name: "Archive " + category }).click();
            await manager.getByRole("dialog", { name: "Archive record" }).getByRole("button", { name: "Archive", exact: true }).click();
            await row(category).getByText("Archived", { exact: true }).waitFor();
            assert.equal((await guest.goto(publicCategory)).status(), 404);
            assert.deepEqual(errors, []);
            await manager.close();
            await guest.close();
        }

        console.log("PASS: live curriculum browsing, management, validation, archive, and RTL at desktop and mobile widths.");
    } finally {
        await browser.close();
    }
})().catch((error) => {
    console.error(error);
    process.exit(1);
});
