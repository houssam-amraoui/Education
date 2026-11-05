const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

// Native dialogs contain focus, support Escape, and restore focus on close.
let dialogTrigger;
function openDialog(id, trigger) {
    dialogTrigger = trigger;
    $(id).showModal();
}
$$("[data-open-directory]").forEach((button) =>
    button.addEventListener("click", (event) => {
        event.preventDefault();
        openDialog("#screen-directory", button);
    }),
);
$$("[data-open-nav]").forEach((button) =>
    button.addEventListener("click", () =>
        openDialog("#mobile-navigation", button),
    ),
);
$$("[data-close-dialog]").forEach((button) =>
    button.addEventListener("click", () => button.closest("dialog").close()),
);
$$("dialog").forEach((dialog) =>
    dialog.addEventListener("close", () => dialogTrigger?.focus()),
);
$("[data-directory-filter]")?.addEventListener("input", (event) => {
    const query = event.target.value.toLocaleLowerCase();
    $$(".directory-link").forEach((link) => {
        link.hidden = !link.textContent.toLocaleLowerCase().includes(query);
    });
});

function feedback(container, message, tone = "success") {
    const output = $("[data-feedback]", container);
    if (!output) return;
    output.className = `notice ${tone}`;
    output.textContent = message;
    output.hidden = false;
}
$$("[data-simulate]").forEach((button) =>
    button.addEventListener("click", () => {
        feedback(
            button.closest("form,article,section,.video-stage") || document,
            button.dataset.simulate,
            button.dataset.tone || "success",
        );
    }),
);
$$("[data-prototype-form]").forEach((form) => {
    form.noValidate = true;
    form.addEventListener("submit", (event) => {
        event.preventDefault();
        $(".validation-summary", form)?.remove();
        $$("[aria-invalid]", form).forEach((field) => {
            field.removeAttribute("aria-invalid");
            field.removeAttribute("aria-describedby");
            if (document.getElementById(field.id + "-help")) {
                field.setAttribute("aria-describedby", field.id + "-help");
            }
        });
        const invalid = $$("input,select,textarea", form).filter(
            (field) => !field.validity.valid,
        );
        const password = $("#password", form),
            confirmation = $("#password-confirmation", form);
        if (confirmation && confirmation.value !== password.value)
            invalid.push(confirmation);
        if (invalid.length) {
            const summary = document.createElement("div");
            summary.className = "validation-summary";
            summary.tabIndex = -1;
            summary.setAttribute("role", "alert");
            const title = document.createElement("strong");
            title.textContent = "Please review the following fields.";
            summary.append(title);
            const list = document.createElement("ul");
            invalid.forEach((field, index) => {
                field.setAttribute("aria-invalid", "true");
                const item = document.createElement("li");
                item.id = `field-error-${index}`;
                const link = document.createElement("a");
                link.href = `#${field.id}`;
                const label =
                    $(`label[for="${field.id}"]`, form)?.textContent ||
                    field.name;
                link.textContent = `${label}: ${field === confirmation && confirmation.value !== password.value ? "Passwords must match." : field.validationMessage}`;
                link.addEventListener("click", (event) => {
                    event.preventDefault();
                    field.focus();
                });
                field.setAttribute(
                    "aria-describedby",
                    [field.id + "-help", item.id]
                        .filter(
                            (id) =>
                                document.getElementById(id) || id === item.id,
                        )
                        .join(" "),
                );
                item.append(link);
                list.append(item);
            });
            summary.append(list);
            form.prepend(summary);
            summary.focus();
            return;
        }
        const button = $('button[type="submit"],button:not([type])', form);
        const original = button?.textContent;
        if (button) {
            button.disabled = true;
            button.textContent = "Previewing…";
            button.setAttribute("aria-busy", "true");
        }
        window.setTimeout(() => {
            feedback(form, form.dataset.success);
            if (button) {
                button.disabled = false;
                button.textContent = original;
                button.removeAttribute("aria-busy");
            }
            const actions = $("[data-success-actions]", form);
            if (actions) actions.hidden = false;
        }, 350);
    });
});
$("[data-attempt-form]")?.addEventListener("submit", (event) => {
    event.preventDefault();
    location.assign(event.currentTarget.dataset.resultUrl);
});

