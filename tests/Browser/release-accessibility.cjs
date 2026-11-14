const assert = require("node:assert/strict");
const { chromium } = require("playwright");

async function signIn(page, base, credentials) {
    await page.goto(base + "/login");
    await page.locator("#email").fill(credentials.email);
    await page.locator("#password").fill(credentials.password);
    await Promise.all([
        page.waitForURL(base + "/dashboard"),
        page.locator("main form button").click(),
    ]);
}

async function audit(page, base, flow, path, locale, width, findings) {
    const url = base + path;
    assert.equal((await page.goto(url)).status(), 200, url);
    if (await page.locator("html").getAttribute("lang") !== locale) {
        await Promise.all([
            page.waitForNavigation(),
            page.locator("[data-language]").selectOption(locale),
        ]);
    }
    assert.equal(await page.locator("html").getAttribute("lang"), locale, url);
    assert.equal(await page.locator("html").getAttribute("dir"), locale === "ar" ? "rtl" : "ltr", url);
    assert.equal(await page.locator("h1").count(), 1, url);

    const record = (issue) => findings.push({ flow, path, locale, width, issue });
    await page.keyboard.press("Tab");
    if (!(await page.locator(".skip-link").evaluate((link) => document.activeElement === link))) {
        record("Skip link is not the first keyboard stop");
    } else {
        await page.keyboard.press("Enter");
        if (!(await page.locator("#main").evaluate((main) => document.activeElement === main))) {
            record("Skip link does not move focus to main content");
        }
    }

    const navigation = page.locator(".mobile-menu");
    if (await navigation.isVisible()) {
        await navigation.focus();
        await page.keyboard.press("Enter");
        const drawer = page.locator("#mobile-navigation");
        await drawer.waitFor({ state: "visible" });
        await page.keyboard.press("Tab");
        if (!(await drawer.evaluate((dialog) => dialog.contains(document.activeElement)))) {
            record("Keyboard focus leaves the open mobile drawer");
        }
        await page.keyboard.press("Escape");
        await drawer.waitFor({ state: "hidden" });
        if (!(await navigation.evaluate((button) => document.activeElement === button))) {
            record("Mobile drawer does not restore focus after Escape");
        }
    }

    if (locale !== "en") {
        const heading = await page.locator("h1").innerText();
        const localizedHeading = await page.locator("h1").evaluate((element) => element.closest("[lang]").lang);
        if (localizedHeading === locale && !/\p{Script=Arabic}/u.test(heading) && locale === "ar") {
            record("English page heading inherits Arabic language");
        }
    }
    await page.emulateMedia({ reducedMotion: "reduce" });
    assert.equal(await page.evaluate(() => matchMedia("(prefers-reduced-motion: reduce)").matches), true);
    const transitions = await page.locator(".nav-link").first().evaluate((link) => getComputedStyle(link).transitionDuration);
    if (transitions.split(",").some((value) => parseFloat(value) > 0)) {
        record("Navigation transition stays active with reduced motion");
    }

    for (const zoom of [1, 2]) {
        // CSS zoom exercises reflow at 200% without relying on browser chrome shortcuts.
        await page.evaluate((factor) => { document.body.style.zoom = factor; }, zoom);
        if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1)) {
            record(`Horizontal page overflow at ${zoom * 100}% zoom`);
        }
    }
}

(async () => {
    const base = process.env.RELEASE_TEST_URL;
    const adminEmail = process.env.RELEASE_TEST_ADMIN_EMAIL;
    const adminPassword = process.env.RELEASE_TEST_ADMIN_PASSWORD;
    assert(base && adminEmail && adminPassword, "Provide an isolated demo-seeded application and administrator credentials.");
    const browser = await chromium.launch({
        executablePath: process.env.CHROME_PATH || "C:/Program Files/Google/Chrome/Application/chrome.exe",
    });
    const findings = [];
    const errors = [];
    let checked = 0;
    try {
        for (const width of [1440, 320]) {
            const flows = [
                ["G02", "/paths"],
                ["S01", "/dashboard", { email: "demo.student@example.test", password: "password" }],
                ["A01", "/admin/overview", { email: adminEmail, password: adminPassword }],
            ];
            for (const [flow, path, credentials] of flows) {
                for (const locale of ["en", "fr", "ar"]) {
                    const page = await browser.newPage({ viewport: { width, height: 950 } });
                    page.on("pageerror", (error) => errors.push(`${flow}/${locale}/${width}: ${error.message}`));
                    if (credentials) await signIn(page, base, credentials);
                    await audit(page, base, flow, path, locale, width, findings);
                    checked++;
                    await page.close();
                }
            }
        }
        console.log(JSON.stringify({ checked, findings, errors }, null, 2));
        assert.deepEqual(errors, []);
    } finally {
        await browser.close();
    }
})().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
