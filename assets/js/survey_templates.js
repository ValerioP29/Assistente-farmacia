(function(){
    if (typeof window === \"undefined\") return;
    window.SURVEY_TEMPLATES = window.SURVEY_TEMPLATES || {
   Diabete: [
        // QUESTIONARIO BASE
        {
            key: 'misura_glicemia',
            label: 'Misura regolarmente la glicemia?',
            type: 'select',
            options: ['Tutti i giorni', 'Qualche volta', 'Raramente']
        },
        {
            key: 'ultimo_valore_glicemia',
            label: 'Ultimo valore glicemico (mg/dL)',
            type: 'text'
        },
        {
            key: 'hba1c_6_mesi',
            label: 'Ha eseguito l’emoglobina glicata (HbA1c) negli ultimi 6 mesi?',
            type: 'select',
            options: ['Sì', 'No']
        },
        {
            key: 'segue_dieta',
            label: 'Segue una dieta specifica per il diabete?',
            type: 'select',
            options: ['Sì', 'No']
        },
        {
            key: 'attivita_fisica',
            label: 'Svolge attività fisica regolare?',
            type: 'select',
            options: ['Sì', 'No']
        },
        {
            key: 'aderenza_farmaci',
            label: 'Assume regolarmente i farmaci prescritti?',
            type: 'select',
            options: ['Sì', 'No']
        },

        // QUESTIONARIO APPROFONDITO
        {
            key: 'ipoglicemie',
            label: 'Ha avuto episodi di ipoglicemia?',
            type: 'select',
            options: ['Mai', 'Talvolta', 'Spesso']
        },
        {
            key: 'controllo_pre_pasto',
            label: 'Controlla la glicemia prima dei pasti o a orari fissi?',
            type: 'select',
            options: ['Sempre', 'A volte', 'Mai']
        },
        {
            key: 'conservazione_insulina',
            label: 'Conserva correttamente l’insulina?',
            type: 'select',
            options: ['Sì', 'No', 'Non uso insulina']
        },
        {
            key: 'controlli_specialisti',
            label: 'Effettua controlli periodici da diabetologo / oculista / podologo?',
            type: 'select',
            options: ['Sì', 'No']
        },
        {
            key: 'difficolta_aderenza',
            label: 'Ha difficoltà a rispettare orari e dosaggi della terapia?',
            type: 'select',
            options: ['No', 'A volte', 'Sì']
        },
        {
            key: 'formicolii_ferite',
            label: 'Ha formicolii o ferite che guariscono lentamente?',
            type: 'select',
            options: ['Sì', 'No']
        }
    ],

    BPCO: [
        // QUESTIONARIO BASE
        {
            key: 'fiato_sforzo',
            label: 'Le manca il fiato quando cammina in piano o sale una rampa di scale?',
            type: 'select',
            options: ['Mai', 'A volte', 'Spesso']
        },
        {
            key: 'tosse_muco',
            label: 'Tossisce o espelle muco durante la giornata?',
            type: 'select',
            options: ['No', 'Saltuariamente', 'Frequentemente']
        },
        {
            key: 'limitazioni_attivita',
            label: 'Ha limitazioni nelle attività quotidiane a causa del respiro?',
            type: 'select',
            options: ['No', 'Qualche volta', 'Spesso']
        },
        {
            key: 'qualita_sonno',
            label: 'Dorme bene la notte?',
            type: 'select',
            options: ['Sì', 'No (a causa del respiro)']
        },
        {
            key: 'stanchezza_generale',
            label: 'Si sente spesso stanco o senza energia?',
            type: 'select',
            options: ['Mai', 'A volte', 'Spesso']
        },
        {
            key: 'uso_inalatore_regolare',
            label: 'Utilizza regolarmente il suo inalatore?',
            type: 'select',
            options: ['Sempre', 'A volte', 'Raramente']
        },

        // QUESTIONARIO APPROFONDITO
        {
            key: 'dispnea_mmrc',
            label: 'Dispnea (scala mMRC)',
            type: 'select',
            options: ['0', '1', '2', '3', '4']
        },
        {
            key: 'riacutizzazioni_annuali',
            label: 'Quante riacutizzazioni ha avuto nell’ultimo anno?',
            type: 'select',
            options: ['Nessuna', '1', '≥2', 'Ricovero ospedaliero']
        },
        {
            key: 'uso_correttamente_dispositivo',
            label: 'È in grado di usare correttamente il dispositivo inalatorio?',
            type: 'select',
            options: ['Sì', 'No', 'Da verificare']
        },
        {
            key: 'effetti_collaterali',
            label: 'Ha effetti collaterali (tosse post-inalazione, secchezza, tremori)?',
            type: 'select',
            options: ['Sì', 'No']
        },
        {
            key: 'spirometria_recentemente',
            label: 'Ha fatto una spirometria negli ultimi 12 mesi?',
            type: 'select',
            options: ['Sì', 'No']
        },
        {
            key: 'fev1_percentuale',
            label: 'FEV1 (% se disponibile)',
            type: 'text'
        }
    ],

    Ipertensione: [
        // QUESTIONARIO BASE
        {
            key: 'storia_pressione_alta',
            label: 'Ha mai avuto la pressione alta?',
            type: 'select',
            options: ['No', 'Sì', 'Non so']
        },
        {
            key: 'misura_pressione_regolare',
            label: 'Misura regolarmente la pressione arteriosa?',
            type: 'select',
            options: ['Sì', 'No']
        },
        {
            key: 'assunzione_farmaci_antipertensivi',
            label: 'Assume farmaci antipertensivi?',
            type: 'select',
            options: ['Sì', 'No', 'Non regolarmente']
        },
        {
            key: 'riduzione_sale',
            label: 'Riduce il consumo di sale e cibi salati?',
            type: 'select',
            options: ['Sì', 'No']
        },
        {
            key: 'attivita_fisica_moderata',
            label: 'Svolge attività fisica moderata?',
            type: 'select',
            options: ['Sì', 'No']
        },
        {
            key: 'sintomi_cardiaci',
            label: 'Ha mai avuto dolore al petto, palpitazioni o svenimenti?',
            type: 'select',
            options: ['No', 'Sì']
        },

        // QUESTIONARIO APPROFONDITO
        {
            key: 'valori_pressori_medi',
            label: 'Ultimi valori pressori medi (es. 130/80 mmHg)',
            type: 'text'
        },
        {
            key: 'gonfiore_gambe',
            label: 'Ha episodi di gonfiore a gambe o caviglie?',
            type: 'select',
            options: ['No', 'Sì']
        },
        {
            key: 'familiarita_cardiovascolare',
            label: 'Ha familiarità per ictus o infarto precoce?',
            type: 'select',
            options: ['No', 'Sì']
        },
        {
            key: 'aderenza_terapia_antipertensiva',
            label: 'Assume regolarmente la terapia antipertensiva?',
            type: 'select',
            options: ['Sempre', 'A volte', 'No']
        },
        {
            key: 'ecg_o_telecardiologia',
            label: 'Ha effettuato ECG o telecardiologia negli ultimi 12 mesi?',
            type: 'select',
            options: ['Sì', 'No']
        },
        {
            key: 'effetti_collaterali_terapia',
            label: 'Presenta effetti collaterali dai farmaci (capogiri, tosse, stanchezza)?',
            type: 'select',
            options: ['Sì', 'No']
        }
    ],

    Dislipidemia: [
        // QUESTIONARIO BASE
        {
            key: 'controllo_lipidico_precedente',
            label: 'Ha mai controllato il colesterolo o i trigliceridi?',
            type: 'select',
            options: ['Sì', 'No']
        },
        {
            key: 'terapia_statine_ezetimibe_omega3',
            label: 'È in terapia con statine, ezetimibe o omega 3?',
            type: 'select',
            options: ['Sì', 'No']
        },
        {
            key: 'dieta_povera_grassi',
            label: 'Segue una dieta povera di grassi saturi e zuccheri?',
            type: 'select',
            options: ['Sì', 'No']
        },
        {
            key: 'familiarita_cardiovascolare',
            label: 'Ha casi in famiglia di colesterolo alto, infarto o ictus precoce?',
            type: 'select',
            options: ['Sì', 'No']
        },
        {
            key: 'attivita_fisica_regolare',
            label: 'Fa attività fisica regolare?',
            type: 'select',
            options: ['Sì', 'No']
        },
        {
            key: 'controllo_lipidico_12_mesi',
            label: 'Ha eseguito un controllo lipidico negli ultimi 12 mesi?',
            type: 'select',
            options: ['Sì', 'No']
        },

        // QUESTIONARIO APPROFONDITO
        {
            key: 'colesterolo_totale',
            label: 'Colesterolo Totale (mg/dL)',
            type: 'text'
        },
        {
            key: 'ldl',
            label: 'LDL (mg/dL)',
            type: 'text'
        },
        {
            key: 'hdl',
            label: 'HDL (mg/dL)',
            type: 'text'
        },
        {
            key: 'trigliceridi',
            label: 'Trigliceridi (mg/dL)',
            type: 'text'
        },
        {
            key: 'ipercolesterolemia_familiare',
            label: 'Diagnosi di ipercolesterolemia familiare?',
            type: 'select',
            options: ['Sì', 'No', 'In valutazione']
        },
        {
            key: 'segni_fisici_displipidemia',
            label: 'Presenza di xantomi, arco corneale o xantelasmi?',
            type: 'select',
            options: ['Sì', 'No']
        },
        {
            key: 'aderenza_farmaco',
            label: 'Assunzione regolare del farmaco?',
            type: 'select',
            options: ['Sempre', 'A volte', 'No']
        },
        {
            key: 'effetti_collaterali_statine',
            label: 'Effetti collaterali da statine (dolori muscolari, stanchezza)?',
            type: 'select',
            options: ['Sì', 'No']
        },
        {
            key: 'ultimo_controllo_followup',
            label: 'Ultimo controllo medico e follow-up (data)',
            type: 'text'
        }
    ]

};
})();
