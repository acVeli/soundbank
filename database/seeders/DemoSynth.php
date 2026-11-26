<?php

namespace Database\Seeders;

/**
 * Petit synthétiseur pour la démo : fabrique de vrais fichiers WAV (kick, hats,
 * 808, voix « aah »…) afin que le catalogue de démonstration soit écoutable.
 */
class DemoSynth
{
    public const RATE = 44100;

    /**
     * @return list<float>
     */
    public function render(string $type, float $bpm = 120): array
    {
        return match ($type) {
            'kick' => $this->kick(),
            'snare' => $this->snare(),
            'clap' => $this->clap(),
            'hat' => $this->hat(0.06),
            'open_hat' => $this->hat(0.45),
            '808' => $this->bass808(55.0, 1.6),
            'sub' => $this->bass808(41.2, 2.2, 0.0),
            'bass_loop' => $this->bassLoop($bpm),
            'pad' => $this->pad([220.0, 261.63, 329.63, 392.0], 6.0),
            'pluck' => $this->pluckSequence($bpm),
            'riser' => $this->riser(4.0),
            'impact' => $this->impact(),
            'drum_loop' => $this->drumLoop($bpm),
            'vocal_female' => $this->vocal([329.63, 392.0, 440.0, 392.0], 'a', 0.75),
            'vocal_female_oh' => $this->vocal([440.0, 493.88, 523.25, 440.0], 'o', 0.6),
            'vocal_male' => $this->vocal([130.81, 146.83, 164.81, 146.83], 'o', 0.75),
            'vocal_chop' => $this->vocalChops($bpm),
            default => $this->kick(),
        };
    }

    /**
     * @param  list<float>  $samples
     */
    public function wav(array $samples, int $rate = self::RATE): string
    {
        $data = '';
        $peak = max(0.0001, max(array_map('abs', $samples)));
        $gain = min(1.0, 0.92 / $peak);

        foreach ($samples as $value) {
            $data .= pack('v', ((int) round(max(-1, min(1, $value * $gain)) * 32767)) & 0xFFFF);
        }

        return 'RIFF'.pack('V', 36 + strlen($data)).'WAVE'
            .'fmt '.pack('VvvVVvv', 16, 1, 1, $rate, $rate * 2, 2, 16)
            .'data'.pack('V', strlen($data)).$data;
    }

    /**
     * Aperçu : 22 kHz, 30 s max.
     *
     * @param  list<float>  $samples
     * @return list<float>
     */
    public function preview(array $samples): array
    {
        $out = [];
        $max = min(count($samples), self::RATE * 30);

        for ($i = 0; $i < $max; $i += 2) {
            $out[] = $samples[$i];
        }

        return $out;
    }

    /**
     * @param  list<float>  $samples
     * @return list<float>
     */
    public function peaks(array $samples, int $buckets = 120): array
    {
        $size = max(1, intdiv(count($samples), $buckets));
        $peaks = [];

        for ($b = 0; $b < $buckets; $b++) {
            $max = 0.0;

            for ($i = $b * $size, $end = min(count($samples), ($b + 1) * $size); $i < $end; $i += 8) {
                $max = max($max, abs($samples[$i]));
            }

            $peaks[] = $max;
        }

        $top = max(0.0001, max($peaks));

        return array_map(fn (float $v): float => round($v / $top, 3), $peaks);
    }

    /**
     * @return list<float>
     */
    private function kick(): array
    {
        $n = (int) (self::RATE * 0.6);
        $out = [];
        $phase = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $t = $i / self::RATE;
            $freq = 45 + 110 * exp(-$t * 28);
            $phase += 2 * M_PI * $freq / self::RATE;
            $click = $i < 200 ? (mt_rand() / mt_getrandmax() * 2 - 1) * 0.3 * (1 - $i / 200) : 0;
            $out[] = tanh(1.6 * sin($phase) * exp(-$t * 6.5)) + $click;
        }

