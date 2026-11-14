<?php

$bac = '2nd Baccalaureate · Demo';
$everyday = 'Everyday English · A2–B1';
$passive = 'The passive voice';
$practice = 'Passive voice practice';
$grammarExam = 'Gifts of youth · Grammar review';
$evidenceExam = 'Reading evidence and recommending a change';
$green = 'Forecasts for a greener school';
$revisionPack = 'A request someone can act on';

return [
    'student' => ['Nour Benjelloun', 'Salé', 'en', 30, 'Improve exam writing and keep tenses consistent.', [$bac, $everyday], 18, [
        [6, 17, 'complete', 'Past participles', []],
        [5, 17, 'video', 'What’s the passive voice? · BBC Learning English', []],
        [5, 18, 'complete', $passive, []],
        [5, 19, 'exercise', $practice, [9, [1 => 'b', 2 => ['verb' => 'was collected'], 4 => 'The equipment will replaced']]],
        [5, 20, 'message', $passive, ['Choosing was or were in a report', 'I wrote “Forty books was collected.” I thought forty books meant one collection. What should I look at to choose was or were?', 'Look at the grammatical subject. Books is plural even if all the books were collected together. Compare “The collection was organised” and “Forty books were collected.”', 'That helps. I’ll underline the subject before I choose the form of be.']],
        [4, 16, 'resource', 'Editing a school bulletin', []],
        [4, 17, 'exercise', $practice, [7, []]],
        [4, 18, 'complete', 'Making suggestions in a club meeting', []],
        [4, 19, 'complete', 'A short report for the school newsletter', []],
        [3, 17, 'exercise', 'From meeting notes to a newsletter', [8, []]],
        [3, 18, 'exam', $grammarExam, [10, [3 => 'a']]],
        [2, 17, 'complete', 'Water, waste and everyday choices', []],
        [2, 18, 'complete', 'First conditional for practical plans', []],
        [2, 19, 'exercise', $green, [9, []]],
        [2, 20, 'complete', 'What the refill station changed', []],
        [1, 15, 'complete', 'An evidence-based proposal', []],
        [1, 16, 'exam', $evidenceExam, [14, []]],
        [1, 17, 'unlock', $revisionPack, []],
        [1, 18, 'resource', $revisionPack, []],
        [1, 20, 'view', 'Reported speech in a meeting summary', []],
    ]],
    'imane' => ['إيمان العلوي', 'فاس', 'ar', 15, 'أريد تحسين فهم النصوص والتدرّب على كتابة الفقرة.', [$bac], 7, [
        [6, 18, 'complete', 'Past participles', []],
        [4, 18, 'complete', $passive, []],
        [4, 19, 'exercise', $practice, [8, [2 => ['verb' => 'were collect'], 3 => ['a', 'd']]]],
        [3, 19, 'exam', $grammarExam, [13, []]],
        [2, 18, 'view', 'A Saturday repair café', []],
        [1, 18, 'message', 'A Saturday repair café', ['الفرق بين الاستنتاج والمعلومة الصريحة', 'ذكرت الفقرة أن العائلات كانت تنتظر قبل العاشرة. هل يمكن أن أستنتج أن المقهى صار معروفاً في المدينة كلها؟', null, null]],
    ]],
    'ilyas' => ['Ilyas Berrada', 'Agadir', 'fr', 45, 'Mieux distinguer les preuves des hypothèses dans les textes.', [$bac], 25, [
        [12, 16, 'complete', 'Water, waste and everyday choices', []],
        [11, 18, 'complete', 'Giving advice with a reason', []],
        [10, 16, 'complete', 'First conditional for practical plans', []],
        [10, 18, 'exercise', $green, [10, [2 => ['condition' => 'will repair', 'result' => 'will waste']]]],
        [5, 18, 'complete', 'What the refill station changed', []],
        [4, 16, 'resource', 'From a count to a claim', []],
        [4, 18, 'complete', 'An evidence-based proposal', []],
        [3, 19, 'exam', $evidenceExam, [16, [1 => 'a', 3 => ['b', 'c']]]],
        [2, 17, 'message', 'What the refill station changed', ['Comment nuancer la conclusion ?', 'Puis-je écrire « The station reduced waste » si le nombre de bouteilles a baissé, mais qu’une classe était absente ?', 'Écris plutôt « The count fell after the station was installed, but attendance was lower. » Cette formulation distingue l’observation de sa cause possible.', null]],
    ]],
    'hanae' => ['Hanae El Mansouri', 'Meknès', 'en', 30, 'Give clear directions and confirm appointment times.', [$everyday], 11, [
        [7, 16, 'complete', 'Landmarks and route instructions', []],
        [5, 17, 'video', 'How to ask for and give directions · BBC Learning English', []],
        [5, 18, 'complete', 'Checking directions politely', []],
        [5, 19, 'exercise', 'From the station to the clinic', [6, []]],
        [2, 18, 'complete', 'A station notice after a platform change', []],
        [2, 19, 'resource', 'Find the right platform', []],
        [1, 17, 'complete', 'Present continuous for arrangements', []],
        [1, 18, 'view', 'Rescheduling an appointment', []],
        [1, 19, 'exercise', 'Confirm or reschedule?', [null, []]],
    ]],
    'adam' => ['Adam Tazi', 'Oujda', 'en', 15, 'Practise under timed exam conditions.', [$bac], 4, [
        [3, 17, 'complete', 'Past participles', []],
        [2, 17, 'complete', $passive, []],
        [2, 18, 'exercise', $practice, [6, [3 => ['a']]]],
        [1, 18, 'exam', $grammarExam, [null, []]],
    ]],
    'safae' => ['Safae Aït Lahcen', 'Taroudant', 'fr', 30, 'Savoir rédiger une demande formelle en anglais.', [$bac], 8, [
        [6, 16, 'complete', 'Rights, responsibilities and participation', []],
        [5, 16, 'complete', 'Reading a neighbourhood consultation notice', []],
        [4, 16, 'complete', 'Reported speech in a meeting summary', []],
        [4, 17, 'exercise', 'Minutes from a youth council meeting', [8, [1 => 'a']]],
        [3, 17, 'exercise', 'Minutes from a youth council meeting', [7, []]],
        [1, 18, 'complete', 'A formal request to a local association', []],
        [1, 19, 'view', 'Agreeing and disagreeing respectfully', []],
        [1, 20, 'access', 'Editing a consultation summary', []],
        [1, 21, 'exercise', 'Editing a consultation summary', [8, []]],
    ]],
    'yacine' => ['ياسين المرابط', 'طنجة', 'ar', 15, 'أبحث عن مسار يساعدني في الاستعداد للامتحان الوطني.', [], 1, []],
    'maya' => ['Maya El Khatib', 'Casablanca', 'en', 45, 'Compare reading strategies across both paths.', [$bac, $everyday], 45, [
        [35, 16, 'complete', 'A Saturday repair café', []],
        [34, 17, 'complete', 'From talent to contribution', []],
        [33, 17, 'complete', 'Making suggestions in a club meeting', []],
        [33, 18, 'complete', 'A short report for the school newsletter', []],
        [33, 19, 'exercise', 'From meeting notes to a newsletter', [8, [3 => ['lead', 'next', 'result']]]],
        [28, 17, 'message', '', ['Planning a weekly study routine', 'I can study for 45 minutes on Sundays. How should I divide that time between reading and writing?', 'Try 15 minutes reading and marking evidence, 20 minutes writing, and 10 minutes checking your work against the task. Keep one example of a change you made.', null]],
    ]],
];
