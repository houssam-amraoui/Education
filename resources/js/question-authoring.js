const form = document.querySelector("[data-question-authoring]");

if (form) {
    const type = form.querySelector("[data-authoring-type]");
    const fields = form.querySelector("[data-authoring-fields]");
    const definition = JSON.parse(fields.dataset.definition);
    const initialType = type.value;
    const sections = new Map();

    function element(tag, attributes = {}, text = "") {
        const node = document.createElement(tag);
        for (const [name, value] of Object.entries(attributes)) node.setAttribute(name, value);
        node.textContent = text;
        return node;
    }

    function list(value) {
        return Array.isArray(value) ? value : [];
    }

    function newItem(label = "") {
        return { id: "item-" + crypto.getRandomValues(new Uint32Array(4)).join("-"), label };
    }

    function button(label, action) {
        const control = element("button", { type: "button", class: "button button-secondary" }, label);
        control.addEventListener("click", action);
        return control;
    }

    function field(label, control) {
        control.id = control.name.replaceAll("[", "-").replaceAll("]", "");
        const wrapper = element("div", { class: "field" });
        wrapper.append(element("label", { for: control.id }, label), control);
        return wrapper;
    }

    function textField(label, name, value, update) {
        const tag = typeof value === "string" && value.includes("\n") ? "textarea" : "input";
        const input = element(tag, { name, required: "", maxlength: "2000", dir: "auto" });
        input.value = typeof value === "string" ? value : "";
        input.addEventListener("input", () => update(input.value));
        return field(label, input);
    }

    function selection(label, name, value, selected, update, multiple = false) {
        const wrapper = element("label", { class: "choice" });
        const input = element("input", { type: multiple ? "checkbox" : "radio", name, value });
        input.checked = selected;
        input.required = !multiple;
        input.addEventListener("change", () => update(input.checked));
        wrapper.append(input, ` ${label}`);
        return wrapper;
    }

    function selectField(label, name, choices, value, update) {
        const select = element("select", { name, required: "", dir: "auto" });
        select.append(element("option", { value: "" }, "Choose an item"));
        choices.forEach((item, index) => {
            select.append(element("option", { value: item.id }, item.label || `Item ${index + 1}`));
        });
        select.value = choices.some((item) => item.id === value) ? value : "";
        select.addEventListener("change", () => update(select.value));
        return field(label, select);
    }

    function repeat(container, values, label, renderRow, add, minimum) {
        const rows = element("div", { class: "stack" });
        const addButton = button(`Add ${label.toLowerCase()}`, () => {
            values.push(add());
            refresh();
            rows.lastElementChild.querySelector("input:not([type=hidden]), select, textarea")?.focus();
        });

        function refresh() {
            rows.replaceChildren(...values.map((value, index) => {
                const row = element("div", { class: "stack" });
                renderRow(row, value, index);
                const remove = button(`Remove ${label.toLowerCase()} ${index + 1}`, () => {
                    values.splice(index, 1);
                    refresh();
                    (rows.children[index]?.querySelector("input:not([type=hidden]), select, textarea") || addButton).focus();
                });
                remove.disabled = values.length <= minimum;
                row.append(remove);
                return row;
            }));
            addButton.disabled = values.length >= 50;
        }

        refresh();
        container.append(rows, addButton);
        return refresh;
    }

    function items(container, group, values, label, extra = () => {}, changed = () => {}, minimum = 2) {
        return repeat(container, values, label, (row, item, index) => {
            const name = `content[${group}][${index}]`;
            row.append(
                element("input", { type: "hidden", name: `${name}[id]`, value: item.id }),
                textField(`${label} ${index + 1}`, `${name}[label]`, item.label, (value) => {
                    item.label = value;
                    changed();
                }),
            );
            extra(row, item, index);
        }, () => newItem(), minimum);
    }

    function accepted(container, values, name) {
        repeat(container, values, "Accepted answer", (row, value, index) => {
            row.append(textField(`Accepted answer ${index + 1}`, `${name}[${index}]`, value, (answer) => {
                values[index] = answer;
            }));
        }, () => "", 1);
    }

    function itemList(value, minimum) {
        const values = list(value).map((item) => ({
            id: typeof item?.id === "string" ? item.id : newItem().id,
            label: typeof item?.label === "string" ? item.label : "",
        }));
        while (values.length < minimum) values.push(newItem());
        return values;
    }

    function render() {
        if (sections.has(type.value)) {
            fields.replaceChildren(sections.get(type.value));
            return;
        }
        const draft = type.value === initialType ? definition : {};
        const content = draft.content ?? {};
        const answer = draft.answer ?? {};
        const section = element("fieldset", { id: "answer" });
        sections.set(type.value, section);
        fields.replaceChildren(section);

        switch (type.value) {
            case "single_choice":
            case "multiple_selection": {
                const multiple = type.value === "multiple_selection";
                const options = itemList(content.options, 2);
                const selected = new Set(multiple ? list(answer.option_ids) : [answer.option_id]);
                section.append(element("legend", {}, multiple ? "Options and correct answers" : "Options and correct answer"));
                items(section, "options", options, "Option", (row, option, index) => {
                    row.append(selection(`Mark option ${index + 1} correct`, multiple ? "answer[option_ids][]" : "answer[option_id]",
                        option.id, selected.has(option.id), (checked) => {
                            if (!multiple) selected.clear();
                            if (checked) selected.add(option.id);
                            else selected.delete(option.id);
                        }, multiple));
                });
                break;
            }
            case "true_false":
                section.append(element("legend", {}, "Correct answer"));
                for (const [label, value] of [["True", "1"], ["False", "0"]]) {
                    section.append(selection(label, "answer[value]", value,
                        [value, value === "1"].includes(answer.value), () => {}));
                }
                break;
            case "matching": {
                const left = itemList(content.left, 2);
                const right = itemList(content.right, 2);
                const pairs = new Map(list(answer.pairs).map((pair) => [pair?.left_id, pair?.right_id]));
                section.append(element("legend", {}, "Answer options and correct mapping"));
                const rightFields = element("div", { class: "stack" });
                section.append(rightFields);
                const refreshLeft = items(section, "left", left, "Left item", (row, item, index) => {
                    row.append(element("input", { type: "hidden", name: `answer[pairs][${index}][left_id]`, value: item.id }),
                        selectField(`Match left item ${index + 1} with`, `answer[pairs][${index}][right_id]`, right,
                            pairs.get(item.id), (value) => pairs.set(item.id, value)));
                });
                items(rightFields, "right", right, "Right item", () => {}, refreshLeft);
                rightFields.addEventListener("click", (event) => {
                    if (event.target.closest("button")) refreshLeft();
                });
                break;
            }
            case "fill_in_the_blanks": {
                const slots = itemList(content.slots, 0);
                if (!slots.length) slots.push(newItem("Blank 1"));
                const answers = new Map(list(answer.slots).map((slot) => [slot?.id, list(slot?.accepted)]));
                section.append(element("legend", {}, "Blank answers"),
                    element("p", {}, "Use ___ for each blank in the prompt. Add the blanks in prompt order."));
                items(section, "slots", slots, "Blank", (row, slot, index) => {
                    if (!answers.get(slot.id)?.length) answers.set(slot.id, [""]);
                    const name = `answer[slots][${index}]`;
                    row.append(element("input", { type: "hidden", name: `${name}[id]`, value: slot.id }));
                    accepted(row, answers.get(slot.id), `${name}[accepted]`);
                }, undefined, 1);
                break;
            }
            case "short_answer": {
                const answers = list(answer.accepted);
                section.append(element("legend", {}, "Short answer"));
                accepted(section, answers.length ? answers : [""], "answer[accepted]");
                break;
            }
            case "ordering": {
                const options = itemList(content.items, 2);
                const order = list(answer.item_ids);
                section.append(element("legend", {}, "Answer options and correct order"));
                const orderFields = element("div", { class: "stack" });
                function refreshOrder() {
                    orderFields.replaceChildren(...options.map((_, index) => selectField(`Position ${index + 1}`,
                        `answer[item_ids][${index}]`, options, order[index], (value) => { order[index] = value; })));
                }
                const itemFields = element("div", { class: "stack" });
                section.append(itemFields, orderFields);
                items(itemFields, "items", options, "Item", () => {}, refreshOrder);
                itemFields.addEventListener("click", (event) => {
                    if (event.target.closest("button")) refreshOrder();
                });
                refreshOrder();
                break;
            }
        }
    }

    type.addEventListener("change", render);
    form.addEventListener("formdata", ({ formData }) => {
        if (type.value !== "fill_in_the_blanks") return;

        // Keep large blank definitions within PHP's form-field count limit.
        const alternatives = new Map();
        for (const [name, value] of [...formData]) {
            const group = name.match(/^(answer\[slots\]\[\d+\]\[accepted\])\[\d+\]$/)?.[1];
            if (!group) continue;
            if (!alternatives.has(group)) alternatives.set(group, []);
            alternatives.get(group).push(value);
            formData.delete(name);
        }
        for (const [name, values] of alternatives) formData.set(name, JSON.stringify(values));
    });
    render();
    form.querySelector("[data-authoring-save]").disabled = false;
}