        return $out;
    }

    /**
     * @return list<float>
     */
    private function snare(): array
    {
        $n = (int) (self::RATE * 0.35);
        $out = [];

        for ($i = 0; $i < $n; $i++) {
            $t = $i / self::RATE;
            $noise = (mt_rand() / mt_getrandmax() * 2 - 1) * exp(-$t * 14);
            $tone = sin(2 * M_PI * 185 * $t) * exp(-$t * 22) * 0.6;
            $out[] = $noise * 0.8 + $tone;
        }

        return $this->highpass($out, 0.85);
    }

    /**
     * @return list<float>
     */
    private function clap(): array
    {
        $n = (int) (self::RATE * 0.4);
        $out = array_fill(0, $n, 0.0);

        foreach ([0, 0.012, 0.024] as $k => $offset) {
            $start = (int) ($offset * self::RATE);

            for ($i = $start; $i < $n; $i++) {
                $t = ($i - $start) / self::RATE;
                $decay = $k === 2 ? 11 : 60;
                $out[$i] += (mt_rand() / mt_getrandmax() * 2 - 1) * exp(-$t * $decay);
            }
        }

        return $this->highpass($out, 0.7);
    }

    /**
     * @return list<float>
     */
    private function hat(float $length): array
    {
        $n = (int) (self::RATE * max(0.08, $length * 1.4));
        $out = [];

        for ($i = 0; $i < $n; $i++) {
            $t = $i / self::RATE;
            $metal = 0.0;

            foreach ([3140, 4280, 5560, 6720, 8150, 9300] as $f) {
                $metal += sin(2 * M_PI * $f * $t) >= 0 ? 1.0 : -1.0;
            }

            $out[] = ($metal / 6 * 0.5 + (mt_rand() / mt_getrandmax() * 2 - 1) * 0.5) * exp(-$t / $length * 3);
        }

        return $this->highpass($this->highpass($out, 0.95), 0.95);
    }

    /**
     * @return list<float>
     */
    private function bass808(float $freq, float $length, float $glide = 1.0): array
    {
        $n = (int) (self::RATE * $length);
        $out = [];
        $phase = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $t = $i / self::RATE;
            $f = $freq * (1 + $glide * 0.6 * exp(-$t * 30));
            $phase += 2 * M_PI * $f / self::RATE;
            $env = min(1, $t * 400) * exp(-$t * 2.2 / $length * 1.5);
            $out[] = tanh(2.2 * sin($phase)) * $env;
        }

        return $out;
    }

    /**
     * @return list<float>
     */
    private function bassLoop(float $bpm): array
    {
        $step = 60 / $bpm / 2;
        $notes = [55.0, 0, 55.0, 65.41, 0, 55.0, 73.42, 65.41, 55.0, 0, 55.0, 82.41, 0, 73.42, 65.41, 49.0];
        $out = [];

        foreach ($notes as $note) {
            $n = (int) ($step * self::RATE);

            for ($i = 0; $i < $n; $i++) {
                $t = $i / self::RATE;

                if ($note === 0) {
                    $out[] = 0.0;

                    continue;
                }

                $saw = 2 * (($t * $note) - floor(0.5 + $t * $note));
                $sub = sin(2 * M_PI * $note * $t);
                $out[] = ($saw * 0.35 + $sub * 0.8) * exp(-$t * 5) * min(1, $t * 300);
            }
        }

        return $this->lowpass($out, 0.12);
    }

    /**
     * @param  list<float>  $chord
     * @return list<float>
     */
    private function pad(array $chord, float $length): array
    {
        $n = (int) (self::RATE * $length);
        $out = [];

        for ($i = 0; $i < $n; $i++) {
            $t = $i / self::RATE;
            $v = 0.0;

            foreach ($chord as $f) {
                foreach ([-0.4, 0, 0.4] as $detune) {
                    $v += sin(2 * M_PI * ($f + $detune) * $t) + 0.3 * sin(4 * M_PI * ($f + $detune) * $t);
                }
            }

            $env = min(1, $t / 1.5) * min(1, ($length - $t) / 1.5);
            $out[] = $v / 12 * $env * (0.85 + 0.15 * sin(2 * M_PI * 0.3 * $t));
        }

        return $out;
    }

    /**
     * @return list<float>
     */
    private function pluckSequence(float $bpm): array
    {
        $step = 60 / $bpm / 2;
        $out = [];

        foreach ([440.0, 523.25, 659.25, 523.25, 587.33, 698.46, 659.25, 523.25] as $freq) {
            $period = (int) (self::RATE / $freq);
            $buffer = [];

            for ($i = 0; $i < $period; $i++) {
                $buffer[] = mt_rand() / mt_getrandmax() * 2 - 1;
            }

            $n = (int) ($step * self::RATE);

            for ($i = 0; $i < $n; $i++) {
                $j = $i % $period;
                $next = ($j + 1) % $period;
                $buffer[$j] = 0.497 * ($buffer[$j] + $buffer[$next]);
                $out[] = $buffer[$j];
            }
        }

        return $out;
    }

    /**
     * @return list<float>
     */
    private function riser(float $length): array
    {
        $n = (int) (self::RATE * $length);
        $out = [];
        $phase = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $t = $i / self::RATE;
            $progress = $t / $length;
            $phase += 2 * M_PI * (200 + 1800 * $progress ** 2) / self::RATE;
            $out[] = (sin($phase) * 0.4 + (mt_rand() / mt_getrandmax() * 2 - 1) * 0.6 * $progress) * $progress ** 1.5;
        }

        return $this->highpass($out, 0.6);
    }

    /**
     * @return list<float>
     */
    private function impact(): array
    {
        $n = (int) (self::RATE * 2.5);
        $out = [];

        for ($i = 0; $i < $n; $i++) {
            $t = $i / self::RATE;
            $out[] = tanh(2 * sin(2 * M_PI * (38 + 60 * exp(-$t * 8)) * $t)) * exp(-$t * 1.8)
                + (mt_rand() / mt_getrandmax() * 2 - 1) * exp(-$t * 6) * 0.5;
        }

        return $this->lowpass($out, 0.3);
    }

    /**
     * @return list<float>
     */
    private function drumLoop(float $bpm): array
    {
        $step = 60 / $bpm / 4;
        $steps = 32;
        $out = array_fill(0, (int) ($steps * $step * self::RATE) + self::RATE, 0.0);
        $sounds = ['kick' => $this->kick(), 'snare' => $this->snare(), 'hat' => $this->hat(0.05)];
        $pattern = [
            'kick' => [0, 6, 10, 16, 22, 26, 27],
            'snare' => [4, 12, 20, 28],
            'hat' => range(0, 31, 2),
        ];

        foreach ($pattern as $name => $hits) {
            $sound = $sounds[$name];

            foreach ($hits as $hit) {
                $offset = (int) ($hit * $step * self::RATE);

                foreach ($sound as $k => $v) {
                    if (isset($out[$offset + $k])) {
                        $out[$offset + $k] += $v * ($name === 'hat' ? 0.35 : 0.8);
                    }
                }
            }
        }

        return array_slice($out, 0, (int) ($steps * $step * self::RATE));
    }

    /**
     * Voix synthétique par formants (voyelle « a » ou « o »), avec vibrato.
     *
     * @param  list<float>  $melody
     * @return list<float>
     */
    private function vocal(array $melody, string $vowel, float $noteLength): array
    {
        $formants = $vowel === 'a'
            ? [[800, 80, 1.0], [1150, 90, 0.5], [2900, 120, 0.25]]
            : [[450, 70, 1.0], [800, 80, 0.45], [2830, 100, 0.15]];

        $out = [];
        $phase = 0.0;
        $total = count($melody) * $noteLength;
        $n = (int) ($total * self::RATE);

        for ($i = 0; $i < $n; $i++) {
            $t = $i / self::RATE;
            $index = min(count($melody) - 1, (int) floor($t / $noteLength));
            $target = $melody[$index];
            $previous = $melody[max(0, $index - 1)];
            $local = $t - $index * $noteLength;
            $f0 = $previous + ($target - $previous) * min(1, $local / 0.06);
            $f0 *= 1 + 0.012 * sin(2 * M_PI * 5.5 * $t) * min(1, $t / 0.4);
            $phase += 2 * M_PI * $f0 / self::RATE;
            $v = 0.0;

            for ($h = 1; $h * $f0 < 5000; $h++) {
                $freq = $h * $f0;
                $amp = 0.0;

                foreach ($formants as [$center, $width, $gain]) {
                    $amp += $gain / (1 + (($freq - $center) / $width) ** 2);
                }

                $v += $amp * sin($h * $phase) / sqrt($h);
            }

            $breath = (mt_rand() / mt_getrandmax() * 2 - 1) * 0.02;
            $env = min(1, $t / 0.12) * min(1, ($total - $t) / 0.4);
            $out[] = ($v + $breath) * $env;
        }

        return $out;
    }

    /**
     * @return list<float>
     */
    private function vocalChops(float $bpm): array
    {
        $step = 60 / $bpm / 2;
        $source = $this->vocal([523.25, 587.33, 659.25, 587.33, 523.25, 659.25, 783.99, 659.25], 'a', $step);
        $length = (int) ($step * self::RATE);
        $out = [];
        $gate = [1, 1, 0, 1, 0, 1, 1, 0];

        foreach ($gate as $k => $on) {
            for ($i = 0; $i < $length; $i++) {
                $v = $source[$k * $length + $i] ?? 0.0;
                $env = min(1, $i / 200) * min(1, ($length * 0.7 - $i) / 400);
                $out[] = $on ? $v * max(0, $env) : 0.0;
            }
        }

        return $out;
    }

    /**
     * @param  list<float>  $in
     * @return list<float>
     */
    private function highpass(array $in, float $a): array
    {
        $out = [];
        $prevIn = 0.0;
        $prevOut = 0.0;

        foreach ($in as $x) {
            $prevOut = $a * ($prevOut + $x - $prevIn);
            $prevIn = $x;
            $out[] = $prevOut;
        }

        return $out;
    }

    /**
     * @param  list<float>  $in
     * @return list<float>
     */
    private function lowpass(array $in, float $a): array
    {
        $out = [];
        $y = 0.0;

        foreach ($in as $x) {
            $y += $a * ($x - $y);
            $out[] = $y;
        }

        return $out;
    }
}
