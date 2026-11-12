import { questionAnswers } from "./question-answers";

const form = document.querySelector("[data-learner-attempt]");

if (form) {
    const submit = form.querySelector("[data-attempt-submit]");
    const label = submit.querySelector("span");
    const summary = form.querySelector("[data-attempt-errors]");
    const questions = new Map([...form.querySelectorAll("[data-learner-question]")]
        .map((question) => [question.dataset.learnerQuestion, question]));
    const timer = document.querySelector("[data-attempt-deadline]");

    function showErrors(errors) {
        const messages = [];
        for (const question of questions.values()) {
            const output = question.querySelector("[data-question-error]");
            output.textContent = "";
            output.hidden = true;
            question.removeAttribute("aria-invalid");
        }

        for (const [key, errorsForField] of Object.entries(errors)) {
            const question = questions.get(key.match(/^answers\.(\d+)(?:\.|$)/)?.[1]);
            const item = document.createElement("li");
            if (question) {
                const message = errorsForField.join(" ");
                const output = question.querySelector("[data-question-error]");
                output.textContent = [output.textContent, message].filter(Boolean).join(" ");
                output.hidden = false;
                question.setAttribute("aria-invalid", "true");
                const link = document.createElement("a");
                link.href = "#" + question.id;
                link.textContent = question.querySelector("legend").textContent.trim() + " " + message;
                item.append(link);
            } else {
                item.textContent = errorsForField.join(" ");
            }
            messages.push(item);
        }

        summary.querySelector("ul").replaceChildren(...messages);
        summary.hidden = messages.length === 0;
        if (messages.length) summary.focus();
    }

    const submissionError = "We couldn’t confirm your submission. Your answers are still on this page. Try submitting again.";
    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        if (submit.disabled) return;

        submit.disabled = true;
        label.textContent = "Submitting…";
        form.setAttribute("aria-busy", "true");
        showErrors({});

        try {
            const response = await fetch(form.action, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": form.elements.namedItem("_token").value,
                },
                body: JSON.stringify({ answers: questionAnswers(form) }),
            });

            if (response.ok) {
                window.location.assign(form.dataset.resultUrl);
                return;
            }

            if (response.status === 422) {
                const { errors } = await response.json();
                showErrors(errors);
            } else if ([401, 419].includes(response.status)) {
                showErrors({ attempt: ["Your session expired. Sign in again before retrying."] });
            } else if ([403, 404].includes(response.status)) {
                showErrors({ attempt: ["This attempt is no longer available to your account."] });
            } else {
                showErrors({ attempt: [submissionError] });
            }
        } catch {
            showErrors({ attempt: [submissionError] });
        }
        form.removeAttribute("aria-busy");
        label.textContent = "Submit answers";
        submit.disabled = false;
    });

    if (timer) {
        const deadline = performance.now() + (Number(timer.dataset.attemptDeadline) - Number(timer.dataset.serverTime)) * 1000;
        const update = () => {
            const seconds = Math.max(0, Math.ceil((deadline - performance.now()) / 1000));
            timer.textContent = Math.floor(seconds / 60) + ":" + String(seconds % 60).padStart(2, "0");
            if (seconds > 0) {
                window.setTimeout(update, 1000);
            } else {
                form.noValidate = true;
                form.querySelector("[data-attempt-time-up]").hidden = false;
            }
        };
        update();
    }

    submit.disabled = false;
}
