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
                explanation: $item['explanation'] ?? null,
                options: array_map(
                    static fn (array $a): array => [$a['text'], (bool) $a['is_correct']],
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

            $this->createQuestion($subjectId, $built['difficulty'], 'single', $built['text'], $built['explanation'], $built['options']);
            $created++;
        }
    }

    /**
     * @return array{text:string, explanation:string, difficulty:string, options:array<int, array{0:string,1:bool}>}|null
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
            'explanation' => "{$a} × {$b} = {$answer}.",
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
            'explanation' => "{$p}% of {$n} = {$p}/100 × {$n} = {$answer}.",
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
            'explanation' => "{$a}x = {$c} − {$b} = ".($c - $b).", so x = {$x}.",
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
            'explanation' => 'Sum = '.array_sum($nums).', divided by '.count($nums)." = {$answer}.",
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
            'explanation' => "{$a}² = {$a} × {$a} = {$answer}.",
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

            $this->createQuestion($subjectId, $built['difficulty'], 'single', $built['text'], $built['explanation'], $built['options']);
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
            'explanation' => "The series increases by {$step} each step, so the next term is {$answer}.",
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
            'explanation' => "Each term is multiplied by {$ratio}, so the next term is {$answer}.",
            'difficulty' => Difficulty::Medium->value,
            'options' => $this->numericOptions($answer),
        ];
    }

    private function generateScienceFiller(int $count): void
    {
        $pools = [
            'biology' => [
                ['Which organelle is responsible for producing most of a cell\'s ATP?', 'Mitochondrion', ['Ribosome', 'Golgi apparatus', 'Lysosome', 'Nucleolus'], 'The mitochondrion generates ATP through oxidative phosphorylation.'],
                ['What is the basic structural and functional unit of the kidney?', 'Nephron', ['Alveolus', 'Neuron', 'Villus', 'Sarcomere'], 'The nephron filters blood and produces urine.'],
                ['Which molecule carries genetic information in most organisms?', 'DNA', ['ATP', 'RNA polymerase', 'Glucose', 'Lipid'], 'DNA stores hereditary information in its base sequence.'],
                ['Which blood cells are primarily responsible for oxygen transport?', 'Erythrocytes', ['Leukocytes', 'Platelets', 'Lymphocytes', 'Macrophages'], 'Red blood cells carry oxygen bound to haemoglobin.'],
            ],
            'chemistry' => [
                ['What is the pH of a neutral aqueous solution at 25°C?', '7', ['0', '1', '14', '10'], 'A neutral solution has equal H⁺ and OH⁻, giving pH 7 at 25°C.'],
                ['Which subatomic particle carries a negative charge?', 'Electron', ['Proton', 'Neutron', 'Positron', 'Nucleus'], 'Electrons carry a negative elementary charge.'],
                ['What type of bond forms between two non-metals sharing electrons?', 'Covalent bond', ['Ionic bond', 'Metallic bond', 'Hydrogen bond', 'Van der Waals'], 'Shared electron pairs between non-metals form covalent bonds.'],
                ['How many protons does a carbon atom have?', '6', ['12', '8', '4', '14'], 'Carbon\'s atomic number is 6.'],
            ],
            'physics' => [
                ['What is the SI unit of force?', 'Newton', ['Joule', 'Watt', 'Pascal', 'Newton-metre'], 'Force is measured in newtons (kg·m/s²).'],
                ['Which quantity is defined as the rate of change of velocity?', 'Acceleration', ['Momentum', 'Displacement', 'Power', 'Force'], 'Acceleration is the time derivative of velocity.'],
                ['What is the approximate acceleration due to gravity at Earth\'s surface?', '9.8 m/s²', ['3.0×10⁸ m/s²', '1.0 m/s²', '98 m/s²', '0.98 m/s²'], 'g ≈ 9.8 m/s² near Earth\'s surface.'],
                ['Which form of energy is associated with motion?', 'Kinetic energy', ['Potential energy', 'Thermal energy', 'Chemical energy', 'Nuclear energy'], 'Kinetic energy is the energy of a moving body, ½mv².'],
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
            [$stem, $correct, $wrong, $explanation] = $template;

            $variant = $created + 1;
            $text = $stem.' (Q'.$variant.')';
            if (isset($this->seen[$text])) {
                continue;
            }

            $options = [[$correct, true]];
            foreach ($wrong as $w) {
                $options[] = [$w, false];
            }

            $this->createQuestion($subjectId, Difficulty::Easy->value, 'single', $text, $explanation, $options);
            $created++;
        }
    }

    /**
     * Build numeric multiple-choice options around the correct answer.
     *
     * @return array<int, array{0:string,1:bool}>
     */
    private function numericOptions(int $answer): array
    {
        $options = [[(string) $answer, true]];
        $used = [$answer];

        while (count($options) < 4) {
            $delta = random_int(1, max(3, (int) round($answer * 0.2)));
            $candidate = random_int(0, 1) === 0 ? $answer + $delta : $answer - $delta;
            if ($candidate < 0 || in_array($candidate, $used, true)) {
                continue;
            }
            $used[] = $candidate;
            $options[] = [(string) $candidate, false];
        }

        shuffle($options);

        return $options;
    }

    /**
     * Persist a question and its answers.
     *
     * @param  array<int, array{0:string,1:bool}>  $options
     */
    private function createQuestion(int $subjectId, string $difficulty, string $type, string $text, ?string $explanation, array $options): void
    {
        $this->seen[$text] = true;

        $question = Question::create([
            'subject_id' => $subjectId,
            'type' => QuestionType::from($type),
            'difficulty' => Difficulty::from($difficulty),
            'text' => $text,
            'explanation' => $explanation,
            'is_active' => true,
            'created_by' => null,
        ]);

        $position = 0;
        $rows = array_map(static function (array $opt) use (&$position): array {
            return [
                'text' => $opt[0],
                'is_correct' => $opt[1],
                'position' => $position++,
            ];
        }, $options);

        $question->answers()->createMany($rows);
    }
}