function filterContent() {
    const query = ($("[data-content-search]")?.value || "")
        .trim()
        .toLocaleLowerCase();
    const type = ($("[data-content-type]")?.value || "").toLocaleLowerCase();
    const items = $$("[data-filter-item]");
    items.forEach((item) => {
        item.hidden =
            !item.textContent.toLocaleLowerCase().includes(query) ||
            !!(type && item.dataset.filterType?.toLocaleLowerCase() !== type);
    });
    const visible = items.filter((item) => !item.hidden).length;
    if ($("[data-result-count]"))
        $("[data-result-count]").textContent =
            `${visible} of ${items.length} sample results`;
    if ($("[data-empty-state]")) $("[data-empty-state]").hidden = visible !== 0;
}
$$("[data-filter-form]").forEach((form) =>
    form.addEventListener("submit", (event) => {
        event.preventDefault();
        filterContent();
    }),
);
$("[data-content-search]")?.addEventListener("input", filterContent);
$("[data-content-type]")?.addEventListener("change", filterContent);
$$("[data-reset-filters]").forEach((button) =>
    button.addEventListener("click", () => {
        if ($("[data-content-search]")) $("[data-content-search]").value = "";
        if ($("[data-content-type]")) $("[data-content-type]").value = "";
        filterContent();
    }),
);
if ($("[data-filter-list]")) filterContent();
$("[data-history-toggle]")?.addEventListener("click", (event) => {
    const table = $("[data-history-table]");
    table.hidden = !table.hidden;
    $("[data-history-empty]").hidden = !table.hidden;
    event.target.textContent = table.hidden
        ? "Show sample history"
        : "Preview empty history";
});
$("[data-print]")?.addEventListener("click", () => window.print());
$$("[data-row-action]").forEach((button) =>
    button.addEventListener("click", () => {
        const badge = $(".badge", button.closest("tr"));
        badge.textContent = `${button.dataset.rowAction} · Preview`;
        const output = $("[data-list-feedback]");
        output.textContent =
            "Publication preview updated. No record was changed.";
        output.hidden = false;
    }),
);
$$("[data-archive]").forEach((button) =>
    button.addEventListener("click", () => {
        const row = button.closest("tr");
        const name = $("strong", row).textContent;
        const dialog = document.createElement("dialog");
        const heading = document.createElement("h2");
        heading.id = "archive-preview-title";
        dialog.setAttribute("aria-labelledby", heading.id);
        heading.textContent = `Archive ${name}?`;
        const copy = document.createElement("p");
        copy.textContent =
            "This previews an archived state. The record will not be removed or saved.";
        const actions = document.createElement("div");
        actions.className = "actions";
        const cancel = document.createElement("button");
        cancel.textContent = "Cancel";
        cancel.className = "button button-secondary";
        const confirm = document.createElement("button");
        confirm.textContent = "Preview archive";
        confirm.className = "button button-primary";
        cancel.onclick = () => dialog.close();
        confirm.onclick = () => {
            $(".badge", row).textContent = "Archived · Preview";
            dialog.close();
        };
        actions.append(cancel, confirm);
        dialog.append(heading, copy, actions);
        document.body.append(dialog);
        dialog.addEventListener("close", () => {
            dialog.remove();
            button.focus();
        });
        dialog.showModal();
        cancel.focus();
    }),
);
$$("[data-move-up]").forEach((button) =>
    button.addEventListener("click", () => {
        const row = button.closest("tr");
        const previous = row.previousElementSibling;
        if (previous) row.parentElement.insertBefore(row, previous);
        const output = $("[data-list-feedback]");
        output.textContent = previous
            ? "Row order preview updated. The order has not been saved."
            : "This row is already first.";
        output.hidden = false;
        button.focus();
    }),
);
$("[data-question-type]")?.addEventListener("change", (event) => {
    $("[data-question-options]").hidden = [
        "Short answer",
        "Fill in the blanks",
    ].includes(event.target.value);
});
$("[data-unlock]")?.addEventListener("click", (event) => {
    feedback(
        event.target.closest("section"),
        "Unlock preview complete. No points were spent and no access was granted.",
    );
    $("[data-unlocked]").hidden = false;
});
$("[data-insufficient]")?.addEventListener("click", (event) => {
    $("[data-balance]").textContent = "40 points · Insufficient-balance sample";
    $("[data-balance-after]").textContent = "60 more points needed";
    $("[data-unlocked]").hidden = true;
    $("[data-unlock]").disabled = true;
    feedback(
        event.target.closest("section"),
        "Not enough points in this sample state. Continue learning to earn more points.",
        "danger",
    );
});

