<?php

class Chatbot
{
    public static function topics(): array
    {
        return [
            [
                'id' => 'greeting',
                'keywords' => ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening'],
                'answer' => 'Hello! I am the SDRU Assistant. I can help you with complaint procedures, requirements, case workflow, hearings, evidence, notifications, messaging, privacy, and other general SDRU procedures. What would you like to know?',
                'suggestions' => ['How do I file a complaint?', 'What are the requirements?', 'How does the case process work?']
            ],
            [
                'id' => 'complaint',
                'keywords' => ['complaint', 'complain', 'file a complaint', 'submit complaint', 'submit a complaint', 'report a complaint', 'report someone'],
                'answer' => 'You can file a complaint through the SDRU Case Management System. Prepare the required complainant, respondent, witness, case, and supporting-document information, then submit the complaint for initial review.',
                'suggestions' => ['What are the requirements?', 'What evidence can I upload?', 'How does the case process work?']
            ],
            [
                'id' => 'requirements',
                'keywords' => ['requirements', 'what do i need', 'what should i prepare', 'documents needed', 'required documents', 'needed to file'],
                'answer' => 'For a complaint, prepare the complainant information, respondent information, relevant witness information when applicable, details of the incident or concern, and supporting evidence or documents when available.',
                'suggestions' => ['How do I file a complaint?', 'What evidence can I upload?', 'Can I revise my complaint?']
            ],
            [
                'id' => 'process',
                'keywords' => ['case process', 'process', 'steps', 'what happens after', 'after i submit', 'workflow', 'procedure'],
                'answer' => 'The general case workflow is: complaint submission, initial review and verification, case assignment, investigation, interviews or witness participation when necessary, hearing or mediation when applicable, decision or resolution, and archival of the case record.',
                'suggestions' => ['How can I track my case?', 'How are hearings scheduled?', 'Who decides the case?']
            ],
            [
                'id' => 'tracking',
                'keywords' => ['track my case', 'case status', 'status of my case', 'where is my case', 'monitor my case', 'case progress'],
                'answer' => 'After logging in, you can monitor your own case through the case-management features available to your account. The system displays the current case status and relevant updates that you are authorized to view.',
                'suggestions' => ['What do the case statuses mean?', 'How will I receive notifications?', 'Can I message SDRU?']
            ],
            [
                'id' => 'evidence',
                'keywords' => ['evidence', 'supporting document', 'attachment', 'upload file', 'upload evidence', 'what can i upload'],
                'answer' => 'You may provide relevant supporting documents or files as evidence, subject to the system\'s file-type and size requirements. Only relevant materials should be submitted, and access to disciplinary records is controlled by user role and permissions.',
                'suggestions' => ['How do I file a complaint?', 'Are my files confidential?', 'Can I revise my complaint?']
            ],
            [
                'id' => 'hearing',
                'keywords' => ['hearing', 'hearing schedule', 'hearing date', 'schedule a hearing', 'online hearing', 'meet link', 'google meet'],
                'answer' => 'Authorized SDRU personnel manage hearing schedules. When an online hearing is applicable, the system can provide the scheduled meeting information and notify involved parties through the available notification mechanisms.',
                'suggestions' => ['How will I know about my hearing?', 'What happens during a hearing?', 'Can witnesses participate?']
            ],
            [
                'id' => 'mediation',
                'keywords' => ['mediation', 'mediate', 'mediation session'],
                'answer' => 'Mediation may be scheduled by authorized SDRU personnel when it is appropriate for the case. The chatbot can explain the general procedure, but it cannot decide whether mediation is required for a specific case.',
                'suggestions' => ['How are hearings scheduled?', 'Can witnesses participate?', 'How does the case process work?']
            ],
            [
                'id' => 'witness',
                'keywords' => ['witness', 'witnesses', 'witness participation', 'witness interview', 'interview a witness'],
                'answer' => 'Witness information may be included when relevant to a complaint. Authorized SDRU personnel may contact or interview witnesses when necessary as part of case processing.',
                'suggestions' => ['How does the case process work?', 'How are hearings scheduled?', 'What evidence can I upload?']
            ],
            [
                'id' => 'notification',
                'keywords' => ['notification', 'notifications', 'reminder', 'hearing reminder', 'how will i know'],
                'answer' => 'The system can provide notifications for relevant case and hearing updates. The exact notifications you receive depend on your role and the actions or events associated with your case.',
                'suggestions' => ['How can I track my case?', 'How are hearings scheduled?', 'Can I message SDRU?']
            ],
            [
                'id' => 'messaging',
                'keywords' => ['message sdru', 'message staff', 'contact sdru', 'send a message', 'messaging', 'chat with sdru'],
                'answer' => 'The SICMS provides internal messaging for case-related communication between students and authorized SDRU personnel. You must log in to use protected messaging features.',
                'suggestions' => ['How can I track my case?', 'How will I receive notifications?', 'How do I file a complaint?']
            ],
            [
                'id' => 'privacy',
                'keywords' => ['privacy', 'confidential', 'confidentiality', 'data safe', 'secure', 'security', 'protected'],
                'answer' => 'Disciplinary information is sensitive. SICMS uses role-based access and controlled access to case records and files. The chatbot itself does not ask for or retrieve confidential case details.',
                'suggestions' => ['What evidence can I upload?', 'How can I track my case?', 'Can I message SDRU?']
            ],
            [
                'id' => 'revision',
                'keywords' => ['revise', 'revision', 'edit complaint', 'returned complaint', 'return for revision', 'change my complaint'],
                'answer' => 'If an authorized SDRU reviewer returns a complaint for revision, the complainant may be asked to provide or correct the required information before the complaint can continue through the review process.',
                'suggestions' => ['Why was my complaint returned?', 'What are the requirements?', 'How does the case process work?']
            ],
            [
                'id' => 'decision',
                'keywords' => ['decision', 'verdict', 'outcome', 'who decides', 'punishment', 'guilty', 'penalty'],
                'answer' => 'The chatbot does not make disciplinary decisions, determine guilt, recommend penalties, or provide legal advice. Final decisions remain under the authority of authorized SDRU personnel.',
                'suggestions' => ['How does the case process work?', 'How can I track my case?', 'What happens during a hearing?']
            ],
            [
                'id' => 'sdru',
                'keywords' => ['what is sdru', 'what does sdru do', 'student discipline', 'student discipline and reformation unit'],
                'answer' => 'The Student Discipline and Reformation Unit (SDRU) handles student disciplinary concerns, including complaint processing, case review, investigations, hearings, documentation, and related case-management activities.',
                'suggestions' => ['How do I file a complaint?', 'How does the case process work?', 'How can I contact SDRU?']
            ],
        ];
    }

    public static function defaultSuggestions(): array
    {
        return ['How do I file a complaint?', 'What are the requirements?', 'How does the case process work?'];
    }

    public static function respond(string $message): array
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $message)));
        $best = null;
        $bestScore = 0;

        foreach (self::topics() as $topic) {
            $score = 0;
            foreach ($topic['keywords'] as $keyword) {
                $keyword = strtolower($keyword);
                if (strpos($normalized, $keyword) !== false) {
                    $score += strlen($keyword) >= 12 ? 4 : 2;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $topic;
            }
        }

        if (!$best || $bestScore < 2) {
            return [
                'answer' => 'I can help with general SDRU procedures, but I did not understand the question yet. Try asking about complaint filing, requirements, case status, evidence, hearings, mediation, witnesses, notifications, messaging, privacy, or revisions.',
                'suggestions' => self::defaultSuggestions()
            ];
        }

        return [
            'answer' => $best['answer'],
            'suggestions' => $best['suggestions'] ?? self::defaultSuggestions()
        ];
    }
}
