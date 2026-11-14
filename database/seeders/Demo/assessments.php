<?php

use App\Support\QuestionType as Q;

$bac = '2nd Baccalaureate · Demo';
$everyday = 'Everyday English · A2–B1';

return [
    [$bac, 'Gifts of youth', 'exercise', 'Passive voice practice',
        'Edit sentences for the school bulletin. Preserve the original tense and check agreement with the new subject.',
        ['The passive voice'], [
            [Q::SingleChoice, 'Volunteers built the library. Which sentence keeps the meaning and uses the passive?', [
                'a' => 'The library was built by volunteers.', 'b' => 'The library is built by volunteers.',
                'c' => 'The volunteers were built by the library.',
            ], 'a', 'Was + built preserves the past tense and puts the library first.', 1],
            [Q::FillInTheBlanks, 'Complete the past passive: Forty books ___ on Monday. (collect)', ['verb' => 'Past passive verb'],
                ['verb' => ['were collected']], 'Books is plural, so use were. Collected is the past participle.', 2],
            [Q::MultipleSelection, 'Which lines describe completed past events in the passive?', [
                'a' => 'The certificates were printed at noon.', 'b' => 'Members printed the certificates at noon.',
                'c' => 'The hall was decorated on Friday.', 'd' => 'The hall is decorated every Friday.',
            ], ['a', 'c'], 'Were printed and was decorated describe completed past events in the passive.', 2],
            [Q::ShortAnswer, 'Rewrite using “The equipment” as the subject. Omit the agent: “The committee will replace the equipment.”',
                [], ['The equipment will be replaced', 'The equipment will be replaced.'], 'Future passive uses will be + past participle.', 2],
        ], null],
    [$bac, 'Gifts of youth', 'exercise', 'From meeting notes to a newsletter',
        'Choose language for a club meeting, then organise a short report about the resulting event.',
        ['Making suggestions in a club meeting', 'A short report for the school newsletter'], [
            [Q::SingleChoice, 'Which suggestion is grammatically complete?', [
                'a' => 'How about meet after class?', 'b' => 'How about meeting after class?', 'c' => 'Why don’t we meeting after class?',
            ], 'b', 'How about takes an -ing form. Why don’t we takes a base verb.', 1],
            [Q::Matching, 'Match each contribution to the strength it demonstrates.', [
                ['a' => 'Tries several ways to fix a stubborn brake.', 'b' => 'Suggests a booking system before anyone asks.', 'c' => 'Explains the same step calmly to three beginners.'],
                ['x' => 'Initiative', 'y' => 'Patience', 'z' => 'Persistence'],
            ], ['a' => 'z', 'b' => 'x', 'c' => 'y'], 'Persistence means continuing despite difficulty. Initiative means acting without waiting to be told. Patience means staying calm while others learn.', 3],
            [Q::Ordering, 'Put these newsletter sentences in order: lead, measurable result, next step.', [
                'next' => 'A second exchange is planned for March.', 'lead' => 'On Tuesday, the English club held a book exchange in the courtyard.',
                'result' => 'Students exchanged 56 books during the lunch break.',
            ], ['lead', 'result', 'next'], 'Introduce the event before giving its result. End with what happens next.', 2],
        ], null],
    [$bac, 'Gifts of youth', 'exam', 'Gifts of youth · Grammar review',
        'You have 12 minutes. Check passive forms, choose when to name the agent, and edit a youth-club announcement.',
        ['The passive voice'], [
            [Q::TrueFalse, '“The invitations were sent yesterday” uses the passive voice.', [], true,
                'Were + sent is the past passive form. The invitations receive the action.', 1],
            [Q::FillInTheBlanks, 'The certificates ___ next Thursday. (award, future passive)', ['verb' => 'Future passive verb'],
                ['verb' => ['will be awarded']], 'Use will be followed by awarded.', 2],
            [Q::SingleChoice, 'The newsletter has just mentioned the library renovation. Which line now credits the club for paying for it?', [
                'a' => 'The renovation was funded in April.', 'b' => 'The renovation will fund the youth club.',
                'c' => 'The renovation was funded by the youth club.',
            ], 'c', 'The by-phrase identifies the youth club as the funder and keeps the renovation as the subject.', 2],
            [Q::MultipleSelection, 'Which bulletin lines have correct passive agreement?', [
                'a' => 'Three awards were presented.', 'b' => 'The main prize were donated by a local shop.',
                'c' => 'The entries are checked every week.', 'd' => 'The volunteers was thanked.',
            ], ['a', 'c'], 'Plural subjects take were or are. Prize is singular, and volunteers is plural.', 2],
            [Q::ShortAnswer, 'Give only the past participle of “choose” to complete “Two designs were ___.”', [], ['chosen'],
                'Choose–chose–chosen. After were, the passive requires chosen.', 1],
        ], 12],
    [$bac, 'Sustainable development', 'exercise', 'Forecasts for a greener school',
        'Practise conservation vocabulary, advice and first conditional plans.',
        ['Water, waste and everyday choices', 'First conditional for practical plans'], [
            [Q::Matching, 'Match the action to the process it illustrates.', [
                ['a' => 'Turn glass bottles into new glass.', 'b' => 'Bring the same bottle every day.', 'c' => 'Close a dripping tap properly.'],
                ['x' => 'Conserve water', 'y' => 'Recycle', 'z' => 'Reuse'],
            ], ['a' => 'y', 'b' => 'z', 'c' => 'x'], 'Recycling transforms material. Reuse keeps the same object. Closing the tap avoids water waste.', 3],
            [Q::FillInTheBlanks, 'If the club ___ the taps, it ___ less water. (repair / waste; use will for the result)',
                ['condition' => 'Condition', 'result' => 'Predicted result'], ['condition' => ['repairs'], 'result' => ['will waste']],
                'Use repairs with the singular subject club and will waste in the result clause.', 2],
            [Q::SingleChoice, 'Which sentence gives advice without presenting it as a rule?', [
                'a' => 'Students must use the new bins.', 'b' => 'Students should bring reusable bottles.', 'c' => 'Students are required to bring a bottle.',
            ], 'b', 'Should suggests a good action. Must and are required to indicate an obligation.', 1],
            [Q::TrueFalse, 'The sentence “We may save water if we fix the taps” presents the result as uncertain.', [], true,
                'May expresses possibility. The speaker does not promise that the result will happen.', 1],
        ], null],
    [$bac, 'Sustainable development', 'exam', 'Reading evidence and recommending a change',
        'Read this brief for all questions: In May, a lycée counted 90 food wrappers over five days. In June it added sorting signs and counted 63 wrappers over five days. Two classes were away in June. Judge the evidence and write language for a follow-up proposal.',
        ['What the refill station changed', 'An evidence-based proposal'], [
            [Q::SingleChoice, 'Which conclusion is justified by the brief?', [
                'a' => 'The signs definitely caused the fall.', 'b' => 'The count fell, but the reason is uncertain.', 'c' => 'Every student threw away fewer wrappers.',
            ], 'b', 'The total fell by 27. Lower attendance makes the cause uncertain.', 2],
            [Q::ShortAnswer, 'How many fewer wrappers were counted in June? Give the number only.', [], ['27', 'twenty-seven'],
                'Subtract the June count from the May count: 90 − 63 = 27.', 1],
            [Q::MultipleSelection, 'Which two actions would make the next comparison more useful?', [
                'a' => 'Record daily attendance.', 'b' => 'Count in a different area each day.',
                'c' => 'Use the same bins and length of observation.', 'd' => 'Stop counting on the quietest day.',
            ], ['a', 'c'], 'Comparable coverage and attendance data make the figures easier to interpret.', 2],
            [Q::FillInTheBlanks, 'If we ___ attendance, we will be able to compare rates per student. (record)',
                ['verb' => 'Condition verb'], ['verb' => ['record']], 'With we, the present simple is record. The result clause already has will.', 1],
            [Q::Ordering, 'Order these parts of a proposal: baseline, action, follow-up measurement.', [
                'check' => 'At the end of each week, compare wrappers per student.', 'act' => 'Keep the signs in place for two more weeks.',
                'base' => 'Our baseline count was 90 wrappers over five school days.',
            ], ['base', 'act', 'check'], 'Give the starting evidence, state the change and specify how its outcome will be checked.', 2],
        ], null],
    [$bac, 'Citizenship', 'exercise', 'Minutes from a youth council meeting',
        'Turn direct speech into accurate meeting minutes and identify the action students need to take.',
        ['Reading a neighbourhood consultation notice', 'Reported speech in a meeting summary'], [
            [Q::SingleChoice, 'Salma asked, “Is the library open?” Which version uses correct reported-question word order?', [
                'a' => 'Salma asked whether was the library open.', 'b' => 'Salma asked whether the library was open.',
                'c' => 'Salma asked whether the library open was.',
            ], 'b', 'After whether, use statement order: subject + verb.', 2],
            [Q::FillInTheBlanks, 'Use backshift: Adam said, “I need more time.” Adam said that he ___ more time.',
                ['verb' => 'Backshifted verb'], ['verb' => ['needed']], 'The reporting verb is in the past. The exercise calls for backshift from need to needed.', 2],
            [Q::TrueFalse, '“Written suggestions must reach the centre by Thursday” means sending them on Thursday is enough even if they arrive Friday.',
                [], false, 'Reach sets an arrival deadline. Sending and arriving are different events.', 1],
            [Q::SingleChoice, 'Which line expresses disagreement respectfully and supplies a reason?', [
                'a' => 'That is obviously a bad plan.', 'b' => 'I’m against it.',
                'c' => 'I see why the gym is convenient, but the library has step-free access.',
            ], 'c', 'The speaker acknowledges the other view and offers a relevant reason.', 1],
        ], null],
    [$everyday, 'Finding your way', 'exercise', 'From the station to the clinic',
        'Follow the directions carefully. Check the difference between passing a landmark and turning at it.',
        ['Landmarks and route instructions', 'Checking directions politely'], [
            [Q::SingleChoice, '“Go past the pharmacy.” What should you do at the pharmacy?', [
                'a' => 'Continue beyond it.', 'b' => 'Enter it.', 'c' => 'Cross the road to it.',
            ], 'a', 'Go past tells you to continue beyond the landmark.', 1],
            [Q::Ordering, 'Order the instructions you heard: first pass the bakery, then turn at the bank. The clinic is opposite the post office.',
                ['clinic' => 'Look for the clinic opposite the post office.', 'bakery' => 'Walk past the bakery.', 'bank' => 'Turn at the bank.'],
                ['bakery', 'bank', 'clinic'], 'Pass the bakery before turning. The post office helps you locate the destination.', 2],
            [Q::SingleChoice, 'You missed whether the speaker said platform 2 or 3. Which reply checks that detail politely?', [
                'a' => 'Say the number again.', 'b' => 'Sorry, did you say platform 2 or platform 3?', 'c' => 'Which train is fastest?',
            ], 'b', 'Repeating both possibilities makes it easy for the speaker to confirm the platform.', 1],
        ], null],
    [$everyday, 'Appointments and follow-up', 'exercise', 'Confirm or reschedule?',
        'Identify fixed appointments and choose a clear next step when a time changes.',
        ['Rescheduling an appointment'], [
            [Q::SingleChoice, 'Which message specifies a confirmed arrangement?', [
                'a' => 'I might visit the adviser soon.', 'b' => 'I’m seeing the adviser at four on Tuesday.', 'c' => 'I’d like to see an adviser one day.',
            ], 'b', 'The present continuous with an exact time describes an arrangement.', 1],
            [Q::MultipleSelection, 'Which details help the recipient identify and move an appointment?', [
                'a' => 'The original date and time.', 'b' => 'A list of unrelated plans.', 'c' => 'Two specific alternatives.', 'd' => 'An old booking for another service.',
            ], ['a', 'c'], 'The original slot identifies the booking and the alternatives support a prompt reply.', 2],
            [Q::TrueFalse, 'If you offer Thursday at 10 a.m., you can assume the booking has changed before the other person replies.', [], false,
                'An offered time is a proposal. Wait for confirmation before treating it as booked.', 1],
        ], null],
    [$bac, 'Citizenship', 'exercise', 'Editing a consultation summary',
        'Polish an update before it goes to the youth council. Check attribution, word order and deadlines.',
        ['Reported speech in a meeting summary', 'A formal request to a local association'], [
            [Q::SingleChoice, 'Which sentence attributes the proposal to its source without presenting it as an agreed decision?', [
                'a' => 'The youth centre will certainly stay open later.',
                'b' => 'Residents proposed keeping the youth centre open later.',
                'c' => 'The youth centre decided that residents would stay later.',
            ], 'b', 'Proposed describes a suggestion and residents identifies who made it.', 2],
            [Q::ShortAnswer, 'Rewrite using backshift: “Is the room available?” Start with “They asked whether”.', [],
                ['They asked whether the room was available', 'They asked whether the room was available.'],
                'Use subject–verb order after whether and backshift is to was.', 2],
            [Q::TrueFalse, 'If the centre requests a reply by noon on Friday, a reply sent at 3 p.m. on Friday meets the deadline.', [], false,
                'By noon means no later than 12 p.m. A reply at 3 p.m. is late.', 1],
        ], null],
];
