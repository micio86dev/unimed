<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class QuestionSeeder extends Seeder
{
    private const TARGET_TOTAL = 500;

    /** @var array<string, int> subject slug => id */
    private array $subjects = [];

    /** @var array<string, bool> de-dupe by question text */
    private array $seen = [];

    public function run(): void
    {
        $this->subjects = Subject::pluck('id', 'slug')->all();

        $this->seedCuratedBank();

        $target = self::TARGET_TOTAL;

        // The bulk of the filler is genuinely-correct, programmatically generated
        // maths and logic — the two subjects we can synthesise reliably. The
        // remainder is recomputed from the live count after each phase so the
        // final filler tops us up to exactly the target.
        $this->generateMath(max(0, min(160, $target - Question::count())));
        $this->generateLogic(max(0, min(110, $target - Question::count())));

        $remaining = max(0, $target - Question::count());
        if ($remaining > 0) {
            $this->generateScienceFiller($remaining);
        }
    }

    /**
     * Load the hand-written, factually-accurate question bank if present.
     * Each item carries English copy plus (where available) Italian
     * translations (text_it / explanation_it / answers[].text_it).
     */
    private function seedCuratedBank(): void
    {
        $path = database_path('data/question_bank.php');

        if (! is_file($path)) {
            return;
        }

        /** @var array<int, array<string, mixed>> $bank */
        $bank = require $path;

        foreach ($bank as $item) {
            $subjectId = $this->subjects[$item['subject']] ?? null;
            if ($subjectId === null) {
                continue;
            }

            $this->createQuestion(
                subjectId: $subjectId,
                difficulty: $item['difficulty'] ?? 'medium',
                type: $item['type'] ?? 'single',
                text: $item['text'],
                textIt: $item['text_it'] ?? null,
                explanation: $item['explanation'] ?? null,
                explanationIt: $item['explanation_it'] ?? null,
                options: array_map(
                    static fn (array $a): array => [$a['text'], $a['text_it'] ?? null, (bool) $a['is_correct']],
                    $item['answers'],
                ),
            );
        }
    }

    private function generateMath(int $count): void
    {
        $subjectId = $this->subjects['mathematics'] ?? null;
        if ($subjectId === null || $count <= 0) {
            return;
        }

        $created = 0;
        $guard = 0;
        while ($created < $count && $guard < $count * 20) {
            $guard++;
            $built = $this->buildMathQuestion();
            if ($built === null || isset($this->seen[$built['text']])) {
                continue;
            }

            $this->createQuestion($subjectId, $built['difficulty'], 'single', $built['text'], $built['text_it'], $built['explanation'], $built['explanation_it'], $built['options']);
            $created++;
        }
    }

    /**
     * @return array{text:string, text_it:string, explanation:string, explanation_it:string, difficulty:string, options:array<int, array{0:string,1:string,2:bool}>}|null
     */
    private function buildMathQuestion(): ?array
    {
        return match (random_int(1, 5)) {
            1 => $this->mathProduct(),
            2 => $this->mathPercentage(),
            3 => $this->mathLinear(),
            4 => $this->mathAverage(),
            default => $this->mathPower(),
        };
    }

    private function mathProduct(): array
    {
        $a = random_int(11, 49);
        $b = random_int(11, 29);
        $answer = $a * $b;

        return [
            'text' => "What is the value of {$a} × {$b}?",
            'text_it' => "Quanto vale {$a} × {$b}?",
            'explanation' => "{$a} × {$b} = {$answer}.",
            'explanation_it' => "{$a} × {$b} = {$answer}.",
            'difficulty' => Difficulty::Easy->value,
            'options' => $this->numericOptions($answer),
        ];
    }

    private function mathPercentage(): array
    {
        $p = Arr::random([5, 10, 15, 20, 25, 40, 50, 75]);
        $n = random_int(2, 20) * 20;
        $answer = (int) ($p / 100 * $n);

        return [
            'text' => "What is {$p}% of {$n}?",
            'text_it' => "Quanto è il {$p}% di {$n}?",
            'explanation' => "{$p}% of {$n} = {$p}/100 × {$n} = {$answer}.",
            'explanation_it' => "{$p}% di {$n} = {$p}/100 × {$n} = {$answer}.",
            'difficulty' => Difficulty::Medium->value,
            'options' => $this->numericOptions($answer),
        ];
    }

    private function mathLinear(): array
    {
        $a = random_int(2, 9);
        $x = random_int(2, 12);
        $b = random_int(1, 20);
        $c = $a * $x + $b;

        return [
            'text' => "Solve for x: {$a}x + {$b} = {$c}.",
            'text_it' => "Risolvi per x: {$a}x + {$b} = {$c}.",
            'explanation' => "{$a}x = {$c} − {$b} = ".($c - $b).", so x = {$x}.",
            'explanation_it' => "{$a}x = {$c} − {$b} = ".($c - $b).", quindi x = {$x}.",
            'difficulty' => Difficulty::Medium->value,
            'options' => $this->numericOptions($x),
        ];
    }

    private function mathAverage(): array
    {
        $nums = [];
        for ($i = 0; $i < 4; $i++) {
            $nums[] = random_int(2, 20) * 2;
        }
        $answer = (int) (array_sum($nums) / count($nums));
        $list = implode(', ', $nums);

        return [
            'text' => "What is the arithmetic mean of {$list}?",
            'text_it' => "Qual è la media aritmetica di {$list}?",
            'explanation' => 'Sum = '.array_sum($nums).', divided by '.count($nums)." = {$answer}.",
            'explanation_it' => 'Somma = '.array_sum($nums).', divisa per '.count($nums)." = {$answer}.",
            'difficulty' => Difficulty::Medium->value,
            'options' => $this->numericOptions($answer),
        ];
    }

    private function mathPower(): array
    {
        $a = random_int(4, 15);
        $answer = $a * $a;

        return [
            'text' => "What is {$a}² (the square of {$a})?",
            'text_it' => "Quanto vale {$a}² (il quadrato di {$a})?",
            'explanation' => "{$a}² = {$a} × {$a} = {$answer}.",
            'explanation_it' => "{$a}² = {$a} × {$a} = {$answer}.",
            'difficulty' => Difficulty::Easy->value,
            'options' => $this->numericOptions($answer),
        ];
    }

    private function generateLogic(int $count): void
    {
        $subjectId = $this->subjects['logic'] ?? null;
        if ($subjectId === null || $count <= 0) {
            return;
        }

        $created = 0;
        $guard = 0;
        while ($created < $count && $guard < $count * 20) {
            $guard++;
            $built = random_int(1, 2) === 1 ? $this->logicArithmeticSeries() : $this->logicGeometricSeries();
            if (isset($this->seen[$built['text']])) {
                continue;
            }

            $this->createQuestion($subjectId, $built['difficulty'], 'single', $built['text'], $built['text_it'], $built['explanation'], $built['explanation_it'], $built['options']);
            $created++;
        }
    }

    private function logicArithmeticSeries(): array
    {
        $start = random_int(1, 30);
        $step = random_int(2, 15);
        $seq = [];
        for ($i = 0; $i < 5; $i++) {
            $seq[] = $start + $i * $step;
        }
        $answer = $start + 5 * $step;
        $shown = implode(', ', $seq);

        return [
            'text' => "What number comes next in the series: {$shown}, ?",
            'text_it' => "Quale numero viene dopo nella serie: {$shown}, ?",
            'explanation' => "The series increases by {$step} each step, so the next term is {$answer}.",
            'explanation_it' => "La serie aumenta di {$step} a ogni passo, quindi il termine successivo è {$answer}.",
            'difficulty' => Difficulty::Easy->value,
            'options' => $this->numericOptions($answer),
        ];
    }

    private function logicGeometricSeries(): array
    {
        $start = random_int(1, 6);
        $ratio = random_int(2, 4);
        $seq = [];
        $v = $start;
        for ($i = 0; $i < 4; $i++) {
            $seq[] = $v;
            $v *= $ratio;
        }
        $answer = $v;
        $shown = implode(', ', $seq);

        return [
            'text' => "What number comes next in the series: {$shown}, ?",
            'text_it' => "Quale numero viene dopo nella serie: {$shown}, ?",
            'explanation' => "Each term is multiplied by {$ratio}, so the next term is {$answer}.",
            'explanation_it' => "Ogni termine è moltiplicato per {$ratio}, quindi il termine successivo è {$answer}.",
            'difficulty' => Difficulty::Medium->value,
            'options' => $this->numericOptions($answer),
        ];
    }

    private function generateScienceFiller(int $count): void
    {
        // Each template: [stem_en, stem_it, [correct_en, correct_it], [[wrong_en, wrong_it], ...], expl_en, expl_it].
        $pools = [
            'biology' => [
                ["Which organelle is responsible for producing most of a cell's ATP?", 'Quale organello è responsabile della produzione della maggior parte dell\'ATP cellulare?', ['Mitochondrion', 'Mitocondrio'], [['Ribosome', 'Ribosoma'], ['Golgi apparatus', 'Apparato di Golgi'], ['Lysosome', 'Lisosoma'], ['Nucleolus', 'Nucleolo']], 'The mitochondrion generates ATP through oxidative phosphorylation.', 'Il mitocondrio produce ATP tramite la fosforilazione ossidativa.'],
                ['What is the basic structural and functional unit of the kidney?', 'Qual è l\'unità strutturale e funzionale di base del rene?', ['Nephron', 'Nefrone'], [['Alveolus', 'Alveolo'], ['Neuron', 'Neurone'], ['Villus', 'Villo'], ['Sarcomere', 'Sarcomero']], 'The nephron filters blood and produces urine.', 'Il nefrone filtra il sangue e produce l\'urina.'],
                ['Which molecule carries genetic information in most organisms?', 'Quale molecola trasporta l\'informazione genetica nella maggior parte degli organismi?', ['DNA', 'DNA'], [['ATP', 'ATP'], ['RNA polymerase', 'RNA polimerasi'], ['Glucose', 'Glucosio'], ['Lipid', 'Lipide']], 'DNA stores hereditary information in its base sequence.', 'Il DNA conserva l\'informazione ereditaria nella sequenza delle basi.'],
                ['Which blood cells are primarily responsible for oxygen transport?', 'Quali cellule del sangue sono principalmente responsabili del trasporto dell\'ossigeno?', ['Erythrocytes', 'Eritrociti'], [['Leukocytes', 'Leucociti'], ['Platelets', 'Piastrine'], ['Lymphocytes', 'Linfociti'], ['Macrophages', 'Macrofagi']], 'Red blood cells carry oxygen bound to haemoglobin.', 'I globuli rossi trasportano l\'ossigeno legato all\'emoglobina.'],
            ],
            'chemistry' => [
                ['What is the pH of a neutral aqueous solution at 25°C?', 'Qual è il pH di una soluzione acquosa neutra a 25°C?', ['7', '7'], [['0', '0'], ['1', '1'], ['14', '14'], ['10', '10']], 'A neutral solution has equal H⁺ and OH⁻, giving pH 7 at 25°C.', 'Una soluzione neutra ha uguali H⁺ e OH⁻, dando pH 7 a 25°C.'],
                ['Which subatomic particle carries a negative charge?', 'Quale particella subatomica ha carica negativa?', ['Electron', 'Elettrone'], [['Proton', 'Protone'], ['Neutron', 'Neutrone'], ['Positron', 'Positrone'], ['Nucleus', 'Nucleo']], 'Electrons carry a negative elementary charge.', 'Gli elettroni portano una carica elementare negativa.'],
                ['What type of bond forms between two non-metals sharing electrons?', 'Che tipo di legame si forma tra due non metalli che condividono elettroni?', ['Covalent bond', 'Legame covalente'], [['Ionic bond', 'Legame ionico'], ['Metallic bond', 'Legame metallico'], ['Hydrogen bond', 'Legame a idrogeno'], ['Van der Waals', 'Van der Waals']], 'Shared electron pairs between non-metals form covalent bonds.', 'Le coppie di elettroni condivise tra non metalli formano legami covalenti.'],
                ['How many protons does a carbon atom have?', 'Quanti protoni ha un atomo di carbonio?', ['6', '6'], [['12', '12'], ['8', '8'], ['4', '4'], ['14', '14']], "Carbon's atomic number is 6.", 'Il numero atomico del carbonio è 6.'],
            ],
            'physics' => [
                ['What is the SI unit of force?', "Qual è l'unità di misura della forza nel SI?", ['Newton', 'Newton'], [['Joule', 'Joule'], ['Watt', 'Watt'], ['Pascal', 'Pascal'], ['Newton-metre', 'Newton-metro']], 'Force is measured in newtons (kg·m/s²).', 'La forza si misura in newton (kg·m/s²).'],
                ['Which quantity is defined as the rate of change of velocity?', 'Quale grandezza è definita come la variazione della velocità nel tempo?', ['Acceleration', 'Accelerazione'], [['Momentum', 'Quantità di moto'], ['Displacement', 'Spostamento'], ['Power', 'Potenza'], ['Force', 'Forza']], 'Acceleration is the time derivative of velocity.', "L'accelerazione è la derivata della velocità rispetto al tempo."],
                ["What is the approximate acceleration due to gravity at Earth's surface?", "Qual è l'accelerazione di gravità approssimativa alla superficie terrestre?", ['9.8 m/s²', '9,8 m/s²'], [['3.0×10⁸ m/s²', '3,0×10⁸ m/s²'], ['1.0 m/s²', '1,0 m/s²'], ['98 m/s²', '98 m/s²'], ['0.98 m/s²', '0,98 m/s²']], 'g ≈ 9.8 m/s² near Earth\'s surface.', 'g ≈ 9,8 m/s² in prossimità della superficie terrestre.'],
                ['Which form of energy is associated with motion?', 'Quale forma di energia è associata al movimento?', ['Kinetic energy', 'Energia cinetica'], [['Potential energy', 'Energia potenziale'], ['Thermal energy', 'Energia termica'], ['Chemical energy', 'Energia chimica'], ['Nuclear energy', 'Energia nucleare']], 'Kinetic energy is the energy of a moving body, ½mv².', "L'energia cinetica è l'energia di un corpo in movimento, ½mv²."],
            ],
        ];

        $slugs = array_keys($pools);
        $i = 0;
        $created = 0;
        $guard = 0;
        while ($created < $count && $guard < $count * 30) {
            $guard++;
            $slug = $slugs[$i % count($slugs)];
            $i++;
            $subjectId = $this->subjects[$slug] ?? null;
            if ($subjectId === null) {
                continue;
            }

            $template = Arr::random($pools[$slug]);
            [$stem, $stemIt, $correct, $wrong, $explanation, $explanationIt] = $template;

            $variant = $created + 1;
            $text = $stem.' (Q'.$variant.')';
            $textIt = $stemIt.' (Q'.$variant.')';
            if (isset($this->seen[$text])) {
                continue;
            }

            $options = [[$correct[0], $correct[1], true]];
            foreach ($wrong as $w) {
                $options[] = [$w[0], $w[1], false];
            }

            $this->createQuestion($subjectId, Difficulty::Easy->value, 'single', $text, $textIt, $explanation, $explanationIt, $options);
            $created++;
        }
    }

    /**
     * Build numeric multiple-choice options around the correct answer. Numbers
     * are language-neutral, so the Italian text mirrors the English.
     *
     * @return array<int, array{0:string,1:string,2:bool}>
     */
    private function numericOptions(int $answer): array
    {
        $options = [[(string) $answer, (string) $answer, true]];
        $used = [$answer];

        while (count($options) < 4) {
            $delta = random_int(1, max(3, (int) round($answer * 0.2)));
            $candidate = random_int(0, 1) === 0 ? $answer + $delta : $answer - $delta;
            if ($candidate < 0 || in_array($candidate, $used, true)) {
                continue;
            }
            $used[] = $candidate;
            $options[] = [(string) $candidate, (string) $candidate, false];
        }

        shuffle($options);

        return $options;
    }

    /**
     * Persist a question and its answers (English copy + optional Italian).
     *
     * @param  array<int, array{0:string,1:?string,2:bool}>  $options  [text_en, text_it, is_correct]
     */
    private function createQuestion(int $subjectId, string $difficulty, string $type, string $text, ?string $textIt, ?string $explanation, ?string $explanationIt, array $options): void
    {
        $this->seen[$text] = true;

        $question = Question::create([
            'subject_id' => $subjectId,
            'type' => QuestionType::from($type),
            'difficulty' => Difficulty::from($difficulty),
            'text' => $text,
            'text_it' => $textIt,
            'explanation' => $explanation,
            'explanation_it' => $explanationIt,
            'is_active' => true,
            'created_by' => null,
        ]);

        $position = 0;
        $rows = array_map(static function (array $opt) use (&$position): array {
            return [
                'text' => $opt[0],
                'text_it' => $opt[1] ?? null,
                'is_correct' => $opt[2],
                'position' => $position++,
            ];
        }, $options);

        $question->answers()->createMany($rows);
    }
}
