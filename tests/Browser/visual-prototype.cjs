// Run against the local visual prototype: npm run test:prototype
const { chromium } = require("playwright");
const assert = require("node:assert/strict");
const fs = require("node:fs");

(async () => {
    const base = process.env.PROTOTYPE_URL || "http://127.0.0.1:8123";
    const executablePath =
        process.env.CHROME_PATH ||
        "C:/Program Files/Google/Chrome/Application/chrome.exe";
    const browser = await chromium.launch({ executablePath, headless: true });
    const page = await browser.newPage();
    const errors = [];
    page.on("pageerror", (error) => errors.push(error.message));
    try {
        await page.goto(base);
        const paths = await page
            .locator(".directory-link")
            .evaluateAll((links) =>
                links.map((link) => new URL(link.href).pathname),
            );
        const knownPaths = new Set(paths);
        let renders = 0;
        for (const width of [1440, 768, 320]) {
            await page.setViewportSize({ width, height: 1000 });
            for (const path of paths) {
                const response = await page.goto(base + path);
                assert.equal(response.status(), 200, path);
                assert.equal(
                    await page.locator("h1").count(),
                    1,
                    `One heading: ${path}`,
                );
                const overflow = await page.evaluate(
                    () => document.documentElement.scrollWidth > innerWidth + 1,
                );
                assert.equal(overflow, false, `${width}px overflow: ${path}`);
                const targets = await page
                    .locator("a[href]")
                    .evaluateAll((links) =>
                        links
                            .map((link) => new URL(link.href))
                            .filter(
                                (url) =>
                                    !url.hash &&
                                    !url.pathname.startsWith("/prototype/"),
                            )
                            .map((url) => url.pathname),
                    );
                targets.forEach((target) =>
                    assert(
                        knownPaths.has(target),
                        `Unknown link ${path} -> ${target}`,
                    ),
                );
                renders++;
            }
        }

        for (const [width, preview] of [[1440, ""], [320, "?preview=student"]]) {
            await page.setViewportSize({ width, height: 1000 });
            await page.goto(base + "/units/gifts-of-youth" + preview);
            const categoryLink = (name) =>
                page.locator("[data-filter-item]")
                    .filter({ has: page.getByRole("heading", { name, exact: true }) })
                    .getByRole("link", { name: "Explore" });

            for (const category of ["Vocabulary", "Functions", "Reading", "Writing"]) {
                await categoryLink(category).click();
                assert(await page.getByRole("heading", { name: category + " lessons" }).isVisible());
                assert(await page.getByRole("heading", { name: "No lessons yet" }).isVisible());
                assert.equal(await page.locator("[data-filter-form], [data-filter-item]").count(), 0);
                assert.equal(
                    await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1),
                    false,
                    `${width}px overflow: ${category} empty category`,
                );
                await page.getByRole("link", { name: "Back to unit categories" }).click();
                assert.equal(page.url(), base + "/units/gifts-of-youth" + preview);
            }

            await categoryLink("Grammar").click();
            assert(await page.getByRole("heading", { name: "Grammar lessons" }).isVisible());
            assert.equal(await page.locator("[data-filter-item]:visible").count(), 2);
            await page.locator("[data-filter-item]").first().getByRole("link", { name: "Explore" }).click();
            assert.equal(page.url(), base + "/lessons/passive-voice" + preview);
        }

        await page.goto(base + "/dashboard");
        await page
            .getByRole("link", { name: "Continue learning", exact: true })
            .click();
        await page.waitForURL("**/lessons/passive-voice?preview=student");
        assert(
            await page
                .locator(".role-switch a[aria-current]")
                .getByText("Student")
                .isVisible(),
        );
        await page.goto(base + "/dashboard");
        await page.getByRole("button", { name: "Open navigation" }).click();
        assert(
            await page
                .locator("#mobile-navigation")
                .evaluate((node) => node.open),
        );
        await page.keyboard.press("Escape");
        assert.equal(
            await page
                .getByRole("button", { name: "Open navigation" })
                .evaluate((node) => node === document.activeElement),
            true,
        );

        await page.getByRole("link", { name: "Screen directory" }).click();
        await page.locator("[data-directory-filter]").fill("A14");
        assert.equal(await page.locator(".directory-link:visible").count(), 1);
        await page.keyboard.press("Escape");

        await page.goto(base + "/search");
        await page.locator("[data-content-search]").fill("zz-no-match");
        assert(await page.locator("[data-empty-state]").isVisible());
        await page.locator("[data-reset-filters]").click();
        assert.equal(
            await page.locator("[data-filter-item]:visible").count(),
            6,
        );
        await page.locator("[data-content-type]").selectOption("Video");
        assert.equal(
            await page.locator("[data-filter-item]:visible").count(),
            1,
        );

        await page.goto(base + "/register");
        await page
            .getByRole("button", { name: "Preview creating account" })
            .click();
        assert(await page.locator(".validation-summary").isVisible());
        await page.locator("#full-name").fill("Sample Learner");
        await page.locator("#email").fill("sample@example.test");
        await page.locator("#password").fill("sample-only");
        await page.locator("#password-confirmation").fill("sample-only");
        await page
            .getByRole("button", { name: "Preview creating account" })
            .click();
        await page
            .locator("[data-success-actions]")
            .waitFor({ state: "visible" });
        assert.match(
            await page.locator("[data-feedback]").innerText(),
            /No account was created/,
        );

        for (const [width, origin, destination] of [
            [1440, "/exercises/passive-voice", "/exercises/passive-voice/attempt"],
            [320, "/exams/unit-one", "/exams/unit-one/attempt"],
        ]) {
            await page.setViewportSize({ width, height: 1000 });
            await page.goto(base + origin);
            await page.getByRole("link", { name: "Preview sign in" }).click();
            const submit = page.getByRole("button", { name: "Preview signing in" });
            const failure = page.getByRole("button", { name: "Preview sign-in error" });
            const feedback = page.locator("[data-feedback]");
            const actions = page.locator("[data-success-actions]");
            await submit.click();
            assert(await page.locator(".validation-summary").isVisible());
            await page.locator(".validation-summary a").first().click();
            assert.equal(await page.locator(":focus").getAttribute("id"), "email");
            await page.locator("#email").fill("sample@example.test");
            await page.locator("#password").fill("sample-only");
            await failure.click();
            assert.match(await feedback.innerText(), /These details could not be matched/);
            assert(await actions.isHidden());
            await submit.click();
            await actions.waitFor({ state: "visible" });
            await failure.click();
            assert(await actions.isHidden());
            await submit.click();
            await actions.getByRole("link").click();
            await page.waitForURL(base + destination);
        }

        await page.goto(base + "/exercises/passive-voice/attempt");
        await page.getByRole("button", { name: /Finish preview/ }).click();
        await page.waitForURL("**/exercises/passive-voice/results");
        assert(
            await page
                .getByText("Fixed sample result", { exact: false })
                .isVisible(),
        );

        await page.goto(base + "/admin/lessons");
        await page.locator("[data-archive]").first().click();
        await page
            .getByRole("button", { name: "Preview archive", exact: true })
            .click();
        assert.match(
            await page.locator("tbody .badge").first().innerText(),
            /Archived/,
        );
        await page.locator("[data-move-up]").last().click();
        assert.match(
            await page.locator("[data-list-feedback]").innerText(),
            /order preview/,
        );

        await page.goto(base + "/rewards/unlock");
        await page.locator("[data-insufficient]").click();
        assert(await page.locator("[data-unlock]").isDisabled());
        assert.match(
            await page.locator("[data-feedback]").innerText(),
            /Not enough points/,
        );

        for (const width of [1440, 320]) {
            await page.setViewportSize({ width, height: 1000 });
            await page.goto(base + "/lessons/passive-voice");
            for (const locale of ["en", "ar"]) {
                if (await page.locator("[data-language]").inputValue() !== locale) {
                    await Promise.all([
                        page.waitForNavigation(),
                        page.locator("[data-language]").selectOption(locale),
                    ]);
                }
                const arabicReading = page.locator(".reading details[lang=ar]");
                assert.equal(await arabicReading.getAttribute("dir"), "rtl");
                assert.equal(await arabicReading.evaluate((details) => details.open), locale === "ar");
                assert.equal(await page.locator(".reading").getAttribute("dir"), "ltr");
                assert.equal(await page.locator("html").getAttribute("dir"), locale === "ar" ? "rtl" : "ltr");
                if (locale === "en") {
                    await arabicReading.locator("summary").press("Enter");
                }
                assert(await arabicReading.locator("p[lang=en][dir=ltr]").first().isVisible());

                const illustration = page.locator(".reading figure img");
                await illustration.scrollIntoViewIfNeeded();
                await page.waitForFunction((img) => img.complete && img.naturalWidth > 0, await illustration.elementHandle());
                assert.equal(
                    await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1),
                    false,
                    `${width}px overflow: ${locale} lesson`,
                );
            }

            const video = page.locator(".reading video");
            await video.evaluate((player) => player.play());
            await page.waitForFunction((player) => player.currentTime > 0, await video.elementHandle());
            await video.evaluate((player) => player.pause());

            const guide = page.locator(".reading").getByRole("link", { name: "Open the study guide" });
            const download = page.locator(".reading").getByRole("link", { name: "Download sample text" });
            const [file] = await Promise.all([page.waitForEvent("download"), download.click()]);
            assert.equal(file.suggestedFilename(), "study-guide.txt");
            await guide.click();
            assert(await page.getByRole("heading", { name: "The passive voice · Study guide" }).isVisible());
            await page.goBack();
        }

        fs.mkdirSync("storage/app/prototype-review", { recursive: true });
        await page.screenshot({
            path: "storage/app/prototype-review/lesson-ar-mobile.png",
            fullPage: true,
        });
        await page.goto(base + "/dashboard");
        await page.screenshot({
            path: "storage/app/prototype-review/dashboard-mobile.png",
            fullPage: true,
        });
        await page.setViewportSize({ width: 1440, height: 1000 });
        await page.screenshot({
            path: "storage/app/prototype-review/dashboard-desktop.png",
            fullPage: true,
        });
        await page.goto(base);
        await page.screenshot({
            path: "storage/app/prototype-review/home-desktop.png",
            fullPage: true,
        });
        await page.goto(base + "/admin/lessons/editor?mode=edit");
        await page.screenshot({
            path: "storage/app/prototype-review/admin-editor-desktop.png",
            fullPage: true,
        });
        assert.deepEqual(errors, []);
        console.log(
            `PASS: ${paths.length} screens at 1440, 768, and 320px (${renders} renders); navigation, link targets, drawer focus, search, validation, sample attempt, archive, reorder, unlock, RTL, and browser errors.`,
        );
    } finally {
        await browser.close();
    }
})().catch((error) => {
    console.error(error);
    process.exit(1);
});
