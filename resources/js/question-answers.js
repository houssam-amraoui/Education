// Convert the renderer's controls to the answer shapes consumed by GradeQuestion.
export function questionAnswers(form) {
    return Object.fromEntries([...form.querySelectorAll("[data-learner-question]")].map((question) => {
        const chosen = [...question.querySelectorAll("input:checked")].map((input) => input.value);
        const items = [...question.querySelectorAll("[data-answer-item]")];
        let answer;

        switch (question.dataset.answerType) {
            case "single_choice":
                answer = chosen[0] ?? null;
                break;
            case "multiple_selection":
                answer = chosen;
                break;
            case "true_false":
                answer = chosen.length ? chosen[0] === "1" : null;
                break;
            case "matching":
            case "fill_in_the_blanks":
                answer = Object.fromEntries(items.map((item) => [item.dataset.answerItem, item.value]));
                break;
            case "short_answer":
                answer = question.querySelector("textarea").value;
                break;
            case "ordering": {
                const positions = items.map((item) => Number(item.value));
                const complete = positions.every((position) => position >= 1 && position <= items.length)
                    && new Set(positions).size === items.length;
                answer = complete ? items.sort((left, right) => Number(left.value) - Number(right.value))
                    .map((item) => item.dataset.answerItem) : [];
                break;
            }
        }

        return [question.dataset.learnerQuestion, answer];
    }));
}
