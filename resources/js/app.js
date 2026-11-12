import "./question-authoring";

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

// Native dialogs contain focus, support Escape, and restore focus on close.
const dialogTriggers = new WeakMap();
function openDialog(id, trigger) {
    const dialog = document.getElementById(id);
    dialogTriggers.set(dialog, trigger);
    dialog.showModal();
}
$$("[data-open-directory]").forEach((button) =>
    button.addEventListener("click", (event) => {
        event.preventDefault();
        openDialog("screen-directory", button);
    }),
);
$$("[data-open-nav]").forEach((button) =>
    button.addEventListener("click", () =>
        openDialog("mobile-navigation", button),
    ),
);
$$("[data-open-dialog]").forEach((button) =>
    button.addEventListener("click", () =>
        openDialog(button.dataset.openDialog, button),
    ),
);
$$("[data-close-dialog]").forEach((button) =>
    button.addEventListener("click", () => button.closest("dialog").close()),
);
$$("dialog").forEach((dialog) =>
    dialog.addEventListener("close", () => dialogTriggers.get(dialog)?.focus()),
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
        const container =
            button.closest("form,article,section,.video-stage") || document;
        if (button.hasAttribute("data-login-error")) {
            $("[data-success-actions]", container).hidden = true;
        }
        feedback(
            container,
            button.dataset.simulate,
            button.dataset.tone || "success",
        );
    }),
);
$$("[data-prototype-form]").forEach((form) => {
    const loginError = $("[data-login-error]", form);
    const resourceFile = $("[data-resource-file]", form);
    const actions = $("[data-success-actions]", form);
    const requiredSelections = $$("[data-required-selection]", form);
    const requiredText = $$("[data-required-text]", form);
    const blankAnswers = $("#blank-answers", form);
    form.noValidate = true;
    form.addEventListener("submit", (event) => {
        event.preventDefault();
        if (loginError) {
            actions.hidden = true;
            $("[data-feedback]", form).hidden = true;
        }
        $(".validation-summary", form)?.remove();
        $$("[aria-invalid]", form).forEach((field) => {
            field.removeAttribute("aria-invalid");
            field.removeAttribute("aria-describedby");
            if (document.getElementById(field.id + "-help")) {
                field.setAttribute("aria-describedby", field.id + "-help");
            }
        });
        if (resourceFile) {
            $("[data-feedback]", form).hidden = true;
            const file = resourceFile.files[0];
            let error = "";
            if (!file && resourceFile.required) {
                error = "Choose a resource file.";
            } else if (file && !file.name.toLowerCase().endsWith(".pdf")) {
                error = "Choose a PDF file.";
            } else if (file && file.size === 0) {
                error = "Choose a PDF file that is not empty.";
            } else if (file && file.size > 10 * 1024 * 1024) {
                error = "Choose a PDF file of 10 MiB or less.";
            }
            resourceFile.setCustomValidity(error);
        }
        requiredSelections.forEach((group) => {
            const first = $('input[type="radio"],input[type="checkbox"]', group);
            first.setCustomValidity(
                $("input:checked", group) ? "" : group.dataset.requiredSelection,
            );
        });
        requiredText.forEach((field) => {
            field.setCustomValidity(field.value.trim() ? "" : "Enter a value.");
        });
        if (blankAnswers?.willValidate && blankAnswers.value.trim()) {
            const prompt = $("#question-prompt", form).value;
            const blanks = (prompt.match(/___/g) || []).length;
            const answers = blankAnswers.value.trim().split(/\r?\n/);
            const hasEmptyAnswer = answers.some((line) =>
                line.split("|").some((answer) => !answer.trim()),
            );
            if (blanks !== answers.length || hasEmptyAnswer) {
                blankAnswers.setCustomValidity("Enter a line of accepted answers for each ___ in the prompt.");
            }
        }
        const invalid = $$("input,select,textarea", form).filter(
            (field) => field.willValidate && !field.validity.valid &&
                (field.type !== "radio" || field.required || field.validity.customError),
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
                    field.dataset.validationLabel ||
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
        if (form.hasAttribute("data-attempt-form")) {
            location.assign(form.dataset.resultUrl);
            return;
        }
        if (form.hasAttribute("data-question-editor")) {
            saveQuestionPreview(form);
            return;
        }
        const button = $('button[type="submit"],button:not([type])', form);
        const original = button?.textContent;
        if (button) {
            button.disabled = true;
            button.textContent = "Previewing…";
            button.setAttribute("aria-busy", "true");
        }
        if (loginError) loginError.disabled = true;
        window.setTimeout(() => {
            feedback(form, form.dataset.success);
            if (button) {
                button.disabled = false;
                button.textContent = original;
                button.removeAttribute("aria-busy");
            }
            if (loginError) loginError.disabled = false;
            if (actions) actions.hidden = false;
        }, 350);
    });
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
    if ($("[data-result-count]")) {
        const label = $("[data-live-question-list]") ? "questions" : "sample results";
        $("[data-result-count]").textContent = `${visible} of ${items.length} ${label}`;
    }
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
const questionType = $("[data-question-type]");
function showQuestionFields() {
    $$("[data-question-fields]", questionType.form).forEach((fields) => {
        const active = fields.dataset.questionFields.split("|").includes(questionType.value);
        fields.hidden = !active;
        fields.disabled = !active;
    });
}
const questionPrompts = {
    "Multiple choice": "Choose the correct passive sentence.",
    "Multiple selection": "Select all past participles.",
    "True / False": "“The room was cleaned” is in the passive voice.",
    "Fill in the blanks": "English ___ spoken here.",
    "Short answer": "Rewrite in the passive: They built the school.",
};
questionType?.addEventListener("change", () => {
    const prompt = $("#question-prompt", questionType.form);
    if (!prompt.value.trim() || Object.values(questionPrompts).includes(prompt.value)) {
        prompt.value = questionPrompts[questionType.value] || prompt.value;
    }
    showQuestionFields();
});

function questionPreviewKey(url) {
    return "question-preview:" + new URL(url, location.href).pathname;
}

function fixtureQuestions(fixtures) {
    return fixtures.map(([prompt, type, status], index) => ({
        id: String(index + 1), prompt, type, status,
    }));
}

function loadQuestionPreview(url, fixtures) {
    const stored = sessionStorage.getItem(questionPreviewKey(url));
    return stored === null ? fixtureQuestions(fixtures) : JSON.parse(stored);
}

function saveQuestionPreview(form) {
    const url = form.dataset.questionEditor;
    const questions = loadQuestionPreview(url, JSON.parse(form.dataset.questionFixtures));
    const values = new FormData(form);
    const entry = {
        id: form.dataset.editQuestion || crypto.getRandomValues(new Uint32Array(4)).join("-"),
        prompt: values.get("question-prompt").trim(),
        type: values.get("question-type"),
        status: values.get("publication-status") === "Published" ? "Ready" : values.get("publication-status"),
        values: [...values],
    };
    const index = questions.findIndex(({ id }) => id === entry.id);
    if (index === -1) questions.push(entry);
    else questions[index] = entry;
    sessionStorage.setItem(questionPreviewKey(url), JSON.stringify(questions));
    location.assign(url);
}

if (questionType) {
    const form = questionType.form;
    const url = form.dataset.questionEditor;
    const id = new URLSearchParams(location.search).get("question");
    const record = loadQuestionPreview(url, JSON.parse(form.dataset.questionFixtures))
        .find((question) => question.id === id);
    if (record) {
        form.dataset.editQuestion = record.id;
        questionType.value = record.type;
        $("#question-prompt", form).value = record.prompt;
        $("#publication-status", form).value = record.status === "Ready" ? "Published" : record.status;
        if (record.values) {
            const values = new URLSearchParams(record.values);
            for (const field of form.elements) {
                if (!values.has(field.name)) continue;
                if (field.type === "checkbox" || field.type === "radio") {
                    field.checked = values.getAll(field.name).includes(field.value);
                } else {
                    field.value = values.get(field.name);
                }
            }
        }
    }
    showQuestionFields();
}

const questionList = $("[data-question-list]");
if (questionList) {
    const addQuestion = $("[data-add-question]");
    const template = questionList.firstElementChild.cloneNode(true);
    const fixtures = [...questionList.rows].map((row) => [
        $("strong", row).textContent, row.cells[1].textContent, row.dataset.filterType,
    ]);
    const questions = loadQuestionPreview(location.href, fixtures);
    const status = $("[data-content-type]");
    for (const value of new Set(questions.map((question) => question.status))) {
        if (![...status.options].some((option) => option.value === value)) {
            status.add(new Option(value));
        }
    }

    function renderQuestions() {
        questionList.replaceChildren(...questions.map((question) => {
            const row = template.cloneNode(true);
            row.dataset.questionId = question.id;
            row.dataset.filterType = question.status;
            $("strong", row).textContent = question.prompt;
            row.cells[1].textContent = question.type;
            const badge = $(".badge", row);
            badge.textContent = question.status;
            badge.classList.toggle("badge-success", question.status === "Ready");
            badge.classList.toggle("badge-neutral", question.status !== "Ready");
            const edit = $(".table-action", row);
            const url = new URL(addQuestion.href);
            url.search = new URLSearchParams({ mode: "edit", question: question.id });
            edit.href = url;
            $(".sr-only", edit).textContent = question.prompt;
            for (const button of $$("[data-question-move]", row)) {
                button.setAttribute("aria-label", `Move ${question.prompt} ${button.dataset.questionMove}`);
            }
            $("[data-question-remove]", row).setAttribute("aria-label", `Remove ${question.prompt}`);
            return row;
        }));
        $("[data-empty-heading]").textContent = questions.length ? "No matching records" : "No questions yet";
        $("[data-empty-hint]").textContent = questions.length ? "Try another name or status." : "Add a question to start the list.";
        $("[data-reset-filters]").hidden = questions.length === 0;
        filterContent();
    }

    renderQuestions();
    questionList.addEventListener("click", (event) => {
        const button = event.target.closest("[data-question-remove], [data-question-move]");
        if (!button) return;

        const row = button.closest("tr");
        const visible = [...questionList.rows].filter((item) => !item.hidden);
        const position = visible.indexOf(row);
        const index = questions.findIndex(({ id }) => id === row.dataset.questionId);
        const direction = button.dataset.questionMove;
        let focusRow = row.dataset.questionId;
        const output = $("[data-list-feedback]");
        output.hidden = false;
        if (direction) {
            const neighbor = visible[position + (direction === "up" ? -1 : 1)];
            if (!neighbor) {
                output.textContent = direction === "up" ? "This question is already first." : "This question is already last.";
                return;
            }
            const destination = questions.findIndex(({ id }) => id === neighbor.dataset.questionId);
            const [question] = questions.splice(index, 1);
            questions.splice(destination, 0, question);
            output.textContent = "Question order updated in this preview.";
        } else {
            const [removed] = questions.splice(index, 1);
            focusRow = (visible[position + 1] || visible[position - 1])?.dataset.questionId;
            output.textContent = `Removed ${removed.prompt} from this preview.`;
        }
        sessionStorage.setItem(questionPreviewKey(location.href), JSON.stringify(questions));
        renderQuestions();
        const nextRow = [...questionList.rows].find((item) => item.dataset.questionId === focusRow && !item.hidden);
        const nextControl = nextRow && $(direction ? `[data-question-move="${direction}"]` : ".table-action", nextRow);
        (nextControl || addQuestion).focus();
    });
}
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

$("[data-language]")?.addEventListener("change", (event) => {
    event.target.form.requestSubmit();
});

$("[data-live-errors]")?.focus();
const curriculumUnit = $("[data-curriculum-unit]");
curriculumUnit?.addEventListener("change", () => {
    const group = curriculumUnit.selectedOptions[0]?.closest("optgroup");
    $("[data-curriculum-level]").value = group?.label || "";
});

$$("[data-live-form]").forEach((form) => {
    let pendingButton;
    let label;

    form.addEventListener("submit", (event) => {
        if (pendingButton) {
            event.preventDefault();
            return;
        }

        pendingButton = event.submitter || $("[data-pending]", form);
        if (!pendingButton) return;

        label = pendingButton.textContent;
        pendingButton.style.minInlineSize = `${pendingButton.offsetWidth}px`;
        pendingButton.textContent = pendingButton.dataset.pending;
        pendingButton.disabled = true;
        pendingButton.setAttribute("aria-busy", "true");
        form.setAttribute("aria-busy", "true");
    });

    window.addEventListener("pageshow", () => {
        if (!pendingButton) return;
        pendingButton.disabled = false;
        pendingButton.removeAttribute("aria-busy");
        pendingButton.style.removeProperty("min-inline-size");
        pendingButton.textContent = label;
        pendingButton = null;
        form.removeAttribute("aria-busy");
    });
});
