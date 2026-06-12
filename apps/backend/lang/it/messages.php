<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Messaggi API dell'applicazione (Italiano)
|--------------------------------------------------------------------------
| Stringhe mostrate all'utente restituite dall'API. La lingua attiva viene
| determinata per ogni richiesta dall'header Accept-Language (middleware
| SetLocale).
*/

return [
    'auth' => [
        'invalid_credentials' => 'Le credenziali inserite non corrispondono ai nostri dati.',
        'account_disabled' => 'Il tuo account è stato disattivato. Contatta un amministratore.',
        'signed_in' => 'Accesso effettuato con successo.',
        'registered' => 'Benvenuto su UniMed! Il tuo account è pronto.',
        'signed_out' => 'Disconnessione effettuata con successo.',
        'reset_link_sent' => 'Se l\'indirizzo email esiste, è stato inviato un link per il reset.',
        'password_reset' => 'La tua password è stata reimpostata. Ora puoi accedere.',
        'unauthenticated' => 'Non autenticato.',
    ],

    'attempt' => [
        'quiz_empty' => 'Questo quiz non contiene ancora domande.',
        'started' => 'Tentativo avviato.',
        'already_submitted' => 'Questo tentativo è già stato inviato.',
        'question_not_in_attempt' => 'Questa domanda non fa parte di questo tentativo.',
        'saved' => 'Salvato.',
        'submitted' => 'Tentativo inviato.',
        'not_completed' => 'Questo tentativo non è ancora stato completato.',
        'not_owner' => 'Questo tentativo non ti appartiene.',
        'not_in_progress' => 'Questo tentativo non è più in corso.',
    ],

    'question' => [
        'created' => 'Domanda creata.',
        'updated' => 'Domanda aggiornata.',
        'deleted' => 'Domanda eliminata.',
        'at_least_one_correct' => 'Almeno una risposta deve essere contrassegnata come corretta.',
        'single_choice_one_correct' => 'Le domande a risposta singola devono avere esattamente una risposta corretta.',
    ],

    'quiz' => [
        'created' => 'Quiz creato.',
        'updated' => 'Quiz aggiornato.',
        'deleted' => 'Quiz eliminato.',
    ],

    'upload' => [
        'image_uploaded' => 'Immagine caricata.',
    ],

    'common' => [
        'not_found' => 'Risorsa non trovata.',
    ],
];