const navigationTranslations = {
    fr: [
        "Tableau de bord",
        "Parcours",
        "Unités",
        "Exercices",
        "Examens",
        "Ressources",
        "Vidéos",
        "Index des thèmes",
        "Rechercher",
        "Progression",
        "Messages",
        "Récompenses",
        "Profil",
        "Préférences",
    ],
    ar: [
        "لوحة التعلم",
        "مسارات التعلم",
        "الوحدات",
        "التمارين",
        "الامتحانات",
        "الموارد",
        "الفيديوهات",
        "فهرس المواضيع",
        "البحث",
        "التقدم",
        "الرسائل",
        "الإنجازات",
        "الملف الشخصي",
        "الإعدادات",
    ],
};
const navKeys = [
    "S01",
    "G02",
    "G04",
    "G11",
    "G13",
    "G16",
    "G19",
    "G09",
    "G08",
    "S10",
    "S13",
    "S16",
    "S22",
    "S23",
];
const originalLabels = new Map(
    $$("[data-nav-id]").map((node) => [node, node.textContent]),
);
const extraNavigationTranslations = {
    fr: {
        G01: "Accueil",
        G21: "Créer un compte",
        G22: "Se connecter",
        A01: "Vue d’ensemble",
        A02: "Utilisateurs",
        A05: "Rôles",
        A07: "Parcours",
        A09: "Unités",
        A11: "Catégories",
        A13: "Leçons",
        A16: "Exercices",
        A20: "Examens",
        A25: "Ressources",
        A27: "Vidéos",
        A29: "Thèmes",
        A31: "Conversations",
        A33: "Règles de points",
        A35: "Récompenses",
        A37: "Séries d’apprentissage",
        A38: "Règles d’accès",
        A44: "Statistiques",
        A43: "Paramètres",
    },
    ar: {
        G01: "الرئيسية",
        G21: "إنشاء حساب",
        G22: "تسجيل الدخول",
        A01: "نظرة عامة",
        A02: "المستخدمون",
        A05: "الأدوار",
        A07: "مسارات التعلم",
        A09: "الوحدات",
        A11: "الفئات",
        A13: "الدروس",
        A16: "التمارين",
        A20: "الامتحانات",
        A25: "الموارد",
        A27: "الفيديوهات",
        A29: "المواضيع",
        A31: "المحادثات",
        A33: "قواعد النقاط",
        A35: "الإنجازات",
        A37: "إعدادات المواظبة",
        A38: "قواعد الوصول",
        A44: "التحليلات",
        A43: "إعدادات المنصة",
    },
};
$("[data-language]")?.addEventListener("change", (event) => {
    const language = event.target.value;
    document.documentElement.lang = language;
    document.documentElement.dir = language === "ar" ? "rtl" : "ltr";
    $$("[data-nav-id]").forEach((node) => {
        const index = navKeys.indexOf(node.dataset.navId);
        node.textContent =
            language === "en"
                ? originalLabels.get(node)
                : index >= 0
                  ? navigationTranslations[language][index]
                  : extraNavigationTranslations[language][node.dataset.navId] ||
                    originalLabels.get(node);
    });
    $(".locale-notice")?.remove();
    if (language !== "en") {
        const notice = document.createElement("p");
        notice.className = "locale-notice";
        notice.textContent =
            language === "ar"
                ? "معاينة اتجاه الصفحة والتنقل. محتوى دروس اللغة الإنجليزية معروض بالإنجليزية."
                : "Aperçu de la navigation et de la mise en page. Le contenu pédagogique reste en anglais.";
        $(".topbar").after(notice);
    }
    $$(".reading,.message-bubble p,.example-block,.formula").forEach((node) => {
        node.dir = "ltr";
        node.lang = "en";
    });
});
