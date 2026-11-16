@extends('layouts.app')

@section('content')
@include('partials.service-detail', ['d' => [
    'key' => 'ft', 'name' => 'Free 3-Day Trial', 'type' => 'Free trial of architectural visualization work',
    'desc' => config('seo.pages')['free-trial']['description'],
    'schemaItems' => ['One real task worked on free for up to 3 working days', 'Scope confirmed in writing within one business day', 'Written quote before you continue', 'No card and no contract'],
    'eyebrow' => 'Free 3-Day Trial',
    'title' => 'Judge Our Work <em>on Your Own Project First.</em>',
    'lead' => 'Send one real task. Our team works on it free for up to 3 working days, so you can judge the quality, speed and communication before you pay anything. No card, no contract.',
    'heroItems' => ['One real task from your project', 'Free for up to 3 working days', 'Written quote before you continue', 'No card, no contract'],
    'image' => ['key' => 'viz/floorplan-3d-top', 'alt' => 'Top-down 3D floor plan with herringbone flooring and furniture layout, rendered by Architive'],
    'cta' => 'Start my free trial', 'topic' => 'Free trial', 'service' => 'visualization',
    'gallery' => null, 'galleryTitle' => '', 'galleryLead' => '',
    'cardsEyebrow' => 'What the trial is', 'cardsTitle' => 'A Real Task. <em>Real Output.</em>',
    'cardsLead' => 'The trial is not a demo with sample files. It is your project, so you see exactly what working with us looks like.',
    'cards' => [
        ['icon' => 'upload', 'title' => 'One real task', 'text' => 'Pick one defined piece of work, such as a 3D floor plan, one render or one drawing, and send the files.'],
        ['icon' => 'message', 'title' => 'Scope in writing', 'text' => 'Within one business day we reply with what we will deliver in the trial and when, or ask what is missing.'],
        ['icon' => 'clock', 'title' => 'Up to 3 working days', 'text' => 'We work on it free for up to 3 working days, so you can judge quality, speed and communication.'],
        ['icon' => 'eye', 'title' => 'Judge the result', 'text' => 'Review real output from your own project and decide whether to continue.'],
        ['icon' => 'file-text', 'title' => 'Quote before you continue', 'text' => 'If you want more, you get a written quote first, listing images or sheets, revision rounds and delivery date.'],
        ['icon' => 'check', 'title' => 'No card, no contract', 'text' => 'You do not enter payment details to start, and you are not committed to anything after the trial.'],
    ],
    'forTitle' => 'Who the Trial <em>Is For</em>',
    'forItems' => [
        ['Architecture studios', 'with a full queue who want to see if outsourced visuals match their standards.'],
        ['Interior designers', 'who need to see a render of their own design before trusting a new team.'],
        ['Builders and developers', 'who want to check a 3D floor plan or render on a real unit or site.'],
        ['Real estate teams', 'who want to compare quality and turnaround before ordering for several listings.'],
    ],
    'sendTitle' => 'What to <em>Send</em>',
    'send' => [
        'Your plans, model or sketches (a link to your files works too)',
        'What you need back: render, 3D floor plan or drawing',
        'Style or finish references, if you have them',
        'Your deadline',
    ],
    'sendNote' => 'Not sure which task to pick? Tell us about the project and we will suggest one that shows our work fairly.',
    'stepsTitle' => 'Three Steps. <em>Nothing to Sign.</em>',
    'stepsLead' => 'From first message to reviewing the work.',
    'steps' => [
        ['n' => '01', 'icon' => 'upload', 'title' => 'Send one real task', 'text' => 'Upload your plans, model or sketches and tell us what you need back. A link to your files works too.'],
        ['n' => '02', 'icon' => 'message', 'title' => 'We confirm the scope', 'text' => 'Within one business day we reply with what we will deliver in the trial and when, or ask what is missing.'],
        ['n' => '03', 'icon' => 'eye', 'title' => 'Judge the work', 'text' => 'Review real output from your own project. Continue only if you are happy, with a written quote first.'],
    ],
    'priceTitle' => 'What Happens <em>After the Trial</em>',
    'price' => [
        'If you are happy and want more, we send a written quote that lists every image or sheet, the revision rounds included and the delivery date. Nothing starts until you agree to it.',
        'If the trial is not right for you, you owe nothing.',
    ],
    'faqs' => [
        1 => ['q' => 'Is the trial really free?', 'a' => 'Yes. We work on one real task free for up to 3 working days. You do not pay anything to judge our work, and no card is needed.'],
        2 => ['q' => 'Do I need to sign a contract?', 'a' => 'No. There is no contract for the trial. If you continue, you get a written quote first.'],
        3 => ['q' => 'What can I send as a trial task?', 'a' => 'One defined piece of work from a real project, such as a 3D floor plan, a render or a drawing. We confirm in writing what the trial covers within one business day.'],
        4 => ['q' => 'Can you sign an NDA first?', 'a' => 'Yes. We can review and sign an NDA before you share project details.'],
        5 => ['q' => 'How fast do you reply?', 'a' => 'Within one business day we reply with what we will deliver in the trial and when, or ask what is missing.'],
        6 => ['q' => 'What if I do not like the result?', 'a' => 'Then you owe nothing and there is no commitment. If you want changes before deciding, tell us and we will say what is possible within the trial.'],
    ],
    'related' => 'Not sure what to send? See <a href="' . pu('what-we-need') . '">what we need from you</a>. Ready to start? <a href="' . pu('contact', [], ['service' => 'visualization']) . '">Send your task</a>. Or see what the work looks like first: <a href="' . pu('services.floorplans') . '">3D floor plans</a>, <a href="' . pu('services.interior') . '">interior rendering</a>, <a href="' . pu('services.exterior') . '">exterior rendering</a>.',
    'ctaTitle' => 'Send One Task. <em>Judge the Work Free.</em>',
]])
@endsection
