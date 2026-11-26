<?php

namespace App\Support;

/**
 * Écrit une archive ZIP « stockée » (sans compression) directement dans la
 * sortie, sans dépendre de l'extension php-zip. Les fichiers audio étant déjà
 * volumineux et peu compressibles, l'archive se crée sans fichier temporaire.
 */
class ZipStream
{
    /**
     * @var list<array{name: string, crc: int, size: int, offset: int, time: int, date: int}>
     */
    private array $entries = [];

    private int $offset = 0;

    /**
     * @param  resource  $output
     */
    public function __construct(private $output) {}

    public function addFile(string $path, string $name): void
    {
        $name = str_replace('\\', '/', $name);
        $size = (int) filesize($path);
        $crc = (int) hexdec((string) hash_file('crc32b', $path));
        [$time, $date] = $this->dosTime(filemtime($path) ?: time());

        $header = pack('VvvvvvVVVvv', 0x04034B50, 20, 0x0800, 0, $time, $date, $crc, $size, $size, strlen($name), 0).$name;
        $this->write($header);

        $entryOffset = $this->offset - strlen($header);
        $in = fopen($path, 'rb');

        while ($in !== false && ! feof($in)) {
            $this->write((string) fread($in, 1048576));
        }

        if ($in !== false) {
            fclose($in);
        }

        $this->entries[] = ['name' => $name, 'crc' => $crc, 'size' => $size, 'offset' => $entryOffset, 'time' => $time, 'date' => $date];
    }

    public function finish(): void
    {
        $start = $this->offset;

        foreach ($this->entries as $entry) {
            $this->write(pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014B50, 20, 20, 0x0800, 0, $entry['time'], $entry['date'], $entry['crc'], $entry['size'], $entry['size'],
                strlen($entry['name']), 0, 0, 0, 0, 0, $entry['offset'],
            ).$entry['name']);
        }

        $length = $this->offset - $start;
        $count = count($this->entries);
        $this->write(pack('VvvvvVVv', 0x06054B50, 0, 0, $count, $count, $length, $start, 0));
    }

    private function write(string $bytes): void
    {
        fwrite($this->output, $bytes);
        $this->offset += strlen($bytes);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function dosTime(int $timestamp): array
    {
        $d = getdate($timestamp);
        $year = max(1980, $d['year']);

        return [
            ($d['hours'] << 11) | ($d['minutes'] << 5) | intdiv($d['seconds'], 2),
            (($year - 1980) << 9) | ($d['mon'] << 5) | $d['mday'],
        ];
    }
}
